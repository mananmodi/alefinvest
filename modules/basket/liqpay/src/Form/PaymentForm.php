<?php

namespace Drupal\liqpay\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\liqpay\API\LiqPayApi;
use Drupal\Core\Url;

/**
 * Class of PaymentForm.
 */
class PaymentForm extends FormBase {

  /**
   * LiqPay object.
   *
   * @var \Drupal\liqpay\LiqPay|object|null
   */
  protected $liqPay;

  /**
   * Payment object.
   *
   * @var object|null
   */
  protected $payment;

  /**
   * {@inheritdoc}
   */
  public function __construct($payment = NULL) {
    $this->liqPay = \Drupal::getContainer()->get('LiqPay');
    $this->payment = $payment;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'payment_form';
  }

  /**
   * Payment form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormStateInterface.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (empty($this->payment)) {
      throw new NotFoundHttpException();
    }
    $form['#id'] = 'payment_form';
    $form['#attached']['library'][] = 'liqpay/css';
    $form['info'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="sum"><label>{{ label }}:</label> {{ sum }} {{ currency }}</div>',
      '#context' => [
        'label' => $this->liqPay->t('Sum'),
      ],
    ];
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->liqPay->t('Pay'),
      ],
    ];

    $this->payment->result_url = Url::fromRoute('liqpay.pages', ['page_type' => 'payment_result'], ['absolute' => TRUE])->toString();
    $this->payment->callback_url = Url::fromRoute('liqpay.pages', ['page_type' => 'api'], ['absolute' => TRUE])->toString();

    // Payment alter:
    $this->basketPaymentFormAlter($form, $form_state, $this->payment);

    $form['info']['#context']['currency'] = $form['#params']['currency'];
    $form['info']['#context']['sum'] = $form['#params']['amount'];
    return $form;
  }

  /**
   * Submit payment form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormStateInterface.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Alter params form payment.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormStateInterface.
   * @param object $payment
   *   Payment object.
   */
  public function basketPaymentFormAlter(array &$form, FormStateInterface $form_state, object $payment) {
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $config = $this->liqPay->getConfig();

    $language = 'en';
    switch (\Drupal::languageManager()->getCurrentLanguage()->getId()) {
      case'uk':
      case'ru':
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        break;
    }

    $form['#params'] = [
      'action' => $this->liqPay::LIQPAY_ACTION,
      'amount' => $payment->amount,
      'currency' => $payment->currency,
      'description' => !empty($config['description'][$langcode]) ? $config['description'][$langcode] : 'Payment',
      'order_id' => $payment->id . '-' . time() . rand(0, 999),
      'version' => $this->liqPay::LIQPAY_VERSION,
      'server_url' => $payment->callback_url,
      'result_url' => $payment->result_url,
      'language' => $language,
    ];

    if (\Drupal::getContainer()->has('Basket') && !empty($config['rro']) && !empty($payment->nid)) {

      $order = \Drupal::getContainer()->get('Basket')->orders(NULL, $payment->nid)->load();

      if (!empty($order->items)) {
        $form['#params']['rro_info']['goods'] = [];
        foreach ($order->items as $item) {
          $form['#params']['rro_info']['goods'][$item->id] = new \stdClass();
          $form['#params']['rro_info']['goods'][$item->id]->good = new \stdClass();
          $form['#params']['rro_info']['goods'][$item->id]->good->code = (string) $item->nid;
          $form['#params']['rro_info']['goods'][$item->id]->good->name = (string) $item->node_fields['title'] ?? $item->nid;
          $form['#params']['rro_info']['goods'][$item->id]->good->barcode = (string) $item->nid;

          $price = !empty($item->price) ? $item->price : 0;
          if (!empty($item->discount['percent'])) {
            $price = $price - ($price / 100 * $item->discount['percent']);
          }
          $form['#params']['rro_info']['goods'][$item->id]->good->price = round($price * 100, 0);
          $form['#params']['rro_info']['goods'][$item->id]->quantity = round($item->count * 1000, 0);
          $form['#params']['rro_info']['goods'][$item->id]->is_return = FALSE;
        }
        $form['#params']['rro_info']['goods'] = array_values($form['#params']['rro_info']['goods']);
        $form['#params']['rro_info']['delivery'] = new \stdClass();
        $form['#params']['rro_info']['delivery']->email = NULL;
      }
    }

    // Alter:
    \Drupal::moduleHandler()->alter('liqpay_payment_params', $form['#params'], $payment, $config);

    if (empty($form['#params']['rro_info']['delivery']->email)) {
      unset($form['#params']['rro_info']['delivery']);
    }
    $liqpay = new LiqPayApi(
      $config['public_key'],
      $config['private_key']
    );
    $liqpay->formAlter($form, $form['#params']);

    $_SESSION['liqpay_last_pay'] = $payment->id;
  }

}
