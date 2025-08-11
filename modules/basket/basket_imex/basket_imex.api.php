<?php

/**
 * @file
 * Describes hooks provided by the Basket IMEX module.
 */

use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Allows you to add your own fields to the list.
 *
 * @param array $fields
 *   NodeType fields.
 * @param string $nodeType
 *   NodeType key.
 */
function hook_basket_imex_node_fields_alter(array &$fields, string $nodeType): void {
  if ($nodeType == 'product') {
    $fields['example_test'] = [
      'label' => 'Example field',
      'type' => 'example_type'
    ];
  }
}

/**
 * Allows you to add your own redirect from the 404 page.
 *
 * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
 *   RequestEvent parameter.
 */
function hook_basket_imex_Reditect_alter(RequestEvent $event): void {

}

/**
 * Called before an entity update begins.
 *
 * @param array $info
 *   Input data.
 */
function hook_basket_imex_preEntityProcess(array $info): void {

}

/**
 * Called after an entity is updated.
 *
 * @param array $info
 *   Input data.
 */
function hook_basket_imex_postEntityProcess(array $info): void {

}