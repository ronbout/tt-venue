<?php 
/**
 * 
 *  Redeem a voucher on the mini order page.  Only the tables
 *  need to be updated (order items and audit).  No calcs
 *  are required for that page.  
 *  UnRedeem is not required.
 *  5/17/2022  Ron Boutilier
 * 
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

function redeem_voucher_update_mini($order_item_id) {
	global $wpdb;

  	// update the database with multiple rows if necessary
	$sql = "UPDATE " . $wpdb->prefix . "woocommerce_order_items
          SET downloaded = 1 where order_item_id = %d ";

  $prepared_sql = $wpdb->prepare($sql, $order_item_id);
  $rows_affected = $wpdb->query($prepared_sql);

  // if not success set error array and return
  if (!$rows_affected) {
    $ret_json = array('error' => 'Could not update order items table.');
    echo wp_json_encode($ret_json);
    return;
  }

  // update the audit table so that a history of the redemptions exist.
  $redemption_audit_table = $wpdb->prefix ."taste_venue_order_redemption_audit";
  $user_id = get_current_user_id();

  $insert_values = '(%d, %d, %d)';
  $insert_parms = array();
  $insert_parms[] = intval($order_item_id);
  $insert_parms[] = $user_id;
  $insert_parms[] = 1;

  $sql = "INSERT into $redemption_audit_table
      (order_item_id, user_id, redemption_value)
    VALUES $insert_values";

  $rows_affected = $wpdb->query($wpdb->prepare($sql, $insert_parms));

  // if not success set error array and return
  if (!$rows_affected) {
    $ret_json = array('error' => 'Could not update order items table.');
    echo wp_json_encode($ret_json);
    return;
  }

  $ret_json = array('success' => true);
  echo wp_json_encode($ret_json);
  return;

}