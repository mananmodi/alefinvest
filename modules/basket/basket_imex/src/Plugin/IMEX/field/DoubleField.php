<?php

namespace Drupal\basket_imex\Plugin\IMEX\field;

use Drupal\basket_imex\Plugins\IMEXfield\BasketIMEXfieldInterface;

/**
 * DoubleField IMEX type.
 *
 * @BasketIMEXfield(
 *   id = "double_field",
 *   type = {"double_field"},
 *   name = "Double field",
 *   type_info = "(string)<br/>First|Second",
 * )
 */
class DoubleField implements BasketIMEXfieldInterface {

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
          if ($val['first'] == '' || $val['second'] == '') {
            continue;
          }
          $line = implode('|', $val);
          $values[$line] = $line;
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
    $setValue = [];
    if (!empty($importValue)) {
      $importValues = explode(PHP_EOL, $importValue);
      foreach ($importValues as $key => $importValue) {
        $importValue = explode('|', $importValue);
        if (trim($importValue[0]) !== '' && trim($importValue[1]) !== '') {
          $setValue[$key] = [
            'first' => isset($importValue[0]) ? trim($importValue[0]) : '',
            'second' => isset($importValue[1]) ? trim($importValue[1]) : '',
          ];
        }
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
