<?php
/**
 * @ViewsField("novaposhta_en_num")
 */

namespace Drupal\novaposhta\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

class NovaPoshtaEnNum extends FieldPluginBase{

  public function query() {
    parent::query();
    $this->query->addField('novaposhta_en', 'new_en_num', 'new_en_num');
  }

  public function render(ResultRow $values){
    return implode(' -> ', array_filter([
      $this->getValue($values),
      $values->new_en_num ?? NULL
    ]));
  }
}