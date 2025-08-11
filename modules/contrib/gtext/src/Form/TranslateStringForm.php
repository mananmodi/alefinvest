<?php

namespace Drupal\gtext\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Gettext\PoItem;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\locale\SourceString;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\locale\StringDatabaseStorage;

/**
 * Provides a TranslateStringForm class for forms.
 */
class TranslateStringForm extends FormBase {
  use StringTranslationTrait;

  /**
   * Returns the language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Defines a class to store localized strings in the database.
   *
   * @var \Drupal\locale\StringDatabaseStorage
   */
  protected $localeStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(LanguageManagerInterface $languageManager, StringDatabaseStorage $localeStorage) {
    $this->languageManager = $languageManager;
    $this->localeStorage = $localeStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('locale.storage'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_translate_string_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [
      '#id'       => 'form__translate_string_form',
      '#attached' => [
        'library'   => ['gtext/gtext.string'],
      ],
    ];

    $lid = $form_state->getBuildInfo()['args'][0]->getId();
    if ($form_state->getValue(['lid'])) {
      $lid = $form_state->getValue(['lid']);
    }
    if (!empty($_SESSION['gtext'][$lid])) {
      $form['listening'] = [
        '#type'  => 'html_tag',
        '#tag'   => 'script',
        '#value' => 'if ( opener ) opener.reloadTranslations(' . $lid . ');',
      ];
      unset($_SESSION['gtext'][$lid]);
    }
    $form['lid'] = [
      '#type'     => 'hidden',
      '#value'    => $lid,
    ];

    $originalSource = new SourceString($form_state->getBuildInfo()['args'][0]);
    $originalString = $originalSource->getPlurals();

    $form['string'] = [
      '#tree'  => TRUE,
    ];
    foreach ($this->languageManager->getLanguages() as $langcode => $language) {
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

      if (count($originalString) == 1) {
        $form['string'][$langcode] = [
          '#type'          => 'textarea',
          '#title'         => $language->getName(),
          '#default_value' => $source_array[0] ?? '',
          '#weight'        => $language->getWeight(),
          '#attributes'    => [
            'class'          => ['gtext-translatable-field'],
            'id'             => 'translation--' . $langcode,
            'data-lang'      => $langcode,
            'data-text'      => $originalString[0],
            'data-url'       => Url::fromRoute('gtext.translate.google')->toString(),
          ],
        ];
      }
      else {
        $pluralCount = (int) $this->getNumberOfPlurals($langcode);
        $form['string'][$langcode] = [
          '#type'          => 'details',
          '#open'          => TRUE,
          '#title'         => $language->getName(),
          '#weight'        => $language->getWeight(),
          '#attributes'    => [
            'data-lang'         => $langcode,
            'data-plural-count' => $pluralCount,
          ],
        ];
        for ($i = 0; $i < $pluralCount; $i++) {
          $form['string'][$langcode][$i] = [
            '#title'            => ($i == 0 ? $this->t('Singular form') : $this->formatPlural($i, 'First plural form', '@count. plural form')),
            '#type'             => 'textarea',
            '#rows'             => 2,
            '#default_value'    => $source_array[$i] ?? '',
            '#parents'          => ['string', $langcode, $i],
            '#weight'        => $i * 2 + 1,
            '#attributes'    => [
              'id'             => 'translation--' . $langcode . '--' . $i,
              'data-lang'      => $langcode,
              'data-text'      => $originalString[$i == 0 ? 0 : 1],
              'data-url'       => Url::fromRoute('gtext.translate.google')->toString(),
              'class'          => ['gtext-translatable-field'],
            ],
          ];
        }
        if ($pluralCount == 2) {
          $form['string'][$langcode][1]['#title'] = $this->t('Plural form');
        }
      }
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type'        => 'submit',
      '#id'          => 'submit_form',
      '#value'       => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    $form['actions']['submit_close'] = [
      '#type'        => 'submit',
      '#id'          => 'submit_form_close',
      '#value'       => $this->t('Save and close'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    foreach ($this->languageManager->getLanguages() as $langcode => $language) {
      if (!\locale_is_translatable($langcode)) {
        continue;
      }
      $strings = $form_state->getValue(['string', $langcode]);
      if (is_array($strings)) {
        foreach ($strings as $pluralKey => $string) {
          if (!empty($string) && !\locale_string_is_safe($string)) {
            $form_state
              ->setErrorByName("string][{$langcode}][{$pluralKey}",
              $this->t('The submitted string contains disallowed HTML: %string', [
                '%string' => $string,
              ]));
            $this
              ->logger('locale')
              ->warning('Attempted submission of a translation string with disallowed HTML: %string', [
                '%string' => $string,
              ]);
          }
        }
      }
      else {
        if (!empty($strings) && !\locale_string_is_safe($strings)) {
          $form_state
            ->setErrorByName("string][{$langcode}",
            $this->t('The submitted string contains disallowed HTML: %string', [
              '%string' => $strings,
            ]));
          $this
            ->logger('locale')
            ->warning('Attempted submission of a translation string with disallowed HTML: %string', [
              '%string' => $strings,
            ]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $lid = $form_state->getValue(['lid']);
    $localStorage = $this->localeStorage;

    $strings = $form_state->getValue(['string']);
    foreach ($this->languageManager->getLanguages() as $langcode => $language) {
      if (!isset($strings[$langcode])) {
        continue;
      }
      $string = $strings[$langcode];
      if ($langcode == 'en' && !\locale_is_translatable('en')) {
        continue;
      }
      $string_delimited = is_string($string) ? $string : implode(PoItem::DELIMITER, $string);

      $translation = $localStorage->getTranslations(
        [
          'lid'        => $lid,
          'language'   => $langcode,
          'translated' => TRUE,
        ]
      );
      $translation = !empty($translation) ? reset($translation) : FALSE;

      if (!$translation || $translation->getValues(['source'])['source'] != $string_delimited) {
        // Changed -> save.
        $translationOptions = ['lid' => $lid, 'language' => $langcode];
        $createTranslation = $localStorage->createTranslation($translationOptions);
        $target = $translation !== FALSE ? $translation : $createTranslation;
        $target->setPlurals(is_string($string) ? [$string] : $string)
          ->setCustomized()
          ->save();

        $_SESSION['gtext'][$lid] = TRUE;
        _gtext_refresh_locale_translations([$langcode], [$lid]);
        _gtext_refresh_locale_configuration([$langcode], [$lid]);
      }
      if ((is_string($string) && empty($string)) || (is_array($string) && count(array_filter($string)) == 0)) {
        // Empty translations -> delete translation.
        if ($translation) {
          $_SESSION['gtext'][$lid] = TRUE;
          $translation->delete();
          _gtext_refresh_locale_translations([$langcode], [$lid]);
          _gtext_refresh_locale_configuration([$langcode], [$lid]);
        }
      }
    }
    $button = $form_state->getTriggeringElement();
    if ($button['#id'] == 'submit_form_close') {
      $form_state->setRedirect('gtext.translate.string', ['lid' => $lid], ['query' => ['action' => 'close']]);
    }
    else {
      $form_state->setRedirect('gtext.translate.string', ['lid' => $lid]);
    }
    $this->messenger()->addStatus($this->t('Saved'));
  }

  /**
   * Ajax callback.
   */
  public static function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

}
