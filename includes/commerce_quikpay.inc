<?php
/**
 * @file
 * Commerce Quikpay module common use functions.
 */

/**
 * Returns a formatted string of the account type
 *
 * @param $account
 *   A string containing the account type from QuikPay
 *
 * @return
 *   Formatted string for the specified account type
 */
function _commerce_quikpay_account($account) {
  switch ($account) {
    case t("VISA"):
    case t("MASTERCARD"):
      return t("@account card", array('@account' => $account));
      break;
    case t("CHECKING"):
    case t("SAVINGS"):
      return t("@account account", array('@account' => $account));
      break;
    default:
      return t("@account", array('@account' => $account));
  }
}

/**
 * Returns a formatted string for linking to the current order
 *
 * @param $order
 *   The order object
 *
 * @return
 *   Formatted string with a link to the order
 */
function _commerce_quikpay_order_link($order) {
  $l = url("user/$order->uid/orders/$order->order_id", array('absolute' => TRUE));
  return "<a href='$l'>$l</a>";
}

/**
 * Returns an logging options as an integer
 *
 * @param $method
 *   Payment method settings array
 *
 * @return
 *   Integer containing the logging options
 */
function _commerce_quikpay_logging($method) {
  $logging = $method['settings']['commerce_quikpay_logging'];
  $ret = 0;
  foreach ($logging as $key => $value) {
    $ret += $key;
  }
  return $ret;
}

/**
 * Returns a string of the URL for QuikPay
 *
 * @param $method
 *   Payment method settings array
 *
 * @return
 *   String of the URL that the payment method should post to
 */
function _commerce_quikpay_url($method) {
  $is_testing = $method['settings']['commerce_quikpay_debug'];
  if ($is_testing == 0) {
    return $method['settings']['commerce_quikpay_test_url'];
  }
  return $method['settings']['commerce_quikpay_live_url'];
}

/**
 * Logs an array of items as textareas
 *
 * @param $items
 *   Array of items to log
 * @param $from
 *   String of where the function was called from
 * @param $title
 *   Optional string title to add before the entire log message
 */
function _commerce_quikpay_log_variables($items, $from = "", $title = "") {
  $msg = ($title != "" ? t("<h2>@title</h2><hr>", array('@title', $title)) : "");
  foreach ($items as $label => $value) {
    $msg_vars = array('@from' => $from, '@label' => $label, '@value' => serialize($value));
    $msg .= t("<p><h3>Called from: @from</h3><strong>@label:</strong><br /><textarea rows=4 cols=80>@value</textarea></p>", $msg_vars);
  }
  watchdog("quikpay", $msg);
}

/**
 * Returns the allowed methods of payment
 *
 * @param $method
 *  The payment method object
 *
 * @return
 *   A string of allowed payment methods
 */
function _commerce_quikpay_methods_allowed($method) {
  $methods_allowed = $method['settings']['commerce_quikpay_allowed_methods'];
  $ret = "";
  foreach ($methods_allowed as $method => $value) {
    $ret .= $method . "_";
  }
  return substr($method, 0, -1);
}

/**
 * Trims a string to a certain length
 *
 * @param $value
 *   The string to trim
 * @param @length
 *   Integer length to trim down to
 *
 * @return
 *   A trimmed to the specified length
 */
function _commerce_quikpay_trim($value, $length) {
  $length = (int) $length;
  $value = (string) $value;
  if ($length == 0) {
    return $value;
  }
  if (drupal_strlen($value) > $length) {
    return drupal_substr($value, 0, $length);
  }
  return $value;
}

/**
 * Removes blank account names from accounts variable
 */
function _commerce_quikpay_accounts_reset() {
  $accounts = variable_get('commerce_quikpay_accounts', array());
  $new_accounts = array();
  foreach ($accounts as $account) {
    if (!_commerce_quikpay_is_blank($account)) {
      $new_accounts[] = $account;
    }
  }
  variable_set('commerce_quikpay_accounts', $new_accounts);
}

/**
 * Checks if a string is all spaces
 */
function _commerce_quikpay_is_blank($value) {
  if (drupal_strlen($value) == 0) {
    return TRUE;
  }
  $blank = "";
  while (drupal_strlen($blank) < drupal_strlen($value)) {
    $blank .= " ";
  }
  if ($value == $blank) {
    return TRUE;
  }
  return FALSE;
}

/**
 * Returns an array of all accounts saved
 */
function _commerce_quikpay_accounts() {
  $accounts = variable_get('commerce_quikpay_accounts', array());
  $ret = array();
  foreach ($accounts as $account) {
    $ret[$account] = $account;
  }
  return $ret;
}