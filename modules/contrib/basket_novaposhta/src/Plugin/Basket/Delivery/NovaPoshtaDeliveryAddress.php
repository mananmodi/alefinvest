<?php

namespace Drupal\novaposhta\Plugin\Basket\Delivery;

use Drupal\basket\Plugins\Delivery\Annotation\BasketDelivery;
use Drupal\basket\Plugins\Delivery\BasketDeliveryInterface;
use Drupal\novaposhta\API\NovaPoshtaAPI;

/**
 * @BasketDelivery(
 *          id        = "novaposhta_address",
 *          name      = "NovaPoshta Delivery Address",
 * )
 */
class NovaPoshtaDeliveryAddress implements BasketDeliveryInterface {
	// ---
	const NOVAPOSHTA_FIELDS = 'novaposhta_address_fields';
	protected static $NovaPoshtaAPI;
	protected static $NovaPoshta;
	protected static $hideLabels;
	protected static $deliverySum;
	protected static $jQueryStyleID;
	// ---
	public function __construct(){
		self::$NovaPoshtaAPI = new NovaPoshtaAPI(\Drupal::config('novaposhta.settings')->get('config.api_key'));
		self::$NovaPoshta = \Drupal::service('NovaPoshta');
		self::$hideLabels = !empty(\Drupal::config('novaposhta.settings')->get('config.hide_labels'));
		self::$jQueryStyleID = self::$NovaPoshta->getJqueryStyleID();
	}
	public function basketFieldParents(){
		return [self::NOVAPOSHTA_FIELDS];
	}
	public function basketFormAlter(&$form, $form_state){
		self::$NovaPoshta->JquerySelectAttached($form);
		$values = self::basketLoad($form_state);
		// ---
		$form['city'] = [
			'#type'			=> 'textfield',
			'#title'		=> self::$NovaPoshta->t('City'),
			'#title_display'=> self::$hideLabels ? 'none' : 'before',
			'#ajax'			=> !empty($form['#ajax_wrap']) ? $form['#ajax_wrap']+['event' => 'autocompleteclose'] : [],
			'#required'		=> TRUE,
			'#autocomplete_route_name' => 'basket.pages',
			'#autocomplete_route_parameters'=> [
				'page_type'			=> 'api-novaposhta_get_cities'
			],
			'#default_value'=> !empty($values['city']) ? $values['city'] : NULL,
			'#attributes'	=> [
        'placeholder'	=> self::$hideLabels ? self::$NovaPoshta->t('Street') : '',
				'autocomplete'	=> 'anyrandomstring'
			]
		];
		// ---
		$triggerElement = $form_state->getTriggeringElement();
		if(!empty($triggerElement['#name']) && $triggerElement['#name'] == 'novaposhta_address_fields[city]') {
			$input = &$form_state->getUserInput();
			$input['novaposhta_address_fields']['street'] = '';
			$form_state->setValue(['novaposhta_address_fields', 'street'], '');
		}
		// ---
		$form['street'] = [
			'#type'			=> 'textfield',
			'#title'		=> self::$NovaPoshta->t('Street'),
			'#title_display'=> self::$hideLabels ? 'none' : 'before',
			'#attributes'	=> [
				'placeholder'	=> self::$hideLabels ? self::$NovaPoshta->t('Street') : '',
				'autocomplete'	=> 'anyrandomstring'
			],
			'#required'		=> TRUE,
			'#default_value'=> !empty($values['street']) ? $values['street'] : NULL,
			'#autocomplete_route_name' => 'basket.pages',
			'#autocomplete_route_parameters'=> [
				'page_type'			=> 'api-novaposhta_get_address',
				'ref'				=> self::$NovaPoshtaAPI->getCityRefByName($form_state->getValue(['novaposhta_address_fields', 'city']))
			],
		];
		// ---
		$form['house'] = [
			'#type'			=> 'textfield',
			'#title'		=> self::$NovaPoshta->t('House number'),
			'#title_display'=> self::$hideLabels ? 'none' : 'before',
			'#attributes'	=> [
				'placeholder'	=> self::$hideLabels ? self::$NovaPoshta->t('House number') : ''
			],
			'#required'		=> TRUE,
			'#default_value'=> !empty($values['house']) ? $values['house'] : NULL
		];
		// ---
		$form['apartment'] = [
			'#type'			=> 'textfield',
			'#title'		=> self::$NovaPoshta->t('Apartment number'),
			'#title_display'=> self::$hideLabels ? 'none' : 'before',
			'#attributes'	=> [
				'placeholder'	=> self::$hideLabels ? self::$NovaPoshta->t('Apartment number') : ''
			],
			'#default_value'=> !empty($values['apartment']) ? $values['apartment'] : NULL
		];
		$form['comment'] = [
			'#type'				=> 'textfield',
			'#title'			=> self::$NovaPoshta->t('Comment to the address'),
			'#title_display'	=> self::$hideLabels ? 'none' : 'before',
			'#attributes'		=> [
				'placeholder'		=> self::$hideLabels ? self::$NovaPoshta->t('Comment to the address') : ''
			],
			'#default_value'	=> !empty($values['comment']) ? $values['comment'] : NULL
		];
		$entity = $form_state->getBuildInfo()['callback_object']->getEntity();
		if(!empty($entity->id()) && \Drupal::currentUser()->hasPermission('access novaposhta en') && $entity->getEntityTypeId() == 'node'){
			$order = \Drupal::service('Basket')->Orders(NULL, $entity->id())->load();
			$NP = NULL;
			if(!empty($order)){
				$AdminPages = new \Drupal\novaposhta\AdminPages();
        		$NP = $AdminPages->loadOrderEN($order->id);
			}
			$form['enNum'] = [
				'#type'			=> 'textfield',
				'#title'		=> self::$NovaPoshta->t('EN'),
				'#default_value'=> !empty($NP->en_num) ? $NP->en_num : NULL
			];
		}
		// Alter
		\Drupal::moduleHandler()->alter('novaposhta_fields', $form, $form_state);
		// ---
		$_SESSION['novaposhta']['recipientCity'] = !empty($values['city']) ? $values['city'] : NULL;
	}
	public function basketDelete($entity, $entity_delete){
		\Drupal::database()->delete('novaposhta')
      ->condition('entity', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->execute();
	}
	public function basketSave($entity, $form_state){
		$values = $form_state->getValue(self::NOVAPOSHTA_FIELDS);
		if(!empty($values)){
			$address = [];
			if(!empty($values['city'])){
				$address[] = $values['city'];
				if(!empty($values['street'])){
					$address[] = $values['street'];
				}
				if(!empty($values['house'])){
					$address[] = ' '.$values['house'];
				}
				if(!empty($values['apartment'])){
					$address[] = self::$NovaPoshta->t('apt.').' '.$values['apartment'];
				}
			}
			\Drupal::database()->merge('novaposhta')
				->key([
					'entity'		=> $entity->getEntityTypeId(),
					'entity_id'		=> $entity->id()
				])
				->fields([
					'entity'		=> $entity->getEntityTypeId(),
					'entity_id'		=> $entity->id(),
					'address'		=> implode(', ', $address),
					'data'			=> serialize($values)
				])
				->execute();
			// Save EN numm
			$order = \Drupal::service('Basket')->Orders(NULL, $entity->id())->load();
			$NP = NULL;
			if(!empty($order)){
				$NovaPoshtaEN = new \Drupal\novaposhta\NovaPoshtaEN();
				$NovaPoshtaEN->updateOrderENNumm($order->id, (!empty($values['enNum']) ? trim($values['enNum']) : NULL));
			}
		}
	}
	public function basketLoad($form_state){
		$values = $form_state->getValue(self::NOVAPOSHTA_FIELDS);
		if(empty($values)){
			$entity = $form_state->getBuildInfo()['callback_object']->getEntity();
			if($entity->id()){
				$load_data = \Drupal::database()->select('novaposhta', 'n')
          ->fields('n', array('data'))
          ->condition('n.entity', $entity->getEntityTypeId())
          ->condition('n.entity_id', $entity->id())
          ->execute()->fetchField();
				if(!empty($load_data)){
					$values = unserialize($load_data);
				}
			}
		}
		return $values;
	}
	public function basketGetAddress($entity){
		return \Drupal::database()->select('novaposhta', 'n')
      ->fields('n', array('address'))
      ->condition('n.entity', $entity->getEntityTypeId())
      ->condition('n.entity_id', $entity->id())
      ->execute()->fetchField();
	}
	public function deliverySumAlter(&$info){
		$senderCity = \Drupal::config('novaposhta.en.template')->get('config.sender.city');
		$senderWeight = \Drupal::config('novaposhta.en.template')->get('config.Weight');
		$recipientCity = !empty($_SESSION['novaposhta']['recipientCity']) ? $_SESSION['novaposhta']['recipientCity'] : '';
		if(!isset(self::$deliverySum[$senderCity][$recipientCity])){
			$sum = \Drupal::service('Basket')->Cart()->getTotalSum(TRUE, TRUE);
			self::$deliverySum[$senderCity][$recipientCity] = self::$NovaPoshtaAPI->getCost($senderCity, $recipientCity, $senderWeight, $sum);
		}
		if(!empty(self::$deliverySum[$senderCity][$recipientCity]['data'][0]['Cost'])){
			$info['sum'] = self::$deliverySum[$senderCity][$recipientCity]['data'][0]['Cost'];
			$info['currency'] = 'UAH';
			$info['description'] = self::$NovaPoshta->t('Estimated Shipping Cost');
		}
		$info['isPay'] = FALSE;
	}
}
