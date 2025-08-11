<?php

namespace Drupal\basket_excel\Plugin\IMEX;

use Drupal\basket_imex\Plugins\IMEX\BasketIMEXBaseForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Drupal\Core\Url;

/**
 * Class of BasketExcel.
 *
 * @BasketIMEX(
 *   id = "excel",
 *   name = "Excel",
 * )
 */
class BasketExcel extends BasketIMEXBaseForm {

  const EXCEL_FOLDER = 'public://basket_imex/excel/';
  const EXCEL_FOLDER_MULTI = 'public://basket_imex/excel_[id]/';

  /**
   * Whether the field matching service is available to the plugin.
   */
  public function getImportExportSettings() {
    return TRUE;
  }

  /**
   * List of fields that will participate in the settings.
   */
  public function getSettingsFields() {
    $columns = [];
    $letter = 'A';
    foreach (range(1, 150) as $num) {
      $columns[] = $letter;
      $letter++;
    }
    return $columns;
  }

  /**
   * Entity search.
   *
   * @param array $updateInfo
   *   Update data.
   */
  public function getEntity(array $updateInfo) {
    $configFields = \Drupal::configFactory()->getEditable('basket_imex.' . $updateInfo['pluginID'])->get($updateInfo['nodeType']);
    if (!empty($configFields['search_field']) && !empty($searchField = $configFields['fields'][$configFields['search_field']]['fieldName'])) {

      $searchFieldValue = trim($updateInfo['fields'][0][$configFields['search_field']] ?? '');

      $query = \Drupal::entityQuery('node');
      $query->condition('type', $updateInfo['nodeType']);

      $fields = basket_imex_get_fields($updateInfo['nodeType']);
      if (!empty($fields[$searchField])) {
        if (is_object($fields[$searchField])) {
          switch ($fields[$searchField]->getType()) {
            case'entity_reference':
              $query->condition($searchField . '.target_id', $searchFieldValue);
              break;

            default:
              $query->condition($searchField . '.value', $searchFieldValue);
              break;
          }
        }
      }

      $nids = $query->accessCheck(TRUE)->execute();
      if (!empty($nids)) {
        return \Drupal::entityTypeManager()->getStorage('node')->load(reset($nids));
      }
    }
    return NULL;
  }

  /**
   * Call after updating / creating entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   * @param array $updateInfo
   *   Update data.
   */
  public function postSave(EntityInterface $entity, array $updateInfo) {}

  /**
   * Import fields.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormState object.
   */
  public function getImportFormFields(array &$form, FormStateInterface $form_state) {
    $multi = $form_state->get('imex_multi');
    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => t('File'),
      '#upload_location' => $multi['excel_folder'] ?? $this::EXCEL_FOLDER,
      '#required' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['xlsx'],
      ],
      '#default_value' => [],
      '#description' => t('Allowed types: @extensions.', ['@extensions' => 'xlsx']),
      '#imex_excel_file' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function managedFileProcess(&$element, FormStateInterface $form_state, &$complete_form) {
    if (!empty($element['#imex_excel_file']) && !empty($element['#files'])) {
      $file = reset($element['#files']);

      if (!empty($file)) {
        $tabs = [];
        $filePath = \Drupal::service('file_system')->realpath($file->getFileUri());
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(TRUE);
        $spreadsheet = $reader->load($filePath);
        foreach ($spreadsheet->getAllSheets() as $sheet) {
          if ($sheet->getSheetState() == 'visible') {
            $tabs[$sheet->getTitle()] = $sheet->getTitle();
          }
        }
        if (!empty($tabs)) {
          $element['imex_tab'] = [
            '#type' => 'select',
            '#title' => t('File tab', [], ['context' => 'basket_imex']),
            '#options' => $tabs,
            '#parents' => ['imex_tab'],
            '#required' => TRUE,
            '#weight' => 100,
          ];
        }
      }
    }
    return $element;
  }

  /**
   * Get the list to run for import.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormState object.
   */
  public function getImportRows(FormStateInterface $form_state) {
    $items = [];
    $systemKey = str_replace(':', '_', self::$plugin['id']);

    $config = \Drupal::config('basket_imex.' . $systemKey)->get($form_state->getValue('node_type') . '.fields');
    $files  = $form_state->getValue('file');

    $emptyRow = [];
    $titleLeter = NULL;
    foreach ($config as $letter => $info) {
      if ($info['fieldName'] == 'title') {
        $titleLeter = $letter;
      }

      // There are many columns in the settings, but
      // not all of them can be filled.
      $info = array_filter($info);
      if (!empty($info)) {
        $emptyRow[$letter] = '';
      }
    }
    if (!empty($files[0])) {
      $file = \Drupal::entityTypeManager()->getStorage('file')->load($files[0]);
      if (!empty($file)) {
        $filePath = \Drupal::service('file_system')->realpath($file->getFileUri());
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(TRUE);
        $spreadsheet = $reader->load($filePath);

        if (!empty($imexTab = $form_state->getValue('imex_tab'))) {
          $worksheet = $spreadsheet->setActiveSheetIndexByName($imexTab);
        }
        else {
          $worksheet = $spreadsheet->getActiveSheet();
        }

        $nodeRows = [];
        foreach ($worksheet->getRowIterator() as $rowNumner => $row) {

          // Header (column names)
          if ($rowNumner == 1) {
            continue;
          }
          $cells = [];
          try {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(TRUE);
            foreach ($cellIterator as $letter => $cell) {
              $cells[$letter] = $cell->isFormula() ? $cell->getCalculatedValue() : $cell->getValue();
            }
          }
          catch (\Exception $e) {

          }

          // Processing: removing spaces in values, clearing data, filling
          // in non-existent indexes (to avoid errors "non-existent index").
          $cells = array_map(function ($value) {
            return $value ?? '';
          }, $cells);
          $cells = array_filter($cells);

          // If the entire string is empty.
          if (empty($cells)) {
            continue;
          }

          $cells += $emptyRow;
          // The first line of a new product if there is a product title?
          if (!empty($titleLeter) && !empty($cells[$titleLeter])) {

            // Transfer information about the previous node.
            if (!empty($nodeRows)) {
              $items[] = [
                'values' => $nodeRows,
              ];
              $nodeRows = [];
            }
          }
          elseif (is_null($titleLeter)) {
            $items[] = [
              'values' => [$cells],
            ];
          }
          $nodeRows[] = $cells;
          // Forming an array for the node.
        }
        // Throwing the last set of lines (when processing the last node in
        // the file, the condition in the loop does not work).
        if (!empty($nodeRows)) {
          $items[] = [
            'values' => $nodeRows,
          ];
          $nodeRows = [];
        }

      }
    }

    // Clear files:
    $this->deleteFiles($form_state);

    return $items;
  }

  /**
   * Import Handler Callback.
   *
   * @param array $info
   *   Info array.
   * @param array|null $context
   *   Context array.
   */
  public function processImport(array $info, ?array &$context) {
    if (!empty($info['line']['values'])) {
      $info['fields'] = $info['line']['values'];
      $info['pluginID'] = str_replace(':', '_', $info['pluginID']);
      parent::entityProcess($info);
    }
  }

  /**
   * Export fields.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormState object.
   */
  public function getExportFormFields(array &$form, FormStateInterface $form_state) {
    $form['node_status'] = [
      '#type' => 'select',
      '#title' => t('Status'),
      '#options' => [
        0 => t('unpublished'),
        1 => t('published'),
      ],
      '#empty_option' => t('all'),
    ];
  }

  /**
   * Getting the list to run for export.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormState object.
   */
  public function getExportRows(FormStateInterface $form_state) {

    $systemKey = str_replace(':', '_', self::$plugin['id']);

    $form_state->set('redirectBath', Url::fromRoute('basket.admin.pages', [
      'page_type' => 'stock-imex',
    ], [
      'query' => [
        'system' => self::$plugin['id'],
        'download' => $form_state->getValue('node_type'),
      ],
    ])->toString());

    // Clear Data:
    \Drupal::database()->delete('basket_excel_items')
      ->condition('system_key', $systemKey)
      ->execute();

    $items = [];
    $node_status = $form_state->getValue('node_status') ?? NULL;

    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->fields('n', ['nid']);
    $query->condition('n.default_langcode', 1);
    if (isset($node_status) && is_numeric($node_status)) {
      $query->condition('n.status', $node_status);
    }
    $query->condition('n.type', $form_state->getValue('node_type'));
    if (!empty($categoryField = $form_state->get('categoryField'))) {
      if (\Drupal::database()->schema()->tableExists('node__' . $categoryField)) {
        $tid = NULL;
        foreach ($form_state->getValue('category') as $valTid) {
          if (empty($valTid)) {
            continue;
          }
          $tid = $valTid;
        }
        if ($tid == '_none') {
          $query->leftJoin('node__' . $categoryField, 'cat', 'cat.entity_id = n.nid');
          $query->isNull('cat.' . $categoryField . '_target_id');
        }
        elseif (!empty($tid)) {
          $query->innerJoin('node__' . $categoryField, 'cat', 'cat.entity_id = n.nid');
          $query->condition('cat.' . $categoryField . '_target_id', $tid);
        }
      }
    }
    $query->groupBy('n.nid');
    $nids = $query->execute()->fetchCol();

    // Alter:
    \Drupal::moduleHandler()->alter('basket_excel_get_export_rows', $nids, $form_state, $systemKey);

    if (!empty($nids)) {
      foreach ($nids as $nid) {
        $items[] = [
          'type' => 'node',
          'nid' => $nid,
          'systemKey' => $systemKey,
        ];
      }
    }
    return $items;
  }

  /**
   * Export Handler Callback.
   *
   * @param array $info
   *   Info array.
   * @param array|null $context
   *   Context array.
   */
  public function processExport(array $info, ?array &$context) {
    if ($info['line']['type'] == 'node') {
      $entity = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($info['line']['nid']);

      // Is translate entity:
      $trans = $entity->getTranslationLanguages();
      if (!empty($trans[$info['langcode']])) {
        $entity = $entity->getTranslation($info['langcode']);
      }
      $info['pluginID'] = $info['line']['systemKey'];

      // Get fields:
      parent::entityExportProcess($info, $entity);
      if (!empty($info['fields'])) {

        \Drupal::database()->insert('basket_excel_items')
          ->fields([
            'data' => serialize($info['fields']),
            'system_key' => $info['line']['systemKey'],
          ])
          ->execute();
      }
    }
  }

  /**
   * Clear files.
   */
  public function deleteFiles($form_state) {

    $multi = $form_state->get('imex_multi');
    $folder = $multi['excel_folder'] ?? $this::EXCEL_FOLDER;

    $fids = \Drupal::database()->select('file_managed', 'f')
      ->fields('f', ['fid'])
      ->condition('f.uri', $folder . '%', 'LIKE')
      ->condition('f.created', strtotime('-1 month'), '<=')
      ->execute()->fetchCol();
    if (!empty($fids)) {
      foreach (\Drupal::entityTypeManager()->getStorage('file')->loadMultiple($fids) as $file) {
        $file->delete();
      }
    }
  }

  /**
   * IMEX Multisystem.
   *
   * Setting plugin.
   */
  public function imexMultiSettings(&$form, $form_state, $parents) {}

  /**
   * Save file name.
   */
  public function imexMultiFileName($fileName) {}

  /**
   * Multi Query Alter.
   */
  public function imexMultiQueryAlter(&$query, $entity) {}

  /**
   * Multi Query Alter.
   */
  public function imexMultiGetData($nid, $entityConfig) {
    return [];
  }

  /**
   * Save.
   */
  public function imexMultiSave($filePath, $items, $entity) {}

  /**
   * Multi Configuration.
   */
  public function multiConfig($entity) {
    return [
      'excel_folder' => str_replace('[id]', $entity->id(), $this::EXCEL_FOLDER_MULTI),
    ];
  }

}
