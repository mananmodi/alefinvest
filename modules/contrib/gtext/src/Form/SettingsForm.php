<?php

namespace Drupal\gtext\Form;

use Drupal\Component\Utility\Xss;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Google\Cloud\Translate\V2\TranslateClient;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Provides a SettingsForm class for forms.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Defines the configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'gtext.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = ['#id' => 'form__settings_form'];

    $form['api_key'] = [
      '#type'           => 'textfield',
      '#title'          => t('Google API key'),
      '#description'    => t('Please enter your Google API key or visit <a href="@url">Google APIs console</a> to create new one.', ['@url' => 'https://code.google.com/apis/console']),
      '#default_value'  => $this->configFactory->get('gtext.settings')->get('google_api_key', ''),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type'        => 'submit',
      '#value'       => t('Save'),
      '#button_type' => 'primary',
      '#ajax'        => [
        'wrapper'      => $form['#id'],
        'callback'     => __CLASS__ . '::ajaxCallback',
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $key = $form_state->getValue(['api_key']);
    if (!empty($key)) {
      try {
        $client = new TranslateClient(
          [
            'key' => $key,
          ]
        );
        $client->localizedLanguages();
      }
      catch (\Exception $e) {
        $message = @json_decode($e->getMessage(), TRUE);
        if ($message !== FALSE && isset($message['error']['code']) && isset($message['error']['message'])) {
          $message = t('Google Translate service returned following error: @error', [
            '@error' => $this->secureErrorMessage($message['error']['message']),
          ]);
        }
        else {
          $message = $e->getMessage();
        }
        $form_state->setError($form['api_key'], $message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('gtext.settings')
      ->set('google_api_key', $form_state->getValue(['api_key']))
      ->save();
    $this->messenger()->addStatus(t('API key saved'));
  }

  /**
   * Ajax callback.
   */
  public static function ajaxCallback(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasAnyErrors() || !$form_state->isSubmitted()) {
      return $form;
    }
    $response = new AjaxResponse();
    $status = TranslateFilterForm::getApiStatus();
    $response->addCommand(new ReplaceCommand('#' . $status['#attributes']['id'], $status));
    $response->addCommand(new CloseDialogCommand());
    $response->addCommand(new AppendCommand('body', ['#type' => 'status_messages']));
    return $response;
  }

  /**
   * Filters HTML to prevent cross-site-scripting (XSS) vulnerabilities.
   *
   * @param string $string
   *   Input string for check html tags.
   *
   * @return string
   *   An XSS safe version of $string, or an empty string if $string is not
   *   valid UTF-8.
   *
   * @ingroup sanitization
   */
  private function secureErrorMessage($string) {
    return Xss::filter($string, [
      'a', 'abbr', 'acronym', 'address', 'b', 'bdo', 'big', 'blockquote', 'br', 'caption',
      'cite', 'code', 'col', 'colgroup', 'dd', 'del', 'dfn', 'dl', 'dt', 'em', 'h1', 'h2',
      'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'ins', 'kbd', 'li', 'ol', 'p', 'pre', 'q', 'samp',
      'small', 'span', 'strong', 'sub', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead',
      'tr', 'tt', 'ul', 'var',
    ]);
  }

}
