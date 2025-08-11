<?php

namespace Drupal\gtext\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Drupal\gtext\TextTranslationFactory;

/**
 * Provides a TranslateController class.
 */
class TwigTranslateExtension extends AbstractExtension implements GlobalsInterface {

  /**
   * Provides a TextTranslationFactory class.
   *
   * @var \Drupal\gtext\TextTranslationFactory
   */
  protected $gtext;

  /**
   * Constructs a new TwigTranslateExtension.
   */
  public function __construct(TextTranslationFactory $gtext) {
    $this->gtext = $gtext;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'gtext.translate_extension';
  }

  /**
   * {@inheritdoc}
   */
  public function getGlobals(): array {
    return [
      'gtext' => $this->gtext,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('gtext', [$this, 'textTranslate']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function gText() {
    return $this->gtext;
  }

  /**
   * {@inheritdoc}
   */
  public function textTranslate(string $text, $contextName, $args = []) {
    return $this->gtext->$contextName->t($text, $args);
  }

}
