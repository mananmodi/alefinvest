<?php

namespace Drupal\novaposhta\Plugin\Basket\Delivery;

use Drupal\basket\Plugins\Delivery\Annotation\BasketDelivery;
use Drupal\basket\Plugins\Delivery\BasketDeliveryInterface;
use Drupal\novaposhta\API\NovaPoshtaAPI;
use Drupal\novaposhta\AdminPages;

/**
 * @BasketDelivery(
 *          id        = "novaposhta",
 *          name      = "NovaPoshta Delivery",
 * )
 */
class NovaPoshtaDelivery implements BasketDeliveryInterface {
	// ---
	const NOVAPOSHTA_FIELDS = 'novaposhta_fields';
	protected $novaPoshtaAPI;
	protected $novaPoshta;
	protected $hideLabels;
	protected static $deliverySum;
	protected $jQueryStyleID;
	protected $basket;
	// ---
	public function __construct(){
		$this->novaPoshtaAPI = new NovaPoshtaAPI(\Drupal::config('novaposhta.settings')->get('config.api_key'));
		$this->novaPoshta = \Drupal::service('NovaPoshta');
		$this->hideLabels = !empty(\Drupal::config('novaposhta.settings')->get('config.hide_labels'));
		$this->jQueryStyleID = $this->novaPoshta->getJqueryStyleID();
		$this->basket = \Drupal::service('Basket');
	}
	public function basketFieldParents(){
		return [self::NOVAPOSHTA_FIELDS];
	}
	public function basketFormAlter(&$form, $form_state){
		$this->novaPoshta->JquerySelectAttached($form);
		$values = $this->basketLoad($form_state);
		$form['region'] = [
			'#type'			=> 'select',
			'#title'		=> $this->novaPoshta->t('Region'),
			'#title_display'=> $this->hideLabels ? 'none' : 'before',
			'#empty_option'	=> $this->hideLabels ? $this->novaPoshta->t('Region') : $this->novaPoshta->t('Not specified'),
			'#options'		=> $this->novaPoshtaAPI->getRegions([]),
			'#ajax'			=> !empty($form['#ajax_wrap']) ? $form['#ajax_wrap'] : [],
			'#required'		=> TRUE,
			'#attributes'	=> [
				'class'			=> [$this->jQueryStyleID.'_np_select']
			]
		];
		if(!empty($values['region'])){
			if(!empty($form['region']['#options'][$values['region']])) 		$form['region']['#default_value'] = $values['region'];
			else 															unset($values['region']);
		}
		// ---
		$form['city'] = [
			'#type'			=> 'select',
			'#title'		=> $this->novaPoshta->t('City'),
			'#title_display'=> $this->hideLabels ? 'none' : 'before',
			'#empty_option'	=> $this->hideLabels ? $this->novaPoshta->t('City') : $this->novaPoshta->t('Not specified'),
			'#options'		=> $this->novaPoshtaAPI->getCitis([
				'region'		=> !empty($values['region']) ? $values['region'] : ''
			]),
			'#ajax'			=> !empty($form['#ajax_wrap']) ? $form['#ajax_wrap'] : [],
			'#required'		=> TRUE,
			'#attributes'	=> [
				'class'			=> [$this->jQueryStyleID.'_np_select']
			]
		];
		if(!empty($values['city'])){
			if(!empty($form['city']['#options'][$values['city']])) 			$form['city']['#default_value'] = $values['city'];
			else 															unset($values['city']);
		}
		// ---
		$form['point'] = [
			'#type'			=> 'select',
			'#title'		=> $this->novaPoshta->t('Point'),
			'#title_display'=> $this->hideLabels ? 'none' : 'before',
			'#empty_option'	=> $this->hideLabels ? $this->novaPoshta->t('Point') : $this->novaPoshta->t('Not specified'),
			'#options'		=> $this->novaPoshtaAPI->getPoints([
				'city'		=> !empty($values['city']) ? $values['city'] : ''
			]),
			'#required'		=> TRUE,
			'#attributes'	=> [
				'class'			=> [$this->jQueryStyleID.'_np_select']
			]
		];
		if(!empty($values['point'])){
			if(!empty($form['point']['#options'][$values['point']])) 		$form['point']['#default_value'] = $values['point'];
			else 															unset($values['point']);
		}
		
    $this->enNum($form, $form_state);
	}
  
  public function enNum(&$form, $form_state) {
    $entity = $form_state->getBuildInfo()['callback_object']->getEntity();
		if(!empty($entity->id()) && \Drupal::currentUser()->hasPermission('access novaposhta en') && $entity->getEntityTypeId() == 'node'){
			$order = $this->basket->Orders(NULL, $entity->id())->load();
			$NP = NULL;
			if(!empty($order)){
        $NP = (new AdminPages)->loadOrderEN($order->id);
			}
			$form['enNum'] = [
				'#type'			=> 'textfield',
				'#title'		=> $this->novaPoshta->t('EN'),
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
			if(!empty($values['region'])){
				$regions = $this->novaPoshtaAPI->getRegions([]);
				if(!empty($regions[$values['region']])){
					$address[] = $regions[$values['region']].' обл.';
				}
				if(!empty($values['city'])){
					$citis = $this->novaPoshtaAPI->getCitis([
						'region'		=> $values['region']
					]);
					if(!empty($citis[$values['city']])){
						$address[] = $citis[$values['city']];
					}
					if(!empty($values['point'])){
						$points = $this->novaPoshtaAPI->getPoints([
							'city'		=> $values['city']
						]);
						if(!empty($points[$values['point']])){
							$address[] = $points[$values['point']];
						}
					}
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
			$order = $this->basket->Orders(NULL, $entity->id())->load();
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
			$sum = $this->basket->Cart()->getTotalSum(TRUE, TRUE);
			self::$deliverySum[$senderCity][$recipientCity] = $this->novaPoshtaAPI->getCost($senderCity, $recipientCity, $senderWeight, $sum);
		}
		if(!empty(self::$deliverySum[$senderCity][$recipientCity]['data'][0]['Cost'])){
			$info['sum'] = self::$deliverySum[$senderCity][$recipientCity]['data'][0]['Cost'];
			$info['currency'] = 'UAH';
			$info['description'] = $this->novaPoshta->t('Estimated Shipping Cost');
		}
		$info['isPay'] = FALSE;
	}
}