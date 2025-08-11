<?php

namespace Drupal\basket_imex;

use Drupal\Core\File\FileSystemInterface;

/**
 * Trait Basket IMEX functions.
 */
trait BasketIMEXTrait {

  /**
   * Options.
   *
   * @var array|null
   */
  protected static $getOptions;

  /**
   * Taxonomy Term.
   */
  public function getTermId($termName, $vocName, $parentTid, $langcode = NULL) {
    $tids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vocName)
      ->condition('name', trim($termName))
      ->condition('parent', $parentTid)
      ->accessCheck()
      ->execute();
    if (!empty($tids)) {
      return reset($tids);
    }
    else {
      $options = [
        'name' => trim($termName),
        'vid' => $vocName,
        'parent' => $parentTid,
      ];
      if ($langcode) {
        $options['langcode'] = $langcode;
      }
      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create($options);
      $term->save();
      return $term->id();
    }
  }

  /**
   * File.
   *
   * @param string $fileUrl
   *   File link.
   * @param object|null $entity
   *   Material object.
   * @param string $dir
   *   The directory where the files are located.
   */
  public function importFileByUri(string $fileUrl = '', $entity = NULL, string $dir = 'public://') {
    if (!$fileUrl) {
      return NULL;
    }
    $url = parse_url($fileUrl);
    $ext = pathinfo($url['path'], PATHINFO_EXTENSION);
    if (empty($ext) && version_compare(PHP_VERSION, '7.2.0', '>=')) {
      $finfo = new \finfo(FILEINFO_EXTENSION);
      $fileContent = @file_get_contents($fileUrl);
      $exts = $finfo->buffer($fileContent);
      if (!empty($exts)) {
        $exts = explode('/', $exts);
        $ext = $exts[0];
      }
    }
    if (!empty($url['path']) && empty($ext)) {
      return NULL;
    }
    if ($ext == '???') {
      \Drupal::logger('basket_imex')->warning('The file "@path" has an unknown extension. File processing aborted.', ['@path' => $fileUrl]);
      return NULL;
    }
    if (!empty($url['host'])) {
      $url['host'] = ltrim($url['host'], 'www.');
      if ($url['host'] == self::getCurrentHost() && !empty($url['path'])) {
        $url['path'] = str_replace('/sites/default/files/', 'public://', $url['path']);

        $fids = \Drupal::entityQuery('file')
          ->condition('uri', urldecode($url['path']))
          ->accessCheck()
          ->execute();

        if (!empty($fids)) {
          return reset($fids);
        }
      }
    }

    $arrContextOptions = [
      'ssl' => [
        'verify_peer' => FALSE,
        'verify_peer_name' => FALSE,
      ],
      'http' => [
        'timeout' => 5,
      ],
    ];

    $fileContent = !empty($fileContent) ? $fileContent : @file_get_contents($fileUrl, 0, stream_context_create($arrContextOptions));
    if (!empty($fileContent) && !empty($url['path'])) {
      if (!empty($entity->basketIMEXupdateField)) {
        $fields = basket_imex_get_fields($entity->bundle());
        if (!empty($fields[$entity->basketIMEXupdateField])) {
          $fieldSettings = $fields[$entity->basketIMEXupdateField]->getSettings();
          if (!empty($fieldSettings['file_directory'])) {
            $dir = 'public://' . $fieldSettings['file_directory'] . '/';
          }
        }
      }
      if ($dir !== 'public://') {
        \Drupal::getContainer()->get('file_system')
          ->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
      }
      $uri = $dir . md5($fileContent) . '.' . $ext;
      $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $uri]);
      $getFile = NULL;

      if (!empty($files)) {
        $getFile = reset($files);
        if (!file_exists($getFile->getFileUri())) {
          $getFile = NULL;
        }
      }
      if (empty($getFile)) {
        $getFile = \Drupal::getContainer()->get('file.repository')
          ->writeData($fileContent, $uri);
      }
      if (!empty($getFile)) {
        return $getFile->id();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  private static function getCurrentHost() {
    return ltrim($_SERVER['HTTP_HOST'], 'www.');
  }

  /**
   * Category.
   */
  public function categoryElements(&$form, $voc, $form_state, $parent, $ajax) {
    $options = $this->getOptions($voc, $parent);
    if (!empty($options)) {
      if ($parent === 0) {
        $options = ['_none' => t("Uncategorized")] + $options;
      }
      if (empty($form['#title'])) {
        $form['#title'] = t('Category', [], ['context' => 'basket_imex']);
      }
      $form[$parent] = [
        '#type' => 'select',
        '#options' => $options,
        '#empty_option' => '',
        '#ajax' => $ajax,
      ];
      if (!empty($nextParent = $form_state->getValue(['category', $parent]))) {
        if (!empty($form[$parent]['#options'][$nextParent]) && $nextParent !== '_none') {
          $this->categoryElements($form, $voc, $form_state, $nextParent, $ajax);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions($voc, $parent) {
    if (!isset(self::$getOptions[$voc][$parent])) {
      $query = \Drupal::database()->select('taxonomy_term_field_data', 't');
      $query->fields('t', ['tid', 'name']);
      $query->condition('t.vid', $voc);
      $query->orderBy('t.weight');
      $query->orderBy('t.name');

      // taxonomy_term__parent:
      $query->innerJoin('taxonomy_term__parent', 'p', 'p.entity_id = t.tid');
      $query->condition('p.parent_target_id', $parent);

      self::$getOptions[$voc][$parent] = $query->execute()->fetchAllKeyed();
    }
    return self::$getOptions[$voc][$parent];
  }

}
