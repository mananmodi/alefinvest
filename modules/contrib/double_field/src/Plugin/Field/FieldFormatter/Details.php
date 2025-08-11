<?php

namespace Drupal\double_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementations for 'details' formatter.
 *
 * @FieldFormatter(
 *   id = "double_field_details",
 *   label = @Translation("Details"),
 *   field_types = {"double_field"}
 * )
 */
class Details extends Base {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'open' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $settings = $this->getSettings();

    $element['open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open'),
      '#default_value' => $settings['open'],
    ];

    $element += parent::settingsForm($form, $form_state);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $open = $this->getSetting('open');
    $summary[] = $this->t('Open: @open', ['@open' => $open ? $this->t('yes') : $this->t('no')]);
    return array_merge($summary, parent::settingsSummary());
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];

    $settings = $this->getSettings();

    $attributes = [
      // No other way to pass context to the theme.
      // @see double_field_theme_suggestions_details_alter()
      'double-field--field-name' => $items->getName(),
      'class' => ['double-field-details'],
    ];

    foreach ($items as $delta => $item) {

      $values = [];
      foreach (['first', 'second'] as $subfield) {
        $values[$subfield] = $item->{$subfield};
      }

      $element[$delta] = [
        '#title' => $values['first'],
        '#value' => $values['second'],
        '#type' => 'details',
        '#open' => $settings['open'],
        '#attributes' => $attributes,
      ];
    }

    return $element;
  }

}
