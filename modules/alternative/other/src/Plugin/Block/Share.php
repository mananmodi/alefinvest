<?php

namespace Drupal\other\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 *
 * @Block(
 *   id = "other_share",
 *   admin_label = @Translation("Share"),
 *   category = @Translation("Other"),
 * )
 */
class Share extends BlockBase {

	public function build() {

    $output = '<script defer src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-5aaa7370dbf2177b"></script>';
    $output .= '<div class="addthis_inline_share_toolbox_qw70"></div>';

    return [
      '#type' => 'inline_template',
      '#template' => $output
    ];

	}

}
