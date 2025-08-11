<?php

namespace Drupal\basket_other\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'responsive_youtube_thumbnail' formatter.
 *
 * @FieldFormatter(
 *   id = "responsive_youtube_thumbnail",
 *   label = @Translation("Responsive YouTube thumbnail"),
 *   field_types = {
 *     "youtube"
 *   }
 * )
 */
class ResponsiveYoutubeThumbnailFormatter extends FormatterBase {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $responsiveImageStyleStorage;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'responsive_image_style' => 'thumbnail',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['responsive_image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image style'),
      '#options' => image_style_options(FALSE),
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => $this->t('None (original image)'),
    ];
    $link_types = [
      'content' => $this->t('Content'),
      'youtube' => $this->t('YouTube'),
    ];
    $elements['image_link'] = [
      '#title' => $this->t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => $this->t('Nothing'),
      '#options' => $link_types,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $responsive_image_style = \Drupal::entityTypeManager()->getStorage('responsive_image_style')->load($this->getSetting('responsive_image_style'));
    if ($responsive_image_style) {
      $summary[] = t('Responsive image style: @responsive_image_style', ['@responsive_image_style' => $responsive_image_style->label()]);
    }
    else {
      $summary[] = t('Select a responsive image style.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities_items) {}

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $responsive_image_style = \Drupal::entityTypeManager()->getStorage('responsive_image_style')->load($this->getSetting('responsive_image_style'));

    foreach ($items as $delta => $item) {

      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      $uri = youtube_build_thumbnail_uri($item->video_id);
      if (!file_exists($uri)) {
        // Retrieve the image from YouTube.
        if (!youtube_get_remote_image($item->video_id)) {
          // Use the remote source if local copy fails.
          $uri = youtube_build_remote_image_path($item->video_id);
        }
      }
      $item->uri = $uri;

      $elements[$delta] = [
        '#theme'            => 'responsive_image_formatter',
        '#item'             => $item,
        '#item_attributes'  => $item_attributes,
        '#responsive_image_style_id' => $responsive_image_style ? $responsive_image_style->id() : '',
        '#url'              => '',
        '#cache'            => [],
      ];
    }

    return $elements;
  }

}
