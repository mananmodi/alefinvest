<?php

namespace Drupal\other;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class OtherTwigExtension extends AbstractExtension {

  public function getName() {
    return 'other_twig_extension';
  }

  public function getFunctions() {
    return [
      new TwigFunction('personal_number_format', [$this, 'personal_number_format']),
    ];
  }

  public static function personal_number_format($number) {
    if ($number == (int)$number) {
      $number = number_format($number, 0, ',', ' ');
    }
    else {
      $number = number_format($number, 2, ',', ' ');
    }
    return $number;
  }

}
