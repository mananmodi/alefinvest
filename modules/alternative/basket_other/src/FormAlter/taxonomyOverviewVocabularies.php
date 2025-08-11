<?php

namespace Drupal\basket_other\FormAlter;

use \Drupal\Core\Render\Element;

class taxonomyOverviewVocabularies{
	public static function alter(&$form, $form_state){
		if(!empty($form['vocabularies'])) {
			foreach (Element::children($form['vocabularies']) as $voc) {
				if(empty($form['vocabularies'][$voc]['label']['#markup'])) continue;
				$vocEntity = \Drupal::service('entity_type.manager')->getStorage('taxonomy_vocabulary')->load($voc);
				if(!empty($vocEntity)) {
					$form['vocabularies'][$voc]['label']['#markup'] = $vocEntity->label();
				}
			}
		}
	}
}