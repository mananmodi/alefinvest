<?php

use Drupal\Core\Form\FormStateInterface;

/**
 * @file
 * Describes hooks provided by the Basket Excel module.
 */

/**
 * Changing the array was carried at the start of the export.
 *
 * @param array|null $nids
 *   Export array ids.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *    FormState object.
 * @param string $systemKey
 *   System key.
 */
function hook_basket_excel_get_export_rows_alter(?array &$nids, FormStateInterface $form_state, string $systemKey): void {

}
