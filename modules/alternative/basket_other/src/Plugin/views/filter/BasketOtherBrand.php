<?php
/**
 * @ViewsFilter("basket_other_brand")
 */

namespace Drupal\basket_other\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;

class BasketOtherBrand extends FilterPluginBase{
	public function query(){
		if(!empty($this->value)){
			$join = Views::pluginManager('join')->createInstance('standard', [
				'type'       => 'INNER',
				'table'      => 'node__field_product_specifications',
				'field'      => 'entity_id',
				'left_table' => 'node_field_data',
				'left_field' => 'nid',
				'operator'   => '=',
				'extra'		 => [[
					'field'		=> 'field_product_specifications_voc',
					'value'		=> 'brand'
				]]
			]);
			$this->query->addRelationship($this->realField, $join, 'node_field_data');
			$this->query->addWhere(NULL, $this->realField.'.field_product_specifications_tid', $this->value);
		}
	}
	public function buildExposedForm(&$form, FormStateInterface $form_state){
		if (empty($this->options['exposed'])) {
			return;
		}
		$identifier = $this->options['expose']['identifier'];

		$options = [];
		foreach (\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('brand', 0, 1, TRUE) as $term){
			$options[$term->id()] = $term->getName();
		}
		$form[$identifier] = [
			'#type'				=> 'select',
			'#options'			=> $options,
			'#empty_option'		=> t('- Select -')
		];
	}
	public function acceptExposedInput($input){
		if(!empty($input[$this->options['expose']['identifier']])){
			$this->value = $input[$this->options['expose']['identifier']];
			return TRUE;
		}
		return FALSE;
	}
}