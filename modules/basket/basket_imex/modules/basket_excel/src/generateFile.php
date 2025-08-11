<?php

namespace Drupal\basket_excel;

use Drupal\Core\Render\Markup;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class of generateFile.
 */
class generateFile {

  /**
   * Basket object.
   *
   * @var \Drupal\basket\Basket
   */
  protected $basket;

  /**
   * System key.
   *
   * @var string
   */
  protected $systemKey;

  /**
   * {@inheritdoc}
   */
  public function __construct($systemKey) {
    $this->basket = \Drupal::getContainer()->get('Basket');
    $this->systemKey = $systemKey;
  }

  /**
   * {@inheritdoc}
   */
  public function run($nodeType, $imexEntity = NULL) {
    if (!class_exists(Spreadsheet::class)) {
      print Markup::create('PhpSpreadsheet: ' . t('Not installed')->__toString() . '. <a href="https://phpspreadsheet.readthedocs.io" target="_blank">' . t('Install') . '</a>');
      exit;
    }

    // Spreadsheet:
    $spreadsheet = new Spreadsheet();
    $spreadsheet->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
    $sheet = $spreadsheet->getActiveSheet();

    // Header:
    $lineHeader = 1;
    $configFields = \Drupal::configFactory()->getEditable('basket_imex.' . $this->systemKey)->get($nodeType . '.fields');
    if (!empty($configFields)) {
      $fields = basket_imex_get_fields($nodeType);
      foreach ($configFields as $keyField => $fieldInfo) {
        if (empty($fieldInfo['fieldName'])) {
          continue;
        }

        $label = '';
        if (!empty($fields[$fieldInfo['fieldName']])) {
          $label = is_object($fields[$fieldInfo['fieldName']]) ? $fields[$fieldInfo['fieldName']]->getLabel() : $fields[$fieldInfo['fieldName']]['label'];
        }
        if (!empty($label)) {
          if (str_contains($label, '->')) {
            $labels = explode('->', $label);
            unset($labels[0]);
            $label = implode('->', $labels);
          }
          $sheet->setCellValue($keyField . '' . $lineHeader, trim($label));
        }
      }
    }

    // Fixed header:
    $sheet->freezePane('A2');

    // Rows:
    $line = $lineHeader;
    $rows = \Drupal::database()->select('basket_excel_items', 'i')
      ->fields('i', ['data'])
      ->condition('i.system_key', $this->systemKey)
      ->execute()->fetchCol();
    $valueLines = [];
    if (!empty($rows)) {
      $line++;
      foreach ($rows as $row) {
        $rowItems = unserialize($row);
        foreach ($rowItems as $values) {
          foreach ($values as $letter => $value) {
            if ($value === '') {
              continue;
            }
            $sheet->setCellValueExplicit($letter . '' . $line, $value, DataType::TYPE_STRING);
            $valueLines[$letter] = TRUE;
          }
          $line++;
        }
      }
    }

    // Colors:
    $highestColumn = $sheet->getHighestColumn();
    $spreadsheet->getActiveSheet()->getStyle('A' . $lineHeader . ':' . $highestColumn . $lineHeader)->applyFromArray([
      'font' => [
        'bold' => TRUE,
      ],
      'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'color' => [
          'rgb' => 'a4d8a4',
        ],
      ],
      'borders' => [
        'allBorders' => [
          'borderStyle' => Border::BORDER_THIN,
          'color' => ['argb' => '333333'],
        ],
      ],
    ]);
    $searchField = \Drupal::configFactory()->getEditable('basket_imex.' . $this->systemKey)->get($nodeType . '.search_field');
    if (!empty($searchField)) {
      $spreadsheet->getActiveSheet()->getStyle($searchField . ($lineHeader + 1) . ':' . $searchField . ($line - 1))->applyFromArray([
        'fill' => [
          'fillType' => Fill::FILL_SOLID,
          'color' => [
            'rgb' => 'efd5d5',
          ],
        ],
      ]);
    }

    // Hide empty columns:
    foreach ($configFields as $keyField => $fieldInfo) {
      if (empty($fieldInfo['fieldName'])) {
        continue;
      }
      if (empty($valueLines[$keyField])) {
        $spreadsheet->getActiveSheet()->getColumnDimension($keyField)->setOutlineLevel(1);
        $spreadsheet->getActiveSheet()->getColumnDimension($keyField)->setVisible(FALSE);
        $spreadsheet->getActiveSheet()->getColumnDimension($keyField)->setCollapsed(TRUE);
      }
    }
    $entityName = !empty($imexEntity) ? $imexEntity->label() : '';

    $filename = 'Export ' . $entityName . '(' . date('d.m.Y H:i') . ').xlsx';

    $file_path = \Drupal::service('file_system')->realpath(\Drupal::service('file_system')->getTempDirectory()) . '/' . $filename;

    // Save:
    $writer = new Xlsx($spreadsheet);
    $writer->setPreCalculateFormulas(FALSE);
    $writer->save($file_path);

    // Download:
    $response = new BinaryFileResponse($file_path, 200, [], FALSE, 'attachment');
    $response->deleteFileAfterSend(TRUE);
    $response->send();
  }

  /**
   * {@inheritdoc}
   */
  public function getColumnInfos() {
    $rows = [];

    $configs = \Drupal::configFactory()->getEditable('basket_imex.' . $this->systemKey)->get();
    if (!empty($configs)) {
      $nodeTypes = $this->basket->getNodeTypes();
      foreach ($configs as $nodeType => $config) {
        if (empty($nodeTypes[$nodeType]->NodeType)) {
          continue;
        }
        $rows[$nodeType]['name'] = $nodeTypes[$nodeType]->NodeType->label();
        $fields = basket_imex_get_fields($nodeType);
        foreach ($config['fields'] as $keyField => $fieldInfo) {
          if (empty($fieldInfo['fieldName'])) {
            continue;
          }
          $label = '';
          if (!empty($fields[$fieldInfo['fieldName']])) {
            $label = is_object($fields[$fieldInfo['fieldName']]) ? $fields[$fieldInfo['fieldName']]->getLabel() : $fields[$fieldInfo['fieldName']]['label'];
          }
          if (!empty($label)) {
            if (str_contains($label, '->')) {
              $labels = explode('->', $label);
              unset($labels[0]);
              $label = implode('->', $labels);
            }
            $rows[$nodeType]['columns'][$keyField]['label'] = trim($label);

            $descriptions = [];
            if (!empty($config['search_field']) && $keyField == $config['search_field']) {
              $descriptions[] = $this->basket->translate('basket_excel')->t('* The key field!');
            }
            if (!empty($fieldInfo['fieldPlugin'])) {
              $descriptions[] = \Drupal::service('BasketIMEXfield')->getFieldInfo($fieldInfo['fieldPlugin']);
            }
            $rows[$nodeType]['columns'][$keyField]['description'] = implode('<br/>', $descriptions);
          }
        }
      }
    }
    return $rows;
  }

}
