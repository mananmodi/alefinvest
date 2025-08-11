<?php

namespace Drupal\basket_imex\Plugin\IMEX\field;

use Drupal\basket_imex\Plugins\IMEXfield\BasketIMEXfieldInterface;

/**
 * NumberField IMEX type.
 *
 * @BasketIMEXfield(
 *   id = "number",
 *   type = {"integer","float","decimal"},
 *   name = "Number",
 *   type_info = "(decimal)",
 * )
 */
class NumberField implements BasketIMEXfieldInterface {

  /**
   * Getting data for export.
   *
   * @param object $entity
   *   Entity that has been updated.
   * @param string $fieldName
   *   Field that has been updated.
   */
  public function getValues($entity, $fieldName) {
    $values = [];
    if (!empty($entity->{$fieldName})) {
      $fieldValues = $entity->get($fieldName)->getValue();
      if (!empty($fieldValues)) {
        foreach ($fieldValues as $val) {
          if (trim($val['value']) == '') {
            continue;
          }
          $values[trim($val['value'])] = trim($val['value']);
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
    $setValue = [
      [
        'value' => '',
      ],
    ];
    if (!empty($importValue)) {
      $importValues = explode(PHP_EOL, $importValue);
      foreach ($importValues as $key => $importValue) {
        if (!empty($importValue)) {
          $importValue = str_replace(',', '.', $importValue);
          $importValue = floatval(preg_replace("/[^-0-9\.]/", "", $importValue));
        }
        $setValue[$key] = [
          'value' => trim($importValue),
        ];
      }
    }
    return $setValue;
  }

  /**
   * Additional field processing after $entity update / creation.
   *
   * @param object $entity
   *   Entity that has been updated.
   * @param string $importValue
   *   Import value.
   */
  public function postSave($entity, $importValue = '') {}

}
