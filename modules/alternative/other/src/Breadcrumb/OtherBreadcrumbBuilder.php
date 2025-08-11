<?php

namespace Drupal\other\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\views\Views;

class OtherBreadcrumbBuilder implements BreadcrumbBuilderInterface{
	
  /**
  * {@inheritdoc}
  */
  public function applies(RouteMatchInterface $route_match) {
    if (!\Drupal::service('router.admin_context')->isAdminRoute()) {
      return TRUE;
    }
  }

  /**
  * {@inheritdoc}
  */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['url.path']);
    $breadcrumb->addCacheContexts(['languages']);
    $breadcrumb->addLink(Link::createFromRoute(t('Home'), '<front>'));
    $parameters = $route_match->getParameters()->all();
    $route_name = $route_match->getRouteName();
    $current_path = \Drupal::service('path.current')->getPath();
    $hasTitle = TRUE;
    $customTitle = '';
		
    switch($route_name) {
      case 'view.catalog.page_2':
        if (\Drupal::currentUser()->isAuthenticated()) {
          $breadcrumb->addLink(Link::fromTextAndUrl(t('Personal area'), Url::fromUri('internal:/user')));
        }
        break;
      case 'view.basket.page_1':
      case 'novaposhta_tracking.user':
      case 'ulogin.user':
        $breadcrumb->addLink(Link::fromTextAndUrl(t('Personal area'), Url::fromUri('internal:/user')));
        break;
    }

    switch ($current_path) {
      case '/basket/view':
      case '/basket/order':
        $customTitle = t('Cart');
        break;
    }

    if (!empty($parameters['taxonomy_term'])) {
      $term = $parameters['taxonomy_term'];
      if(!empty($term)) {
        $term = \Drupal::service('entity.repository')->getTranslationFromContext($term);
        switch($term->bundle()) {
          case 'brands':
            $breadcrumb->addLink(Link::fromTextAndUrl(t('Brands'), Url::fromRoute('view.brands.page_1')));
            break;
          case 'product_categories':
            $breadcrumb->addLink(Link::fromTextAndUrl(t('Catalog'), Url::fromUri('internal:/catalog/all')));
            break;
        }
  
        $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
        $termParents = $termStorage->loadAllParents($term->id());
        if (!empty($termParents)) {
          foreach (array_reverse($termParents) as $parent) {
            if ($parent->id() === $term->id()) continue;
            $parent = \Drupal::service('entity.repository')->getTranslationFromContext($parent);
            $breadcrumb->addLink(Link::createFromRoute($parent->getName(), 'entity.taxonomy_term.canonical', ['taxonomy_term' => $parent->id()]));
          }
        }
      }
    }

    if (!empty($parameters['view_id'])) {
      $view = $parameters['view_id'];
    }
    if (!empty($parameters['node'])) {
      $node = $parameters['node'];
      switch($node->bundle()) {
        case 'promotion':
          $breadcrumb->addLink(Link::fromTextAndUrl(t('Sale of goods'), Url::fromUri('internal:/promotions')));
          $hasTitle = FALSE;
          break;

        case 'news':
          $breadcrumb->addLink(Link::fromTextAndUrl(t('News'), Url::fromUri('internal:/news')));
          break;

        case 'product':
          $breadcrumb->addLink(Link::fromTextAndUrl(t('Catalog'), Url::fromUri('internal:/catalog/all')));
          if ($node->hasField('field_product_category')) {
            if ($categories = $node->get('field_product_category')->getString()) {
              $categories = explode(',', $categories);
              $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load(trim(end($categories)));
            }
            if (!empty($term)) {
              $parents = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadAllParents($term->id());
              if (!empty($parents)) {
                foreach (array_reverse($parents) as $parent) {
                  if ($parent->id() === $term->id()) continue;
                  $parent = \Drupal::service('entity.repository')->getTranslationFromContext($parent);
                  $breadcrumb->addLink(Link::createFromRoute($parent->getName(), 'entity.taxonomy_term.canonical', ['taxonomy_term' => $parent->id()]));
                }
              }
              $term = \Drupal::service('entity.repository')->getTranslationFromContext($term);
              $breadcrumb->addLink(Link::createFromRoute($term->getName(), 'entity.taxonomy_term.canonical', ['taxonomy_term' => $term->id()]));
            }
          }
          break;
      }
    }

    if ($hasTitle) {
      if (!empty($customTitle)) {
        $title = $customTitle;
      }
      else {
        if (!empty($parameters['view_id']) && !empty($parameters['display_id']) && empty($parameters['taxonomy_term'])) {
          $view = Views::getView($parameters['view_id']);
          $view->setDisplay($parameters['display_id']);
          $title = $view->getTitle();
        } else {
          $request = \Drupal::request();
          $title = \Drupal::service('title_resolver')->getTitle($request, $route_match->getRouteObject());
        }
      }
      $breadcrumb->addLink(Link::createFromRoute($title, '<none>'));
    }

    return $breadcrumb;
  }
}