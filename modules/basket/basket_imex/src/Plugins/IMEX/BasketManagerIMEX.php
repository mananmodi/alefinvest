<?php

namespace Drupal\basket_imex\Plugins\IMEX;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Provides an Basket IMEX plugin manager.
 *
 * @see \Drupal\basket_imex\Plugins\IMEX\Annotation\BasketIMEX
 * @see \Drupal\basket_imex\Plugins\IMEX\BasketIMEXInterface
 * @see plugin_api
 */
class BasketManagerIMEX extends DefaultPluginManager {

  /**
   * Basket object.
   *
   * @var \Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Constructs a IMEXManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/IMEX',
      $namespaces,
      $module_handler,
      'Drupal\basket_imex\Plugins\IMEX\BasketIMEXInterface',
      'Drupal\basket_imex\Plugins\IMEX\Annotation\BasketIMEX'
    );
    $this->alterInfo('basket_imex_info');
    $this->setCacheBackend($cache_backend, 'basket_imex_info_plugins');
    $this->basket = \Drupal::getContainer()->get('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function getInstanceByID(string $id, $settings = FALSE) {
    $defs = $this->getDefinitions();
    if (!isset($defs[$id])) {
      return FALSE;
    }
    return $this->getInstance($defs[$id], $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options, $settings = FALSE) {
    if (!$this->providerExists($options['provider'])) {
      return FALSE;
    }
    static $cache;
    if (isset($cache[$options['id']])) {
      return $cache[$options['id']];
    }
    $cls = $options['class'];
    $instance = new $cls($settings, $options);
    $cache[$options['id']] = $instance;
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm($system, $settings = FALSE) {
    $form = $this->getInstanceByID($system, $settings);
    if (!empty($form)) {
      return \Drupal::formBuilder()->getForm($form);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function subFields(&$fields) {
    foreach ($fields as $fieldName => $fieldDefinition) {
      switch ($fieldDefinition->getType()) {
        case'entity_reference_revisions':
          $fieldSettings = $fieldDefinition->getSettings();
          if (!empty($fieldSettings['target_type']) && $fieldSettings['target_type'] == 'paragraph') {
            foreach ($fieldSettings['handler_settings']['target_bundles'] as $targetBundleKey) {
              $subSields = \Drupal::service('entity_field.manager')->getFieldDefinitions('paragraph', $targetBundleKey);
              if (!empty($subSields)) {
                foreach ($subSields as $subSieldName => $subSieldDefinition) {
                  $subFieldClone = clone $subSieldDefinition;
                  if ($subSieldName == 'id') {
                    $subFieldClone->setLabel(implode(' -> ', [
                      $fieldDefinition->getLabel(),
                      $subSieldDefinition->getLabel(),
                    ]));
                    $fields[$fieldName . '->' . $subSieldName] = $subFieldClone;
                  }
                  if (!empty($subSieldDefinition->getTargetBundle())) {
                    switch ($subSieldName) {
                      case'status':
                      case'created':
                        break;

                      default:
                        switch ($subSieldDefinition->getType()) {
                          case'basket_price_field':
                            foreach ([
                              'old_value' => $this->basket->translate()->t('Price old'),
                              'value' => $this->basket->translate()->t('Price'),
                              'currency' => $this->basket->translate()->t('Currency'),
                            ] as $priceField => $priceLabel) {
                              $subFieldClone = clone $subSieldDefinition;
                              $subFieldClone->setLabel(implode(' -> ', [
                                $fieldDefinition->getLabel(),
                                $subFieldClone->getLabel(),
                                $priceLabel,
                              ]));
                              $subFieldClone->fieldSubType = $subFieldClone->getType() . ':' . $priceField;
                              $fields[$fieldName . '->' . $subSieldName . ':' . $priceField] = $subFieldClone;
                            }
                            break;

                          default:
                            $subFieldClone->setLabel(implode(' -> ', [
                              $fieldDefinition->getLabel(),
                              $subSieldDefinition->getLabel(),
                            ]));
                            $fields[$fieldName . '->' . $subSieldName] = $subFieldClone;
                            break;
                        }
                        break;
                    }
                  }
                }
              }
            }
          }
          break;

        case'basket_price_field':
          foreach ([
            'old_value' => $this->basket->translate()->t('Price old'),
            'value' => $this->basket->translate()->t('Price'),
            'currency' => $this->basket->translate()->t('Currency'),
          ] as $priceField => $priceLabel) {
            $subFieldClone = clone $fieldDefinition;
            $subFieldClone->setLabel(implode(' -> ', [
              $fieldDefinition->getLabel(),
              $priceLabel,
            ]));
            $subFieldClone->fieldSubType = $subFieldClone->getType() . ':' . $priceField;
            $fields[$fieldName . ':' . $priceField] = $subFieldClone;
          }
          break;

        case 'basket_filter_type':
          // Maybe will be need to add a permanent
          // cache instead of a storage load.
          $vocs = \Drupal::entityTypeManager()->getStorage('filter_entity_type')->loadMultiple(NULL, TRUE);
          foreach ($vocs as $vid => $voc) {
            $subFieldClone = clone $fieldDefinition;
            $subFieldClone->setLabel(implode(' -> ', [
              $fieldDefinition->getLabel(),
              $voc->label(),
            ]));
            $fields[$fieldName . ':' . $vid] = $subFieldClone;
          }
          unset($fields[$fieldName]);
          break;

        case'vocabilary_terms_field':
          $fieldSettings = $fieldDefinition->getSettings();
          if (!empty($fieldSettings['vocabilarys'])) {
            $vocs = Vocabulary::loadMultiple($fieldSettings['vocabilarys']);
            foreach ($fieldSettings['vocabilarys'] as $vid => $voc) {
              if (!isset($vocs[$vid])) {
                continue;
              }
              $subFieldClone = clone $fieldDefinition;
              $subFieldClone->setLabel(implode(' -> ', [
                $fieldDefinition->getLabel(),
                is_object($vocs[$vid]) ? $vocs[$vid]->label() : $vocs[$vid],
              ]));
              $fields[$fieldName . ':' . $vid] = $subFieldClone;
            }
          }
          unset($fields[$fieldName]);
          break;

        case'text_with_summary':
          $fieldSettings = $fieldDefinition->getSettings();
          if (!empty($fieldSettings['display_summary'])) {
            $subFieldClone = clone $fieldDefinition;
            $subFieldClone->setLabel(implode(' -> ', [
              $fieldDefinition->getLabel(),
              t('Summary'),
            ]));
            $subFieldClone->fieldSubType = $subFieldClone->getType() . ':summary';
            $fields[$fieldName . ':summary'] = $subFieldClone;
          }
          break;
      }
    }
  }

}
