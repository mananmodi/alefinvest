<?php

declare(strict_types=1);

namespace Drupal\nbsp\Plugin\CKEditor4To5Upgrade;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor4To5UpgradePluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\filter\FilterFormatInterface;

/**
 * Provides the CKEditor 4 to 5 upgrade for Drupal core's CKEditor plugins.
 *
 * @CKEditor4To5Upgrade(
 *   id = "nbsp",
 *   cke4_buttons = {
 *     "DrupalNbsp"
 *   }
 * )
 *
 * @internal
 *   Plugin classes are internal.
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @codingStandardsIgnoreFile
 */
class Nbsp extends PluginBase implements CKEditor4To5UpgradePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function mapCKEditor4ToolbarButtonToCKEditor5ToolbarItem(string $cke4_button, HTMLRestrictions $text_format_html_restrictions): ?array {
    switch ($cke4_button) {
      case 'DrupalNbsp':
        return ['nbsp'];

      default:
        throw new \OutOfBoundsException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mapCKEditor4SettingsToCKEditor5Configuration(string $cke4_plugin_id, array $cke4_plugin_settings): ?array {
    switch ($cke4_plugin_id) {
      default:
        throw new \OutOfBoundsException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function computeCKEditor5PluginSubsetConfiguration(string $cke5_plugin_id, FilterFormatInterface $text_format): ?array {
    switch ($cke5_plugin_id) {
      default:
        throw new \OutOfBoundsException();
    }
  }

}
