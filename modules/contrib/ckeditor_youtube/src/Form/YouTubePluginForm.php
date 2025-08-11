<?php

namespace Drupal\ckeditor_youtube\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Provide settings for CKEditor Youtube plugin.
 */
class YouTubePluginForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'youtube_plugin_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ckeditor_youtube.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ckeditor_youtube.settings');

    $form['ckeditor_youtube_settings']['library_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter the path the CKEditor Youtube plugin is installed at.'),
      '#required' => TRUE,
      '#description' => $this->t('By default this is "libraries/youtube/youtube".'),
      '#default_value' => $config->get('library_path'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('ckeditor_youtube.settings')
      ->set('library_path', $values['library_path'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
