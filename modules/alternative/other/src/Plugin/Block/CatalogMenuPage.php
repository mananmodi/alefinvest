<?php

namespace Drupal\other\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 *
 * @Block(
 *   id = "other_catalog_menu_page",
 *   admin_label = @Translation("Catalog Menu Page"),
 *   category = @Translation("other"),
 * )
 */
class CatalogMenuPage extends BlockBase {

  protected $current_path;

  public static function getChild($tid) {

    $output = [];
    $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('product_categories', $tid, 1, TRUE);
    if (!empty($children)) {
      foreach ($children as $child) {
        $child = \Drupal::service('entity.repository')->getTranslationFromContext($child);
        $output[$child->id()] = [
          'name'   => $child->getName(),
          'url'    => $child->toUrl()->toString(),
          'weight' => $child->get('weight')->getString(),
        ];
        if ($childs = self::getChild($child->id())) {
          $output[$child->id()]['children'] = $childs;
        }
      }
      return $output;
    }
  }

	public function build() {
    $route_match = \Drupal::service('current_route_match');
    $parameters = $route_match->getParameters()->all();
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $cid = 'catalog_menu_page:' . $language;
    if (!$categories = \Drupal::cache()->get($cid)) {
      $categories = [];
      $ids = [];
      $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('product_categories', 0, 1, TRUE);
      foreach ($terms as $term) {
        $ids[] = $term->id();
        $term = \Drupal::service('entity.repository')->getTranslationFromContext($term);
        $categories[$term->id()] = [
          'name'   => $term->getName(),
          'url'    => $term->toUrl()->toString(),
          'weight' => $term->get('weight')->getString(),
        ];
        if ($childs = self::getChild($term->id())) {
          $categories[$term->id()]['children'] = $childs;
        }
      }
      \Drupal::cache()->set($cid, $categories, Cache::PERMANENT, ['handy_cache_tags:taxonomy_term:product_categories']);
    } else {
      $categories = $categories->data;
    }

    $this->current_path = \Drupal::request()->getRequestUri();
    $this->setActiveClass($categories);
    $output['categories'] = $categories;

    return [
      '#theme' => 'other_catalog_menu_page',
      '#output' => $output,
      '#cache' => [
        'contexts' => [
          'url.path',
        ],
      ],
    ];
  }

  private function setActiveClass(&$categories) {
    foreach ($categories as &$category) {
      if (!empty($category['children'])) {
        if ($this->setActiveClass($category['children'])) {
          $category['tid_active'] = TRUE;
          $category['tid_url'] = TRUE;
          return TRUE;
        }
      }
      if ($category['url'] === $this->current_path) {
        $category['tid_active'] = TRUE;
        $category['tid_url'] = TRUE;
        return TRUE;
      }
    }
  }

}
