<?php

namespace Drupal\basket_imex\Plugin\IMEX\field;

use Drupal\basket_imex\Plugins\IMEXfield\BasketIMEXfieldInterface;

/**
 * BooleanField IMEX type.
 *
 * @BasketIMEXfield(
 *   id = "boolean",
 *   type = {"boolean"},
 *   name = "Logical field",
 *   type_info = "(int)<br/>1 / 0",
 * )
 */
class BooleanField implements BasketIMEXfieldInterface {

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
          $values[trim($val['value'])] = !empty($val['value']) ? 1 : 0;
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
        'value' => 0,
      ],
    ];
    if (!empty($importValue)) {
      $importValues = explode(PHP_EOL, $importValue);
      foreach ($importValues as $key => $importValue) {
        $setValue[$key] = [
          'value' => !empty(trim($importValue)) ? 1 : 0,
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
