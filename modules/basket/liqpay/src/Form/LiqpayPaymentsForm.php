<?php

namespace Drupal\liqpay\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Url;

/**
 * Class of LiqpayPaymentsForm.
 */
class LiqpayPaymentsForm extends FormBase {

  /**
   * LiqPay object.
   *
   * @var \Drupal\liqpay\LiqPay|object|null
   */
  protected $liqPay;

  /**
   * Types array.
   *
   * @var array
   */
  protected $types;

  /**
   * Statuses array.
   *
   * @var array
   */
  protected $statuses;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->liqPay = \Drupal::getContainer()->get('LiqPay');

    $configFile = Yaml::decode(file_get_contents(\Drupal::getContainer()->get('extension.list.module')->getPath('liqpay') . '/config_types.yml'));

    $ln = \Drupal::languageManager()->getCurrentLanguage()->getId();
    foreach (['types', 'statuses'] as $type) {
      if (!empty($configFile[$type])) {
        foreach ($configFile[$type] as $key => $names) {
          $this->{$type}[$key] = $names[$ln] ?? $names['en'] ?? $key;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'liqpay_payments';
  }

  /**
   * Payments form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormStateInterface.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->liqPay->t('Payments');
    $form['filter'] = [
      '#type' => 'details',
      '#title' => $this->liqPay->t('Filter'),
    ];
    $form['filter']['status'] = [
      '#type' => 'select',
      '#title' => $this->liqPay->t('Status'),
      '#options' => $this->getOptionsStatus(),
      '#empty_option' => '',
      '#default_value' => @$_GET['status'],
    ];
    $form['filter']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->liqPay->t('Search'),
    ];
    $form['table'] = [
      '#type' => 'table',
      '#header' => [
        [
          'data' => 'ID',
          'field' => 'l.id',
          'sort' => 'desc',
        ], [
          'data' => $this->liqPay->t('Created'),
          'field' => 'l.created',
        ], [
          'data' => 'NID',
          'field' => 'l.nid',
        ], [
          'data' => 'SID',
          'field' => 'l.sid',
        ], [
          'data' => $this->liqPay->t('User ID'),
          'field' => 'l.uid',
        ], [
          'data' => $this->liqPay->t('Amount'),
          'field' => 'l.amount',
        ], [
          'data' => $this->liqPay->t('Status'),
          'field' => 'l.status',
        ], [
          'data' => $this->liqPay->t('Payment time'),
          'field' => 'l.paytime',
        ],
        $this->liqPay->t('Payment type'),
      ],
    ];
    foreach ($this->getPays($form['table']['#header']) as $row) {

      $row->data = !empty($row->data) ? unserialize($row->data) : [];

      $form['table'][$row->id]['id'] = [
        '#markup' => $row->id,
      ];
      $form['table'][$row->id]['created'] = [
        '#markup' => date('d.m.Y H:i', $row->created),
      ];
      $form['table'][$row->id]['nid'] = [
        '#markup' => !empty($row->nid) ? $row->nid : '- - -',
      ];
      $form['table'][$row->id]['sid'] = [
        '#markup' => !empty($row->sid) ? $row->sid : '- - -',
      ];
      $form['table'][$row->id]['user'] = [
        '#markup' => !empty($row->uid) ? $row->uid : '- - -',
      ];
      $form['table'][$row->id]['amount'] = [
        '#markup' => $row->amount . ' ' . $row->currency,
      ];
      $form['table'][$row->id]['status'] = [
        '#markup' => $this->getPayStatus($row),
      ];
      $form['table'][$row->id]['paytime'] = [
        '#markup' => !empty($row->paytime) ? date('d.m.Y H:i', $row->paytime) : '- - -',
      ];
      $form['table'][$row->id]['pay_type'] = [
        '#markup' => $this->getPayType($row),
      ];
      if ($row->status == $this->liqPay::LIQPAY_INSERT_STATUS) {
        $form['table'][$row->id]['paytime']['#markup'] = Url::fromRoute('liqpay.pages', [
          'page_type' => 'pay',
        ], [
          'absolute' => TRUE,
          'query' => [
            'pay_id' => $row->id,
          ],
        ])->toString();
      }
    }
    $form['pager'] = [
      '#type' => 'pager',
    ];
    return $form;
  }

  /**
   * Submit form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormStateInterface.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = $_GET;
    $query['status'] = $form_state->getValue('status');
    $url = Url::fromRoute('liqpay.payments', [], [
      'query' => $query,
    ]);
    $form_state->setRedirectUrl($url);
  }

  /**
   * {@inheritdoc}
   */
  public function getPays($header) {
    $query = \Drupal::database()->select('payments_liqpay', 'l');
    $query->fields('l');
    if (!empty($_GET['status'])) {
      $query->condition('l.status', $_GET['status']);
    }
    $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header);
    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(50);
    return $pager->execute()->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
  public function getPayType($row) {
    $paytype = !empty($row->data['paytype']) ? $row->data['paytype'] : '- - -';
    if (!empty($this->types[$paytype])) {
      $paytype = $this->types[$paytype];
    }
    return $paytype;
  }

  /**
   * {@inheritdoc}
   */
  public function getPayStatus($row) {
    $status = !empty($row->status) ? $row->status : '- - -';
    if (!empty($this->statuses[$status])) {
      $status = $this->statuses[$status];
    }
    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsStatus() {
    $query = \Drupal::database()->select('payments_liqpay', 'l');
    $query->fields('l', ['status', 'status']);
    $options = $query->execute()->fetchAllKeyed();
    foreach ($options as $key => $value) {
      if (!empty($this->statuses[$key])) {
        $options[$key] = $this->statuses[$key];
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatuses() {
    return $this->statuses;
  }

}
