<?php

namespace Drupal\basket_imex\Plugin\IMEX\field;

use Drupal\basket_imex\Plugins\IMEXfield\BasketIMEXfieldInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Url;
use Drupal\basket_imex\BasketIMEXTrait;

/**
 * FileField IMEX type.
 *
 * @BasketIMEXfield(
 *   id = "file",
 *   type = {"image","file"},
 *   name = "File",
 *   type_info = "(string)<br/>Web link to file",
 * )
 */
class FileField implements BasketIMEXfieldInterface {

  use BasketIMEXTrait;

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
          if (empty($val['target_id'])) {
            continue;
          }
          $file = File::load($val['target_id']);
          if (!empty($file)) {
            $url = Url::fromUri(\Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()))->toString();
            $values[$url] = $url;
          }
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
    $oldFileInfos = [];
    if (!empty($entity->basketIMEXupdateField) && !empty($entity->{$entity->basketIMEXupdateField})) {
      foreach ($entity->get($entity->basketIMEXupdateField)->getValue() as $val) {
        $oldFileInfos[$val['target_id']] = $val;
      }
    }

    $setValue = [];
    if (!empty($importValue)) {
      $importValues = explode(PHP_EOL, $importValue);

      $filePaths = [];
      foreach ($importValues as $lineValue) {
        foreach (explode(',', $lineValue) as $file) {
          $file = trim($file);
          $filePaths[$file] = $file;
        }
      }

      foreach ($filePaths as $filePath) {
        $getFid = $this->importFileByUri($filePath, $entity);
        if (!empty($getFid)) {
          $setValue[] = [
            'target_id' => $getFid,
            'alt' => $oldFileInfos[$getFid]['alt'] ?? '',
            'title' => $oldFileInfos[$getFid]['title'] ?? '',
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
