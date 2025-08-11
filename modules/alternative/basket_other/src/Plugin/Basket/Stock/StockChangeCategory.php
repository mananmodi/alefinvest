<?php

namespace Drupal\basket_other\Plugin\Basket\Stock;

use Drupal\basket\Plugins\Stock\BasketStockBulkBaseForm;
use Drupal\node\Entity\Node;

/**
 * @BasketStockBulk(
 *          id        = "stock_change_category",
 *          name      = "Change category",
 *          weight 	  = 1,
 *          color 	  = "#ffffff",
 * )
 */
class StockChangeCategory extends BasketStockBulkBaseForm {

	const FIELD = 'field_product_category';
	const NODE_TYPE = 'product';
	// Return svg icon code
	public function getIcoContent(){
		return '<svg width="15" height="12" viewBox="0 0 15 12" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M0 0H3.31913V3.31913H0V0Z" fill="#2B4E72"/>
			<path d="M4.37393 0H15V3.31913H4.37393V0Z" fill="#2B4E72"/>
			<path d="M0 4.30685H3.31913V7.62598H0V4.30685Z" fill="#2B4E72"/>
			<path d="M4.37393 4.30685H15V7.62598H4.37393V4.30685Z" fill="#2B4E72"/>
			<path d="M0 8.61371H3.31913V11.933H0V8.61371Z" fill="#2B4E72"/>
			<path d="M4.37393 8.61371H15V11.933H4.37393V8.61371Z" fill="#2B4E72"/>
			</svg>';
	}
	// Fill out the form with your fields
	public function getForm(&$form, $form_state){
		$emptyNode = \Drupal::service('entity_type.manager')->getStorage('node')->create(array(
			'type'          => $this::NODE_TYPE,
		));
		$widget = \Drupal::service('entity_type.manager')->getStorage('entity_form_display')->load('node.'.$this::NODE_TYPE.'.default')->getRenderer($this::FIELD);
		if(!empty($widget)){
			$items = $emptyNode->get($this::FIELD);
			$items->filterEmptyItems();
			$form['cat'] = $widget->form($items, $form, $form_state);
			$form['cat']['widget']['#required'] = FALSE;
		}
	}
	// Getting settings for further processing
	public function getBulkSettings($form_state){
		return $form_state->getValue($this::FIELD);
	}
	// Apply changes to a node
	public function processBulk($nid, $settings){
		$entity = Node::load($nid);
		if(!empty($entity) && !empty($entity->{$this::FIELD})){
			$setValues = [];
			if(!empty($settings)){
				foreach ($settings as $k => $value){
					if(!is_numeric($k)) continue;
					if(!empty($value['hs'])){
						foreach ($value['hs'] as $hs){
							if(!empty($hs)){
								$setValues[] = [
									'target_id'		=> $hs
								];
							}
						}
					}
				}
			}
			$entity->get($this::FIELD)->setValue($setValues);
			$entity->save();
		}
	}
}