<?php

/**
 * @file
 * Hooks provided by the liqpay module.
 */

/**
 * Alter payment params && config.
 *
 * @param array $params
 *   Params data.
 * @param object $payment
 *   Payment object.
 * @param array $config
 *   Config data.
 */
function hook_liqpay_payment_params_alter(array &$params, object $payment, array &$config): void {
  // Do something.
}

/**
 * Alter liqpay pre run API.
 *
 * @param string $order_id
 *   Order ID.
 * @param array $data
 *   Payment data.
 */
function hook_liqpay_payment_api_pre_run(string $order_id, array $data): void {
  // Do something.
}

/**
 * Alter liqpay API.
 *
 * @param object $payment
 *   Payment object.
 * @param array $data
 *   Payment data.
 */
function hook_liqpay_api_alter(object $payment, array $data) {
  // Do something.
}

/**
 * Alter get order_id by data.
 *
 * @param string|null $orderId
 *   Order ID.
 * @param array $data
 *   Payment data.
 */
function hook_liqpay_get_order_id_by_data_alter(?string &$orderId, array $data): void {
  // Do something.
}
