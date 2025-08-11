<?php

namespace Drupal\gtext\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Extension\ModuleHandler;

/**
 * Provides a TranslateFilterForm class.
 */
class TranslateFilterForm extends FormBase {

  /**
   * Returns the language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Turns a render array into a HTML string.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Class that manages modules in a Drupal installation.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a new TranslateFilterForm.
   */
  public function __construct(LanguageManagerInterface $languageMmanager, Renderer $renderer, ModuleHandler $moduleHandler) {
    $this->languageManager = $languageMmanager;
    $this->renderer = $renderer;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('module_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gtext_translate_filter';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'gtext-translate-form-filter';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $this->languageManager->reset();
    $allLanguages = $this->languageManager->getLanguages();

    $languages = [];
    $ntrans    = [];
    foreach ($allLanguages as $langcode => $language) {
      if ($langcode == 'en') {
        continue;
      }
      $languages[$langcode] = $language;
      $ntrans['_ntrans:' . $langcode] = t('All not translated strings in %lang', ['%lang' => $language->getName()]);
    }

    $status = self::getApiStatus();

    // Filter entity.configurable_language.collection.
    $form['filter'] = [
      '#type'         => 'details',
      '#title'        => t('Filter translatable strings') . $this->renderer->render($status),
      '#open'         => TRUE,
      'string' => [
        '#type'         => 'search',
        '#title'        => t('String contains'),
        '#description'  => t('Leave blank to show all strings. The search is case sensitive.'),
        '#default_value' => isset($_GET['string']) ? $_GET['string'] : '',
      ],
      'context' => [
        '#type'         => 'select',
        '#title'        => t('Text group'),
        '#options'      => [
          'Filters'  => [
            '_all'    => t('All contexts'),
            '_none'   => t('All translatable strings'),
          ] + $ntrans,
          'Contexts' => $this->moduleHandler->invokeAll('gtext_contexts'),
        ],
        '#default_value' => isset($_GET['context']) ? $_GET['context'] : '_none',
      ],
      'actions' => [
        '#type'         => 'actions',
        '#attributes'   => ['class' => ['container-inline']],
        'submit' => [
          '#type'         => 'submit',
          '#value'        => t('Filter'),
        ],
        'reset' => [
          '#markup'       => Link::fromTextAndUrl(
            t('Reset'),
            new Url('gtext.translate', [], ['attributes' => ['class' => ['button']]])
          )->toString(),
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $query = [];
    if (!empty($values['string'])) {
      $query['string'] = $values['string'];
    }
    if (!empty($values['context'])) {
      $query['context'] = $values['context'];
    }
    $url = Url::fromRoute('gtext.translate', [], ['query' => $query]);
    $form_state->setRedirectUrl($url);
  }

  /**
   * {@inheritdoc}
   */
  public static function getApiStatus() {
    $apiKey = \Drupal::config('gtext.settings')->get('google_api_key', FALSE);
    if (empty($apiKey)) {
      $keyStatus   = t('not set');
      $statusClass = 'not-set';
    }
    else {
      $keyStatus   = $apiKey;
      $statusClass = 'set';
    }
    $status = [
      '#type'       => 'html_tag',
      '#tag'        => 'div',
      '#value'      => t('Google API key: @key', ['@key' => $keyStatus]),
      '#attributes' => [
        'id'        => 'google-api-status',
        'class'     => ['google-api-status', $statusClass],
      ],
      'link'        => [
        '#type'     => 'link',
        '#url'      => Url::fromRoute('gtext.translate.settings'),
        '#title'    => t('Settings'),
        '#attributes' => [
          'class'               => ['api-settings-link', 'use-ajax'],
          'data-dialog-type'    => 'modal',
          'data-dialog-options' => Json::encode(
            [
              'width' => '90%',
            ]
          ),
        ],
      ],
    ];
    return $status;
  }

}
