<?php

namespace Drupal\gtext;

/**
 * Provides a TextTranslationFactory class.
 */
class TextTranslationFactory {

  /**
   * The context the sourcen string belongs to.
   *
   * @var array
   */
  protected $contextTranslations = [];

  /**
   * Is invoked automatically when a non-existing method.
   *
   * {@inheritDoc}
   */
  public function __call($context, $args) {
    if (!isset($this->contextTranslations[$context])) {
      $this->contextTranslations[$context] = new TextTranslationWrapper($context);
    }

    return $this->contextTranslations[$context]->t($args[0], !empty($args[1]) ? $args[1] : []);
  }

  /**
   * Is utilized for reading data from inaccessible.
   *
   * {@inheritDoc}
   */
  public function __get($context) {
    if (!isset($this->contextTranslations[$context])) {
      $this->contextTranslations[$context] = new TextTranslationWrapper($context);
    }

    return $this->contextTranslations[$context];
  }

  /**
   * Is triggered by calling isset() or empty()
   *
   * {@inheritDoc}
   */
  public function __isset($context) {
    return \ctype_alnum($context) && !ctype_digit(mb_substr($context, 0, 1));
  }

}
