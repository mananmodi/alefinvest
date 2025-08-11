<?php
/**
 * @ViewsField("novaposhta_en_cost")
 */

namespace Drupal\novaposhta\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

class NovaPoshtaEnCost extends FieldPluginBase{
  
  public function render(ResultRow $values){
    return [
      '#type' => 'inline_template',
      '#template' => '<span class="nowrap">{{ value|round(2) }} {{ currency }}</span>',
      '#context' => [
        'value'	=> $this->getValue($values),
        'currency' => \Drupal::service('NovaPoshta')->t('UAH')
      ]
    ];
  }
}