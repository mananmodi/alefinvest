<?php

namespace Drupal\gtext\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Database\Query\Condition;
use Drupal\locale\SourceString;
use Drupal\Component\Gettext\PoItem;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\locale\StringDatabaseStorage;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Database\Connection;

/**
 * Provides a TranslateForm class.
 */
class TranslateForm extends FormBase {

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
   * Class that manages modules in a Drupal installation.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Database API class.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new TranslateForm.
   *
   * {@inheritDoc}
   */
  public function __construct(
        LanguageManagerInterface $languageManager,
        StringDatabaseStorage $localeStorage,
        ModuleHandler $moduleHandler,
        Connection $database
    ) {
    $this->languageManager = $languageManager;
    $this->localeStorage = $localeStorage;
    $this->moduleHandler = $moduleHandler;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('language_manager'),
          $container->get('locale.storage'),
          $container->get('module_handler'),
          $container->get('database')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gtext_translate';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->languageManager->reset();
    $form['#attached'] = [
      'library'           => [
        'gtext/gtext',
      ],
    ];

    $isD9 = function_exists('Drupal::translation');

    $form['#attributes']['class'][] = 'gtext-translate-form';

    $allLanguages = $this->languageManager->getLanguages();
    $languages    = [];
    $ntrans       = [];
    foreach ($allLanguages as $langcode => $language) {
      if ($langcode == 'en') {
        continue;
      }
      $languages[$langcode] = $language;
      $ntrans['_ntrans:' . $langcode] = $this->t('All not translated strings in %lang', ['%lang' => $language->getName()]);
    }

    $translate_english = \locale_is_translatable('en');

    $strings = $this->getStrings($languages, isset($_GET['string']) ? $_GET['string'] : FALSE, isset($_GET['context']) ? $_GET['context'] : '_none');
    $pluralCount = [];

    foreach ($languages as $langcode => $language) {
      $pluralCount[$langcode] = $this->getNumberOfPlurals($langcode);
      $header[$langcode]      = [
        'data'          => $language->getName(),
        'data-col-lang' => $langcode,
      ];
    }

    if (empty($strings['en'])) {
      $form['strings'] = [
        '#type'   => 'table',
        '#header' => array_merge([$this->t('Source string')], $header),
        '#rows'   => [],
        '#empty'  => $this->t('Not found strings'),
        '#sticky' => TRUE,
      ];
      return $form;
    }

    $form['strings'] = [
      '#type'   => 'table',
      '#sticky' => TRUE,
      '#header' => array_merge([$this->t('Source string')], $header),
      '#attributes' => [
        'class' => ['translate-form-table'],
      ],
    ];

    $form['strings']['#header']['save'] = [
      'class' => ['fixed-save-button-wrapper'],
      'data'  => Markup::create('<div class="fixed-save-button"></div>'),
    ];
    foreach ($strings['en'] as $string) {
      $source = new SourceString($string);
      $source_array = $source->getPlurals();

      foreach ($languages as $langcode => $language) {
        $translation_array[$langcode] = $strings[$langcode][$string->lid]->getPlurals();
      }

      if (count($source_array) == 1) {
        $plural = FALSE;
        $form['strings'][$string->lid]['original'] = [
          '#type'          => 'item',
          '#title'         => $this->t('Source string (@language)', ['@language' => $this->t('Built-in English')]),
          '#title_display' => 'invisible',
          '#plain_text'    => $source_array[0],
          '#weight'        => 0,
        ];
        if ($translate_english && !empty($string->customized)) {
          $form['strings'][$string->lid]['original']['source'] = [
            '#type'          => 'item',
            '#title'         => $this->t('Original string'),
            '#title_display' => 'invisible',
            '#plain_text'    => $form['strings'][$string->lid]['original']['#plain_text'],
            '#weight'        => 0,
            '#wrapper_attributes' => [
              'class' => ['gtext-item-strike'],
            ],
          ];
          $form['strings'][$string->lid]['original']['replaced'] = [
            '#type'          => 'item',
            '#title'         => $this->t('Replaced string'),
            '#title_display' => 'invisible',
            '#plain_text'    => $string->translation,
            '#weight'        => 1,
          ];
          unset($form['strings'][$string->lid]['original']['#plain_text']);
        }
        foreach ($languages as $langcode => $language) {
          $form['strings'][$string->lid][$langcode][0] = [
            '#type'           => 'textarea',
            '#title'          => $this->t('Translated string (@language)', ['@language' => $langcode]),
            '#title_display'  => 'invisible',
            '#rows'           => 2,
            '#cols'           => 40,
            '#default_value'  => $translation_array[$langcode][0],
            '#attributes'     => [
              'lang'      => $langcode,
              'class'     => ['gtext-translatable-field'],
              'data-lang' => $langcode,
              'data-lid'  => $string->lid,
              'data-text' => $source_array[0],
              'data-url'  => Url::fromRoute('gtext.translate.google')->toString(),
            ],
          ];
        }
      }
      else {
        $plural = TRUE;
        $original_singular = [
          '#type'       => 'item',
          '#title'      => $this->t('Singular form'),
          '#plain_text' => $source_array[0],
          '#prefix'     => '<span class="visually-hidden">' . $this->t('Source string (@language)', ['@language' => $this->t('Built-in English')]) . '</span><span lang="en">',
          '#suffix'     => '</span>',
        ];
        $original_plural = [
          '#type'       => 'item',
          '#title'      => $this->t('Plural form'),
          '#plain_text' => $source_array[1],
          '#preffix'    => '<span lang="en">',
          '#suffix'     => '</span>',
        ];
        if ($translate_english && !empty($string->customized)) {
          if ($original_singular['#plain_text'] != $string->getPlurals()[0]) {
            $original_singular['source'] = [
              '#type'          => 'item',
              '#title'         => $this->t('Original string'),
              '#title_display' => 'invisible',
              '#plain_text'    => $original_singular['#plain_text'],
              '#weight'        => 0,
              '#wrapper_attributes' => [
                'class' => ['gtext-item-strike'],
              ],
            ];
            $original_singular['replaced'] = [
              '#type'          => 'item',
              '#title'         => $this->t('Replaced string'),
              '#title_display' => 'invisible',
              '#plain_text'    => $string->getPlurals()[0],
              '#weight'        => 1,
            ];
            unset($original_singular['#plain_text']);
          }

          if ($original_plural['#plain_text'] != $string->getPlurals()[1]) {
            $original_plural['source'] = [
              '#type'          => 'item',
              '#title'         => $this->t('Original string'),
              '#title_display' => 'invisible',
              '#plain_text'    => $original_plural['#plain_text'],
              '#weight'        => 0,
              '#wrapper_attributes' => [
                'class' => ['gtext-item-strike'],
              ],
            ];
            $original_plural['replaced'] = [
              '#type'          => 'item',
              '#title'         => $this->t('Replaced string'),
              '#title_display' => 'invisible',
              '#plain_text'    => $string->getPlurals()[1],
              '#weight'        => 1,
            ];
            unset($original_plural['#plain_text']);
          }
        }
        $form['strings'][$string->lid]['original'] = [
          'original_singular' => $original_singular + ['#weight' => 0],
          'original_plural'   => $original_plural + ['#weight' => 2],
        ];
        foreach ($languages as $langcode => $language) {
          for ($i = 0; $i < $pluralCount[$langcode]; $i++) {
            $form['strings'][$string->lid][$langcode][$i] = [
              '#type'          => 'textarea',
              '#title'         => ($i == 0 ? $this->t('Singular form') : $this->t('Plural form')),
              '#rows'          => 2,
              '#cols'          => 40,
              '#default_value' => isset($translation_array[$langcode][$i]) ? $translation_array[$langcode][$i] : '',
              '#attributes'    => [
                'lang'           => $langcode,
                'class'          => ['gtext-translatable-field'],
                'data-lang'      => $langcode,
                'data-text'      => $source_array[$i == 0 ? 0 : 1],
                'data-url'       => Url::fromRoute('gtext.translate.google')->toString(),
              ],
              '#prefix'        => $i == 0 ? ('<span class="visually-hidden">' . $this->t('Translated string (@language)', ['@language' => $langcode]) . '</span>') : '',
              '#weight'        => $i * 2,
            ];
          }
          if ($pluralCount[$langcode] == 2) {
            $form['strings'][$string->lid][$langcode][1]['#title'] = $this->t('Plural form');
          }
        }
      }

      $form['strings'][$string->lid]['save'] = [];

      $form['strings'][$string->lid]['original']['delete'] = [
        '#weight'   => -10,
        '#markup'   => Link::fromTextAndUrl(
            '',
            Url::fromUserInput(
                '#delete', [
                  'attributes' => [
                    'class'    => ['gtext-remove-button'],
                    'title'    => $this->t('Delete'),
                    'data-url' => Url::fromRoute('gtext.translate.delete', ['lid' => $string->lid])->toString(),
                  ],
                ]
            ),
        )->toString(),
      ];
      $title = $this->t('Edit translations on new tab');
      $form['strings'][$string->lid]['original']['edit_form'] = [
        '#weight'  => -10,
        '#markup'  => Markup::create('<a href="javascript:void(0)" class="gtext-edit-form-link" title="' . $title . '" data-url="' . Url::fromRoute('gtext.translate.string', ['lid' => $string->lid])->toString() . '"><svg xmlns="http://www.w3.org/2000/svg" width="16px" viewBox="0 0 448 512"><path fill="#00f" d="M448 80v352c0 26.51-21.49 48-48 48H48c-26.51 0-48-21.49-48-48V80c0-26.51 21.49-48 48-48h352c26.51 0 48 21.49 48 48zm-88 16H248.029c-21.313 0-32.08 25.861-16.971 40.971l31.984 31.987L67.515 364.485c-4.686 4.686-4.686 12.284 0 16.971l31.029 31.029c4.687 4.686 12.285 4.686 16.971 0l195.526-195.526 31.988 31.991C358.058 263.977 384 253.425 384 231.979V120c0-13.255-10.745-24-24-24z"/></svg></a>'),
      ];
      if ($translate_english) {
        $form['strings'][$string->lid]['original']['edit'] = [
          '#weight'  => -9,
          '#markup' => Link::fromTextAndUrl(
              '',
              Url::fromUserInput(
                  '#replace', [
                    'attributes' => [
                      'data-lid' => $string->lid,
                      'class'    => ['gtext-edit-button'],
                      'title'    => $this->t('Replace'),
                    ],
                  ]
              )
          )->toString(),
        ];
        if ($plural) {
          for ($i = 0; $i < $pluralCount[$langcode]; $i++) {
            $form['strings'][$string->lid]['original'][$i] = [
              '#type'             => 'textarea',
              '#rows'             => 2,
              '#cols'             => 40,
              '#default_value'    => !empty($string->customized) ? $string->getPlurals()[$i] : '',
              '#parents'          => ['strings', $string->lid, 'en', $i],
              '#attributes'       => [
                'class' => ["replace_en_{$string->lid}replaced_original_text"],
                'style'    => 'display:none',
                'data-lid' => $string->lid,
              ],
              '#weight'        => $i * 2 + 1,
            ];
          }
          if ($pluralCount[$langcode] == 2) {
            $form['strings'][$string->lid][$langcode][1]['#title'] = $this->t('Plural form');
          }
        }
        else {
          $form['strings'][$string->lid]['original'][0] = [
            '#type'             => 'textarea',
            '#rows'             => 2,
            '#cols'             => 40,
            '#default_value'    => !empty($string->customized) ? $string->translation : '',
            '#parents'          => ['strings', $string->lid, 'en', 0],
            '#attributes'       => [
              'class' => ["replace_en_{$string->lid}replaced_original_text"],
              'style' => 'display:none',
              'data-lid' => $string->lid,
            ],
            '#weight'        => 1,
          ];
        }
      }
      if (!empty($string->context)) {
        $form['strings'][$string->lid]['original'][] = [
          '#type'         => 'inline_template',
          '#template'     => '<br><small class="gtext-context-wrapper">{{ context_title }}: <span lang="en">{{ context }}</span></small>',
          '#context'      => [
            'context_title' => $this->t('In Context'),
            'context'       => $string->context,
          ],
          '#weight'        => 10,
        ];
      }

      $form['strings'][$string->lid]['original']['copy'] = [
        '#type'        => 'html_tag',
        '#tag'         => 'div',
        '#weight'      => 99999,
        '#attached'    => [
          'library'      => ['gtext/gtext.copy']
        ],
        '#attributes'  => [
          'class'        => ['gtext-click-copy-wrapper'],
        ],
      ];
      $form['strings'][$string->lid]['original']['copy']['twig'] = [
        '#type'        => 'html_tag',
        '#tag'         => 'img',
        '#attributes'  => [
          'title'        => t('Click to copy code for Twig'),
          'class'        => ['gtext-click-copy'],
          'src'          => '/' . \Drupal::service('extension.list.module')->getPath('gtext') . '/misc/img/twig.svg',
        ]
      ];
      $form['strings'][$string->lid]['original']['copy']['code'] = [
        '#type'        => 'html_tag',
        '#tag'         => 'img',
        '#attributes'  => [
          'title'        => t('Click to copy code for PHP'),
          'class'        => ['gtext-click-copy'],
          'src'          => '/' . \Drupal::service('extension.list.module')->getPath('gtext') . '/misc/img/php.svg',
        ]
      ];
      $context = str_replace('\'', '\\\'', $string->context);
      if ( count($source_array) == 1 ) {
        $text = str_replace('\'', '\\\'', $source_array[0]);
        if ( !empty($string->context) ){
          $form['strings'][$string->lid]['original']['copy']['code']['#attributes']['data-copy-text'] = "t('$text', [], ['context' => '$context'])";
          $form['strings'][$string->lid]['original']['copy']['twig']['#attributes']['data-copy-text'] = "{{ '$text'|t({}, {context: '$context'}) }}";
        } else {
          $form['strings'][$string->lid]['original']['copy']['code']['#attributes']['data-copy-text'] = "t('$text')";
          $form['strings'][$string->lid]['original']['copy']['twig']['#attributes']['data-copy-text'] = "{{ '$text'|t }}";
        }
      } else {
        $textOne  = str_replace('\'', '\\\'', $source_array[0]);
        $textMore = str_replace('\'', '\\\'', $source_array[1]);
        if ( $isD9 ){
          if ( !empty($string->context) ){
            $form['strings'][$string->lid]['original']['copy']['twig']['#access'] = false;
            $form['strings'][$string->lid]['original']['copy']['code']['#attributes']['data-copy-text'] = "\Drupal::translation()->formatPlural(\$count, '$textOne', '$textMore', [], ['context' => '$context'])";
          } else {
            $form['strings'][$string->lid]['original']['copy']['code']['#attributes']['data-copy-text'] = "\Drupal::translation()->formatPlural(\$count, '$textOne', '$textMore')";
          }
        } else {
          $form['strings'][$string->lid]['original']['copy']['twig']['#attributes']['data-copy-text'] = '{% trans %}\\n\\t'.$textOne.'\\n{% plural count %}\\n\\t'.$textMore.'\\n{% endtrans %}';
          if ( !empty($string->context) ){
            $form['strings'][$string->lid]['original']['copy']['code']['#attributes']['data-copy-text'] = "format_plural(\$count, '$textOne', '$textMore', [], ['context' => '$context'])";
          } else {
            $form['strings'][$string->lid]['original']['copy']['code']['#attributes']['data-copy-text'] = "format_plural(\$count, '$textOne', '$textMore')";
          }
        }
      }
    }
    krsort($form['strings']);


    $form['actions'] = [
      '#type'         => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type'         => 'submit',
      '#value'        => $this->t('Save translations'),
      '#submit'       => ['::saveTranslate'],
      '#id'           => 'save_strings',
    ];

    $form['pager']['#type'] = 'pager';

    $links = [];
    foreach ($languages as $langcode => $language) {
      $links[] = [
        'title'     => $this->t('Export %lang', ['%lang' => $language->getName()]),
        'url'       => new Url(
            'gtext.translate.export', [
              'langcode' => $language->getId(),
              'group'    => isset($_GET['context']) ? $_GET['context'] : '_all',
            ]
        ),
      ];
    }
    $form['actions']['export'] = [
      '#type'         => 'dropbutton',
      '#links'        => $links,
      '#prefix'       => Markup::create('<div style="float:right;">'),
      '#suffix'       => '</div>',
    ];
    if (isset($_GET['context']) && strpos($_GET['context'], '_ntrans:') !== FALSE) {
      $form['actions']['export']['#access'] = FALSE;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $allLanguages = $this->languageManager->getLanguages();
    $languages    = [];
    $ntrans       = [];
    foreach ($allLanguages as $langcode => $language) {
      if ($langcode == 'en' && !\locale_is_translatable('en')) {
        continue;
      }
      $languages[$langcode] = $language;
    }
    foreach ($form_state->getValue('strings') as $lid => $translations) {
      foreach ($translations as $langcode => $strings) {
        if ($langcode == 'original') {
          continue;
        }
        foreach ($strings as $pluralIndex => $string) {
          if (empty($string)) {
            continue;
          }
          if (!locale_string_is_safe($string)) {
            $form_state
              ->setErrorByName("strings][{$lid}][{$langcode}][{$pluralIndex}",
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
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function formFilter(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect(
      'gtext.translate', [], [
        'query'     => [
          'string'  => $form_state->getValue('string'),
          'context' => $form_state->getValue('context'),
        ],
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formReset(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('gtext.translate');
  }

  /**
   * {@inheritdoc}
   */
  public function saveTranslate(array &$form, FormStateInterface $form_state) {
    $updated = [];

    $translate_english = \locale_is_translatable('en');

    $languages = [];
    $existing_objects = [];
    $lids = array_keys($form_state->getValue('strings'));

    $allLanguages = $this->languageManager->getLanguages();
    foreach ($allLanguages as $langcode => $language) {
      if (!$translate_english && $langcode == 'en') {
        continue;
      }
      $languages[$langcode] = $language;
      $options = ['lid' => $lids, 'language' => $langcode, 'translated' => TRUE];
      $translations = $this->localeStorage->getTranslations($options);
      foreach ($translations as $existing_translation_object) {
        $existing_objects[$existing_translation_object->lid][$langcode] = $existing_translation_object;
      }
    }

    foreach ($form_state->getValue('strings') as $lid => $new_translation) {
      foreach ($languages as $langcode => $language) {
        $new_translation_string_delimited = implode(PoItem::DELIMITER, $new_translation[$langcode]);

        $existing_translation = isset($existing_objects[$lid][$langcode]);
        $new_translation_string = implode('', $new_translation[$langcode]);

        $is_changed = FALSE;
        if ($existing_translation && $existing_objects[$lid][$langcode]->translation != $new_translation_string_delimited) {
          $is_changed = TRUE;
        }
        elseif (!$existing_translation && !empty($new_translation_string)) {
          $is_changed = TRUE;
        }

        if ($is_changed) {
          $target = NULL;
          if (isset($existing_objects[$lid][$langcode])) {
            $target = $existing_objects[$lid][$langcode];
          }
          else {
            $options = ['lid' => $lid, 'language' => $langcode];
            $target = $this->localeStorage->createTranslation($options);
          }
          $target->setPlurals($new_translation[$langcode])
            ->setCustomized()
            ->save();
          $updated[] = $target->getId();
        }
        if (empty($new_translation_string) && isset($existing_objects[$lid][$langcode])) {
          $existing_objects[$lid][$langcode]->delete();
          $updated[] = $lid;
        }
      }
    }

    $this->messenger()->addStatus($this->t('The strings have been saved.'));

    if ($updated) {
      _gtext_refresh_locale_translations([$langcode], $updated);
      _gtext_refresh_locale_configuration([$langcode], $updated);
    }
  }

  /**
   * {@inheritdoc}
   */
  private function getStrings($languages, $string = FALSE, $context = FALSE) {
    $languageAliases = [];
    $strings = [];
    $lids = [];

    $query = $this->database
      ->select('locales_source', 's')
      ->extend('Drupal\\Core\\Database\\Query\\PagerSelectExtender');
    $query->fields('s', ['lid']);

    foreach ($languages as $langcode => $language) {
      if (!isset($languageAliases[$langcode])) {
        $languageAliases[$langcode] = 'locale_langcode_' . count($languageAliases);
      }
      $langAlias = $languageAliases[$langcode];
      $query->leftJoin('locales_target', $langAlias, $langAlias . '.lid = s.lid AND ' . $langAlias . '.language = :' . $langAlias . '_name', [
        ':' . $langAlias . '_name' => $langcode,
      ]);
    }
    // Filter.
    if (!empty($string)) {
      $db_or = new Condition('OR');
      $db_or->condition('s.source', '%' . trim($string) . '%', 'LIKE');
      foreach ($languages as $langcode => $language) {
        $db_or->condition($languageAliases[$langcode] . '.translation', '%' . $this->database->escapeLike(trim($string)) . '%', 'LIKE');
      }
      $query->condition($db_or);
    }

    if (strpos($context, '_ntrans:') !== FALSE) {
      // Not translated into language.
      $lng = substr($context, 8);
      $query->condition($languageAliases[$lng] . '.translation', NULL, 'IS NULL');
    }
    elseif ($context != '_none') {
      // All contexts.
      if (empty($context) || $context == '_all') {
        // All existing contexts.
        $contexts = array_keys($this->getAllContexts());
        if (empty($contexts)) {
          $contexts[] = 'not_exists';
        }
        $conditions['context'] = $contexts;
        $query->condition('s.context', $contexts, 'IN');
      }
      else {
        // Specific context.
        $query->condition('s.context', $context);
      }
    }
    $query->limit(30);
    $query->orderBy('s.lid', 'DESC');
    $lids = $query->execute()->fetchCol();

    // Load languages.
    $languages['en'] = TRUE;
    foreach ($languages as $langcode => $language) {
      if (!empty($lids)) {
        $conditions = [
          'language' => $langcode,
          'lid'      => $lids,
        ];
        $stringsLang = $this->localeStorage->getTranslations($conditions, []);
        foreach ($stringsLang as $string) {
          $strings[$langcode][$string->lid] = $string;
        }
      }
      else {
        $strings[$langcode] = [];
      }
    }

    return $strings;
  }

  /**
   * {@inheritdoc}
   */
  private function getAllContexts() {
    return $this->moduleHandler->invokeAll('gtext_contexts');
  }

}
