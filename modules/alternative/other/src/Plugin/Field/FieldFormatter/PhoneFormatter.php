<?php

namespace Drupal\other\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'phone' formatter.
 *
 * @FieldFormatter(
 *   id = "phone",
 *   label = @Translation("Phone"),
 *   field_types = {
 *     "string",
 *   }
 * )
 */
class PhoneFormatter extends FormatterBase {

  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if (!$item->isEmpty()) {
        $filtered_value = preg_replace('/[^+0-9]/', '', $item->value);
        $field = '<a href="tel:' . $filtered_value . '">' . $item->value . '</a>';
        $elements[$delta] = [
          '#markup' => $field
        ];
      }
    }

    return $elements;
  }

}
