<?php

namespace Drupal\novaposhta;

use Drupal\novaposhta\API\NovaPoshtaAPI;

class NovaPoshtaView{

	protected $basket;
	protected $novaPoshta;
	protected $API;
	protected static $EN;

	function __construct($viewID = NULL, $viewNUM = NULL){
		$this->basket = \Drupal::service('Basket');
		$this->novaPoshta = \Drupal::service('NovaPoshta');
		self::$EN = $this->novaPoshta->loadEnByID($viewID);
		if(empty(self::$EN) && !empty($viewNUM)){
			self::$EN = $this->novaPoshta->loadEnByNum($viewNUM);
		}
		$this->API = new NovaPoshtaAPI();
		if(!empty(self::$EN->all_info)){
			self::$EN->all_info = unserialize(self::$EN->all_info);
			if(!empty(self::$EN->all_info['DateTime'])){
				self::$EN->all_info['DateTime'] = strtotime(self::$EN->all_info['DateTime']);
			}
			if(!empty(self::$EN->all_info['PayerType'])){
				$PayerTypes = $this->API->getTypesOfPayers([]);
				if(!empty($PayerTypes[self::$EN->all_info['PayerType']])){
					self::$EN->all_info['PayerType'] = $PayerTypes[self::$EN->all_info['PayerType']];
				}
			}
			if(!empty(self::$EN->all_info['PaymentMethod'])){
				$PaymentMethods = $this->API->getPaymentForms([]);
				if(!empty($PaymentMethods[self::$EN->all_info['PaymentMethod']])){
					self::$EN->all_info['PaymentMethod'] = $PaymentMethods[self::$EN->all_info['PaymentMethod']];
				}
			}
			if(!empty(self::$EN->all_info['CargoType'])){
				$CargoTypes = $this->API->getCargoTypes([]);
				if(!empty($CargoTypes[self::$EN->all_info['CargoType']])){
					self::$EN->all_info['CargoType'] = $CargoTypes[self::$EN->all_info['CargoType']];
				}
			}
			if(!empty(self::$EN->all_info['Recipient'])){
				$Recipient = self::$EN->all_info['Recipient'];
				$Recipients = $this->API->getCounterparties(['CounterpartyProperty'	=> 'Recipient']);
				if(!empty($Recipients[self::$EN->all_info['Recipient']])){
					self::$EN->all_info['Recipient'] = $Recipients[self::$EN->all_info['Recipient']];
				}
				if(!empty(self::$EN->all_info['ContactRecipient'])){
					$ContactRecipient = $this->API->getCounterpartyContactPersons(['Ref' => $Recipient], FALSE, self::$EN->all_info['RecipientsPhone']);
					if(!empty($ContactRecipient['data'][0])){
					  self::$EN->all_info['ContactRecipient'] = $ContactRecipient['data'][0]['LastName'] . ' ' . $ContactRecipient['data'][0]['FirstName'];
					}
				  }
			}
      else if (!empty(self::$EN->all_info['RecipientName'])) {
        self::$EN->all_info['ContactRecipient'] = self::$EN->all_info['RecipientName'];
      }
			if(!empty(self::$EN->all_info['RecipientContactPerson'])){
				self::$EN->all_info['ContactRecipient'] = self::$EN->all_info['RecipientContactPerson'];
			}
      if (empty(self::$EN->all_info['CityRecipientDescription'])) {
        $address = [];
        if (!empty(self::$EN->all_info['RecipientCityName'])) {
          $address[] = self::$EN->all_info['RecipientCityName'];
        }
				if (!empty(self::$EN->all_info['RecipientAddressName'])) {
          $address[] = self::$EN->all_info['RecipientAddressName'];
        }
				if (!empty(self::$EN->all_info['RecipientHouse'])) {
          $address[] = self::$EN->all_info['RecipientHouse'];
        }
				if (!empty(self::$EN->all_info['RecipientFlat'])) {
          $address[] = self::$EN->all_info['RecipientFlat'];
        }
				self::$EN->all_info['CityRecipientDescription'] = implode(', ', $address);
			}
		}
	}
	public function build(){
		if(empty(self::$EN)){
			return $this->basket->getError(404);
		}
		self::$EN->print_link = $this->API->getPrintLink(self::$EN->en_num);
		return [
			'#theme' => 'novaposhta_en_view',
			'#info'	=> self::$EN,
			'#attached' => [
				'library'	=> ['novaposhta/admin']
			]
		];
	}
	public function getPrintLink(){
		if(empty(self::$EN)) return [];
		if(empty(self::$EN->en_num)) return [];
		$getPrintLink = $this->API->getPrintLink(self::$EN->en_num);
		if(!empty($getPrintLink)){
			return [
				'text' => $this->novaPoshta->t('Print'),
        'ico' => $this->basket->getIco('print.svg', 'novaposhta'),
        'url'	=> $getPrintLink,
        'target' => '_blank'
			];
		}
	}
}
