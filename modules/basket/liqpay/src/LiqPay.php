<?php

namespace Drupal\liqpay;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\liqpay\API\LiqPayApi;

/**
 * Class of LiqPay.
 */
class LiqPay {

  const LIQPAY_INSERT_STATUS = 'liqpay_new';
  const LIQPAY_SUCCESS_STATUS = 'success|sandbox|subscribed|unsubscribed|hold_wait';
  const LIQPAY_ACTION = 'pay';
  const LIQPAY_VERSION = '3';

  /**
   * Load payment by filter params.
   *
   * @param array $params
   *   Filter params.
   */
  public function load(array $params) {
    $query = \Drupal::database()->select('payments_liqpay', 'l');
    $query->fields('l');
    if (!empty($params['id'])) {
      $query->condition('l.id', $params['id']);
    }
    if (!empty($params['nid'])) {
      $query->condition('l.nid', $params['nid']);
    }
    if (!empty($params['sid'])) {
      $query->condition('l.sid', $params['sid']);
    }
    if (empty($params['data'])) {
      $params['data'] = [];
    }
    $payment = $query->execute()->fetchObject();

    if (!empty($params['create_new'])) {
      $payment = FALSE;
    }
    if (empty($payment) && !empty($params['amount'])) {
      $payment = (object) [
        'nid' => !empty($params['nid']) ? $params['nid'] : NULL,
        'sid' => !empty($params['sid']) ? $params['sid'] : NULL,
        'uid' => \Drupal::currentUser()->id(),
        'created' => time(),
        'paytime' => NULL,
        'amount' => $params['amount'],
        'currency' => !empty($params['currency']) ? $params['currency'] : \Drupal::config('liqpay.settings')->get('config.currency'),
        'status' => $this::LIQPAY_INSERT_STATUS,
        'data' => serialize($params['data']),
      ];
      $payment->id = \Drupal::database()->insert('payments_liqpay')
        ->fields((array) $payment)
        ->execute();
    }
    return $payment;
  }

  /**
   * Update payment info.
   *
   * @param object $payment
   *   Payment object.
   */
  public function update(object $payment) {
    if (!empty($payment->id)) {
      $updateFields = (array) $payment;
      unset($updateFields['id']);
      \Drupal::database()->update('payments_liqpay')
        ->fields($updateFields)
        ->condition('id', $payment->id)
        ->execute();
    }
  }

  /**
   * Get configuration.
   */
  public function getConfig() {
    $config = \Drupal::config('liqpay.settings')->get('config');
    if (!empty($config['sandbox'])) {
      $config['public_key'] = $config['public_key_sandbox'];
      $config['private_key'] = $config['private_key_sandbox'];
    }
    return $config;
  }

  /**
   * Get LiqPay status by order id.
   *
   * @param int $orderId
   *   Order ID.
   */
  public function getStatus(int $orderId) {
    $config = $this->getConfig();
    $liqpay = new LiqPayApi(
      $config['public_key'],
      $config['private_key']
    );
    return $liqpay->api('request', [
      'action' => 'status',
      'version' => $this::LIQPAY_VERSION,
      'order_id' => $orderId,
    ]);
  }

  /**
   * Translatable string.
   *
   * @param string $string
   *   A string containing the English text to translate.
   * @param array $args
   *   (optional) An associative array of replacements to make after
   *   translation. Based on the first character of the key, the value is
   *   escaped and/or themed. See
   *   \Drupal\Component\Render\FormattableMarkup::placeholderFormat() for
   *   details.
   */
  public function t(string $string, array $args = []) {
    return new TranslatableMarkup($string, $args, ['context' => 'liqpay']);
  }

  /**
   * Return all constants.
   */
  public static function getConstants() {
    $refl = new \ReflectionClass(__CLASS__);
    return $refl->getConstants();
  }

  /**
   * Get payment ID by data.
   *
   * @param array $data
   *   Payment data.
   */
  public function getPayIdByData(array $data) {
    $orderId = NULL;

    // Alter:
    \Drupal::getContainer()->get('module_handler')->alter('liqpay_get_order_id_by_data', $orderId, $data);

    if (is_null($orderId)) {
      @[$orderId, $rand] = explode('-', $data['order_id']);
    }
    return $orderId;
  }

}
