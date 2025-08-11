<?php

namespace Drupal\novaposhta;

use Drupal\Component\Utility\Html;

class NovaPoshtaEN{

	protected static $NovaPoshta;
	protected static $API;

	function __construct($apiKEY = NULL){
		self::$NovaPoshta = \Drupal::service('NovaPoshta');
		self::$API = new \Drupal\novaposhta\API\NovaPoshtaAPI($apiKEY);
	}

	public function defValues($form_state = NULL, $editEN = NULL, $orderId = NULL){
		$newValues = &$form_state->getValues();
    if(empty($newValues)){
      // Edit info
      if(!empty($editEN->all_info)){
        $data = unserialize($editEN->all_info);
        $newValues['ServiceType'] = $data['ServiceType'];
        foreach ($data as $key => $value) {
          if(empty($value)) continue;
          switch($key){
            case'Weight':
            case'SeatsAmount':
            case'Cost':
            case'Description':
            case'PayerType':
            case'PaymentMethod':
            case'InfoRegClientBarcodes':
            case'CargoType':
              $newValues[$key] = $value;
              break;
            case'DateTime':
              $newValues['DateTime'] = !empty($value) ? date('Y-m-d', strtotime($value)) : NULL;
              break;
            case'Recipient':
            case'ContactRecipient':
            case'RecipientsPhone':
            case'RecipientsPhoneCustom':
              $newValues['recipient'][$key] = $value;
              break;
            case'Sender':
            case'ContactSender':
            case'SendersPhone':
              $newValues['sender'][$key] = $value;
              break;
            case'CitySender':
              if(!empty($value)){
                $getCityInfo = self::$API->getCity([
                  'Ref'           => $value
                ]);
                if(!empty($getCityInfo['Area'])){
                  $newValues['sender']['region'] = $getCityInfo['Area'];
                }
              }
              $newValues['sender']['city'] = $value;
              break;
            case'SenderAddress':
              $newValues['sender']['point'] = $value;
              break;
            case'CityRecipient':
              if(!empty($value)){
                $getCityInfo = self::$API->getCity([
                  'Ref'           => $value
                ]);
                if(!empty($getCityInfo['Area'])){
                  $newValues['recipient']['region'] = $getCityInfo['Area'];
                }
              }
              $newValues['recipient']['city'] = $value;
              break;
            case'RecipientAddress':
              $newValues['recipient']['point'] = $value;
              break;
            case'BackwardDeliveryMoney':
              if(!empty($value)){
                $newValues['BackwardDelivery'] = [
                  'on' => TRUE,
                  'disabled' => TRUE,
                  'amount' => $value
                ];
              }
              break;
            case'BackwardDeliveryData':
              if(!empty($value[0]['RedeliveryString']) && $value[0]['CargoType'] == 'Money'){
                $newValues['BackwardDelivery'] = [
                  'on' => TRUE,
                  'disabled' => TRUE,
                  'amount' => $value[0]['RedeliveryString']
                ];
              }
              break;
          }
        }
      } else {
        $getValues = \Drupal::config('novaposhta.en.template')->get('config');
        if(!empty($getValues)){
          $newValues = array_merge_recursive($newValues, $getValues);
        }
				if(!empty($newValues['sender']['Sender'])) {
					$senders = self::$API->getCounterparties([
		        'CounterpartyProperty' => 'Sender'
		      ]);
					if(empty($senders)) {
						$newValues = [];
					}
				}

        $newValues['DateTime'] = date('Y-m-d');
        $newValues['ServiceType'] = 'WarehouseWarehouse';
        // Order
        $AutoCreateRecipient = TRUE;
        if(!empty($orderId)){
          $newValues['InfoRegClientBarcodes'] = $orderId;
          $order = \Drupal::service('Basket')->Orders($orderId)->load();
          if(!empty($order->nid)){
            $orderData = \Drupal::database()->select('novaposhta', 'n')
              ->fields('n', array('data'))
              ->condition('n.entity', 'node')
              ->condition('n.entity_id', $order->nid)
              ->execute()->fetchField();
            if(!empty($orderData)){
              $orderData = unserialize($orderData);
              if(!empty($orderData['region'])) $newValues['recipient']['region'] = $orderData['region'];
              if(!empty($orderData['city'])) $newValues['recipient']['city'] = $orderData['city'];
              if(!empty($orderData['point'])) $newValues['recipient']['point'] = $orderData['point'];
              if(!empty($orderData['street'])) $newValues['recipient']['street'] = $orderData['street'];
              if(!empty($orderData['house'])) $newValues['recipient']['house'] = $orderData['house'];
              if(!empty($orderData['apartment'])) $newValues['recipient']['apartment'] = $orderData['apartment'];
              if(!empty($orderData['comment'])) $newValues['recipient']['Description'] = $orderData['comment'];
            }
            $newValues['Cost'] = ceil($order->price);
            if(!empty($order->delivery_id)){
              $deliveryService = \Drupal::service('Basket')->getSettings('delivery_services', $order->delivery_id);
              if(!empty($deliveryService) && $deliveryService == 'novaposhta_address'){
                $newValues['ServiceType'] = 'WarehouseDoors';
                $newValues['recipient']['RecipientType'] = 'PrivatePerson';
                $AutoCreateRecipient = FALSE;
                $orderNode = \Drupal::entityTypeManager()->getStorage('node')->load($order->nid);
                $recipient = \Drupal::config('novaposhta.en.settings')->get('config.recipient');
                $recipientFields = $recipient['fields'];
                foreach ($recipientFields as &$value){
                  $value = Html::decodeEntities(\Drupal::token()->replace($value, ['node' => $orderNode], ['clear' => TRUE]));
                }
                $newValues['recipient']['RecipientLastName'] = !empty($recipientFields['LastName']) ? $recipientFields['LastName'] : '';
                $newValues['recipient']['RecipientFirstName'] = !empty($recipientFields['FirstName']) ? $recipientFields['FirstName'] : '';
                $newValues['recipient']['RecipientMiddleName'] = !empty($recipientFields['MiddleName']) ? $recipientFields['MiddleName'] : '';
                $newValues['recipient']['RecipientsPhone'] = trim($recipientFields['Phone']);
              }
            }
          }
        }
        // Automatically create a recipient from an order
        if(!empty($order->nid) && $AutoCreateRecipient){
          $orderNode = \Drupal::service('entity_type.manager')->getStorage('node')->load($order->nid);
          $this->AutoCreateRecipient($form_state, $orderNode);
        }
        // Alter
        \Drupal::moduleHandler()->alter('novaposhta_en_default', $newValues, $orderId);
        // ---
      }
      $userInput = &$form_state->getUserInput();
      $userInput = $newValues;
    }
	}

	public function AutoCreateRecipient($form_state, $orderNode){
		$recipient = \Drupal::config('novaposhta.en.settings')->get('config.recipient');
		if(!empty($recipient['auto'])){
			$newValues = &$form_state->getValues();
			$recipientFields = $recipient['fields'];
      foreach ($recipientFields as $kv => $value){
        $recipientFields[$kv] = Html::decodeEntities(\Drupal::token()->replace($value, ['node' => $orderNode], ['clear' => TRUE]));
        if(str_contains($value, '{{')) {
          $value = [
            '#type' => 'inline_template',
            '#template' => $value,
            '#context' => [
              'node' => $orderNode
            ]
          ];
          $recipientFields[$kv] = \Drupal::service('renderer')->renderRoot($value);
          if(!empty($recipientFields[$kv])) {
            $recipientFields[$kv] = $recipientFields[$kv]->__toString();
          }
        }
      }
      if(!empty($recipientFields['Phone']) && !empty($recipientFields['LastName']) && !empty($recipientFields['FirstName'])){

        $recipientFields['Phone'] = self::$NovaPoshta->replacePhone(trim($recipientFields['Phone']));
        $recipientFields['CounterpartyType'] = 'PrivatePerson';
        $recipientFields['CounterpartyProperty'] = 'Recipient';

        $result = self::$API->saveCounterparty($recipientFields);
        if(!empty($result['data'][0]['Ref'])){
          $newValues['recipient']['Recipient'] = $result['data'][0]['Ref'];
          if(!empty($result['data'][0]['ContactPerson']['data'][0]['Ref'])){
            $newValues['recipient']['ContactRecipient'] = $result['data'][0]['ContactPerson']['data'][0]['Ref'];
            $newValues['recipient']['RecipientsPhone'] = $recipientFields['Phone'];
            $newValues['recipient']['LastName'] = $result['data'][0]['LastName'];
            $newValues['recipient']['FirstName'] = $result['data'][0]['FirstName'];
//            self::$API->getCounterpartyContactPersons([
//              'Ref'           => $result['data'][0]['Ref']
//            ], FALSE, $result['data'][0]['LastName']);
          }
        } else if(!empty($result['errors'])){
          foreach ($result['errors'] as $error){
            \Drupal::messenger()->addMessage($error, 'error', FALSE);
          }
        }
      }
		}
	}

	public function setEn($form_state, $editEN = NULL){
		$values = $form_state->getValues();
		$triggerdElement = $form_state->getTriggeringElement();

    if($values['sender']['SendersPhone'] == 'PhoneCustom' && !empty($values['sender']['SendersPhoneCustom'])){
      $values['sender']['SendersPhone'] = $values['sender']['SendersPhoneCustom'];
    }
    if($values['recipient']['RecipientsPhone'] == 'PhoneCustom' && !empty($values['recipient']['RecipientsPhoneCustom'])){
      $values['recipient']['RecipientsPhone'] = $values['recipient']['RecipientsPhoneCustom'];
    }
    $params = [
      'NewAddress' => 1,
      'PayerType' => $values['PayerType'],
      'PaymentMethod' => $values['PaymentMethod'],
      'CargoType' => $values['CargoType'],
      'Weight' => $values['Weight'],
      'ServiceType' => $values['ServiceType'],
      'SeatsAmount' => $values['SeatsAmount'],
      'Description' => trim($values['Description']),
      'Cost' => $values['Cost'],
      'CitySender' => $values['sender']['city'],
      'Sender' => $values['sender']['Sender'],
      'SenderAddress' => $values['sender']['point'],
      'ContactSender' => $values['sender']['ContactSender'],
      'SendersPhone' => str_replace(['-', ')', '('], '', $values['sender']['SendersPhone']),
      'RecipientsPhone' =>  str_replace(['-', ')', '('], '', $values['recipient']['RecipientsPhone']),
      'InfoRegClientBarcodes' => !empty($values['InfoRegClientBarcodes']) ? trim($values['InfoRegClientBarcodes']) : '',
      'DateTime' => date('d.m.Y', strtotime($values['DateTime']))
    ];

    if(!empty($values['OptionsSeat'])){
      $params['OptionsSeat'][0] = [
        'weight' => $params['Weight']
      ];
      [$params['OptionsSeat'][0]['volumetricHeight'], $params['OptionsSeat'][0]['volumetricWidth'], $params['OptionsSeat'][0]['volumetricLength']] = explode('x', $values['OptionsSeat']);
      $params['OptionsSeat'][0]['volumetricVolume'] = round(($params['OptionsSeat'][0]['volumetricWidth']/100)*($params['OptionsSeat'][0]['volumetricLength']/100)*($params['OptionsSeat'][0]['volumetricHeight']/100), 2);
    }

    switch($values['ServiceType']){
      case'WarehouseDoors':
        $city = self::$API->searchSettlements($values['recipient']['city']);
        if (!empty($city['data'][0]['Addresses'][0])) {
          $params['RecipientArea'] = $city['data'][0]['Addresses'][0]['Area'];
          $params['RecipientCityName'] = $city['data'][0]['Addresses'][0]['MainDescription'];
        }
        $params['RecipientAreaRegions'] = '';
        $params['RecipientAddressName'] = trim($values['recipient']['street']);
        $params['RecipientHouse'] = trim($values['recipient']['house']);
        $params['RecipientFlat'] = '';
        $params['RecipientName'] = implode(' ', [
          $values['recipient']['RecipientLastName'],
          $values['recipient']['RecipientFirstName'],
          $values['recipient']['RecipientMiddleName']
        ]);
        $params['RecipientType'] = $values['recipient']['RecipientType'];
        $params['Description'] = t('Site order');
        $params['NewAddress'] = 1;
        break;
      default:
        $params['CityRecipient'] = $values['recipient']['city'];
        $params['RecipientAddress'] = $values['recipient']['point'];
        $params['ContactRecipient'] = $values['recipient']['ContactRecipient'];
        $params['Recipient'] = $values['recipient']['Recipient'];
        break;
    }

    if(!empty($values['BackwardDelivery']['on']) && !empty($values['BackwardDelivery']['amount'])){
      $params['BackwardDeliveryData'] = [[
        'PayerType' => 'Recipient',
        'CargoType' => 'Money',
        'RedeliveryString' => $values['BackwardDelivery']['amount']
      ]];
    }
    if(!empty($values['AfterpaymentOnGoodsCost']['on']) && !empty($values['AfterpaymentOnGoodsCost']['amount'])){
      $params['AfterpaymentOnGoodsCost'] = $values['AfterpaymentOnGoodsCost']['amount'];
    }
    switch($triggerdElement['#name']){
      case'insert':
        $insert = self::$API->InternetDocument($params);
        if(!empty($insert['data'][0]['IntDocNumber'])){
          $params += $insert['data'][0];
          // Address
          if(!empty($params['CityRecipient'])){
            $getCityInfo = self::$API->getCity([
              'Ref' => $params['CityRecipient']
            ]);
            if(!empty($getCityInfo['Description'])){
              if(!empty($getCityInfo['SettlementTypeDescription'])){
                $params['CityRecipientDescription'] = $getCityInfo['SettlementTypeDescription'].' '.$getCityInfo['Description'];
              }
            }
            if(!empty($params['RecipientAddress'])){
              $points = self::$API->getPoints([
                'city' => $params['CityRecipient']
              ]);
              if(!empty($points) && !empty($points[$params['RecipientAddress']])){
                $params['RecipientAddressDescription'] = $points[$params['RecipientAddress']];
              }
            }
          }

          self::$API->insertEN($params);
          $form_state->set('IntDocNumber', $params['IntDocNumber']);

          // novaposhta_en_orders
          if(!empty(trim($params['InfoRegClientBarcodes']))){
            $this->updateOrderENNumm(trim($params['InfoRegClientBarcodes']), $params['IntDocNumber']);
          }
        } else {
          if(!empty($insert['errors'])){
            foreach ($insert['errors'] as $error){
              $form_state->setErrorByName($error, $error);
            }
          }
        }
        break;
      case'update':
        if(!empty($editEN->ref)){
          $params['Ref'] = $editEN->ref;
        }
        $update = self::$API->InternetDocumentUpdate($params);
        if(!empty($update['data'][0]['IntDocNumber'])){} else {
          if(!empty($update['errors'])){
            foreach ($update['errors'] as $error){
              $form_state->setErrorByName($error, self::$NovaPoshta->t(trim($error)));
            }
          }
        }
        break;
    }
	}

  public function updateOrderENNumm($order_id, $en_num){

    // Get Old EN
    $oldEN = \Drupal::database()->select('novaposhta_en_orders', 'en')
      ->fields('en', ['en_num'])
      ->condition('en.order_id', $order_id)
      ->execute()->fetchField();

    // Update
    \Drupal::database()->merge('novaposhta_en_orders')
      ->key([
        'order_id' => $order_id
      ])
      ->fields([
        'en_num' => $en_num
      ])
      ->execute();

    // Set logs
    if(\Drupal::hasService('BasketLogs')){
      $logs = [];
      if(empty($oldEN)){
        $logs[] = [
          'type' => self::$NovaPoshta->t('Added TTN'),
          'new' => $en_num
        ];
      }
      else if($oldEN != $en_num){
        $logs[] = [
          'type' => self::$NovaPoshta->t('Changed TTN'),
          'old' => $oldEN,
          'new' => $en_num
        ];
      }
      \Drupal::service('BasketLogs')->trigger('custom', [
        'orderID' => $order_id,
        'logs' => $logs
      ]);
    }
  }
}
