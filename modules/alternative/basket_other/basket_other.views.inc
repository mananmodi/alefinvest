<?php

/**
 * Implements hook_views_data_alter().
 */
function basket_other_views_data_alter(array &$data){
	$data['node']['basket_other_brand'] = [
		'title'         => 'Basket other (BRAND)',
		'filter'		=> [
			'id'            => 'basket_other_brand',
		]
	];
}