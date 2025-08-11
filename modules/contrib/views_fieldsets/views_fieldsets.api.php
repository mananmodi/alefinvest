<?php

/**
 * @file
 * Hooks defined by Views Fieldset.
 */

/**
 * Alter the options for the views fieldset module.
 *
 * @param array $data
 *   List of fieldset options.
 */
function hook_views_fieldsets_wrapper_types_alter(array &$data) {
  $data["modal-group"] = "modal-group";
}
