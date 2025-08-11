<?php

namespace Drupal\basket_imex\Plugins\IMEX;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\basket_imex\BasketIMEXTrait;

/**
 * Class of BasketIMEXBaseForm.
 */
abstract class BasketIMEXBaseForm extends FormBase implements BasketIMEXInterface {

  use BasketIMEXTrait;

  /**
   * Settings flag.
   *
   * @var false|mixed
   */
  protected static mixed $isSettings;

  /**
   * Ajax info.
   *
   * @var array
   */
  protected static array $ajax;

  /**
   * Plugin info.
   *
   * @var array|mixed
   */
  protected static mixed $plugin;

  /**
   * Field info.
   *
   * @var array|null
   */
  protected static ?array $getFieldsNodeType;

  /**
   * Plugin info.
   *
   * @var array|null
   */
  protected static ?array $getPluginsNodeType;

  /**
   * Category info.
   *
   * @var array|null
   */
  protected static ?array $getCategoryFieldsNodeType;

  /**
   * Type info.
   *
   * @var array|null
   */
  protected static ?array $getType;

  /**
   * NodeType active.
   *
   * @var string|null
   */
  protected static ?string $activeNodeType;

  /**
   * BasketManagerIMEXfield object.
   *
   * @var \Drupal\basket_imex\Plugins\IMEXfield\BasketManagerIMEXfield
   */
  protected $imexField;

  /**
   * Basket object.
   *
   * @var \Drupal\basket\Basket
   */
  protected $basket;

  /**
   * {@inheritdoc}
   */
  public function __construct($isSettings = FALSE, $plugin = []) {
    self::$isSettings = $isSettings;
    self::$plugin = $plugin;
    self::$ajax = [
      'wrapper' => 'basket_imex_form_ajax_wrap',
      'callback' => [$this, 'ajaxReload'],
    ];
    $this->imexField = \Drupal::getContainer()->get('BasketIMEXfield');
    $this->basket = \Drupal::getContainer()->get('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_imex_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form += [
      '#prefix' => '<div id="' . self::$ajax['wrapper'] . '">',
      '#suffix' => '</div>',
    ];
    if (empty(self::$isSettings)) {
      $options = [];

      // import:
      $import = [];
      $this->getImportFormFields($import, $form_state);
      if (!empty($import)) {
        $options['import'] = $this->basket->translate('basket_imex')->t('Import');
      }

      // export:
      $export = [];
      $this->getExportFormFields($export, $form_state);
      if (!empty($export)) {
        $options['export'] = $this->basket->translate('basket_imex')->t('Export');
      }

      $form['imex'] = [
        '#type' => 'select',
        '#options' => $options,
        '#ajax' => self::$ajax,
        '#weight' => -100,
      ];
      if (empty($form_state->getValue('imex')) && !empty($options)) {
        $form_state->setValue('imex', key($options));
      }
      switch ($form_state->getValue('imex')) {
        case'import':
          $import = [];
          $this->getImportFormFields($import, $form_state);
          if (!empty($import)) {
            $this->importDefValues($form_state);
            $form['import'] = [
              '#type' => 'details',
              '#title' => $this->basket->translate('basket_imex')->t('Import'),
              '#open' => TRUE,
              'node_type' => $this->nodeTypeField() + [
                '#ajax' => self::$ajax,
                '#default_value' => $form_state->getValue('node_type') ?? key($this->nodeTypeField()['#options']),
              ],
              'langcode' => [
                '#type' => 'select',
                '#title' => t('Content language'),
                '#required' => TRUE,
                '#options' => $this->getLanguages(),
                '#default_value' => $form_state->getValue('langcode') ?? key($this->getLanguages()),
              ],
              'fields' => $import,
              'actions' => [
                '#type' => 'actions',
                'submit' => [
                  '#type' => 'submit',
                  '#value' => $this->basket->translate('basket_imex')->t('Start import'),
                  '#name' => 'runImport',
                ],
                'settings' => $this->getSettings(),
              ],
            ];
          }
          break;

        case'export':
          $export = [];
          $this->getExportFormFields($export, $form_state);
          if (!empty($export)) {
            $this->exportDefValues($form_state);
            $form['export'] = [
              '#type' => 'details',
              '#title' => $this->basket->translate('basket_imex')->t('Export'),
              '#open' => TRUE,
              'node_type' => $this->nodeTypeField() + [
                '#ajax' => self::$ajax,
                '#default_value' => $form_state->getValue('node_type'),
              ],
              'langcode' => [
                '#type' => 'select',
                '#title' => t('Content language'),
                '#required' => TRUE,
                '#options' => $this->getLanguages(),
                '#default_value' => $form_state->getValue('langcode'),
              ],
              'category' => [
                '#tree' => TRUE,
                '#type' => 'item',
              ],
              'fields' => $export,
              'actions' => [
                '#type' => 'actions',
                'submit' => [
                  '#type' => 'submit',
                  '#value' => $this->basket->translate('basket_imex')->t('Start export'),
                  '#name' => 'runExport',
                ],
                'settings' => $this->getSettings(),
              ],
            ];
            $categoryField = \Drupal::configFactory()->getEditable('basket_imex.' . str_replace(':', '_', self::$plugin['id']))->get($form_state->getValue('node_type') . '.category_field');
            if (!empty($categoryField)) {
              [$fieldName, $voc] = explode(':', $categoryField);
              $form_state->set('categoryField', $fieldName);
              $this->categoryElements($form['export']['category'], $voc, $form_state, 0, self::$ajax);
            }
          }
          break;
      }
    }
    else {
      $columns = $this->getSettingsFields();
      $form['node_type'] = $this->nodeTypeField() + ['#ajax' => self::$ajax];
      if (!empty($node_type = $form_state->getValue('node_type'))) {
        $config = \Drupal::configFactory()->getEditable('basket_imex.' . str_replace(':', '_', self::$plugin['id']))->get($node_type);
        if (empty($form_state->getValue('config')) && !empty($config['fields'])) {
          $form_state->setvalue('config', $config['fields']);
        }
        $form['category_field'] = [
          '#type' => 'select',
          '#title' => $this->basket->translate('basket_imex')->t('Product Category Field'),
          '#options' => $this->getCategoryFieldsNodeType($node_type),
          '#empty_option' => '',
          '#default_value' => !empty($config['category_field']) ? $config['category_field'] : NULL,
        ];
        $form['config'] = [
          '#type' => 'table',
          '#header' => [
            $this->basket->translate('basket_imex')->t('Column'),
            $this->basket->translate('basket_imex')->t('Search by field'),
            $this->basket->translate('basket_imex')->t('Field name'),
            $this->basket->translate('basket_imex')->t('Field plugin'),
          ],
          '#sticky' => TRUE,
        ];
        if (!empty($columns)) {
          if (empty($config['search_field'])) {
            $config['search_field'] = $columns[key($columns)];
          }
          foreach ($columns as $column) {
            $columnLabel = '<b>' . $column . '</b>';
            if (is_array($column)) {
              if (!empty($column['label'])) {
                $columnLabel = '<b>' . $column['label'] . '</b>';
              }
              if (!empty($column['key'])) {
                $columnLabel .= '<br/>' . $column['key'];
              }
              $column = !empty($column['key']) ? $column['key'] : '';
            }
            if ($column == '') {
              continue;
            }

            $isNotUpdateAccess = FALSE;
            $fNameLine = $form_state->getValue(['config', $column, 'fieldName']);
            if (!empty($fNameLine)) {
              $isNotUpdateAccess = TRUE;
              if (str_contains($fNameLine, '->') || str_contains($fNameLine, ':')) {
                $isNotUpdateAccess = FALSE;
              }
            }

            $form['config'][$column] = [
              'column' => [
                '#markup' => $columnLabel,
              ],
              'search' => [
                '#type' => 'radio',
                '#parents' => ['search_field'],
                '#return_value' => $column,
                '#default_value' => !empty($config['search_field']) && $config['search_field'] == $column ? $column : FALSE,
                '#title' => 'on',
                '#attributes' => ['class' => ['not_label']],
              ],
              'fieldName' => [
                '#type' => 'select',
                '#options' => $this->getFieldsNodeType($node_type),
                '#ajax' => [
                  'wrapper' => 'fieldPlugin_' . $this->getColumnId($column) . '_ajax_wrap',
                  'callback' => [$this, 'ajaxReloadColumn'],
                ],
                '#empty_option' => '',
                '#default_value' => !empty($config['fields'][$column]['fieldName']) ? $config['fields'][$column]['fieldName'] : NULL,
                '#ajax_column' => $column,
              ],
              'fieldPlugin' => [
                '#type' => 'container',
                '#id' => 'fieldPlugin_' . $this->getColumnId($column) . '_ajax_wrap',
                'fieldPlugin' => [
                  '#type' => 'select',
                  '#options' => $this->getPluginsNodeType(
                    $node_type,
                    $form_state->getValue(['config', $column, 'fieldName'])
                  ),
                  '#empty_option' => '',
                  '#default_value' => !empty($config['fields'][$column]['fieldPlugin']) ? $config['fields'][$column]['fieldPlugin'] : NULL,
                  '#parents' => ['config', $column, 'fieldPlugin'],
                ],
                'notUpdate' => [
                  '#type' => 'checkbox',
                  '#title' => $this->basket->translate('basket_imex')->t('Do not update if data is available'),
                  '#default_value' => $config['fields'][$column]['notUpdate'] ?? NULL,
                  '#access' => $isNotUpdateAccess,
                  '#parents' => ['config', $column, 'notUpdate'],
                  '#wrapper_attributes' => [
                    'style' => 'margin-top: 5px;',
                  ],
                ],
              ],
            ];
          }
        }
        $form['actions'] = [
          '#type' => 'actions',
          'submit' => [
            '#type' => 'submit',
            '#value' => $this->basket->translate('basket_imex')->t('Save configuration'),
            '#name' => 'saveSettings',
          ],
        ];
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxReload($form, $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function importDefValues($form_state) {}

  /**
   * {@inheritdoc}
   */
  public function exportDefValues($form_state) {}

  /**
   * {@inheritdoc}
   */
  public function ajaxReloadColumn($form, $form_state) {
    $triggerElement = $form_state->getTriggeringElement();
    if (!empty($triggerElement['#ajax_column'])) {
      return $form['config'][$triggerElement['#ajax_column']]['fieldPlugin'];
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguages() {
    $options = [];
    $languages = \Drupal::languageManager()->getLanguages();
    foreach ($languages as $language) {
      if (empty(\Drupal::config('language.entity.' . $language->getId())->get('third_party_settings.disable_language.disable'))) {
        $options[$language->getId()] = $language->getName() . ' (' . $language->getId() . ')';
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function nodeTypeField() {
    $options = [];
    foreach ($this->basket->getNodeTypes() as $type => $info) {
      $options[$type] = $info->NodeType->label();
    }
    return [
      '#type' => 'select',
      '#title' => $this->basket->translate('basket_imex')->t('Material type'),
      '#options' => $options,
      '#empty_option' => '',
      '#required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsNodeType($nodeType) {
    if (!isset(self::$getFieldsNodeType[$nodeType])) {
      foreach (basket_imex_get_fields($nodeType) as $fieldName => $fieldDefinition) {
        $field_label = is_object($fieldDefinition) ? $fieldDefinition->getLabel() : $fieldDefinition['label'];
        switch ($fieldName) {
          case'uuid':
          case'uid':
          case'langcode':
          case'vid':
          case'type':
          case'revision_timestamp':
          case'revision_uid':
          case'revision_log':
          case'default_langcode':
          case'revision_default':
          case'revision_translation_affected':
          case'content_translation_outdated':
          case'content_translation_source':
          case'menu_link':
          case'changed':
          case'promote':
          case'sticky':
            break;

          default:
            self::$getFieldsNodeType[$nodeType][$fieldName] = $field_label;
            break;
        }
      }
    }
    return self::$getFieldsNodeType[$nodeType];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginsNodeType($nodeType, $fieldName) {
    if (empty($fieldName)) {
      return [];
    }
    if (!isset(self::$getPluginsNodeType[$nodeType][$fieldName])) {
      self::$getPluginsNodeType[$nodeType][$fieldName] = [];
      $type = $this->getType($nodeType, $fieldName);
      self::$getPluginsNodeType[$nodeType][$fieldName] = $this->imexField->getServicesByType($type);
      if (empty(self::$getPluginsNodeType[$nodeType][$fieldName])) {
        self::$getPluginsNodeType[$nodeType][$fieldName] = [
          $this->basket->translate('basket_imex')->t('No plugin found for field') . ' "' . $type . '"',
        ];
      }
      else {
        self::$getPluginsNodeType[$nodeType][$fieldName][0] = $this->basket->translate('basket_imex')->t('Plugin field') . ' "' . $type . '"';
      }
    }
    return self::$getPluginsNodeType[$nodeType][$fieldName];
  }

  /**
   * {@inheritdoc}
   */
  public function getCategoryFieldsNodeType($nodeType) {
    if (!isset(self::$getCategoryFieldsNodeType[$nodeType])) {
      self::$getCategoryFieldsNodeType[$nodeType] = [];
      foreach (basket_imex_get_fields($nodeType) as $fieldName => $fieldDefinition) {
        if (!is_object($fieldDefinition)) {
          continue;
        }
        if ($fieldDefinition->getType() == 'entity_reference') {
          $fieldSettings = $fieldDefinition->getSettings();
          if (!empty($fieldSettings['target_type']) && $fieldSettings['target_type'] == 'taxonomy_term') {
            foreach ($fieldSettings['handler_settings']['target_bundles'] as $voc) {
              self::$getCategoryFieldsNodeType[$nodeType][$fieldName . ':' . $voc] = $fieldDefinition->getLabel() . ' "' . $voc . '"';
            }
          }
        }
      }
    }
    return self::$getCategoryFieldsNodeType[$nodeType];
  }

  /**
   * {@inheritdoc}
   */
  public function getType($nodeType, $fieldName) {
    if (!isset(self::$getType[$nodeType][$fieldName])) {
      self::$getType[$nodeType][$fieldName] = '';
      $fields = basket_imex_get_fields($nodeType);
      if (!empty($fields[$fieldName])) {
        self::$getType[$nodeType][$fieldName] = is_object($fields[$fieldName]) ? $fields[$fieldName]->getType() : $fields[$fieldName]['type'];
        if (is_object($fields[$fieldName]) && !empty($fields[$fieldName]->fieldSubType)) {
          self::$getType[$nodeType][$fieldName] = $fields[$fieldName]->fieldSubType;
        }
        if (self::$getType[$nodeType][$fieldName] == 'entity_reference') {
          $fieldSettings = $fields[$fieldName]->getSettings();
          if (!empty($fieldSettings['target_type'])) {
            self::$getType[$nodeType][$fieldName] .= ':' . $fieldSettings['target_type'];
          }
        }
      }
    }
    return self::$getType[$nodeType][$fieldName];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    if ($this->getImportExportSettings()) {
      return [
        '#type' => 'link',
        '#title' => $this->basket->translate('basket_imex')->t('Settings'),
        '#url' => new Url('<current>', [
          'page_type' => 'stock-imex',
        ], [
          'query' => [
            'system' => self::$plugin['id'],
            'settings' => TRUE,
          ],
        ]),
      ];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggerElement = $form_state->getTriggeringElement();
    if (!empty($triggerElement['#name'])) {
      switch ($triggerElement['#name']) {
        case'runImport':
          $operations = [];
          $rows = $this->getImportRows($form_state);
          if (!empty($rows)) {
            foreach ($rows as $row) {
              $operations[] = [
                [$this, 'formProcessImport'],
                [
                  [
                    'line' => $row,
                    'nodeType' => $form_state->getValue('node_type'),
                    'langcode' => $form_state->getValue('langcode'),
                    'pluginID' => self::$plugin['id'],
                  ],
                ],
              ];
            }
          }
          $transaction = \Drupal::database()->startTransaction();

          $batch = [
            'title' => t('Import', [], ['context' => 'basket']),
            'operations' => $operations,
            'basket_batch' => TRUE,
          ];
          batch_set($batch);
          if (!empty($redirectBath = $form_state->get('redirectBath'))) {
            $response = batch_process($redirectBath);
            $response->send();
          }
          unset($transaction);
          break;

        case'runExport':
          $operations = [];
          $rows = $this->getExportRows($form_state);
          if (!empty($rows)) {
            foreach ($rows as $row) {
              $operations[] = [
                [$this, 'formProcessExport'],
                [
                  [
                    'line' => $row,
                    'nodeType' => $form_state->getValue('node_type'),
                    'langcode' => $form_state->getValue('langcode'),
                    'pluginID' => self::$plugin['id'],
                  ],
                ],
              ];
            }
          }
          $transaction = \Drupal::database()->startTransaction();

          $batch = [
            'title' => t('Export', [], ['context' => 'basket']),
            'operations' => $operations,
            'basket_batch' => TRUE,
          ];
          batch_set($batch);
          if (!empty($redirectBath = $form_state->get('redirectBath'))) {
            $response = batch_process($redirectBath);
            $response->send();
          }

          unset($transaction);
          break;

        case'saveSettings':
          $form_state->setRedirect('<current>', [
            'page_type' => 'stock-imex',
          ], [
            'query' => [
              'system' => self::$plugin['id'],
            ],
          ]);

          // Save config:
          $config = \Drupal::configFactory()->getEditable('basket_imex.' . str_replace(':', '_', self::$plugin['id']));
          $config->set($form_state->getValue('node_type'), [
            'fields' => $form_state->getValue('config'),
            'search_field' => $form_state->getValue('search_field'),
            'category_field' => $form_state->getValue('category_field'),
          ]);
          $config->save();
          \Drupal::messenger()->addMessage(
            $this->basket->translate()->t('The configuration options have been saved.')
          );
          break;
      }
    }
  }

  /**
   * Import processing.
   */
  public function formProcessImport($info, &$context) {
    $this->processImport($info, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function entityProcess($info) {

    // Hook:
    \Drupal::moduleHandler()->invokeAll('basket_imex_preEntityProcess', [$info]);

    self::$activeNodeType = $info['nodeType'];
    $entity = $this->getEntity($info);
    if (!empty($entity) && $entity->bundle() !== $info['nodeType']) {
      $entity = NULL;
    }
    if (empty($entity)) {
      $entity = \Drupal::entityTypeManager()->getStorage('node')->create([
        'type' => $info['nodeType'],
        'langcode' => $info['langcode'],
      ]);
    }
    $trans = $entity->getTranslationLanguages();
    if (!empty($trans[$info['langcode']])) {
      $entityTrans = $entity->getTranslation($info['langcode']);
    }
    else {
      $entityTrans = $entity->addTranslation($info['langcode']);
    }

    if (empty($info['configFields'])) {
      $info['configFields'] = \Drupal::configFactory()->getEditable('basket_imex.' . $info['pluginID'])->get($info['nodeType'] . '.fields');
    }
    $configFields = $info['configFields'];
    if (empty($configFields)) {
      return FALSE;
    }
    $subEntityData = [];
    $subEntityConfig = [];
    $nodeRowNumber = 0;

    // Delete entity.
    foreach ($configFields as $column => $configField) {
      if (empty($configField['fieldName'])) {
        continue;
      }
      if (empty($configField['fieldPlugin'])) {
        continue;
      }
      if ($configField['fieldName'] == 'basket_imex_is_delete'
        && $configField['fieldPlugin'] == 'is_delete'
        && !empty($info['fields'][0][$column])) {
        $isDeleteEntity = TRUE;
      }
    }
    if ($isDeleteEntity) {
      if (!empty($entity->id())) {
        \Drupal::logger('basket_imex')->notice('Delete entity: ' . $entity->getTitle() . ' (ID: ' . $entity->id() . ')');
        $entity->delete();
      }
      else {
        \Drupal::logger('basket_imex')->notice('No entity found for deletion');
      }
      return FALSE;
    }

    foreach ($info['fields'] as $importKey => $importRows) {
      foreach ($configFields as $column => $configField) {
        if (empty($configField['fieldName'])) {
          continue;
        }
        if (empty($configField['fieldPlugin'])) {
          continue;
        }
        if ($configField['fieldName'] == 'nid') {
          continue;
        }

        // setValues.
        $entityTrans->basketIMEXupdateField = $configField['fieldName'];
        $entityTrans->imexSystem = $info['pluginID'];
        $entityTrans->imexFieldColumnKeY = $column;

        // Update sub-fields.
        // These are the columns of the paragraph.
        if (str_contains($configField['fieldName'], '->')) {
          // In case of paragraph in paragraph (for the future).
          [$fieldName, $fieldSubName] = explode('->', $configField['fieldName'], 2);
          // Settings are collected when passing the first line.
          if (empty($nodeRowNumber)) {
            $subEntityConfig[$fieldName][$column] = $configField;
            $subEntityConfig[$fieldName][$column]['subFieldName'] = $fieldSubName;
          }
          // Data collection for all paragraphs.
          $subEntityData[$fieldName][$nodeRowNumber][$column] = $importRows[$column] ?? '';
        }
        // These are columns of the node.
        else {
          $configField['importValue'] = $importRows[$column] ?? '';
          // If the nodes have several lines - there are
          // several values of paragraphs ...
          // in this case - for a node we are interested only in the
          // first line (all others are only for paragraphs).
          if (!empty($nodeRowNumber)) {
            continue;
          }
          $setValues = $this->setValues($configField, $configFields, $importRows, $entityTrans);
          if (empty($setValues['notUpdate'])) {
            if (empty($setValues)) {
              $setValues = [];
            }
            if (str_contains($configField['fieldName'], ':')) {
              [$configField['fieldName'], $fieldSubNameSecond] = explode(':', $configField['fieldName']);
            }
            if (isset($entityTrans->{$configField['fieldName']})) {
              $entityTrans->get($configField['fieldName'])->setValue($setValues);
            }
          }
        }
      }
      $nodeRowNumber++;
    }
    // Paragraph processing (data collected).
    foreach ($subEntityData as $fieldName => $subEntityDataItems) {
      $this->subEntityProcess($entity, $fieldName, $subEntityDataItems, $subEntityConfig[$fieldName]);
    }

    if (empty($entity->get('title')->value)
      && !empty($entityTrans->get('title')->value)) {
      $entity->set('title', $entityTrans->get('title')->value);
    }
    if (!empty($entityTrans->getTitle())) {

      // created.
      if (empty($entity->id())) {
        $entity->created = $entityTrans->created;
      }

      $entity->save();
      $entityTrans->save();

      // Call after updating / creating entity.
      $this->postSave($entityTrans, $info);

      // Additional field processing after $entity update / creation.
      foreach ($configFields as $column => $configField) {
        if (empty($configField['fieldName'])) {
          continue;
        }
        if (empty($configField['fieldPlugin'])) {
          continue;
        }

        // setValues:
        $importValue = $info['fields'][0][$column] ?? '';
        $entityTrans->basketIMEXupdateField = $configField['fieldName'];
        $entityTrans->allImportValues = $info['fields'];
        $this->imexField->postSave($configField['fieldPlugin'], $entityTrans, $importValue, $info);
      }

      // resetCache:
      \Drupal::service('entity_type.manager')->getViewBuilder('node')->resetCache([$entity]);
    }

    // Hook:
    \Drupal::moduleHandler()->invokeAll('basket_imex_postEntityProcess', [$info]);
  }

  /**
   * {@inheritdoc}
   */
  private function subEntityProcess($entity, $fieldName, $subEntityDataItems, $subEntityDataConfig) {
    $existsSubEntities = [];
    foreach ($this->getNodeSubFields($entity, $fieldName) as $paragraph) {
      $existsSubEntities[$paragraph->id()] = $paragraph;
    }

    // Paragraph type.
    $paragraphType = NULL;
    // We obtain the type of paragraph.
    $fields = basket_imex_get_fields($entity->bundle());
    if (!empty($fields[$fieldName]) && is_object($fields[$fieldName])) {
      $fieldSettings = $fields[$fieldName]->getSettings();
      if (!empty($fieldSettings['handler_settings']['target_bundles'])) {
        $paragraphType = key($fieldSettings['handler_settings']['target_bundles']);
      }
    }
    // Unknown paragraph type - ignore field updates.
    if (empty($paragraphType)) {
      return;
    }

    $idColumnName = NULL;
    foreach ($subEntityDataConfig as $column => $columnInfo) {
      if ($columnInfo['subFieldName'] == 'id') {
        $idColumnName = $column;
        break;
      }
    }
    if (!empty($idColumnName)) {
      foreach ($subEntityDataItems as $k => $item) {
        if (!empty($item[$idColumnName]) && !empty($existsSubEntities[$item[$idColumnName]])) {
          $subEntityDataItems[$k]['id'] = $item[$idColumnName];
        }
      }
    }
    // NO ID - we update the existing ones in order.
    if (empty($idColumnName)) {
      if (!empty($existsSubEntities)) {
        $keysSubEntity = array_keys($existsSubEntities);
        $keyID = 1;
        foreach ($subEntityDataItems as $k => $item) {
          if (!empty($keysSubEntity[($keyID - 1)])) {
            $subEntityDataItems[$k]['id'] = $keysSubEntity[($keyID - 1)];
          }
          $keyID++;
        }
      }
    }
    $newEntityValue = [];
    foreach ($subEntityDataItems as $item) {
      $item = array_filter($item);
      // The data set is empty - this line is probably
      // created solely to fill other paragraphs.
      if (empty($item)) {
        continue;
      }
      // The essence of the paragraph.
      $paragraph = NULL;
      // We get the existing paragraph.
      if (!empty($item['id']) && isset($existsSubEntities[$item['id']])) {
        $paragraph = $existsSubEntities[$item['id']];
      }
      // Paragraph type check.
      if ($paragraph && $paragraph->bundle() != $paragraphType) {
        $paragraph = NULL;
      }
      // If no paragraph is found - create.
      if (empty($paragraph)) {
        $paragraph = \Drupal::entityTypeManager()->getStorage('paragraph')->create(['type' => $paragraphType]);
        $paragraph->isNew();
      }
      // Pass through the fields and update information.
      foreach ($subEntityDataConfig as $column => $configField) {
        // The system column ID is not imported.
        if ($configField['subFieldName'] == 'id') {
          continue;
        }
        $configField['importValue'] = $item[$column] ?? '';
        $paragraph->basketIMEXupdateField = $configField['subFieldName'];
        $setValues = $this->setValues($configField, $subEntityDataConfig, $item, $paragraph);
        if (empty($setValues['notUpdate'])) {
          if (empty($setValues)) {
            $setValues = [];
          }
          if (str_contains($configField['subFieldName'], ':')) {
            [$configField['fieldName'], $fieldSubNameSecond] = explode(':', $configField['subFieldName']);
          }
          if (isset($paragraph->{$configField['fieldName']})) {
            $paragraph->get($configField['fieldName'])->setValue($setValues);
          }
          if (isset($paragraph->{$configField['subFieldName']})) {
            $paragraph->get($configField['subFieldName'])->setValue($setValues);
          }
        }
      }
      $paragraph->save();

      $newEntityValue[] = [
        'target_id' => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ];
    }

    $entity->get($fieldName)->setValue($newEntityValue);
  }

  /**
   * {@inheritdoc}
   */
  private function getNodeSubFields($entity, $fieldName) {
    if (empty($entity->id())) {
      return [];
    }
    if (!empty($entity->{$fieldName})) {
      return $entity->{$fieldName}->referencedEntities();
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  private function setValues($activeConfigField, $configFields, $importRows, $entity) {
    $fieldName = $activeConfigField['fieldName'];
    if (str_contains($activeConfigField['fieldName'], '->')) {
      [$parentFieldName, $fieldName] = explode('->', $activeConfigField['fieldName']);
    }

    if (!empty($activeConfigField['notUpdate'])) {
      $getVal = $this->imexField->getValues($activeConfigField['fieldPlugin'], $entity, $fieldName);
      if (!empty($getVal)) {
        return [
          'notUpdate' => TRUE,
        ];
      }
    }

    $setValues = [];
    $setCombiValue = FALSE;
    if (str_contains($fieldName, ':')) {
      $fields = basket_imex_get_fields(self::$activeNodeType);
      if (!empty($fields[$activeConfigField['fieldName']]) && is_object($fields[$activeConfigField['fieldName']])) {
        switch ($fields[$activeConfigField['fieldName']]->getType()) {
          case'basket_price_field':
            [$fieldNameFirst, $fieldNameSecond] = explode(':', $fieldName);
            $allFields = $this->getAllFields($configFields, $fieldNameFirst, $importRows);
            $setValues = $entity->get($fieldNameFirst)->getValue();
            if (!empty($allFields)) {
              foreach ($allFields as $setKey => $setRow) {
                $setValues[0][$setKey] = $this->imexField->setValues($setRow['plugin'], $entity, $setRow['value']);
              }
            }
            $setCombiValue = TRUE;
            break;

          case'vocabilary_terms_field':
          case 'basket_filter_type':
            [$fieldNameFirst, $fieldNameVoc] = explode(':', $fieldName);
            $entity->basketIMEXgetVocName = $fieldNameVoc;
            $entity->basketIMEXupdateField = $fieldNameFirst;
            $setValues = $this->imexField->setValues($activeConfigField['fieldPlugin'], $entity, $activeConfigField['importValue']);
            $setCombiValue = TRUE;
            break;
        }
      }
    }
    if (empty($setCombiValue)) {
      $setValues = $this->imexField->setValues($activeConfigField['fieldPlugin'], $entity, $activeConfigField['importValue']);
    }
    if (empty($setValues)) {
      $setValues = [];
    }
    return $setValues;
  }

  /**
   * {@inheritdoc}
   */
  private function getAllFields($configFields, $fieldSubName, $importRows) {
    $fields = [];
    foreach ($configFields as $column => $configField) {
      if (empty($configField['fieldName'])) {
        continue;
      }
      if (empty($configField['fieldPlugin'])) {
        continue;
      }
      if (str_contains($configField['fieldName'], $fieldSubName . ':')) {
        [$first, $second] = explode($fieldSubName . ':', $configField['fieldName']);
        $fields[$second] = [
          'value' => !empty($importRows[$column]) ? $importRows[$column] : '',
          'plugin' => $configField['fieldPlugin'],
        ];
      }
    }
    if (empty($fields['currency'])) {
      $currencyID = $this->basket->currency()->getCurrent(TRUE);
      if (!empty($currencyID)) {
        $currency = $this->basket->currency()->load($currencyID);
        if (!empty($currency->iso)) {
          $fields['currency'] = [
            'value' => $currency->iso,
            'plugin' => 'basket_currency',
          ];
        }
      }
    }
    return $fields;
  }

  /**
   * Export processing.
   */
  public function formProcessExport($info, &$context) {
    $this->processExport($info, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function entityExportProcess(&$info, $entity) {

    // Is delete.
    if (\Drupal::database()->schema()->tableExists('basket_node_delete')) {
      $isDelete = \Drupal::database()->select('basket_node_delete', 'n')
        ->fields('n')
        ->condition('nid', $entity->id())
        ->execute()->fetchField();
      if (!empty($isDelete)) {
        return FALSE;
      }
    }

    $configFields = \Drupal::configFactory()->getEditable('basket_imex.' . $info['pluginID'])->get($info['nodeType'] . '.fields');
    if (empty($configFields)) {
      return NULL;
    }
    $key = 0;
    foreach ($configFields as $keyField => $fieldInfo) {
      $info['fields'][$key][$keyField] = '';
      if (empty($fieldInfo['fieldPlugin'])) {
        continue;
      }
      if (empty($fieldInfo['fieldName'])) {
        continue;
      }

      $entity->imexSystem = $info['pluginID'];
      $entity->imexFieldColumnKeY = $keyField;

      // Sub fields.
      if (str_contains($fieldInfo['fieldName'], '->')) {
        $subEntityList = [];
        [$fieldName, $fieldSubName] = explode('->', $fieldInfo['fieldName']);
        foreach ($this->getNodeSubFields($entity, $fieldName) as $subEntity) {
          if (empty($subEntity)) {
            continue;
          }
          $subEntityList[$subEntity->id()] = $subEntity;
        }
        $k = 0;
        foreach ($subEntityList as $subEntity) {
          $getBasketSubField = FALSE;
          if (str_contains($fieldSubName, ':')) {
            $fields = basket_imex_get_fields($entity->bundle());
            if (!empty($fields[$fieldInfo['fieldName']]) && is_object($fields[$fieldInfo['fieldName']])) {
              if ($fields[$fieldInfo['fieldName']]->getType() == 'basket_price_field') {
                [$fieldNameFirst, $fieldNameSecond] = explode(':',
                  $fieldSubName);
                $subEntity->basketIMEXgetSubField = $fieldNameSecond;
                $info['fields'][$k][$keyField] = $this->imexField->getValues($fieldInfo['fieldPlugin'], $subEntity, $fieldNameFirst);
                $getBasketSubField = TRUE;
              }
            }
          }
          if (!$getBasketSubField) {
            $info['fields'][$k][$keyField] = $this->imexField->getValues($fieldInfo['fieldPlugin'], $subEntity, $fieldSubName);
          }
          $k++;
        }
      }
      else {
        $getReplaceField = FALSE;
        if (str_contains($fieldInfo['fieldName'], ':')) {
          $fields = basket_imex_get_fields($entity->bundle());
          if (!empty($fields[$fieldInfo['fieldName']]) && is_object($fields[$fieldInfo['fieldName']])) {
            switch ($fields[$fieldInfo['fieldName']]->getType()) {
              case'basket_price_field':
                [$fieldNameFirst, $fieldNameSecond] = explode(':', $fieldInfo['fieldName']);
                $entity->basketIMEXgetSubField = $fieldNameSecond;
                $info['fields'][$key][$keyField] = $this->imexField->getValues($fieldInfo['fieldPlugin'], $entity, $fieldNameFirst);
                $getReplaceField = TRUE;
                break;

              case'vocabilary_terms_field':
              case 'basket_filter_type':
                [$fieldNameFirst, $vocName] = explode(':', $fieldInfo['fieldName']);
                $entity->basketIMEXgetVocName = $vocName;
                $info['fields'][$key][$keyField] = $this->imexField->getValues($fieldInfo['fieldPlugin'], $entity, $fieldNameFirst);
                $getReplaceField = TRUE;
                break;
            }
          }
        }
        if (!$getReplaceField) {
          $info['fields'][$key][$keyField] = $this->imexField->getValues($fieldInfo['fieldPlugin'], $entity, $fieldInfo['fieldName']);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  private function getColumnId($column) {
    return str_replace(':', '_', $column);
  }

  /**
   * Get plugin info.
   */
  public function getPluginInfo() {
    return self::$plugin;
  }

}
