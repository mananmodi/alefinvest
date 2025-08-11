<?php

namespace Drupal\basket_imex\Plugins\IMEXfield;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an Basket IMEX plugin manager.
 *
 * @see \Drupal\basket\Plugins\Discount\Annotation\BasketDiscount
 * @see \Drupal\basket\Plugins\Discount\BasketDiscountInterface
 * @see plugin_api
 */
class BasketManagerIMEXfield extends DefaultPluginManager {

  /**
   * Basket object.
   *
   * @var \Drupal\basket\Basket
   */
  protected object $basket;

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
      'Plugin/IMEX/field',
      $namespaces,
      $module_handler,
      'Drupal\basket_imex\Plugins\IMEXfield\BasketIMEXfieldInterface',
      'Drupal\basket_imex\Plugins\IMEXfield\Annotation\BasketIMEXfield'
    );
    $this->alterInfo('basket_imex_field_info');
    $this->setCacheBackend($cache_backend, 'basket_imex_field_info_plugins');
    $this->basket = \Drupal::getContainer()->get('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function getServicesByType($type) {
    static $cache;
    if (isset($cache[$type])) {
      return $cache[$type];
    }
    $services = [];
    $plugins = $this->getDefinitions();
    if (!empty($plugins)) {
      foreach ($plugins as $plugin) {
        if (empty($plugin['type'])) {
          continue;
        }
        if (in_array($type, $plugin['type'])) {
          $services[$plugin['id']] = $this->basket->translate($plugin['provider'])->trans($plugin['name']);
        }
      }
    }
    $cache[$type] = $services;
    return $services;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstanceByID(string $id) {
    $defs = $this->getDefinitions();
    if (!isset($defs[$id])) {
      return FALSE;
    }
    return $this->getInstance($defs[$id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    if (!$this->providerExists($options['provider'])) {
      return FALSE;
    }
    static $cache;
    if (isset($cache[$options['id']])) {
      return $cache[$options['id']];
    }
    $cls = $options['class'];
    $instance = new $cls();

    $cache[$options['id']] = $instance;
    return $instance;
  }

  /**
   * Data array formation.
   */
  public function setValues($pluginID, $entity, $importValue) {
    $service = $this->getInstanceByID($pluginID);
    if (!empty($service)) {
      return $service->setValues($entity, $importValue);
    }
    return [];
  }

  /**
   * Additional field processing after $entity update / creation.
   */
  public function postSave($pluginID, $entity, $importValue, $config = NULL) {
    $service = $this->getInstanceByID($pluginID);
    if (!empty($service)) {
      $service->postSave($entity, $importValue, $config);
    }
  }

  /**
   * Getting data for export.
   */
  public function getValues($pluginID, $entity, $fieldName) {
    $service = $this->getInstanceByID($pluginID);
    if (!empty($service)) {
      return $service->getValues($entity, $fieldName);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldInfo($pluginID) {
    $info = '(string)';
    $defs = $this->getDefinitions();
    if (isset($defs[$pluginID]['type_info'])) {
      $info = $defs[$pluginID]['type_info'];
    }
    return $info;
  }

}
