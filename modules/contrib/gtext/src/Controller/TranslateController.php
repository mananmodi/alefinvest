<?php

namespace Drupal\gtext\Controller;

use Drupal\gtext\Form\TranslateForm;
use Drupal\gtext\Form\TranslateFilterForm;
use Drupal\gtext\Form\TranslateStringForm;
use Drupal\locale\SourceString;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a TranslateController class.
 */
class TranslateController {

  /**
   * Defines a class to store localized strings in the database.
   *
   * @var \Drupal\locale\StringDatabaseStorage
   */
  protected $localeStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->localeStorage = \Drupal::service('locale.storage');
  }

  /**
   * {@inheritdoc}
   */
  public function translatePage() {
    $filter_form    = \Drupal::formBuilder()->getForm(TranslateFilterForm::class);
    $translate_form = \Drupal::formBuilder()->getForm(TranslateForm::class);
    return [
      'filter'    => $filter_form,
      'translate' => $translate_form,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function translateStringPage($lid) {
    if (\Drupal::request()->query->get('action', 'form') == 'close') {
      unset($_SESSION['gtext'][$lid]);
      return [
        '#type'  => 'html_tag',
        '#tag'   => 'script',
        '#value' => 'opener.reloadTranslations(' . $lid . '); window.close();',
      ];
    }
    $string = $this->localeStorage->findTranslation(['lid' => $lid]);
    if (empty($string)) {
      throw new NotFoundHttpException();
    }
    return \Drupal::formBuilder()->getForm(TranslateStringForm::class, $string);
  }

  /**
   * {@inheritdoc}
   */
  public function translateStringTitle($lid) {
    $translationOptions = ['lid' => $lid, 'language' => 'en'];
    $string = $this->localeStorage->findTranslation($translationOptions);
    if (empty($string)) {
      throw new NotFoundHttpException();
    }
    $source = new SourceString($string);
    $source_array = $source->getPlurals();
    return gtext()->gtext(
          "Translating \"%string\"", [
            '%string' => $source_array[0],
          ]
      );
  }

  /**
   * {@inheritdoc}
   */
  public function translateStringUpdate($lid) {
    $result = [];

    foreach (\Drupal::languageManager()->getLanguages() as $langcode => $language) {
      if (!\locale_is_translatable($langcode)) {
        continue;
      }

      $string = $this->localeStorage->getTranslations(
            [
              'lid'        => $lid,
              'language'   => $langcode,
              'translated' => TRUE,
            ]
        );

      $source_array = [];
      if ($string) {
        $string = reset($string);
        $source_array = $string->getPlurals();
      }
      if (empty($source_array)) {
        for ($i = 0; $i <= 2; $i++) {
          $result['strings[' . $lid . '][' . $langcode . '][' . $i . ']'] = '';
        }
      }
      else {
        foreach ($source_array as $i => $str) {
          $result['strings[' . $lid . '][' . $langcode . '][' . $i . ']'] = $str;
        }
      }
    }
    return new JsonResponse($result);
  }

}
