<?php
/**
 * @ViewsField("novaposhta_en_address")
 */

namespace Drupal\novaposhta\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

class NovaPoshtaEnAddress extends FieldPluginBase{
  
  public function render(ResultRow $values){
    return [
      '#type' => 'inline_template',
      '#template' => '{{ value|raw }}',
      '#context' => [
        'value' => $this->getValue($values)
      ]
    ];
  }
}