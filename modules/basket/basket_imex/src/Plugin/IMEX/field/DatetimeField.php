<?php

namespace Drupal\basket_imex\Plugin\IMEX\field;

use Drupal\basket_imex\Plugins\IMEXfield\BasketIMEXfieldInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * DatetimeField IMEX type.
 *
 * @BasketIMEXfield(
 *   id = "datetime",
 *   type = {"datetime"},
 *   name = "Datetime",
 * )
 */
class DatetimeField implements BasketIMEXfieldInterface {

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
      foreach ($entity->get($fieldName) as $date_row) {
        if (!is_object($date_row->date)) {
          continue;
        }
        $values[] = \Drupal::service('date.formatter')->format($date_row->date->getTimestamp(), 'custom', 'd.m.Y H:i:s');
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
        $date = new DrupalDateTime(trim($importValue));
        $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
        $setValue[$key] = [
          'value' => $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
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
