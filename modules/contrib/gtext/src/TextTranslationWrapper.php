<?php

namespace Drupal\gtext;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;

/**
 * Provides a TextTranslationWrapper class.
 */
class TextTranslationWrapper {

  /**
   * Context translation string.
   *
   * @var array
   */
  protected $context;

  /**
   * Constructs a new TextTranslationWrapper.
   *
   * {@inheritDoc}
   */
  public function __construct($context) {
    $this->context = $context;
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * This functionality is a wrapper of @link i18n Localization API. @endlink.
   *
   * See \Drupal\Core\StringTranslation\TranslatableMarkup::__construct() for
   * important security information and usage guidelines.
   *
   * @param string $string
   *   A string containing the English text to translate.
   * @param array $args
   *   (optional) An associative array.
   * @param array $options
   *   (optional) An associative array of additional options, with the following
   *   elements:
   *   - 'langcode' (defaults to the current language): A language code, to
   *     translate to a language other than what is used to display the page.
   *
   * @see \Drupal\Core\StringTranslation\TranslatableMarkup::__construct()
   */
  public function t(string $string, array $args = [], array $options = []) {
    $options['context'] = $this->context;
    return new TranslatableMarkup($string, $args, $options);
  }

  /**
   * Formats a string containing a count of items.
   *
   * {@inheritDoc}
   */
  public function plural($count, $singular, $plural, array $args = [], $options = []) {
    $options['context'] = $this->context;
    return new PluralTranslatableMarkup($count, $singular, $plural, $args, $options);
  }

}
