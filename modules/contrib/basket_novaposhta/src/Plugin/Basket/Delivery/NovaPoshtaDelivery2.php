<?php

namespace Drupal\novaposhta\Plugin\Basket\Delivery;

use Drupal\basket\Plugins\Delivery\Annotation\BasketDelivery;

/**
 * @BasketDelivery(
 *          id        = "novaposhta2",
 *          name      = "NovaPoshta (Search City)",
 * )
 */
class NovaPoshtaDelivery2 extends NovaPoshtaDelivery {
  
  /**
   * Embedding the form in the delivery field.
   */
  public function basketFormAlter(&$form, $form_state) {
    $this->novaPoshta->JquerySelectAttached($form);
		$values = $this->basketLoad($form_state);
    
    $options = [];
    foreach ($this->novaPoshtaAPI->getRegions() as $areaId => $area) {
      foreach ($this->novaPoshtaAPI->getCitis([
        'region'      => $areaId
      ]) as $id => $city) {
        $options[$id] = $city.', '.$area.' обл.';
      }
    }
    
		$form['city'] = [
			'#type'			    => 'select',
			'#title'		    => $this->novaPoshta->t('City'),
			'#title_display'=> $this->hideLabels ? 'none' : 'before',
			'#empty_option'	=> $this->hideLabels ? $this->novaPoshta->t('City') : $this->novaPoshta->t('Not specified'),
			'#options'		  => $options,
			'#ajax'			    => !empty($form['#ajax_wrap']) ? $form['#ajax_wrap'] : [],
			'#required'		  => TRUE,
			'#attributes'	  => [
				'class'			    => [$this->jQueryStyleID . '_np_select'],
        'data-min'      => 3
			]
		];
		if(!empty($values['city'])){
			if(!empty($form['city']['#options'][$values['city']])) {
        $form['city']['#default_value'] = $values['city'];
      }
      else {
        unset($values['city']);
      }
		}
  
		$form['point'] = [
			'#type'			    => 'select',
			'#title'		    => $this->novaPoshta->t('Point'),
			'#title_display'=> $this->hideLabels ? 'none' : 'before',
			'#empty_option'	=> $this->hideLabels ? $this->novaPoshta->t('Point') : $this->novaPoshta->t('Not specified'),
			'#options'		  => $this->novaPoshtaAPI->getPoints([
				'city'		      => !empty($values['city']) ? $values['city'] : ''
			]),
			'#required'		  => TRUE,
			'#attributes'	  => [
				'class'			    => [$this->jQueryStyleID . '_np_select']
			]
		];
		if(!empty($values['point'])) {
			if(!empty($form['point']['#options'][$values['point']])){
        $form['point']['#default_value'] = $values['point'];
      }
			else {
        unset($values['point']);
      }
		}
    
    $this->enNum($form, $form_state);
  }
  
  /**
   * Saving delivery data.
   */
  public function basketSave($entity, $form_state) {
    $values = $form_state->getValue(self::NOVAPOSHTA_FIELDS);
    if(!empty($values['city'])) {
      $address = [];
      
      $city = $this->novaPoshta->listItem($values['city'], 'city');
      if(!empty($city)) {
        
        if(!empty($city->parent_id)) {
          $area = $this->novaPoshta->listItem($city->parent_id, 'area');
          if(!empty($area)) {
            $values['region'] = $area->ref_id;
            $address[] = $area->name.' обл.';
          }
        }
        
        $address[] = $city->name;
      }
      
      if(!empty($values['point'])){
        $points = $this->novaPoshtaAPI->getPoints([
          'city'		=> $values['city']
        ]);
        if(!empty($points[$values['point']])){
          $address[] = $points[$values['point']];
        }
      }
      
      \Drupal::database()->merge('novaposhta')
				->key([
					'entity'		  => $entity->getEntityTypeId(),
					'entity_id'		=> $entity->id()
				])
				->fields([
					'entity'		  => $entity->getEntityTypeId(),
					'entity_id'		=> $entity->id(),
					'address'		  => implode(', ', $address),
					'data'			  => serialize($values)
				])
				->execute();
    }
  }
}