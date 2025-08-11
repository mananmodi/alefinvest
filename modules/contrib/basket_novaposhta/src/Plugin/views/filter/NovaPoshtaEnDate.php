<?php

namespace Drupal\novaposhta\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filter to handle dates stored as a timestamp.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("novaposhta_en_date")
 */
class NovaPoshtaEnDate extends FilterPluginBase{
	
  /**
   * Provide a simple textfield for equality
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
			'#type'			=> 'date',
		];
  }
  
  public function acceptExposedInput($input){
    if (!empty($this->options['expose']['identifier'])){
      $value = $input[$this->options['expose']['identifier']];
      if(!empty($value)){
        $this->value = $value;
        return TRUE;
      }
    }
    return FALSE;
  }
  
  /**
   * Add this filter to the query.
   *
   * Due to the nature of fapi, the value and the operator have an unintended
   * level of indirection. You will find them in $this->operator
   * and $this->value respectively.
   */
  public function query() {
    $this->ensureMyTable();
    $this->query->addWhere($this->options['group'], "$this->tableAlias.$this->realField", strtotime($this->value), '>=');
    $this->query->addWhere($this->options['group'], "$this->tableAlias.$this->realField", strtotime($this->value.' 23:59:59'), '<=');
	}
}