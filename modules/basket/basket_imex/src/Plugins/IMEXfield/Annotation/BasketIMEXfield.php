<?php

namespace Drupal\basket_imex\Plugins\IMEXfield\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Class of BasketIMEXfield.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
class BasketIMEXfield extends Plugin {

  /**
   * Name.
   *
   * @var string
   */
  public $name;

  /**
   * Type.
   *
   * @var array
   */
  public $type;

  /**
   * Type info.
   *
   * @var string
   */
  public $type_info;

}
