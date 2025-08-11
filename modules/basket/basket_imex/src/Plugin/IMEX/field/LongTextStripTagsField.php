<?php

namespace Drupal\basket_imex\Plugin\IMEX\field;

/**
 * LongTextStripTagsField IMEX type.
 *
 * @BasketIMEXfield(
 *   id = "big_text_strip_tags",
 *   type = {"text_with_summary","text_long"},
 *   name = "Big text (strip_tags)",
 *   type_info = "(string)<br/>Text with HTML (strip_tags)",
 * )
 */
class LongTextStripTagsField extends LongTextField {

  /**
   * Getting data for export.
   *
   * @param object $entity
   *   Entity that has been updated.
   * @param string $fieldName
   *   Field that has been updated.
   */
  public function getValues($entity, $fieldName) {
    $values = parent::getValues($entity, $fieldName);
    $values = str_replace('&nbsp;', ' ', $values);
    return strip_tags($values);
  }

}
