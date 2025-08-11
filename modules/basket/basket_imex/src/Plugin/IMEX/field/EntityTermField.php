<?php

namespace Drupal\basket_imex\Plugin\IMEX\field;

use Drupal\basket_imex\Plugins\IMEXfield\BasketIMEXfieldInterface;
use Drupal\basket_imex\BasketIMEXTrait;
use Drupal\hierarchical_select_widget\HierarchicalSelectWidget;

/**
 * EntityTermField IMEX type.
 *
 * @BasketIMEXfield(
 *   id = "entity_reference:taxonomy_term",
 *   type = {"entity_reference:taxonomy_term"},
 *   name = "Taxonomy term",
 *   type_info = "(string)<br/>Hierarchy: Category->Subcategory",
 * )
 */
class EntityTermField implements BasketIMEXfieldInterface {

  use BasketIMEXTrait;

  /**
   * {@inheritdoc}
   */
  protected static $getEntityFormComponents;

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
        $getEntityFormComponents = $this->getEntityFormComponents($entity->getEntityTypeId(), $entity->bundle(), 'default');
        if (!empty($getEntityFormComponents[$fieldName]['type']) && $getEntityFormComponents[$fieldName]['type'] == 'HierarchicalSelectWidgetSelects') {
          $lines = HierarchicalSelectWidget::getLineage($fieldValues);
          foreach ($lines as $lines_) {
            $terms = \Drupal::service('entity_type.manager')->getStorage("taxonomy_term")->loadMultiple($lines_);
            $items = [];
            foreach ($lines_ as $item) {
              if (!empty($terms[$item])) {
                $terms[$item] = \Drupal::service('entity.repository')->getTranslationFromContext($terms[$item], $entity->get('langcode')->value);
                $items[] = $terms[$item]->getName();
              }
            }
            $values[] = implode('->', $items);
          }
        }
        else {
          foreach ($fieldValues as $val) {
            if (empty($val['target_id'])) {
              continue;
            }
            $parents = \Drupal::service('entity_type.manager')->getStorage("taxonomy_term")->loadAllParents($val['target_id']);
            if (!empty($parents)) {
              $terms = [];
              foreach (array_reverse($parents) as $parent) {
                $parent = \Drupal::service('entity.repository')->getTranslationFromContext($parent, $entity->get('langcode')->value);
                $terms[$parent->id()] = $parent->getName();
              }
              $values[] = implode('->', $terms);
            }
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
    static $vocNameCache = [];

    $setValue = [];
    if (!empty($importValue)) {
      $vocName = NULL;
      if (!empty($entity->basketIMEXupdateField)) {
        $fieldName = $entity->basketIMEXupdateField;
        $cacheKey = $entity->getEntityTypeId() . '::' . $entity->bundle() . '::' . $fieldName;

        if (!isset($vocNameCache[$cacheKey])) {
          $fieldDefinitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
          if (!empty($fieldDefinitions[$fieldName])) {
            $fieldSettings = $fieldDefinitions[$fieldName]->getSettings();
            if (!empty($fieldSettings['handler_settings']['target_bundles'])) {
              $vocNameCache[$cacheKey] = key($fieldSettings['handler_settings']['target_bundles']);
            }
          }

          // In order not to play constantly with
          // downloading all non-existent data.
          if (empty($vocNameCache[$cacheKey])) {
            $vocNameCache[$cacheKey] = '';
          }
        }
        $vocName = $vocNameCache[$cacheKey];
      }

      if (!empty($vocName)) {
        $importValues = explode(PHP_EOL, $importValue);
        $getEntityFormComponents = $this->getEntityFormComponents($entity->getEntityTypeId(), $entity->bundle(), 'default');
        if (!empty($getEntityFormComponents[$entity->basketIMEXupdateField]['type']) && $getEntityFormComponents[$entity->basketIMEXupdateField]['type'] == 'HierarchicalSelectWidgetSelects') {
          foreach ($importValues as $importValue) {
            $getTid = 0;
            foreach (explode('->', $importValue) as $termName) {
              if (empty(trim($termName))) {
                continue;
              }
              $getTid = $this->getTermId($termName, $vocName, $getTid, $entity->language()->getId());
              if (!empty($getTid)) {
                $setValue[]['target_id'] = $getTid;
              }
            }
          }
        }
        else {
          foreach ($importValues as $importValue) {
            $getTid = 0;
            foreach (explode('->', $importValue) as $termName) {
              $getTid = $this->getTermId($termName, $vocName, $getTid, $entity->language()->getId());
            }
            if (!empty($getTid)) {
              $setValue[]['target_id'] = $getTid;
            }
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

  /**
   * {@inheritdoc}
   */
  public function getEntityFormComponents($entity_type, $bundle, $form_mode) {
    if (!isset(self::$getEntityFormComponents[$entity_type][$bundle][$form_mode])) {
      $form_display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load($entity_type . '.' . $bundle . '.' . $form_mode);
      self::$getEntityFormComponents[$entity_type][$bundle][$form_mode] = $form_display->getComponents();
    }
    return self::$getEntityFormComponents[$entity_type][$bundle][$form_mode];
  }

}
