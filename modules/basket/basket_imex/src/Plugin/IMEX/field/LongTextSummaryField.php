<?php

namespace Drupal\basket_imex\Plugin\IMEX\field;

/**
 * LongTextSummaryField IMEX type.
 *
 * @BasketIMEXfield(
 *   id = "big_text_summary",
 *   type = {"text_with_summary:summary"},
 *   name = "Big text (Summary)",
 * )
 */
class LongTextSummaryField extends LongTextField {

  /**
   * Getting data for export.
   *
   * @param object $entity
   *   Entity that has been updated.
   * @param string $fieldName
   *   Field that has been updated.
   */
  public function getValues($entity, $fieldName) {
    if (str_contains($fieldName, ':')) {
      [$fieldName, $fieldSubName] = explode(':', $fieldName);
    }
    $values = [];
    if (!empty($entity->{$fieldName})) {
      $fieldValues = $entity->get($fieldName)->getValue();
      if (!empty($fieldValues)) {
        foreach ($fieldValues as $val) {
          if (trim($val['summary']) == '') {
            continue;
          }
          $values[trim($val['summary'])] = trim($val['summary']);
        }
      }
    }
    return implode(PHP_EOL, $values);
  }

  /**
   * Data array formation.
   *
   * @param object $entity
   *   Entity that has been updated.
   * @param string $importValue
   *   Import value.
   */
  public function setValues($entity, $importValue = '') {
    if (str_contains($entity->basketIMEXupdateField, ':')) {
      [$entity->basketIMEXupdateField, $lastField] = explode(':', $entity->basketIMEXupdateField);
    }
    $setValue = [
      [
        'summary' => '',
        'value' => $entity->{$entity->basketIMEXupdateField}[0]->value ?? '',
        'format' => $entity->{$entity->basketIMEXupdateField}[0]->format ?? '',
      ],
    ];
    if (!empty($importValue)) {
      $importValues = explode(PHP_EOL, $this->replaceNl2br($importValue));
      foreach ($importValues as $key => $importValue) {
        $setValue[$key] = [
          'summary' => trim($importValue),
          'value' => $entity->{$entity->basketIMEXupdateField}[$key]->value ?? '',
          'format' => $entity->{$entity->basketIMEXupdateField}[$key]->format ?? '',
        ];
      }
    }
    return $setValue;
  }

}
