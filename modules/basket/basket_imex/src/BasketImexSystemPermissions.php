<?php

namespace Drupal\basket_imex;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides dynamic permissions for Basket Imex System.
 *
 * @ingroup basket_imex
 */
class BasketImexSystemPermissions {

  use StringTranslationTrait;

  /**
   * Basket object.
   *
   * @var \Drupal\basket\Basket
   */
  protected $basket;

  /**
   * IMEX object.
   *
   * @var \Drupal\basket_imex\Plugins\IMEX\BasketManagerIMEX
   */
  protected $basketImex;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::getContainer()->get('Basket');
    $this->basketImex = \Drupal::getContainer()->get('BasketIMEX');
  }

  /**
   * Returns an array of node type permissions.
   *
   * @return array
   *   The ImexSystem by bundle permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function generatePermissions() {
    $perms = [];
    $services = $this->basketImex->getDefinitions();
    foreach ($services as $service) {
      if (!empty($service['hide'])) {
        continue;
      }
      $perms += $this->buildPermissions($service);
    }

    return $perms;
  }

  /**
   * Returns a list of node permissions for a given system type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions($system) {
    $type_id = $system['id'];
    return [
      "access basket imex $type_id"  => [
        'title' => $this->t('Access to the imex system «%type_name»', [
          '%type_name' => $system['name'] ?? $system['id'],
        ]),
      ],
    ];
  }

}
