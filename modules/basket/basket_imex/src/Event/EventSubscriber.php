<?php

namespace Drupal\basket_imex\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onKernelRequestCheckRedirect', 33];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function onKernelRequestCheckRedirect(RequestEvent $event) {
    if (!$event->isMainRequest()) {
      $url = $_SERVER['REQUEST_URI'];

      // Alter:
      \Drupal::moduleHandler()->alter('basket_imex_Reditect', $event);

      $redirectNID = \Drupal::database()->select('basket_imex_redirect', 'r')
        ->fields('r', ['nid'])
        ->condition('r.old_url', $url)
        ->execute()->fetchField();
      if (empty($redirectNID)) {
        $request = clone $event->getRequest();
        $redirectNID = \Drupal::database()->select('basket_imex_redirect', 'r')
          ->fields('r', ['nid'])
          ->condition('r.old_url', $request->getPathInfo() . '%', 'LIKE')
          ->orderBy('r.old_url')
          ->range(0, 1)
          ->execute()->fetchField();
      }
      if (!empty($redirectNID)) {
        $url = Url::fromRoute('entity.node.canonical', ['node' => $redirectNID])->toString();
        header('Location: ' . $url, TRUE, 301);
        exit;
      }
    }
  }

}
