<?php

namespace Drupal\basket_other;

class nodeView{
	public static function alter(&$build, $entity, $display){
		switch($entity->bundle()){
			case'product':
				switch($build['#view_mode']){
					case'teaser':
					case'compare':
						if(!empty($build['basket_add']['#info']['input'])){
							unset($build['basket_add']['#info']['input']);
						}


            $request = \Drupal::request()->query->all();
						$build['node_price'] = [
							'#context'		=> \Drupal::getContainer()->get('Basket')->getNodePrice($entity, 'MIN', $request['price'] ?? [])
						];
						if(!empty($build['node_price']['#context'])){
							$build['node_price']['#context'] = (array)$build['node_price']['#context'];
						}
						$build['node_price']['#context']['currency'] = \Drupal::service('Basket')->Cart()->getCurrencyName();
					break;
					default:
						$build['node_price'] = [
							'#theme'		=> 'basket_other_node_price_full',
							'#info'			=> [
								'price'			=> \Drupal::service('Basket')->getNodePrice($entity, 'FIRST'),
								'currency'		=> \Drupal::service('Basket')->Cart()->getCurrencyName()
							],
							'#prefix'		=> '<div id="node_price_full_'.$entity->id().'">',
							'#suffix'		=> '</div>'
						];
					break;
				}
			break;
		}
	}
}