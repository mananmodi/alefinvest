<?php

/**
 * @file
 * Provide views data for dblog.module.
 */

/**
 * Implements hook_views_data().
 */
function novaposhta_views_data() {
  $data = [];

  $data['novaposhta_en']['table']['group'] = 'Novaposhta';
  $data['novaposhta_en']['table']['wizard_id'] = 'novaposhta_en';

  $data['novaposhta_en']['table']['base'] = [
    'field' => 'id',
    'title' => t('ID EN'),
    'help' => '',
  ];
  foreach ([
    'id' => [
      'title' => t('ID', ['context' => 'novaposhta']),
      'field_id' => 'standard',
      'sort_id' => 'standard',
    ],
    'created' => [
      'title' => t('Time to create invoice', [], ['context' => 'novaposhta']),
      'field_id' => 'date',
      'sort_id' => 'date',
      'filter_id' => 'novaposhta_en_date',
    ],
    'en_num' => [
      'title' => t('Invoice number', [], ['context' => 'novaposhta']),
      'field_id' => 'novaposhta_en_num',
      'sort_id' => 'standard',
      'filter_id' => 'string',
    ],
    'delivery_day'  => [
      'title' => t('Estimated delivery date', [], ['context' => 'novaposhta']),
      'field_id' => 'date',
      'sort_id' => 'date',
      'filter_id' => 'novaposhta_en_date',
    ],
    'cost' => [
      'title' => t('Announced price', [], ['context' => 'novaposhta']),
      'field_id' => 'novaposhta_en_cost',
      'sort_id' => 'standard',
    ],
    'cost_delivery' => [
      'title' => t('Shipping cost', [], ['context' => 'novaposhta']),
      'field_id' => 'novaposhta_en_cost',
      'sort_id' => 'standard',
    ],
    'weight' => [
      'title' => t('Weight', [], ['context' => 'novaposhta']),
      'field_id' => 'novaposhta_en_weight',
      'sort_id' => 'standard',
    ],
    'seats_amount' => [
      'title' => t('Number of places of departure', [], ['context' => 'novaposhta']),
      'field_id' => 'standard',
      'sort_id' => 'standard',
    ],
    'description' => [
      'title' => t('Description of departure', [], ['context' => 'novaposhta']),
      'field_id' => 'standard',
      'sort_id' => 'standard',
    ],
    'address' => [
      'title' => t('Receive address', [], ['context' => 'novaposhta']),
      'field_id' => 'novaposhta_en_address',
      'sort_id' => 'standard',
    ],
    'status' => [
      'title' => t('Status', [], ['context' => 'novaposhta']),
      'field_id' => 'standard',
      'sort_id' => 'standard',
    ],
    'date_receiving' => [
      'title' => t('Date of receiving', [], ['context' => 'novaposhta']),
      'field_id' => 'date',
      'sort_id' => 'date',
    ]
  ] as $fieldName => $field) {
    $data['novaposhta_en'][$fieldName] = [
      'title' => $field['title'],
      'help' => ''
    ];
    if(!empty($field['field_id']))  $data['novaposhta_en'][$fieldName]['field']['id'] = $field['field_id'];
    if(!empty($field['sort_id']))  $data['novaposhta_en'][$fieldName]['sort']['id'] = $field['sort_id'];
    if(!empty($field['filter_id'])) $data['novaposhta_en'][$fieldName]['filter']['id'] = $field['filter_id'];
  }
  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function novaposhta_views_data_alter(array &$data){
  $data['novaposhta_en']['settings'] = [
    'title' => t('Settings', [], ['context' => 'novaposhta']),
    'field' => [
      'title' => t('Settings', [], ['context' => 'novaposhta']),
      'help' => '',
      'id' => 'novaposhta_en_settings',
      'click sortable' => FALSE,
    ]
  ];
}