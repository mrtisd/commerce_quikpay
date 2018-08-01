<?php
/**
 * @file
 * Commerce Quikpay rules integration.
 */

/**
 * Implements hook_rules_action_info().
 */
function commerce_quikpay_rules_action_info() {
  $actions["commerce_quikpay_rules_action_product_remove"] = array(
    'label'      =>  t('Remove the recently added product'),
    'group'      =>  t('Commerce Line Item'),
    'parameter'  =>  array(
      'commerce_order'      =>  array(
        'type'                =>  'commerce_order',
        'label'               =>  t('Order'),
        'description'         =>  t('The order from which to remove the product'),
      ),
      'commerce_product'  =>  array(
        'type'                =>  'commerce_product',
        'label'               =>  t('Product'),
        'description'         =>  t('The product to remove'),
      ),
    ),
    'callbacks'  =>  array(
      'execute'    =>  'commerce_quikpay_rules_action_remove_product',
    ),
  );

  return $actions;
}

/**
 * Implements hook_rules_condition_info().
 */
function commerce_quikpay_rules_condition_info() {
  $conditions["commerce_quikpay_rules_product_type"] = array(
    'label'      =>  t('Order contains only a particular product type'),
    'group'      =>  t('Commerce Order'),
    'parameter'  =>  array(
      'commerce_order'  =>  array(
        'type'            =>  'commerce_order',
        'label'           =>  t('Order'),
        'description'     =>  t('The order whose line items should be checked for a specific product type.'),
      ),
      'product_type'    =>  array(
         'type'           =>  'text',
         'label'          =>  t('Product Type'),
         'description'    =>  t('The product type to look for'),
         'options list'   =>  'commerce_quikpay_rules_product_types',
         'restriction'    =>  'input',
      ),
    ),
    'callbacks'  =>  array(
      'execute'    =>  'commerce_quikpay_rules_product_type_order',
    ),
  );

  $conditions["commerce_quikpay_rules_single_product_type"] = array(
    'label'      =>  t('Order contains only one product type'),
    'group'      =>  t('Commerce Order'),
    'parameter'  =>  array(
      'commerce_order'  =>  array(
        'type'            =>  'commerce_order',
        'label'           =>  t('Order'),
        'description'     =>  t('The order whose line items should be checked for a single product type.'),
      ),
    ),
    'callbacks'  =>  array(
      'execute'    =>  'commerce_quikpay_rules_product',
    ),
  );

  return $conditions;
}

/**
 * Returns product types array for rules select list
 */
function commerce_quikpay_rules_product_types() {
  $product_types = commerce_product_types();
  $types = array();
  foreach ($product_types as $key => $value) {
    $types[$key] = $value['name'];
  }
  return $types;
}

/**
 * Checks to see if order only has particular product type
 *
 * @param $order
 *   Commerce order object
 * @param $type
 *   Product type to check order for
 */
function commerce_quikpay_rules_product_type_order($order, $type) {
  $order_wrapper = entity_metadata_wrapper('commerce_order', $order);

  foreach ($order_wrapper->commerce_line_items as $delta => $line_item_wrapper) {
    if ($line_item_wrapper->commerce_product->type->value() != $type) {
      return FALSE;
    }
  }
  return TRUE;
}

/**
 * Checks to see if order only has one product type
 *
 * @param $order
 *   Commerce order object
 */
function commerce_quikpay_rules_product($order) {
  $order_wrapper = entity_metadata_wrapper('commerce_order', $order);

  $type = "";

  foreach ($order_wrapper->commerce_line_items as $delta => $line_item_wrapper) {
    if ($type == "") {
      $type = $line_item_wrapper->commerce_product->type->value();
    }
    if ($line_item_wrapper->commerce_product->type->value() != $type) {
      return FALSE;
    }
  }
  return TRUE;
}

/**
 * Removes a line item from the cart
 *
 * @param $order
 *   The order to remove the product from
 * @param $product
 *   The product to remove
 */
function commerce_quikpay_rules_action_remove_product($order, $product) {
  $order_wrapper = entity_metadata_wrapper('commerce_order', $order);
  $product_wrapper = entity_metadata_wrapper('commerce_product', $product);
  $line_item_id = "";
  foreach ($order_wrapper->commerce_line_items as $delta => $line_item_wrapper) {
    if ($line_item_wrapper->commerce_product->product_id->value() == $product_wrapper->product_id->value()) {
      $line_item_id = $line_item_wrapper->line_item_id->value();
      break;
    }
  }
  if ($line_item_id != "") {
    $order = commerce_cart_order_product_line_item_delete($order, $line_item_id);
    //$line_item = commerce_line_item_load($line_item_id);
    //$line_item->quantity = 0;
    //commerce_line_item_save($line_item);
  }
}