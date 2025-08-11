<?php

namespace Drupal\basket_other;

use Drupal\Core\Template\Attribute;

class viewCartGoodsCart{
	public static function viewsViewTable(&$vars){
		$vars['header'] = [];
		if(!empty($vars['view']->result)){
			$newRows = [];
			foreach ($vars['rows'] as $key => $row){
				$params = [];
				if(!empty($vars['rows'][$key]['columns']['all_params']['content'][0]['field_output']['#markup'])){
					$params = [
						'#prefix'		=> '<div class="params">',
						'#suffix'		=> '</div>',
						'#markup'		=> $vars['rows'][$key]['columns']['all_params']['content'][0]['field_output']['#markup']
					];
				}
				$newRows[$key]	 = [
					'columns'		=> [
						'img'			=> [
							'content'		=> !empty($vars['rows'][$key]['columns']['img']['content']) ? $vars['rows'][$key]['columns']['img']['content'] : [],
							'attributes'	=> new Attribute(['class' => ['td_img']])
						],
						'info'			=> [
							'content'		=> [[
								'field_output'	=> [
									[
										'#prefix'		=> '<div class="title">',
										'#suffix'		=> '</div>',
										'#markup'		=> !empty($vars['rows'][$key]['columns']['title']['content'][0]['field_output']['#markup']) ? $vars['rows'][$key]['columns']['title']['content'][0]['field_output']['#markup'] : ''
									],[
										'#prefix'		=> '<div class="line_group">',
										'#suffix'		=> '</div>',
										$params,[
											'#prefix'		=> '<div class="count">',
											'#suffix'		=> '</div>',
											'#markup'		=> !empty($vars['rows'][$key]['columns']['count']['content'][0]['field_output']['#markup']) ? $vars['rows'][$key]['columns']['count']['content'][0]['field_output']['#markup'] : ''	
										],[
											'#markup'		=> '<div class="price_x">x</div>'
										],[
											'#prefix'		=> '<div class="price">',
											'#suffix'		=> '</div>',
											'#markup'		=> !empty($vars['rows'][$key]['columns']['price']['content'][0]['field_output']['#markup']) ? $vars['rows'][$key]['columns']['price']['content'][0]['field_output']['#markup'] : ''	
										]
									]
								]
							]],
							'attributes'	=> new Attribute(['class' => ['td_info']])
						],
						'last'			=> [
							'content'		=> [[
								'field_output'	=> [
									[
										'#prefix'		=> '<div class="delete">',
										'#suffix'		=> '</div>',
										'#markup'		=> !empty($vars['rows'][$key]['columns']['delete']['content'][0]['field_output']['#markup']) ? $vars['rows'][$key]['columns']['delete']['content'][0]['field_output']['#markup'] : ''
									],[
										'#prefix'		=> '<div class="sum">',
										'#suffix'		=> '</div>',
										'#markup'		=> !empty($vars['rows'][$key]['columns']['sum']['content'][0]['field_output']['#markup']) ? $vars['rows'][$key]['columns']['sum']['content'][0]['field_output']['#markup'] : ''
									]
								]
							]],
							'attributes'	=> new Attribute(['class' => ['td_last']])
						]
					]
				];
			}
			$vars['rows'] = $newRows;
		}
	}
	public static function viewsViewTableBlock1(&$vars){
		if(!empty($vars['view']->result)){
			$newRows = [];
			foreach ($vars['rows'] as $key => $row){
				$params = [];
				if(!empty($vars['rows'][$key]['columns']['all_params']['content'][0]['field_output']['#markup'])){
					$params = [
						'#prefix'		=> '<div class="params">',
						'#suffix'		=> '</div>',
						'#markup'		=> $vars['rows'][$key]['columns']['all_params']['content'][0]['field_output']['#markup']
					];
				}
				$newRows[$key]	 = [
					'columns'		=> [
						'img'			=> [
							'content'		=> !empty($vars['rows'][$key]['columns']['img']['content']) ? $vars['rows'][$key]['columns']['img']['content'] : [],
							'attributes'	=> new Attribute(['class' => ['td_img']])
						],
						'info'			=> [
							'content'		=> [[
								'field_output'	=> [
									[
										'#prefix'		=> '<div class="title_wrap">',
										'#suffix'		=> '</div>',
										[
											'#prefix'		=> '<div class="title">',
											'#suffix'		=> '</div>',
											'#markup'		=> !empty($vars['rows'][$key]['columns']['title']['content'][0]['field_output']['#markup']) ? $vars['rows'][$key]['columns']['title']['content'][0]['field_output']['#markup'] : ''
										],$params
									],[
										'#prefix'		=> '<div class="all_wrap">',
										'#suffix'		=> '</div>',
										[
											'#prefix'		=> '<div class="count">',
											'#suffix'		=> '</div>',
											'#markup'		=> !empty($vars['rows'][$key]['columns']['count']['content'][0]['field_output']['#markup']) ? $vars['rows'][$key]['columns']['count']['content'][0]['field_output']['#markup'].' '.gtext('shop')->t('qty.') : ''
										],[
											'#prefix'		=> '<div class="sum">',
											'#suffix'		=> '</div>',
											'#markup'		=> !empty($vars['rows'][$key]['columns']['sum']['content'][0]['field_output']['#markup']) ? $vars['rows'][$key]['columns']['sum']['content'][0]['field_output']['#markup'] : ''
										]
									]
								]
							]],
							'attributes'	=> new Attribute(['class' => ['td_info']])
						]
					]
				];
			}
			$vars['rows'] = $newRows;
		}
	}
	public static function viewsViewBlock1(&$vars){
		$vars['header'][] = [
			'#type'			=> 'inline_template',
			'#template'		=> '<div class="b_title">{{ \'Your order\'|t }}</div>
									<div class="total_count">
										{{ gtext.shop.plural(Cart.getCount|round(6), \'@count product\', \'@count products\') }}
									</div>',
			'#context'		=> [
				'Cart'			=> \Drupal::service('Basket')->Cart()
			]
		];
	}
}