<?php

namespace Drupal\other\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 *
 * @Block(
 *   id = "other_front_block",
 *   admin_label = @Translation("Front block"),
 *   category = @Translation("other"),
 * )
 */
class FrontBlock extends BlockBase {

	public function build() {

    $output = [];
    return [
      '#theme' => 'other_front_block',
      '#output' => $output,
    ];

  }

}
