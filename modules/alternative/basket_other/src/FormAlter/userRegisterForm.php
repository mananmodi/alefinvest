<?php

namespace Drupal\basket_other\FormAlter;

class userRegisterForm{
	public static function alter(&$form, $form_state){		
		if(!empty($form['field_user_phone']['widget'][0]['value'])){
			$phoneMask = \Drupal::service('Basket')->getSettings('order_form', 'config.phone_mask.mask');
			if(!empty($phoneMask)){
				$form['#attached']['library'][] = 'basket/jquery.inputmask';
				$form['field_user_phone']['widget'][0]['value']['#attributes']['class'][] = 'js-basket-input-mask';
				$form['field_user_phone']['widget'][0]['value']['#attributes']['data-inputmask'] = '\'mask\': \''.trim($phoneMask).'\'';
			}
		}
		if(empty($form['name']['widget']['#default_value']) && !empty($form['account']['name']['#value'])){
			$form['name']['widget']['#default_value'] = $form['account']['name']['#value'];
			$form['name']['widget']['#type'] = 'hidden';
		}
	}
}