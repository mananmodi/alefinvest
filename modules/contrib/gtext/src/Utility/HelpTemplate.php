<?php

namespace Drupal\gtext\Utility;

/**
 * Provides a HelpTemplate class.
 */
class HelpTemplate {

  /**
   * Generate a render array with our templated content.
   *
   * @return array
   *   A render array.
   */
  public static function help() {
    $template_path = self::getDescriptionTemplatePath();
    $template = file_get_contents($template_path);
    $build = [
      'description' => [
        '#type' => 'inline_template',
        '#template' => $template,
        '#context' => self::getDescriptionVariables(),
      ],
    ];
    return $build;
  }

  /**
   * Name of module.
   *
   * @return string
   *   A module name.
   */
  public static function getModuleName() {
    return 'gtext';
  }

  /**
   * Variables to act as context to the twig template file.
   *
   * @return array
   *   Associative array that defines context for a template.
   */
  public static function getDescriptionVariables() {
    $variables = [
      'module' => self::getModuleName(),
    ];
    return $variables;
  }

  /**
   * Get full path to the template.
   *
   * @return string
   *   Path string.
   */
  public static function getDescriptionTemplatePath() {
    return \Drupal::service('extension.list.module')->getPath(self::getModuleName()) . "/templates/help.html.twig";
  }

}
