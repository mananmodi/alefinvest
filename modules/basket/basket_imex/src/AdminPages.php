<?php

namespace Drupal\basket_imex;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Yaml;

/**
 * {@inheritdoc}
 */
class AdminPages {

  /**
   * Basket object.
   *
   * @var \Drupal\basket\Basket
   */
  protected $basket;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::getContainer()->get('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function alter(&$element, $params) {
    if ($params['page_type'] == 'stock' && $params['page_subtype'] == 'imex') {
      if (!\Drupal::currentUser()->hasPermission('access basket imex')) {
        $element['#info']['content']['view'] = $this->basket->getError(403);
      }
      else {
        $system = \Drupal::request()->query->get('system');
        $settings = \Drupal::request()->query->get('settings');
        $services = \Drupal::service('BasketIMEX')->getDefinitions();
        if (!empty($system) && !empty($services[$system])) {
          if (!\Drupal::currentUser()->hasPermission('access basket imex ' . $system)) {
            $element['#info']['content']['view'] = $this->basket->getError(403);
          }
          else {
            $element['#info']['content']['view'] = [
              '#prefix' => '<div class="basket_table_wrap">',
              '#suffix' => '</div>',
              [
                '#prefix' => '<div class="b_title">',
                '#suffix' => '</div>',
                '#markup' => $this->basket->translate($services[$system]['provider'])->trans($services[$system]['name']),
              ], [
                '#prefix' => '<div class="b_content">',
                '#suffix' => '</div>',
                'form' => \Drupal::getContainer()->get('BasketIMEX')->getForm($system, $settings),
              ],
            ];
          }
        }
        else {
          $element['#info']['content']['view'] = [
            '#prefix' => '<div class="sub_pages_list">',
            '#suffix' => '</div>',
          ];
          $imexServices = Yaml::decode(file_get_contents(\Drupal::service('extension.list.module')->getPath('basket_imex') . '/config/all/services.yml'));
          if (!empty($services)) {
            foreach ($services as $service) {
              if (!empty($service['hide'])) {
                continue;
              }

              if (!\Drupal::currentUser()->hasPermission('access basket imex ' . $service['id'])) {
                continue;
              }
              $context = [
                'name' => !empty($service['name']) ? $this->basket->translate($service['provider'])->trans($service['name']) : $service['id'],
              ];
              $title = '';
              if (!empty($imexServices[$service['id']])) {
                if (!empty($imexServices[$service['id']]['ico'])) {
                  $context['ico'] = $this->basket->getIco($imexServices[$service['id']]['ico'], 'basket_imex');
                }
                if (!empty($imexServices[$service['id']]['title'])) {
                  $title = $this->basket->translate('basket_imex')->trans($imexServices[$service['id']]['title']);
                }
                unset($imexServices[$service['id']]);
              }

              if (empty($context['ico']) && !empty($service['ico'])) {
                $context['ico'] = $this->basket->getIco(
                  $service['ico'],
                  $service['ico_context'] ?? 'basket_imex'
                );
              }

              $element['#info']['content']['view'][$service['id']] = [
                '#type' => 'link',
                '#title' => [
                  '#type' => 'inline_template',
                  '#template' => '<span>{{ ico|raw }} {{ name }}</span>',
                  '#context' => $context,
                ],
                '#url' => new Url('basket.admin.pages', [
                  'page_type' => 'stock-imex',
                ],
                [
                  'query' => [
                    'system' => $service['id'],
                  ],
                ]),
                '#attributes' => ['title' => $title],
                '#prefix' => '<div class="item">',
                '#suffix' => '</div>',
                '#weight' => $service['weight'] ?? 0,
              ];
            }
          }
          if (!empty($imexServices)) {
            $onclick = 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-help'])->toString() . '\')';
            foreach ($imexServices as $id => $info) {
              $element['#info']['content']['view'][$id] = [
                '#type' => 'inline_template',
                '#template' => '<a href="javascript:void(0);" title="{{ title }}" class="disabled" onclick="{{ onclick }}">
									<span>{{ ico|raw }} {{ name }}</span>
								</a>',
                '#context' => [
                  'ico' => !empty($info['ico']) ? $this->basket->getIco($info['ico'], 'basket_imex') : '',
                  'name' => !empty($info['name']) ? $info['name'] : $id,
                  'title' => !empty($info['title']) ? $this->basket->translate('basket_imex')->trans($info['title']) : '',
                  'onclick' => $onclick,
                ],
                '#prefix' => '<div class="item">',
                '#suffix' => '</div>',
                '#weight' => $info['weight'] ?? 100,
              ];
            }
          }
          uasort(
            $element['#info']['content']['view'],
            [SortArray::class, 'sortByWeightProperty']
          );
        }
      }
    }
  }

}
