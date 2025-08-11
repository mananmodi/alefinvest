<?php

namespace Drupal\basket_imex\Plugin\IMEX\field;

use Drupal\basket_imex\Plugins\IMEXfield\BasketIMEXfieldInterface;
use Drupal\Component\Utility\Html;
use Drupal\basket_imex\BasketIMEXTrait;

/**
 * LongTextField IMEX type.
 *
 * @BasketIMEXfield(
 *   id = "big_text",
 *   type = {"text_with_summary","text_long"},
 *   name = "Big text",
 *   type_info = "(string)<br/>Text with HTML",
 * )
 */
class LongTextField implements BasketIMEXfieldInterface {

  use BasketIMEXTrait;

  /**
   * Getting data for export.
   *
   * @param object $entity
   *   Entity that has been updated.
   * @param string $fieldName
   *   Field that has been updated.
   */
  public function getValues($entity, $fieldName) {
    $values = [];
    if (!empty($entity->{$fieldName})) {
      $fieldValues = $entity->get($fieldName)->getValue();
      if (!empty($fieldValues)) {
        foreach ($fieldValues as $val) {
          if (trim($val['value']) == '') {
            continue;
          }
          $values[trim($val['value'])] = trim($val['value']);
        }
      }
    }
    return implode(PHP_EOL, $values);
  }

  /**
   * Data array formation.
   *
   * @param object $entity
   *   Entity that has been updated.
   * @param string $importValue
   *   Import value.
   */
  public function setValues($entity, $importValue = '') {
    $setValue = [
      [
        'value' => '',
        'summary' => $entity->{$entity->basketIMEXupdateField}[0]->summary ?? '',
      ],
    ];
    if (!empty($importValue)) {
      $importValues = explode(PHP_EOL, $this->replaceNl2br($importValue));
      foreach ($importValues as $key => $importValue) {
        $setValue[$key] = [
          'summary' => !empty($entity->{$entity->basketIMEXupdateField}[$key]->summary) ? $entity->{$entity->basketIMEXupdateField}[$key]->summary : '',
          'value' => trim($importValue),
          'format' => self::getFirstFormat(),
        ];
      }
    }
    return $setValue;
  }

  /**
   * Additional field processing after $entity update / creation.
   *
   * @param object $entity
   *   Entity that has been updated.
   * @param string $importValue
   *   Import value.
   */
  public function postSave($entity, $importValue = '') {}

  /**
   * {@inheritdoc}
   */
  private static function getFirstFormat() {
    $formats = \Drupal::entityTypeManager()->getStorage('filter_format')->loadByProperties(['status' => TRUE]);
    if (!empty($formats['full_html'])) {
      return 'full_html';
    }
    return !empty($formats) ? key($formats) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function replaceNl2br($text) {

    // Replace entity space.
    $text = htmlentities($text, ENT_QUOTES | ENT_SUBSTITUTE, 'utf-8');
    $text = str_replace("&nbsp;", " ", $text);
    $text = html_entity_decode($text);

    // All replace.
    $text = str_replace('
', '<br>', $text);
    $text = str_replace('<br/><br/>', '<br>', $text);
    $text = str_replace('<br><br>', '<br>', $text);
    $text = str_replace('<p> </p>', '', $text);

    // Parse images.
    $this->parseImages($text);

    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public function parseImages(&$text) {
    $html = Html::load($text);
    foreach ($html->getElementsByTagName('img') as $img) {
      $src = $img->getAttribute('src');
      $getFid = $this->importFileByUri(trim($src), NULL, 'public://text_images/');
      if (!empty($getFid)) {
        $file = \Drupal::entityTypeManager()->getStorage('file')->load($getFid);
        if (!empty($file)) {
          $img->setAttribute('src', str_replace($GLOBALS['base_url'], '', $file->createFileUrl()));
        }
      }
    }
    $text = Html::serialize($html);
  }

}
