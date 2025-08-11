<?php

namespace Drupal\basket_imex\Plugin\IMEX\field;

/**
 * FileFieldInFolder IMEX type.
 *
 * @BasketIMEXfield(
 *   id = "file_in_folder",
 *   type = {"image","file"},
 *   name = "File in folder /IMEX_FILES/",
 *   type_info = "(string)<br/>File in a folder /IMEX_FILES/",
 * )
 */
class FileFieldInFolder extends FileField {

  const FOLDER = '/IMEX_FILES/';

  /**
   * Data array formation.
   *
   * @param object $entity
   *   Entity that has been updated.
   * @param string $importValue
   *   Import value.
   */
  public function setValues($entity, $importValue = '') {
    $dir = DRUPAL_ROOT . $this::FOLDER;
    $newImportValue = [];
    $images = explode(PHP_EOL, $importValue);
    if (!empty($images)) {
      foreach ($images as $image) {
        $fileRun = NULL;
        $files = NULL;
        if (is_dir($dir)) {
          $files = \Drupal::service('file_system')->scanDirectory($dir, '/^' . preg_quote(trim($image)) . '$/');
        }
        if (!empty($files)) {
          foreach ($files as $file) {
            if ($file->filename == trim($image)) {
              $fileRun = str_replace(DRUPAL_ROOT, $GLOBALS['base_url'], $file->uri);
              break;
            }
          }
        }
        $newImportValue[trim($image)] = $fileRun ?? trim($image);
      }
    }
    $importValue = !empty($newImportValue) ? implode(PHP_EOL, $newImportValue) : $importValue;
    return parent::setValues($entity, $importValue);
  }

}
