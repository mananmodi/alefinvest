<?php

namespace Drupal\novaposhta\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * Defines a wizard for the novaposhta table.
 *
 * @ViewsWizard(
 *   id = "novaposhta_en",
 *   module = "novaposhta",
 *   base_table = "novaposhta_en",
 *   title = @Translation("NovaPoshta EN")
 * )
 */
class NovaPoshta extends WizardPluginBase {

  /**
   * Set the created column.
   *
   * @var string
   */
  protected $createdColumn = 'created';

  /**
   * {@inheritdoc}
   */
  protected function defaultDisplayOptions() {
    $display_options = parent::defaultDisplayOptions();

    // Add permission-based access control.
    $display_options['access']['type'] = 'perm';
    $display_options['access']['options']['perm'] = 'access novaposhta en';

    return $display_options;
  }

}
