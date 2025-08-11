<?php

namespace Drupal\novaposhta\API;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;

class NovaPoshtaAPI{

	use DependencySerializationTrait;

	// ---
	public $apiKEY;
	public $apiKEYDef;
	protected $novaPoshta;
	protected $API;
	protected $API2;
	protected $DIR;
	protected $notCache = FALSE;
  protected $langcode;
  protected $db;

	const SERVICE_TYPE = 'WarehouseWarehouse'; /*Склад-Склад*/

	function __construct($apiKEY = NULL, $NotCache = FALSE){
    $this->langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
		$this->apiKEYDef = \Drupal::config('novaposhta.settings')->get('config.api_key');
		$this->notCache = $NotCache;
		if(empty($apiKEY)) $apiKEY = $this->apiKEYDef;
		$this->apiKEY = $apiKEY;
		$this->novaPoshta = \Drupal::service('NovaPoshta');
    $this->API = new NovaPoshtaApi2($this->apiKEY, $this->langcode);
    $this->API2 = new NovaPoshtaApi2(NULL, $this->langcode);
		$this->DIR = $this->novaPoshta::NOVAPOCHTA_DIR.'/'.date('W_m_Y');

    $this->db = \Drupal::getContainer()->get('database');
	}

  /**
   * List of areas.
   */
	public function getRegions($params = []) {
    $query = \Drupal::database()->select('novaposhta_lists', 'l');
    $query->fields('l', ['ref_id', 'name']);
    $query->condition('l.type', 'area');
    $query->innerJoin('novaposhta_lists','pl', 'pl.parent_id = l.ref_id');
    $options = $query->execute()->fetchAllKeyed();
		return $options ?? [];
	}

  /**
   * List of cities.
   */
	public function getCitis($params = []) {
    $query = \Drupal::database()->select('novaposhta_lists', 'l');
    $query->fields('l', ['ref_id', 'name']);
    $query->condition('l.type', 'city');
    $query->condition('l.parent_id', $params['region']);
    $options = $query->execute()->fetchAllKeyed();
		return $options ?? [];
	}
	public function getCity($params){
		if(empty($params['Ref'])) return [];
		$options = $this->getCache($params, 'getCity');
		if(!empty($options)) return $options;
		$options = [];
		$results = $this->API2->getCities(0, '', $params['Ref']);
		if(!empty($results['data'][0])){
			$options = $results['data'][0];
		}
		$this->setCache($options, $params, 'getCity');
		return $options;
	}

  /**
   * List of branches.
   */
	public function getPoints($params){
		if(empty($params['city'])) return [];
		$options = $this->getCache($params, 'point');
		if(!empty($options)) return $options;
		$options = [];
		// ---
		$results = $this->API2->getWarehouses($params['city']);
		if(!empty($results['data'])){
			foreach ($results['data'] as $row){
				$options[$row['Ref']] = str_replace('№', '№ ', $row['Description']);
			}
		}
		$this->setCache($options, $params, 'point');
		return $options;
	}

  /**
   * Getting shipping costs.
   */
	public function getCost($senderCity, $recipientCity, $weight = 1, $price = 0){
		return $this->API->getDocumentPrice($senderCity, $recipientCity, 'WarehouseDoors', $weight, $price);
	}

  /**
   * Tracking.
   */
	public function getTrackingDocument($params){
		return $this->API->getTrackingDocument($params);
	}

  /**
   * Download the list of Counterparties of senders/recipients/third parties.
   */
	public function getCounterparties($params, $resetCache = FALSE){
		if(empty($params['CounterpartyProperty'])) return [];
		if(!$this->notCache){
			$options = $this->getCache($params, 'getCounterparties');
			if(!empty($options) && !$resetCache) return $options;
		}
		$options = [];
		$results = $this->API->getCounterparties($params['CounterpartyProperty'], 1);
		if(!empty($results['data'][0]['Description'])){
			foreach ($results['data'] as $key => $value) {
				$options[$value['Ref']] = !empty($value['OwnershipFormDescription']) ? $value['OwnershipFormDescription'] : $value['Description'];
				if($value['CounterpartyType'] == 'Organization'){
					$options[$value['Ref']] .= ' "'.$value['Description'].'"';
				}
			}
		}
		if(!$this->notCache){
			$this->setCache($options, $params, 'getCounterparties');
		}
		return $options;
	}

  /**
   * Download the list of contact persons of the Counterparty.
   */
	public function getCounterpartyContactPersons($params, $resetCache = FALSE, $name = NULL){

		if(empty($params['Ref'])) return [];
    if ($name) {
      return $this->API->getCounterpartyContactPersons($params['Ref'], 0, $name);
    }
		$options = $this->getCache($params, 'getCounterpartyContactPersons');
		if(!empty($options) && !$resetCache) return $options;
		$options = [];
		$resultData = $this->getCache(['Ref' => $params['Ref']], 'getContactPersonsAll');
		if(empty($resultData) || !empty($resetCache)){
			$resultData = [];
			$page = 0;
			$results = $this->API->getCounterpartyContactPersons($params['Ref'], $page);
			if(!empty($results['data'])){
				foreach ($results['data'] as $dataRow){
					$resultData[$dataRow['Ref']] = $dataRow;
				}
				if(!empty($results['info']['totalCount']) && $results['info']['totalCount'] > count($resultData)){
					$page++;
					$this->getContactPersonsAlters($resultData, $results['info']['totalCount'], $params['Ref'], $page);
				}
			}
			$this->setCache($resultData, ['Ref' => $params['Ref']], 'getContactPersonsAll');
		}
		if(!empty($resultData)){
			if(!empty($params['Contact'])){
				foreach ($resultData as $row){
					if($params['Contact'] == $row['Ref'] && !empty($row['Phones'])){
						$options[$row['Phones']] = $row['Phones'];
					}
				}
				$options['PhoneCustom'] = $this->novaPoshta->t('Add another phone number');
			} else {
				foreach ($resultData as $row){
					$name = !empty($row['Description']) ? [$row['Description']] : [];
					if(empty($name)){
						if(empty($row['LastName'])) $name[] = $row['LastName'];
						if(empty($row['FirstName'])) $name[] = $row['FirstName'];
						if(empty($row['MiddleName'])) $name[] = $row['MiddleName'];
					}
					$options[$row['Ref']] = implode(' ', $name);
				}
			}
		}
		$this->setCache($options, $params, 'getCounterpartyContactPersons');
		return $options;
	}
	private function getContactPersonsAlters(&$resultData, $max, $Ref, $page){
		$results = $this->API->getCounterpartyContactPersons($Ref, $page);
		if(!empty($results['data'])){
			foreach ($results['data'] as $dataRow){
				$resultData[$dataRow['Ref']] = $dataRow;
			}
			if($max > count($resultData)){
				$page++;
				$this->getContactPersonsAlters($resultData, $max, $Ref, $page);
			}
		}
	}

  /**
   * Types of counterparties.
   */
	public function getTypesOfCounterparties($params = []){
		$options = $this->getCache($params, 'getTypesOfCounterparties');
		if(!empty($options)) return $options;
		$options = [];
		// ---
		$results = $this->API->getTypesOfCounterparties();
		if(!empty($results['data'])){
			foreach ($results['data'] as $row){
				$options[$row['Ref']] = $row['Description'];
			}
		}
		$this->setCache($options, $params, 'getTypesOfCounterparties');
		return $options;
	}

  /**
   * Forms of ownership.
   */
	public function getOwnershipFormsList($params = []){
		$options = $this->getCache($params, 'getOwnershipFormsList');
		if(!empty($options)) return $options;
		$options = [];
		// ---
		$results = $this->API->getOwnershipFormsList();
		if(!empty($results['data'])){
			foreach ($results['data'] as $row){
				$options[$row['Ref']] = $row['Description'];
			}
		}
		$this->setCache($options, $params, 'getOwnershipFormsList');
		return $options;
	}

  /**
   * Types of cargo.
   */
	public function getCargoTypes($params = []){
		$options = $this->getCache($params, 'getCargoTypes');
		if(!empty($options)) return $options;
		$options = [];
		// ---
		$results = $this->API->getCargoTypes();
		if(!empty($results['data'])){
			foreach ($results['data'] as $row){
				if($row['Ref'] == 'TiresWheels') 	continue; /*Шини-диски*/
				if($row['Ref'] == 'Pallet') 		continue; /*Палети*/
				$options[$row['Ref']] = $row['Description'];
			}
		}
		$this->setCache($options, $params, 'getCargoTypes');
		return $options;
	}

  /**
   * Create a Contractor.
   */
	public function saveCounterparty($params){
		return $this->API->saveCounterparty($params);
	}

  /**
   * Create Contractor Contact Person.
   */
	public function saveContactPerson($params){
		return $this->API->saveContactPerson($params);
	}

  /**
   * Types of payers.
   */
	public function getTypesOfPayers($params){
		$options = $this->getCache($params, 'getTypesOfPayers');
		if(!empty($options)) return $options;
		$options = [];
		// ---
		$results = $this->API->getTypesOfPayers();
		if(!empty($results['data'])){
			foreach ($results['data'] as $row){
				if($row['Ref'] == 'ThirdPerson') 	continue; /*Третя особа*/
				$options[$row['Ref']] = $row['Description'];
			}
		}
		$this->setCache($options, $params, 'getTypesOfPayers');
		return $options;
	}

  /**
   * Forms of payment.
   */
	public function getPaymentForms($params){
		$options = $this->getCache($params, 'getPaymentForms');
		if(!empty($options)) return $options;
		$options = [];
		// ---
		$results = $this->API->getPaymentForms();
		if(!empty($results['data'])){
			foreach ($results['data'] as $row){
				$options[$row['Ref']] = $row['Description'];
			}
		}
		$this->setCache($options, $params, 'getPaymentForms');
		return $options;
	}

  /**
   * Create an express delivery note.
   */
	public function InternetDocument($params){
		return $this->API->saveInternetDocumentNew($params);
	}

  /**
   * Edit express waybill.
   */
	public function InternetDocumentUpdate($params){
		$update = $this->API->InternetDocumentUpdate($params);
		if(!empty($update['data'][0]['Ref'])){
			foreach ($update['data'][0] as $key => $value){
				$params[$key] = $value;
			}
			$this->insertEN($params);
		}
		return $update;
	}

  /**
   * Updating the list of invoices.
   */
  public function updateDocumentList($params): void {

    // Clear old items
    $this->db->truncate('novaposhta_en')
      ->execute();

    $results = $this->API->getDocumentList($params);
    if(!empty($results['data'])) {
      $docs = [];
      foreach ($results['data'] as $row) {
        $this->insertEN($row);
        $docs[] = $row['IntDocNumber'];
      }
      $this->getStatusDocuments($docs);
    }
  }

  /**
   * Recording of invoice data in the database.
   */
	public function insertEN($row): void {
		$address = array_filter([
      $row['CityRecipientDescription'] ?? NULL,
      $row['RecipientAddressDescription'] ?? NULL,
    ]);
    if (empty($address)) {
      $address = array_filter([
        $row['RecipientCityName'] ?? NULL,
        $row['RecipientAddressName'] ?? NULL,
        $row['RecipientHouse'] ?? NULL,
        $row['RecipientFlat'] ?? NULL
      ]);
		}

		$this->db->merge('novaposhta_en')
      ->keys([
        'ref' => $row['Ref']
      ])
      ->fields([
        'ref'	=> $row['Ref'],
        'order_id' => !empty($row['InfoRegClientBarcodes']) ? $row['InfoRegClientBarcodes'] : 0,
        'en_num' => $row['IntDocNumber'],
        'created' => strtotime($row['DateTime']),
        'delivery_day' => strtotime($row['EstimatedDeliveryDate']),
        'cost' => $row['Cost'],
        'cost_delivery' => $row['CostOnSite'],
        'weight' => $row['Weight'],
        'seats_amount' => $row['SeatsAmount'],
        'description' => $row['Description'],
        'address' => implode(',<br/>', $address),
        'status' => !empty($row['StateName']) ? $row['StateName'] : NULL,
        'date_receiving' => !empty($row['RecipientDateTime']) ? strtotime($row['RecipientDateTime']) : NULL,
        'all_info' => serialize($row),
      ])
      ->execute();
	}
	public function getPrintLink($EnNum){
		$link = 'https://my.novaposhta.ua/orders/printDocument/orders/'.$EnNum.'/type/pdf/apiKey/'.$this->apiKEY;
		switch(\Drupal::config('novaposhta.en.settings')->get('config.print')) {
			case'printDocumentPdf':
				break;
			case'printMarking100x100':
				$link = 'https://my.novaposhta.ua/orders/printMarking100x100/orders/'.$EnNum.'/type/pdf/apiKey/'.$this->apiKEY.'/zebra';
			break;
			case'printMarking85x85':
				$link = 'https://my.novaposhta.ua/orders/printMarking85x85/orders/'.$EnNum.'/type/pdf/apiKey/'.$this->apiKEY.'/zebra';
			break;
		}
		return $link;
	}

  /**
   * Obtaining document statuses.
   */
	public function getStatusDocuments($EnNums, $parentNum = NULL) {
		if(empty($EnNums)){
			return NULL;
		}
		$params = [
			'Documents' => []
		];
		foreach ($EnNums as $EnNum){
			$params['Documents'][] = [
				'DocumentNumber' => $EnNum,
			];
		}
    $results = $this->API->getTrackingDocument($params);

    if(!empty($results['data'])) {
      foreach ($results['data'] as $datum) {

        // Update info
        $updateFields = array_filter([
          'status' => $datum['Status'] ?? NULL,
          'weight' => $datum['DocumentWeight'] ?? 0,
          'cost_delivery'	=> $datum['DocumentCost'] ?? 0
        ]);

        if(!empty($datum['ScheduledDeliveryDate'])){
          $updateFields['delivery_day'] = strtotime($datum['ScheduledDeliveryDate']);
        }

        if(!empty($datum['ScheduledDeliveryDate'])){
          $updateFields['delivery_day'] = strtotime($datum['ScheduledDeliveryDate']);
        }

        if(!empty($datum['RecipientDateTime'])){
          $updateFields['date_receiving'] = strtotime($datum['RecipientDateTime']);
        }

        if(!empty($datum['LastCreatedOnTheBasisDocumentType'])
          && $datum['LastCreatedOnTheBasisDocumentType'] == 'Redirecting'
          && !empty($datum['LastCreatedOnTheBasisNumber'])) {
          $updateFields['new_en_num'] = $datum['LastCreatedOnTheBasisNumber'];
        }

        $updateFields['address'] = implode('<br/>', array_filter([
          $datum['CityRecipient'] ?? NULL,
          $datum['WarehouseRecipient'] ?? NULL
        ]));

        if(empty($updateFields['address']) && !empty($datum['WarehouseRecipientAddress'])) {
          $updateFields['address'] = $datum['WarehouseRecipientAddress'];
        }
        if(!empty($parentNum)) {

          // We save forwarding data
          $parentData = $this->db->select('novaposhta_en', 'n')
            ->fields('n', ['all_info'])
            ->condition('n.en_num', $parentNum)
            ->execute()->fetchField();
          if(!empty($parentData)) {
            $parentData = unserialize($parentData);
            $parentData['RedirectingData'] = $datum;
            $updateFields['all_info'] = serialize($parentData);
          }

          $datum['Number'] = $parentNum;
        }

        if(!empty($datum['Number']) && !empty($updateFields)) {
          $this->db->update('novaposhta_en')
            ->fields($updateFields)
            ->condition('en_num', $datum['Number'])
            ->execute();
        }

        if(!empty($updateFields['new_en_num'])) {
          $this->db->update('novaposhta_en_orders')
            ->fields([
              'new_en_num' => $updateFields['new_en_num']
            ])
            ->condition('en_num', $datum['Number'])
            ->execute();

          if(count($EnNums) == 1) {
            return $this->getStatusDocuments([$datum['LastCreatedOnTheBasisNumber']], $datum['Number']);
          }
          else {
            $this->getStatusDocuments([$datum['LastCreatedOnTheBasisNumber']], $datum['Number']);
          }
        }
      }
			return count($EnNums) == 1 ? reset($results['data']) : $results['data'];
    }
		return NULL;
	}
	public function InternetDocumentDelete($params){
		$this->API->InternetDocumentDelete($params);
	}

	public function searchSettlements($search){
		return $this->API->searchSettlements([
			'CityName' => trim($search),
			'Limit' => 20
		]);
	}
	public function getCityRefByName($search){
		$results = $this->API->searchSettlements([
			'CityName' => trim($search),
			'Limit' => 1
		]);
		return !empty($results['data'][0]['Addresses'][0]['Ref']) ? $results['data'][0]['Addresses'][0]['Ref'] : '';
	}
	public function searchSettlementStreets($search, $ref){
		return $this->API->searchSettlementStreets([
			'StreetName' => trim($search),
			'SettlementRef' => $ref,
			'Limit' => 20
		]);
	}

  /**
   * Forms of payment.
   */
	public function getMessageCodeText(){
		$results = $this->getCache([], 'getMessageCodeText');
		if(!empty($results)) return $results;
		// ---
		$response = $this->API->getMessageCodeText();
		$results = !empty($response['data']) ? $response['data'] : [];
		$this->setCache($results, [], 'getMessageCodeText');
		return $results;
	}
	public function getErrorText($errorCode){
		$result = $this->getMessageCodeText();
	}

  /**
   * getCache.
   */
	public function getCache($params, $type){
		if($this->notCache){
			return NULL;
		}
		if($this->apiKEY !== $this->apiKEYDef){
			return NULL;
		}
		$options = $this->novaPoshta->variableGet($this->getFileName($params, $type), $this->DIR);
		return !empty($options) ? $options : [];
	}

  /**
   * setCache.
   */
	public function setCache($options, $params, $type){
		if($this->notCache){
			return NULL;
		}
		if($this->apiKEY !== $this->apiKEYDef){
			return NULL;
		}
		$this->novaPoshta->variableSet($options, $this->getFileName($params, $type), $this->DIR);
	}
	public function getFileName($params, $type){
		if(!is_array($params)){
			\Drupal::logger('params')->notice('<pre>'.print_r($params, TRUE).'</pre>');
		}
		return $type.'-'.implode('_', $params);
	}
}
