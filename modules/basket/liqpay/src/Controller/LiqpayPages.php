<?php

namespace Drupal\liqpay\Controller;

use Drupal\Core\Serialization\Yaml;
use Drupal\liqpay\Form\PaymentForm;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Url;

/**
 * Class of LiqpayPages.
 */
class LiqpayPages {

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
   * Request params.
   *
   * @var array
   */
  protected $request;

  /**
   * ModuleHandler object.
   *
   * @var \Drupal\Core\Extension\ModuleHandler|object|null
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->liqPay = \Drupal::getContainer()->get('LiqPay');
    $this->basket = \Drupal::getContainer()->has('Basket') ? \Drupal::getContainer()->get('Basket') : NULL;
    $this->request = \Drupal::request()->query->all();
    $this->moduleHandler = \Drupal::getContainer()->get('module_handler');
  }

  /**
   * {@inheritdoc}
   */
  public function title(array $_title_arguments = [], $_title = '') {
    return t($_title, $_title_arguments, ['context' => 'liqpay']);
  }

  /**
   * {@inheritdoc}
   */
  public function pages($page_type, $all = []) {
    $element = [];
    switch ($page_type) {
      case'api':
        if (!empty($_REQUEST['data']) && !empty($_REQUEST['signature'])) {
          $config = $this->liqPay->getConfig();

          $data = json_decode(base64_decode($_REQUEST['data']), TRUE);
          $order_id = $this->liqPay->getPayIdByData($data);
          $payment = $this->liqPay->load([
            'id' => $order_id,
          ]);
          $params = [];

          // Alter:
          $this->moduleHandler
            ->alter('liqpay_payment_params', $params, $payment, $config);
          $this->moduleHandler
            ->invokeAll('liqpay_payment_api_pre_run', [$order_id, $data]);

          $successes = explode('|', $this->liqPay::LIQPAY_SUCCESS_STATUS);

          // Checking the relevance of data in the presence of previous data.
          $isCheck = TRUE;
          if (!empty($payment) && in_array($data['status'], ['failure', 'error']) && in_array($payment->status, $successes)) {
            $payData = @unserialize($payment->data);
            if (!empty($payData['payment_id']) && $payData['payment_id'] != $data['payment_id']) {
              $isCheck = FALSE;
              \Drupal::logger('liqpay_failure')->info('<pre>' . print_r([
                'payment' => $payment,
                'data' => $data,
              ], TRUE) . '</pre>');
            }
          }

          $signature = base64_encode(sha1($config['private_key'] . $_REQUEST['data'] . $config['private_key'], 1));
          if ($signature == $_REQUEST['signature'] && $isCheck) {
            if (!empty($data['order_id']) && !empty($payment)) {
              $pre_pay = @unserialize($payment->data);
              $data['pre_pay'] = isset($pre_pay['pre_pay']) ? $pre_pay['pre_pay'] : $pre_pay;

              $payment->paytime = time();
              $payment->data = serialize($data);

              if (!empty($data['status'])) {
                $payment->status = $data['status'];
                if (in_array($data['status'], $successes)) {
                  if (!empty($this->basket)) {
                    if (method_exists($this->basket, 'paymentFinish')) {
                      $this->basket->paymentFinish($payment->nid);
                    }
                  }
                }
              }
              $this->liqPay->update($payment);

              // Alter:
              $this->moduleHandler->alter('liqpay_api', $payment, $data);

              // Trigger change payment status by order.
              if (!empty($payment->nid) && !empty($this->basket)) {
                $order = $this->basket->Orders(NULL, $payment->nid)->load();
                if (!empty($order) && \Drupal::getContainer()->has('BasketNoty')) {
                  \Drupal::getContainer()->get('BasketNoty')->trigger('change_liqpay_status', [
                    'order' => $order,
                  ]);
                }
              }
            }
          }
        }
        break;

      case'pay':
        $is_404 = TRUE;
        if (!empty($this->request['pay_id'])) {
          $payment = $this->liqPay->load([
            'id' => $this->request['pay_id'],
          ]);
          $successes = explode('|', $this->liqPay::LIQPAY_SUCCESS_STATUS);
          if (!empty($payment->status) && !in_array($payment->status, $successes)) {
            $is_404 = FALSE;
            $element['form'] = \Drupal::formBuilder()->getForm(new PaymentForm($payment));
          }
        }
        if ($is_404) {
          throw new NotFoundHttpException();
        }
        break;

      case'payment_result':
        $element['#title'] = $this->liqPay->t('Payment result');
        $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $payON = FALSE;
        if (!empty($_SESSION['liqpay_last_pay'])) {
          $payment = $this->liqPay->load(['id' => $_SESSION['liqpay_last_pay']]);
          $successes = explode('|', $this->liqPay::LIQPAY_SUCCESS_STATUS);
          if (!empty($payment->status) && in_array($payment->status, $successes)) {
            $payON = TRUE;
          }
          if (!$payON) {
            $getStatus = $this->liqPay->getStatus($_SESSION['liqpay_last_pay']);
            if (!empty($getStatus->code) && $getStatus->code !== 'payment_not_found') {
              $payON = TRUE;
            }
          }
          if ($payON) {
            $config = $this->liqPay->getConfig();
            $success = !empty($config['success'][$langcode]) ? $config['success'][$langcode] : $config['success']['en'];
            if ($payment->status == 'unsubscribed') {
              $success['value'] = '<p class="text-align-center"><strong>Подписка успешно деактивирована.</strong></p>';
            }
            $element += [
              '#theme' => 'liqpay_success_page',
              '#info' => [
                'text' => [
                  '#type' => 'processed_text',
                  '#text' => !empty($success['value']) ? $success['value'] : '',
                  '#format' => !empty($success['format']) ? $success['format'] : NULL,
                ],
                '_text' => $success['value'] ?? '',
                'type' => 'success',
                'payment' => $payment,
              ],
              '#attached' => [
                'library' => ['liqpay/css'],
              ],
            ];
          }
          else {

            $mess = $this->liqPay->t('No payment data.');
            $payData = !empty($payment->data) ? unserialize($payment->data) : [];
            $isProgress = 1;

            if (!empty($payData['err_code'])) {
              $errors = Yaml::decode(file_get_contents(\Drupal::getContainer()->get('extension.list.module')->getPath('liqpay') . '/errors.yml'));
              if (!empty($errors[$payData['err_code']])) {
                $mess = $errors[$payData['err_code']][$langcode] ?? reset($errors[$payData['err_code']]);
              }
              elseif (!empty($payData['err_description'])) {
                $mess = t(trim($payData['err_description']), [], ['context' => 'liqpay_err']);
              }
              $isProgress = 0;
            }
            $element += [
              '#theme' => 'liqpay_success_page',
              '#info' => [
                'text' => [
                  'text' => [
                    '#type' => 'processed_text',
                    '#text' => '<p class="text-align-center"><strong>' . $mess . '</strong></p>',
                    '#format' => 'full_html',
                  ],
                  'link' => [
                    '#type' => 'link',
                    '#title' => $this->liqPay->t('Repeat'),
                    '#url' => new Url('liqpay.pages', [
                      'page_type' => 'pay',
                    ], [
                      'attributes' => [
                        'class' => ['form-submit'],
                      ],
                      'query' => [
                        'pay_id' => $_SESSION['liqpay_last_pay'],
                      ],
                    ]),
                  ],
                ],
                'isProgress' => $isProgress,
                '_text' => $mess,
                'type' => 'error',
                'payment' => $payment,
              ],
              '#attached' => [
                'library' => ['liqpay/css'],
              ],
            ];
          }
        }
        break;
    }
    return $element;
  }

}
