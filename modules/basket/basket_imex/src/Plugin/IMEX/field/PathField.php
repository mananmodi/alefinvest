<?php

namespace Drupal\basket_imex\Plugin\IMEX\field;

use Drupal\basket_imex\Plugins\IMEXfield\BasketIMEXfieldInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * PathField IMEX type.
 *
 * @BasketIMEXfield(
 *   id = "path",
 *   type = {"path"},
 *   name = "URL alias",
 * )
 */
class PathField implements BasketIMEXfieldInterface {

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
    $getEntityTypeId = $entity->getEntityTypeId();
    if ($getEntityTypeId == 'node') {
      $options = ['absolute' => TRUE];

      $languages = \Drupal::service('language_manager')->getLanguages();
      if (!empty($languages[$entity->get('langcode')->value])) {
        $options['language'] = $languages[$entity->get('langcode')->value];
      }
      $values[] = Url::fromRoute('entity.' . $getEntityTypeId . '.canonical', [
        '' . $getEntityTypeId . '' => $entity->id(),
      ], $options)->toString();
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
  public function setValues($entity, $importValue = '') {}

  /**
   * Additional field processing after $entity update / creation.
   *
   * @param object $entity
   *   Entity that has been updated.
   * @param string $importValue
   *   Import value.
   */
  public function postSave($entity, $importValue = '') {
    if ($entity instanceof NodeInterface && !empty($importValue)) {
      foreach (explode(PHP_EOL, $importValue) as $item) {
        $alias = \Drupal::entityTypeManager()->getStorage('path_alias')->create([
          'path' => '/node/' . $entity->id(),
          'alias' => parse_url($item, PHP_URL_PATH),
          'langcode' => $entity->get('langcode')->value,
        ]);
        $alias->save();
      }
    }
  }

}
