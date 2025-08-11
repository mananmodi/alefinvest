<?php
/**
 * @ViewsField("novaposhta_en_weight")
 */

namespace Drupal\novaposhta\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

class NovaPoshtaEnWeight extends FieldPluginBase{
  
  public function render(ResultRow $values){
    return [
      '#type' => 'inline_template',
      '#template' => '<span class="nowrap">{{ value|round(2) }} {{ suffix }}</span>',
      '#context' => [
        'value'	=> $this->getValue($values),
        'suffix' => \Drupal::service('NovaPoshta')->t('kg')
      ]
    ];
  }
}