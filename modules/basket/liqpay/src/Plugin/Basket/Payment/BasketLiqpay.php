<?php

namespace Drupal\liqpay\Plugin\Basket\Payment;

use Drupal\basket\Plugins\Payment\BasketPaymentInterface;
use Drupal\liqpay\Form\PaymentForm;
use Drupal\liqpay\Controller\LiqpayPages;

/**
 * Class of BasketLiqpay.
 *
 * @BasketPayment(
 *   id = "liqpay",
 *   name = "LiqPay",
 * )
 */
class BasketLiqpay implements BasketPaymentInterface {

  /**
   * LiqPay object.
   *
   * @var \Drupal\liqpay\LiqPay|object|null
   */
  protected $liqPay;

  /**
   * Basket object.
   *
   * @var \Drupal\basket\Basket|object|null
   */
  protected $basket;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->liqPay = \Drupal::getContainer()->get('LiqPay');
    $this->basket = \Drupal::getContainer()->get('Basket');
  }

  /**
   * SettingsFormAlter.
   *
   * Alter for making adjustments when creating a payment item.
   */
  public function settingsFormAlter(&$form, $form_state) {
    $tid = $form['tid']['#value'];
    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->liqPay->t('Settings'),
      '#open' => TRUE,
      '#parents' => ['basket_payment_liqpay_settings'],
      '#tree' => TRUE,
      'status' => [
        '#type' => 'select',
        '#title' => $this->liqPay->t('Change order status after payment to'),
        '#options' => $this->basket->term()->getOptions('fin_status'),
        '#empty_option' => $this->liqPay->t('Do not change status'),
        '#default_value' => $this->basket->getSettings('payment_settings', $tid . '.status'),
      ],
    ];
    $form['#submit'][] = [$this, 'formSubmit'];
  }

  /**
   * Save settings.
   */
  public function formSubmit($form, $form_state) {
    $tid = $form_state->getValue('tid');
    if (!empty($tid)) {
      $this->basket->setSettings(
        'payment_settings',
        $tid,
        $form_state->getValue('basket_payment_liqpay_settings')
      );
    }
  }

  /**
   * GetSettingsInfoList.
   *
   * Interpretation of the list of settings for pages with payment types
   * return [];.
   */
  public function getSettingsInfoList($tid) {
    $items = [];
    $settings = $this->basket->getSettings('payment_settings', $tid);
    if (!empty($settings)) {
      $value = $this->liqPay->t('Do not change status');
      $term = $this->basket->term()->load($settings['status'] ?? 0);
      if (!empty($term)) {
        $value = $this->basket->translate()->trans($term->name);
      }
      $items[] = [
        '#type' => 'inline_template',
        '#template' => '<b>{{ label }}: </b> {{ value }}',
        '#context' => [
          'label' => $this->liqPay->t('Change order status after payment to'),
          'value' => $value,
        ],
      ];
    }
    return $items;
  }

  /**
   * CreatePayment.
   *
   * Creation of payment
   * return [
   * 'payID' => 10,
   * 'redirectUrl' => \Drupal::url('payment.routing')
   * ];.
   */
  public function createPayment($entity, $order) {
    if (!empty($order->pay_price)) {
      $currency = $this->basket->currency()->load($order->pay_currency);
      $payment = $this->liqPay->load([
        'nid' => $entity->id(),
        'create_new' => TRUE,
        'amount' => $order->pay_price,
        'currency' => $currency->iso,
      ]);
      if (!empty($payment)) {
        return [
          'payID' => $payment->id,
          'redirectUrl' => NULL,
        ];
      }
    }
    return [
      'payID' => NULL,
      'redirectUrl' => NULL,
    ];
  }

  /**
   * LoadPayment.
   *
   * Downloading Payment Data
   * return [
   * 'payment' => object,
   * 'isPay' => TRUE/FALSE
   * ];.
   */
  public function loadPayment($id) {
    $payment = $this->liqPay->load(['id' => $id]);
    if (!empty($payment)) {
      $successes = explode('|', $this->liqPay::LIQPAY_SUCCESS_STATUS);
      return [
        'payment' => $payment,
        'isPay' => !empty($payment->status) && in_array($payment->status, $successes),
      ];
    }
  }

  /**
   * Alter page redirects for payment.
   */
  public function paymentFormAlter(&$form, $form_state, $payment) {
    (new PaymentForm())->basketPaymentFormAlter($form, $form_state, $payment);
  }

  /**
   * BasketPaymentPages.
   *
   * Alter processing pages of interaction between
   * the payment system and the site
   * $pageType = callback/result/cancel.
   */
  public function basketPaymentPages($pageType) {
    switch ($pageType) {
      case'callback':
        return (new LiqpayPages())->pages('api');

      case'cancel':
      case'result':
        return (new LiqpayPages())->pages('payment_result');

    }
  }

  /**
   * Order update upon successful ordering by payment item settings.
   */
  public function updateOrderBySettings($pid, $order) {
    $orderData = $order->load();
    $settings = $this->basket->getSettings('payment_settings', $pid);
    if (!empty($orderData) && !empty($settings)) {
      if (!empty($settings['status'])) {
        $orderData->fin_status = $settings['status'];
        $order->replaceOrder($orderData);
        $order->save();
      }
    }
  }

}
