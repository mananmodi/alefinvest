<?php

namespace Drupal\simple_pass_reset\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form.
 */
class SettingsForm extends ConfigFormBase {

  const PATH_FIELD_NAME = 'redirect_path';

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface|null $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   */
  public function __construct(
    protected ConfigFactoryInterface $config_factory,
    protected $typedConfigManager,
    protected PathValidatorInterface $path_validator,
  ) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_pass_reset_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $path = $this->config('simple_pass_reset.settings')->get('login_redirection');

    $form['redirects'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Redirects'),
    ];
    $form['redirects'][self::PATH_FIELD_NAME] = [
      '#type' => 'textfield',
      '#title' => 'Redirect destination',
      '#size' => 60,
      '#maxlength' => 128,
      '#description' => $this->t('Redirect after user has logged in. Add a valid url prefixed with / or &lt;front&gt; for the front page.'),
      '#required' => TRUE,
      '#default_value' => $path,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    // Disable caching.
    $form['#cache']['max-age'] = 0;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $valid_path = FALSE;
    $path = $form_state->getValue(self::PATH_FIELD_NAME);

    if ($path === '<front>') {
      $valid_path = TRUE;
    }

    if (!$valid_path && strpos($path, '/') !== 0) {
      $form_state->setErrorByName(self::PATH_FIELD_NAME, $this->t('The url should start with a forward slash (/).'));
    }

    $valid_path = $this->path_validator->isValid($path);
    if (!$valid_path) {
      $form_state->setErrorByName(self::PATH_FIELD_NAME, $this->t('The url is not valid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue(self::PATH_FIELD_NAME) === '<front>') {
      $login_redirection = '/';
    }
    else {
      $login_redirection = $form_state->getValue(self::PATH_FIELD_NAME);
    }

    $this->config('simple_pass_reset.settings')->set('login_redirection', $login_redirection)->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simple_pass_reset.settings'];
  }

}
