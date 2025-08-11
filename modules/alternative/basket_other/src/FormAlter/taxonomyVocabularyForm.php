<?php

namespace Drupal\basket_other\FormAlter;

use \Drupal\Core\Render\Element;

class taxonomyVocabularyForm{
	public static function alter(&$form, $form_state){
		$form['description_full'] = $form['description'];
		$form['description_full']['#type'] = 'text_format';
		$form['description']['#access'] = FALSE;
		$form['#validate'][] = __CLASS__.'::validDescriptionFull';
	}
	public static function validDescriptionFull($form, $form_state){
		$form_state->setValue('description', $form_state->getValue(['description_full', 'value']));
	}

	public static function alterConfig(&$form, $form_state){
		if(!empty($form['config_names'])){
			$children = Element::children($form['config_names']);
			if(!empty($children) && strpos(reset($children), 'taxonomy.vocabulary.') !== FALSE){
				if(!empty($form['config_names'][reset($children)]['description']['translation'])){
					$form['config_names'][reset($children)]['description']['translation']['#type'] = 'text_format';
					$form['#validate'][] = __CLASS__.'::validConfigDescriptionFull';
				}
			}
		}
	}
	public static function validConfigDescriptionFull($form, $form_state){
		$values = $form_state->getValues();
		foreach ($values['translation']['config_names'] as $keyVoc => &$fields){
			if(isset($fields['description']['value'])){
				$fields['description'] = $fields['description']['value'];
			}
		}
		$form_state->setValues($values);
	}
}