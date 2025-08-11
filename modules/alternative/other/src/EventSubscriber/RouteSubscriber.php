<?php
namespace Drupal\other\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Dummy route subscriber.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    /** @var \Symfony\Component\Routing\Route $route */
    if ($route = $collection->get('ulogin.user')) {
      $route->setDefault('_title_callback', '');
      $route->setDefault('_title', 'Social networks');
    }
    if ($route = $collection->get('entity.user.canonical')) {
      $route->setDefault('_title_callback', '');
      $route->setDefault('_title', 'Personal area');
    }

    if ($route = $collection->get('entity.user.edit')) {
      $route->setDefault('_title_callback', '');
      $route->setDefault('_title', 'Profile settings');
    }

    if ($route = $collection->get('entity.user.edit_form')) {
      $route->setDefault('_title_callback', '');
      $route->setDefault('_title', 'Profile settings');
    }
  }
}