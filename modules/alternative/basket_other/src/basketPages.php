<?php

namespace Drupal\basket_other;

use Drupal\Core\Ajax\ReplaceCommand;

class basketPages{
	public static function alter(&$element, $page_type, $page_subtype){
		switch($page_type){
			case'api':
				switch($page_subtype){
					case'delete_item':
					case'change_count':
						$element->addCommand(new ReplaceCommand('[data-cartid="view_wrap-cart_goods-block_1"]', \Drupal::service('Basket')->getView('cart_goods', 'block_1')));
						if(empty(\Drupal::service('Basket')->Cart()->getCount())){
							$element->addCommand(new ReplaceCommand('.basket-pages-wrap.basket-pages-order', \Drupal::service('Basket')->getView('cart_goods', 'cart')));
						}
						break;
					case'load_popup':
						if(!empty($_POST['load_popup'])){
							switch($_POST['load_popup']){
								case'basket_view_edit':
									$BasketPopup = \Drupal::service('BasketPopup');
									$BasketPopup->isSite(TRUE);
									$BasketPopup->openModal(
										$element,
										\Drupal::service('Basket')->Translate()->t('Basket'),
										\Drupal::service('Basket')->getView('cart_goods', 'block_2'),
										[
											'width' => 960,
											'class' => ['basket_popup_view']
										]
									);
								break;
							}	
						}
					break;
				}
			break;
			case'order':
				if(empty(\Drupal::service('Basket')->Cart()->getCount())){
					$element = [
						'view'		=> \Drupal::service('Basket')->getView('cart_goods', 'cart')
					];
				}
			break;
		}
	}
}