<?php

namespace Drupal\simple_pass_reset\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Alter user reset password page.
    $route = $collection->get('user.reset');
    if ($route) {
      $route->setDefaults([
        '_title_callback' => '\Drupal\simple_pass_reset\Controller\User::title',
        '_controller' => '\Drupal\simple_pass_reset\Controller\User::resetPass',
      ]);
      $route->setRequirement('_simple_pass_reset_access', '\Drupal\simple_pass_reset\AccessChecks\ResetPassAccessCheck::access');
    }
  }

}
