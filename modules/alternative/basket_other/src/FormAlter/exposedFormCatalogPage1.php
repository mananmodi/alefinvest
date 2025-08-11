<?php

namespace Drupal\basket_other\FormAlter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\views\Views;

class exposedFormCatalogPage1{

	protected static $getFields;
	const DEF_SORT = 'action|DESC';
	const MAX_ITEMS = 5;

	public static function alter(&$form, $form_state){

		$view = $form_state->get('view');

		hide($form['sort_by']);
		hide($form['sort_order']);
		$form['actions']['submit']['#attributes']['style'] = 'display:none;';
		$form['#attached']['library'][] = 'jquery_ui_slider/slider';
		$form['#attached']['library'][] = 'basket_other/tooltipster';
		$form['#attached']['library'][] = 'basket_other/basket_other.js';
		// ---
		$form['f_title'] = [
			'#prefix'		=> '<div class="f_title">',
			'#suffix'		=> '</div>',
			'#markup'		=> gtext('shop')->t('Filter'),
			'#weight'		=> -1000
		];
		// ---
		$pageTerm = \Drupal::routeMatch()->getParameters()->get('taxonomy_term');
		if(empty($pageTerm) && !empty($view->args[0]) && is_numeric($view->args[0])){
			$pageTerm = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($view->args[0]);
		}
		$fields = self::getFields($pageTerm);
		// price
		if(!empty($form['price'][0])){
			if(empty($fields['price'])){
				hide($form['price']);
			}
			$form['price'][0]['min']['#title_display'] = 'none';
			$form['price'][0]['max']['#title_display'] = 'none';
			$form['price'][] = [
				'#markup'		=> '<div class="ui_slider_init ui_slider_init_price" data-min="'.$fields['price']['min'].'" data-max="'.$fields['price']['max'].'" data-field="price"></div>'
			];
			// change
			$form['price'][0]['min']['#attributes']['onchange'] = 'slider_set_val_onkeyup(this, \'price\')';
			$form['price'][0]['max']['#attributes']['onchange'] = 'slider_set_val_onkeyup(this, \'price\')';
			// attr min
			$form['price'][0]['min']['#attributes']['min'] = $fields['price']['min'];
			$form['price'][0]['max']['#attributes']['min'] = $fields['price']['min'];
			// attr max
			$form['price'][0]['max']['#attributes']['max'] = $fields['price']['max'];
			$form['price'][0]['min']['#attributes']['max'] = $fields['price']['max'];
		}
		$form['sort'] = [
			'#type'			=> 'hidden',
		];
		$form['#cache']['tags'][] = 'filter_fields';
		$form['#cache']['tags'][] = \Drupal::languageManager()->getCurrentLanguage()->getId();
		
		if(!empty($pageTerm)) {
			$form['#action'] = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $pageTerm->id()])->toString();
		}
	}
	protected static function getFields($pageTerm){
		$tid = !empty($pageTerm) ? $pageTerm->id() : 0;
		$language = \Drupal::languageManager()->getCurrentLanguage()->getId();
		// ---
		$cacheKeys = [
			'getFields',
			$tid,
			$language
		];
		$request = \Drupal::request()->query->all();
		if(!empty($request['price'])){
			$cacheKeys[] = implode('_', $request['price']);
		}
		// ---
		if ($cache = \Drupal::cache()->get(implode('_', $cacheKeys))){
			self::$getFields[$tid] = $cache->data;
		}
		if(!isset(self::$getFields[$tid])){
			self::$getFields[$tid] = [];
			// Price
      $getPriceQuery = \Drupal::getContainer()->get('BasketQuery')->getPriceQuery('MIN');
			if(!empty($getPriceQuery)){
				$query = \Drupal::database()->select('node_field_data', 'n');
				$query->condition('n.status', 1);
				$query->condition('n.type', 'product');
				// getPriceQuery
				$query->innerJoin($getPriceQuery, 'getPriceQuery', 'getPriceQuery.nid = n.nid');
				$query->addExpression('MIN(ROUND(getPriceQuery.priceConvert))', 'min');
				$query->addExpression('MAX(ROUND(getPriceQuery.priceConvert))', 'max');
				// field_product_category
				if(!empty($tid)){
					$query->innerJoin('node__field_product_category', 'field_product_category', 'field_product_category.entity_id = n.nid');
					$query->condition('field_product_category.field_product_category_target_id', $tid);
				}
				$results = $query->execute()->fetchObject();
				if(!empty($results)){
					self::$getFields[$tid]['price'] = [
						'min'			=> !empty($results->min) ? $results->min : 0,
						'max'			=> !empty($results->max) ? $results->max : 0,
					];
				}
			}
			\Drupal::cache()->set(implode('_', $cacheKeys), self::$getFields[$tid], Cache::PERMANENT, ['filter_fields']);
		}
		return self::$getFields[$tid];
	}
	public static function viewsQueryAlter($view, $query){
		$request = \Drupal::request()->query->all();
		$query->addField('node_field_data', 'nid', 'n_nid', ['function' => 'groupby']);
		$query->addGroupBy("node_field_data.nid");
		// SORT
		$activeSort = \Drupal::request()->query->get('sort');
		if(empty($activeSort)) $activeSort = self::DEF_SORT;
		[$sortBy, $sortOrder] = explode('|', $activeSort);
		$query->orderby = [];
		switch($sortBy){
			case'action':
				$join = Views::pluginManager('join')->createInstance('standard', [
					'type'       => 'LEFT',
					'table'      => 'node__field_product_promotion',
					'field'      => 'entity_id',
					'left_table' => 'node_field_data',
					'left_field' => 'nid',
					'operator'   => '=',
				]);
				$query->addRelationship('field_product_promotion', $join, 'node_field_data');
				$query->addOrderBy('field_product_promotion', 'field_product_promotion_target_id', $sortOrder);
			break;
			case'price':
				if(!empty($query->relationships['basket_get_price_field_getPriceQuery_MIN'])){
					$query->addOrderBy(NULL, 'basket_get_price_field_getPriceQuery_MIN.priceConvert', $sortOrder, '_getPriceSort');
				}
			break;
			case'rating':
				$join = Views::pluginManager('join')->createInstance('standard', [
					'type'       => 'LEFT',
					'table'      => 'node__field_product_rating',
					'field'      => 'entity_id',
					'left_table' => 'node_field_data',
					'left_field' => 'nid',
					'operator'   => '=',
				]);
				$query->addRelationship('field_product_rating', $join, 'node_field_data');
				$query->addOrderBy('field_product_rating', 'field_product_rating_value', $sortOrder);
			break;
			case'popular':
				$join = Views::pluginManager('join')->createInstance('standard', [
					'type'       => 'LEFT',
					'table'      => 'node__field_product_popular',
					'field'      => 'entity_id',
					'left_table' => 'node_field_data',
					'left_field' => 'nid',
					'operator'   => '=',
				]);
				$query->addRelationship('field_product_popular', $join, 'node_field_data');
				$query->addOrderBy('field_product_popular', 'field_product_popular_value', $sortOrder);
			break;
		}
		$query->addOrderBy('node_field_data', 'created', 'DESC');
		$query->addOrderBy('node_field_data', 'nid', 'DESC');
	}
	public static function countView($count = 0, $class = []){
		$class[] = 'count_views_catalog';
		return [
			'#markup'		=> gtext('shop')->plural($count, '@count product', '@count products'),
			'#weight'		=> -1000,
			'#prefix'		=> '<div class="'.implode(' ', $class).'">',
			'#suffix'		=> '</div>'
		];
	}
	public static function viewsView(&$vars){
		$vars['header'][] = self::countView($vars['view']->total_rows);
		// sort
		$activeSort = \Drupal::request()->query->get('sort');
		if(empty($activeSort)) $activeSort = self::DEF_SORT;
		$vars['header'][] = [
			'#type'			=> 'select',
			'#options'		=> [
				'price|ASC'			=> gtext('shop')->t('From cheap to expensive'),
				'price|DESC'		=> gtext('shop')->t('From expensive to cheap'),
				'popular|DESC'	=> gtext('shop')->t('Popular'),
				'action|DESC'		=> gtext('shop')->t('Promotional'),
				'rating|DESC'		=> gtext('shop')->t('By rating')
			],
			'#attributes'	=> [
				'onchange'		=> 'filter_change_sort(this)'
			],
			'#value'		=> $activeSort,
			'#prefix'		=> '<div class="catalog_sort_wrap">',
			'#suffix'		=> '</div>'
		];
	}
}