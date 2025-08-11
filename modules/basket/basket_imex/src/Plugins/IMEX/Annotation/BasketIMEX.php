<?php

namespace Drupal\basket_imex\Plugins\IMEX\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Class of BasketIMEX.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
class BasketIMEX extends Plugin {

  /**
   * Name.
   *
   * @var string
   */
  public $name;

}
