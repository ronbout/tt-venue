<?php 
/**
 *  order-trans-metabox.php
 * 
 *  create metabox in the Order Admin screen
 *  for listing the transactions related to the order
 * 
 * 	6/25/2022
 *  Ron Boutilier
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

function taste_register_order_meta_box() {
	$post_types = array('shop_order');

	foreach($post_types as $p_type) {
		add_meta_box( 'taste-order-transaction-box', __( 'Order Venue Transactions'), 'taste_display_order_trans_info', $p_type, 'normal', 'high' );
	}

}
add_action( 'add_meta_boxes', 'taste_register_order_meta_box' );



function taste_display_order_trans_info($post_info) {
	global $wpdb;

	if (property_exists($post_info, 'ID')) {
		$order_trans_display = disp_order_trans_box($post_info->ID);
		if ($order_trans_display['prev_order_id']) {
			echo "<h3>Previous Credited Order(s)</h3>";
			display_other_orders($order_trans_display['prev_order_id']);
		}
		echo "<h3>Current Order:</h3>";
    echo $order_trans_display['display'];
		if ($order_trans_display['next_order_id']) {
			echo "<h3>Taste Credit Purchased Order(s)</h3>";
			display_other_orders($order_trans_display['next_order_id']);
		}
	}

}

function display_other_orders($order_id_string) {
	$order_ids = explode(',', $order_id_string);
	$first = true;
	foreach($order_ids as $order_id) {
		if (!$first) {
			echo "<hr>";
		}
		$order_trans_display = disp_order_trans_box($order_id);
		echo $order_trans_display['display'];
		$first = false;
	}
}
