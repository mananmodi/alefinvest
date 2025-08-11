<?php

namespace Drupal\other\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 *
 * @Block(
 *   id = "other_catalog_menu",
 *   admin_label = @Translation("Catalog Menu"),
 *   category = @Translation("other"),
 * )
 */
class CatalogMenu extends BlockBase {

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

    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $cid = 'catalog_menu:' . $language;
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

    $query = \Drupal::database()->select('node_field_data', 'nfd');
    $query->leftJoin('node__field_promotion_menu_show', 'ps', 'ps.entity_id = nfd.nid');
    $query->leftJoin('node__field_promotion_date_start', 'promotion_date_start', 'promotion_date_start.entity_id = nfd.nid');
    $query->leftJoin('node__field_promotion_date_end', 'promotion_date_end', 'promotion_date_end.entity_id = nfd.nid');

    $currentTime = new DrupalDateTime();
    $time = $currentTime->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $query->condition('promotion_date_start.field_promotion_date_start_value', $time, '<=');
    $query->condition('promotion_date_end.field_promotion_date_end_value', $time, '>');

    $query->condition('nfd.type', 'promotion');
    $query->condition('nfd.status', 1);
    $query->condition('nfd.default_langcode', 1);
    $query->condition('ps.field_promotion_menu_show_value', 1);
    $query->addExpression('MIN(promotion_date_end.field_promotion_date_end_value)', 'min');
    $query->range(0, 2);
    $expiration_date = $query->execute()->fetchAll();
    if (!empty($expiration_date[0]->min)) {
      $date = new DrupalDateTime($expiration_date[0]->min);
      $expiration_date = $date->format('U') + 1;
    }
    else {
      $expiration_date = Cache::PERMANENT;
    }


    $promotions = [];
    $cid = 'catalog_menu_promotions:' . $language;
    if (!$promotions = \Drupal::cache()->get($cid)) {
      $query = \Drupal::database()->select('node_field_data', 'nfd');
      $query->leftJoin('node__field_promotion_menu_show', 'ps', 'ps.entity_id = nfd.nid');
      $query->leftJoin('node__field_promotion_date_start', 'promotion_date_start', 'promotion_date_start.entity_id = nfd.nid');
      $query->leftJoin('node__field_promotion_date_end', 'promotion_date_end', 'promotion_date_end.entity_id = nfd.nid');

      $currentTime = new DrupalDateTime();
      $time = $currentTime->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

      $query->condition('promotion_date_start.field_promotion_date_start_value', $time, '<=');
      $query->condition('promotion_date_end.field_promotion_date_end_value', $time, '>');

      $query->condition('nfd.type', 'promotion');
      $query->condition('nfd.status', 1);
      $query->condition('nfd.default_langcode', 1);
      $query->condition('ps.field_promotion_menu_show_value', 1);
      $query->addField('nfd', 'nid');
      $query->range(0, 2);
      $results = $query->execute()->fetchAll();

      if (!empty($results)) {
        $nids = [];
        foreach ($results as $item) {
          $nids[] = $item->nid;
        }
        $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
        foreach ($nodes as $node) {
          $image = $node->get('field_promotion_image')->view([
            'label' => 'hidden',
            'settings' => [
              'image_style' => '290x215'
            ],
          ]);
//          $image[0]['#item_attributes']['lazy_enabled'] = TRUE;
          $promotions[] = [
            'url' => $node->toUrl()->toString(),
            'image' => \Drupal::service('renderer')->render($image),
            'title' => $node->getTitle()
          ];
        }
      }
      \Drupal::cache()->set($cid, $promotions, $expiration_date, ['handy_cache_tags:node:promotion']);
    }
    else {
      $promotions = $promotions->data;
    }

    $output['promotions'] = $promotions;
    $output['categories'] = $categories;
    return [
      '#theme' => 'other_catalog_menu',
      '#output' => $output,
      '#cache' => [
        'tags' => [
          'handy_cache_tags:node:promotion'
        ],
        'max-age' => !empty($expiration_date) ? $expiration_date - time() : Cache::PERMANENT,
      ],
    ];
  }

}
