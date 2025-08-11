<?php

namespace Drupal\basket_imex\Plugin\IMEX\field;

use Drupal\basket_imex\BasketIMEXTrait;

/**
 * LongTextFieldWithoutDeadline IMEX type.
 *
 * @BasketIMEXfield(
 *   id = "big_text_without_deadline",
 *   type = {"text_with_summary","text_long"},
 *   name = "Big text (Without deadline)",
 *   type_info = "(string)<br/>Text with HTML (without deadline)",
 * )
 */
class LongTextFieldWithoutDeadline extends LongTextField {

  use BasketIMEXTrait;

  /**
   * Data array formation.
   *
   * @param object $entity
   *   Entity that has been updated.
   * @param string $importValue
   *   Import value.
   */
  public function setValues($entity, $importValue = '') {
    $setValue = [
      [
        'value' => '',
        'summary' => $entity->{$entity->basketIMEXupdateField}[0]->summary ?? '',
      ],
    ];
    if (!empty($importValue)) {
      $setValue[0] = [
        'summary' => $entity->{$entity->basketIMEXupdateField}[0]->summary ?? '',
        'value' => $this->replaceNl2brWithoutDeadline($importValue),
        'format' => $this->getFirstFormatDeadline(),
      ];
    }
    return $setValue;
  }

  /**
   * {@inheritdoc}
   */
  public function replaceNl2brWithoutDeadline($text) {

    // Replace entity space.
    $text = htmlentities($text, ENT_QUOTES | ENT_SUBSTITUTE, 'utf-8');
    $text = str_replace("&nbsp;", " ", $text);
    $text = html_entity_decode($text);

    // All replace.
    $text = str_replace('<br/><br/>', '<br>', $text);
    $text = str_replace('<br><br>', '<br>', $text);
    $text = str_replace('<p> </p>', '', $text);

    // Parse images.
    $this->parseImages($text);

    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstFormatDeadline() {
    $formats = \Drupal::entityTypeManager()->getStorage('filter_format')->loadByProperties(['status' => TRUE]);
    if (!empty($formats['full_html'])) {
      return 'full_html';
    }
    return !empty($formats) ? key($formats) : NULL;
  }

}
