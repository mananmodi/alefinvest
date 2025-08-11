<?php
namespace Drupal\other\Translate;

class other {
  public static function t($text, $args = []){
    return t(trim($text), $args, ['context' => 'other']);
  }
}
