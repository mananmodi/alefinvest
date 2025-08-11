<?php

namespace Drupal\other\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 *
 * @Block(
 *   id = "other_header",
 *   admin_label = @Translation("Header block"),
 *   category = @Translation("other"),
 * )
 */
class HeaderBlock extends BlockBase {

	public function build() {

    $block_manager = \Drupal::service('plugin.manager.block');
    $output = [];

    // Basket count.
    $plugin_block = $block_manager->createInstance('basket_count');
    if (!empty($plugin_block)) {
      $output['basket_count'] = $plugin_block->build();
    }

    // Basket currency.
    $plugin_block = $block_manager->createInstance('basket_currency');
    if (!empty($plugin_block)) {
      $output['basket_currency'] = $plugin_block->build();
    }

    // Language block.
    $plugin_block = $block_manager->createInstance('language_block:language_interface');
    if (!empty($plugin_block)) {
      $output['language_block'] = $plugin_block->build();
    }

    return [
      '#theme' => 'other_header_block',
      '#output' => $output,
    ];

  }

}
