<?php

namespace Drupal\basket_other\FormAlter;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

class userEditForm{
	public static function alter(&$form, $form_state){
		$entity = $form_state->getBuildInfo()['callback_object']->getEntity();
		if(\Drupal::service('theme.manager')->getActiveTheme()->getName() == 'personal' && \Drupal::routeMatch()->getRouteName() == 'entity.user.edit_form'){
			if($entity->id() == \Drupal::currentUser()->id()){
				$response = new RedirectResponse(Url::fromRoute('entity.user.canonical', [
					'user'			=> $entity->id()
				],[
					'query'			=> \Drupal::request()->query->all()
				])->toString());
				$response->send();
			}
		}
		if(isset($form['current_pass']['#title'])){
			$form['current_pass']['#title'] = gtext('shop')->t('Old password');
			$form['current_pass']['#description'] = '';
		}
		if(!empty($form['pass']['widget'])){
			$form['pass']['widget']['#pre_render'][] = __CLASS__.'::passPreRender';
		}
		if(!empty($form['field_user_phone']['widget'][0]['value'])){
			$phoneMask = \Drupal::service('Basket')->getSettings('order_form', 'config.phone_mask.mask');
			if(!empty($phoneMask)){
				$form['#attached']['library'][] = 'basket/jquery.inputmask';
				$form['field_user_phone']['widget'][0]['value']['#attributes']['class'][] = 'js-basket-input-mask';
				$form['field_user_phone']['widget'][0]['value']['#attributes']['data-inputmask'] = '\'mask\': \''.trim($phoneMask).'\'';
			}
		}
		if(!empty($form['account']['name']['#type']) && $form['account']['name']['#type'] == 'value'){
			$form['account']['name']['#value'] = $entity->get('name')->value;
		}
	}
	public static function passPreRender($element){
		$element['pass1']['#title'] = gtext('shop')->t('New password');
		$element['pass2']['#title'] = gtext('shop')->t('Repeat password');
		return $element;
	}
}