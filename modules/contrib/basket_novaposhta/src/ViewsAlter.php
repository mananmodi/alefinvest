<?php

namespace Drupal\novaposhta;

use Drupal\Core\Render\Element;
use Drupal\novaposhta\API\NovaPoshtaAPI;

class ViewsAlter{

	protected $novaPoshta;
	protected $basket;

	function __construct(){
		$this->novaPoshta = \Drupal::service('NovaPoshta');
		$this->basket = \Drupal::service('Basket');
	}
	public function viewsViewTable(&$vars){
		if(!empty($vars['header'])){
			$this->basket->getClass('Drupal\basket\ViewsAlters')->tableHeaderAlter($vars['header'], 'novaposhta');
		}
		if(empty($vars['view']->total_rows) && !empty($vars['rows'])){
			$vars['rows'][0]['columns'][0]['content'][1]['field_output'] = [
				'#markup'		=> $this->novaPoshta->t('The list is empty.')
			];
		}

    $res = $vars['view']->query->query()->execute()->fetchAll();
    $ids = [];
    if(!empty($res)) {
      foreach ($res as $re) {
        $ids[$re->np_en_num] = $re->np_en_num;
      }
    }
    if(!empty($ids)) {
      $vars['caption_needed'] = TRUE;
      $vars['caption'] = [
        '#type' => 'inline_template',
        '#template' => '<a href="{{ url }}" target="_blank" class="np-print-filter">{{ ico|raw }}</a>',
        '#context' => [
          'ico' => \Drupal::getContainer()->get('Basket')->getIco('print.svg', 'novaposhta'),
          'url' => (new NovaPoshtaAPI())->getPrintLink(implode(',', $ids))
        ]
      ];
    }
	}
	public function formAlter(&$form){
		if(!empty($form['actions']['submit']['#value'])){
			$form['actions']['submit']['#value'] = $this->novaPoshta->t(trim($form['actions']['submit']['#value']));
		}
		foreach (Element::children($form) as $fieldName){
			if(!empty($form[$fieldName]['#attributes']['placeholder'])){
				$form[$fieldName]['#attributes']['placeholder'] = $this->novaPoshta->t(trim($form[$fieldName]['#attributes']['placeholder']));
			}
			if(!empty($form['#info']['filter-'.$fieldName]['label'])){
				$form['#info']['filter-'.$fieldName]['label'] = $this->novaPoshta->t(trim($form['#info']['filter-'.$fieldName]['label']));
			}
		}
	}
}