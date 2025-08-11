<?php

namespace Drupal\basket_imex\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Core\Generator\GeneratorInterface;
use Drupal\Console\Extension\Manager;

/**
 * Class EventNotificationsGenerator.
 *
 * @package Drupal\Console\Generator
 */
class ImexGenerator extends Generator implements GeneratorInterface {

  /**
   * Manager object.
   *
   * @var \Drupal\Console\Extension\Manager
   */
  protected $extensionManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(Manager $extensionManager) {
    $this->extensionManager = $extensionManager;
  }

  /**
   * {@inheritdoc}
   */
  public function generate(array $parameters) {

    // Example how to render a twig template using the renderFile method.
    $this->renderer->addSkeletonDir(__DIR__ . '/templates');

    $module = $parameters['module'];
    $class = $parameters['class'];

    $moduleInstance = $this->extensionManager->getModule($module);
    $moduleDir = $moduleInstance->getPath();

    switch ($parameters['g_type']) {
      case'service':
        $this->renderFile(
          'basket_imex-generate-service.php.twig',
          $moduleDir . '/src/Plugin/IMEX/' . $class . '.php',
          $parameters
        );
        break;

      case'field':
        $this->renderFile(
          'basket_imex-generate-field.php.twig',
          $moduleDir . '/src/Plugin/IMEX/field/' . $class . '.php',
          $parameters
        );
        break;
    }
  }

}
