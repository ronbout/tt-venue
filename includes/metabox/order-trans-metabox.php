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
    echo $order_trans_display;
	}

}
