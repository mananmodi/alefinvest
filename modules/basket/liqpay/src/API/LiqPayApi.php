<?php

namespace Drupal\liqpay\API;

/**
 * Liqpay Payment Module.
 */
class LiqPayApi {
  const CURRENCY_EUR = 'EUR';
  const CURRENCY_USD = 'USD';
  const CURRENCY_UAH = 'UAH';

  /**
   * Payment API url.
   *
   * @var string
   */
  private $apiUrl = 'https://www.liqpay.ua/api/';

  /**
   * Payment checkout url.
   *
   * @var string
   */
  private $checkoutUrl = 'https://www.liqpay.ua/api/3/checkout';

  /**
   * Supported currency.
   *
   * @var string[]
   */
  protected $supportedCurrencies = [
    self::CURRENCY_EUR,
    self::CURRENCY_USD,
    self::CURRENCY_UAH,
  ];

  /**
   * Public KEY.
   *
   * @var string
   */
  private $publicKey;

  /**
   * Private KEY.
   *
   * @var string
   */
  private $privateKey;

  /**
   * {@inheritdoc}
   */
  public function __construct($public_key, $private_key) {
    if (empty($public_key)) {
      throw new InvalidArgumentException('public_key is empty');
    }
    if (empty($private_key)) {
      throw new InvalidArgumentException('private_key is empty');
    }
    $this->publicKey = $public_key;
    $this->privateKey = $private_key;
  }

  /**
   * Call API.
   *
   * @param string $path
   *   Request path.
   * @param array $params
   *   Params data.
   */
  public function api(string $path, array $params = []) {
    if (!isset($params['version'])) {
      throw new InvalidArgumentException('version is null');
    }
    $url = $this->apiUrl . $path;
    $public_key = $this->publicKey;
    $private_key = $this->privateKey;
    $data = base64_encode(json_encode(array_merge(compact('public_key'), $params)));
    $signature = base64_encode(sha1($private_key . $data . $private_key, 1));
    $postfields = http_build_query([
      'data'  => $data,
      'signature' => $signature,
    ]);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $server_output = curl_exec($ch);
    curl_close($ch);
    return json_decode($server_output);
  }

  /**
   * CnbForm.
   *
   * @param array $params
   *   Params data.
   */
  public function cnbForm(array $params) {
    $language = 'en';
    if (array_key_exists(
      $GLOBALS['language']->language,
      ['en' => 'en', 'ru' => 'ru', 'uk' => 'uk']
    )) {
      $language = $GLOBALS['language']->language;
    }
    $params = $this->cnbParams($params);
    $data = base64_encode(json_encode($params));
    $signature = $this->cnbSignature($params);
    return sprintf('
    	<form id="liqpay_pay_form" method="POST" action="%s" accept-charset="utf-8">
      	%s
        %s
      </form>
    ',
      $this->checkoutUrl,
      sprintf('<input type="hidden" name="%s" value="%s" />', 'data', $data),
      sprintf('<input type="hidden" name="%s" value="%s" />', 'signature', $signature),
      $language
    );
  }

  /**
   * Alter form params.
   *
   * @param array $form
   *   Form data.
   * @param array $params
   *   Params data.
   */
  public function formAlter(array &$form, array $params) {
    $params = $this->cnbParams($params);
    $data = base64_encode(json_encode($params));
    $signature = $this->cnbSignature($params);

    $form['#action'] = $this->checkoutUrl;
    $form['#method'] = 'POST';
    $form['data'] = [
      '#type' => 'hidden',
      '#value' => $data,
    ];
    $form['signature'] = [
      '#type' => 'hidden',
      '#value' => $signature,
    ];
  }

  /**
   * CnbSignature.
   *
   * @param array $params
   *   Params.
   */
  public function cnbSignature(array $params) {
    $params = $this->cnbParams($params);
    $private_key = $this->privateKey;

    $json = base64_encode(json_encode($params));
    return $this->strToSign($private_key . $json . $private_key);
  }

  /**
   * CnbParams.
   *
   * @param array $params
   *   Params.
   */
  private function cnbParams(array $params) {
    $params['public_key'] = $this->publicKey;
    if (!isset($params['version'])) {
      throw new \InvalidArgumentException('version is null');
    }
    if (!isset($params['amount'])) {
      throw new \InvalidArgumentException('amount is null');
    }
    if (!isset($params['currency'])) {
      throw new \InvalidArgumentException('currency is null');
    }
    if (!in_array($params['currency'], $this->supportedCurrencies)) {
      throw new \InvalidArgumentException('currency is not supported');
    }
    if (!isset($params['description'])) {
      throw new \InvalidArgumentException('description is null');
    }
    return $params;
  }

  /**
   * StrToSign.
   *
   * @param string $str
   *   String.
   */
  public function strToSign($str) {
    return base64_encode(sha1($str, 1));
  }

}
