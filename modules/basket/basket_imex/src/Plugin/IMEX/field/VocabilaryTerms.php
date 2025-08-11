<?php

namespace Drupal\basket_imex\Plugin\IMEX\field;

use Drupal\basket_imex\Plugins\IMEXfield\BasketIMEXfieldInterface;
use Drupal\basket_imex\BasketIMEXTrait;

/**
 * VocabilaryTerms IMEX type.
 *
 * @BasketIMEXfield(
 *   id = "vocabilary_terms_field",
 *   type = {"vocabilary_terms_field"},
 *   name = "Vocabilary terms field",
 * )
 */
class VocabilaryTerms implements BasketIMEXfieldInterface {

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
    if (!empty($entity->{$fieldName}) && !empty($entity->basketIMEXgetVocName)) {
      $fieldValues = $entity->get($fieldName)->getValue();
      if (!empty($fieldValues)) {
        $tids = [];
        foreach ($fieldValues as $val) {
          if ($val['voc'] != $entity->basketIMEXgetVocName) {
            continue;
          }
          $tids[$val['tid']] = $val['tid'];
        }
        if (!empty($tids)) {
          foreach (\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadMultiple($tids) as $term) {
            $term = \Drupal::service('entity.repository')->getTranslationFromContext($term, $entity->get('langcode')->value);
            $values[] = $term->getName();
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
    $setValue = [];
    if (str_contains($entity->basketIMEXupdateField, ':')) {
      [$entity->basketIMEXupdateField, $entity->basketIMEXgetVocName] = explode(':', $entity->basketIMEXupdateField);
    }
    if (!empty($entity->basketIMEXupdateField) && !empty($entity->{$entity->basketIMEXupdateField})) {
      $setValue = $entity->get($entity->basketIMEXupdateField)->getValue();
    }
    if (!empty($entity->basketIMEXgetVocName)) {
      foreach ($setValue as $key => $value) {
        if ($value['voc'] == $entity->basketIMEXgetVocName) {
          unset($setValue[$key]);
        }
      }
      if (!empty($importValue)) {
        foreach (explode(PHP_EOL, $importValue) as $key => $termName) {
          if (empty(trim($termName))) {
            continue;
          }
          $getTid = $this->getTermId(trim($termName), $entity->basketIMEXgetVocName, 0);
          if (!empty($getTid)) {
            $setValue[] = [
              'voc' => $entity->basketIMEXgetVocName,
              'tid' => $getTid,
            ];
          }
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
