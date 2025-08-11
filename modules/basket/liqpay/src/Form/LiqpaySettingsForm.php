<?php

namespace Drupal\liqpay\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class of LiqpaySettingsForm.
 */
class LiqpaySettingsForm extends FormBase {

  /**
   * LiqPay object.
   *
   * @var \Drupal\liqpay\LiqPay|object|null
   */
  protected $liqPay;

  /**
   * ImmutableConfig object.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->liqPay = \Drupal::getContainer()->get('LiqPay');
    $this->config = \Drupal::getContainer()->get('config.factory')->get('liqpay.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'liqpay_settings_form';
  }

  /**
   * Config form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormStateInterface.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $form += [
      '#prefix' => '<div id="liqpay_settings_form_ajax_wrap">',
      '#suffix' => '</div>',
      'status_messages' => [
        '#type' => 'status_messages',
      ],
    ];
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $languages = \Drupal::languageManager()->getLanguages();
    if (count($languages) > 1) {
      $options = [];
      foreach ($languages as $language) {
        $options[$language->getId()] = $language->getName();
      }
      $form['language'] = [
        '#type' => 'select',
        '#title' => $this->liqPay->t('Language'),
        '#options' => $options,
        '#ajax' => [
          'wrapper' => 'liqpay_settings_form_ajax_wrap',
          'callback' => [$this, 'ajaxSubmit'],
        ],
        '#default_value' => $langcode,
      ];
    }
    if (!empty($values['language'])) {
      $langcode = $values['language'];
    }
    $isSandbox = !empty($values) ? $values['config']['sandbox'] : $this->config->get('config.sandbox');
    $form['config'] = [
      '#tree' => TRUE,
      'sandbox' => [
        '#type' => 'checkbox',
        '#title' => $this->liqPay->t('Test mode'),
        '#ajax' => [
          'wrapper' => 'liqpay_settings_form_ajax_wrap',
          'callback' => [$this, 'ajaxSubmit'],
        ],
        '#default_value' => $this->config->get('config.sandbox'),
      ],
      'public_key_sandbox' => [
        '#type' => !empty($isSandbox) ? 'textfield' : 'hidden',
        '#title' => 'Public key sandbox',
        '#required' => !empty($isSandbox),
        '#default_value' => $this->config->get('config.public_key_sandbox'),
      ],
      'private_key_sandbox' => [
        '#type' => !empty($isSandbox) ? 'textfield' : 'hidden',
        '#title' => 'Private key sandbox',
        '#required' => !empty($isSandbox),
        '#default_value' => $this->config->get('config.private_key_sandbox'),
      ],
      'public_key' => [
        '#type' => empty($isSandbox) ? 'textfield' : 'hidden',
        '#title' => $this->liqPay->t('Public key'),
        '#required' => empty($isSandbox),
        '#default_value' => $this->config->get('config.public_key'),
      ],
      'private_key' => [
        '#type' => empty($isSandbox) ? 'textfield' : 'hidden',
        '#title' => $this->liqPay->t('Private key'),
        '#required' => empty($isSandbox),
        '#default_value' => $this->config->get('config.private_key'),
      ],
      'currency' => [
        '#type' => 'select',
        '#title' => $this->liqPay->t('Currency'),
        '#options' => [
          'USD' => 'USD',
          'EUR' => 'EUR',
          'UAH' => 'UAH',
        ],
        '#required' => TRUE,
        '#default_value' => $this->config->get('config.currency'),
      ],
      'description' => [
        '#type' => 'textfield',
        '#title' => $this->liqPay->t('Purpose of payment') . ' (' . $langcode . ')',
        '#required' => TRUE,
        '#default_value' => $this->config->get('config.description.' . $langcode),
        '#parents' => ['config', 'description', $langcode],
      ],
      'server_url' => [
        '#type' => 'textfield',
        '#title' => $this->liqPay->t('URL API in your store for payment status change notifications (server-> server)'),
        '#value' => Url::fromRoute('liqpay.pages', ['page_type' => 'api'], ['absolute' => TRUE])->toString(),
        '#disabled' => TRUE,
        '#parents' => ['server_url'],
      ],
      'result_url' => [
        '#type' => 'textfield',
        '#title' => $this->liqPay->t('The URL in your store to which the buyer will be redirected upon completion of the purchase'),
        '#value' => Url::fromRoute('liqpay.pages', ['page_type' => 'payment_result'], ['absolute' => TRUE])->toString(),
        '#disabled' => TRUE,
        '#parents' => ['result_url'],
      ],
      'success' => [
        '#type' => 'text_format',
        '#title' => $this->liqPay->t('Successful payment page text') . ' (' . $langcode . ')',
        '#default_value' => $this->config->get('config.success.' . $langcode . '.value'),
        '#format' => $this->config->get('config.success.' . $langcode . '.format'),
        '#parents' => ['config', 'success', $langcode],
      ],
      'rro' => [
        '#type' => 'checkbox',
        '#title' => $this->liqPay->t('Transfer order data for PPO'),
        '#default_value' => $this->config->get('config.rro'),
        '#access' => \Drupal::moduleHandler()->moduleExists('basket'),
      ],
    ];
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#name' => 'save',
        '#value' => $this->liqPay->t('Save configuration'),
        '#attributes' => [
          'class' => ['button--primary'],
        ],
        '#ajax' => [
          'wrapper' => 'liqpay_settings_form_ajax_wrap',
          'callback' => [$this, 'ajaxSubmit'],
        ],
      ],
    ];
    return $form;
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormStateInterface.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $triggerdElement = $form_state->getTriggeringElement();
    if (!empty($triggerdElement['#name']) && $triggerdElement['#name'] == 'save') {
      $config = \Drupal::configFactory()->getEditable('liqpay.settings');
      $configSave = $form_state->getValue('config');
      if (!empty($preConfig = $this->config->get('config'))) {
        $configSave['description'] += $preConfig['description'];
        $configSave['success'] += $preConfig['success'];
      }
      $config->set('config', $configSave);
      $config->save();
      \Drupal::messenger()->addMessage($this->liqPay->t('The configuration options have been saved.'));
    }
    return $form;
  }

  /**
   * Submit configuration.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormStateInterface.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
