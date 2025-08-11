<?php

namespace Drupal\basket_other\FormAlter;

class userLoginForm{
	public static function alter(&$form, $form_state){
		$form['name']['#title'] = gtext('shop')->t('E-mail');
	}
}