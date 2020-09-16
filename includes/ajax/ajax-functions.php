<?php 

defined('ABSPATH') or die('Direct script access disallowed.');

function taste_ajax_load_vouchers() {

	if (!check_ajax_referer('taste-venue-nonce','security', false)) {
		echo '<h2>Security error loading data.  <br>Please Refresh the page and try again.</h2>';
		wp_die();
	}

	if (!isset($_POST['product_id'])) {
		echo 'No valid product id';
		wp_die();
	}
	$product_id = $_POST['product_id'];

	require_once(plugin_dir_path(__FILE__). 'display-voucher-table.php');
	display_voucher_table($product_id);

	wp_die();
}

function taste_ajax_redeem_voucher() {

	if (!check_ajax_referer('taste-venue-nonce','security', false)) {
		echo '<h2>Security error loading data.  <br>Please Refresh the page and try again.</h2>';
		wp_die();
	}

	if (!isset($_POST['order_list']) || !isset($_POST['product_info']) || !isset($_POST['venue_info'])) {
		echo 'Missing valid order list or product / venue info';
		wp_die();
	}

	$order_list = $_POST['order_list'];
	$product_info = $_POST['product_info'];
	$venue_info = $_POST['venue_info'];

	require_once(plugin_dir_path(__FILE__). 'redeem_voucher.php');
	redeem_voucher_update($order_list, $product_info, $venue_info);

	wp_die();
}

function taste_ajax_make_payment() {

	if (!check_ajax_referer('taste-venue-nonce','security', false)) {
		echo '<h2>Security error loading data.  <br>Please Refresh the page and try again.</h2>';
		wp_die();
	}

	if (!isset($_POST['map_amount']) || !isset($_POST['product_info']) || !isset($_POST['venue_info'])) {
		echo 'Missing valid payment amount or product / venue info';
		wp_die();
	}

	$map_amount = $_POST['map_amount'];
	$product_info = $_POST['product_info'];
	$venue_info = $_POST['venue_info'];

	require_once(plugin_dir_path(__FILE__). 'make_payment.php');
	make_payment_update($map_amount, $product_info, $venue_info);

	wp_die();
}

if ( is_admin() ) {
	add_action('wp_ajax_load_vouchers','taste_ajax_load_vouchers');
	add_action('wp_ajax_redeem_voucher','taste_ajax_redeem_voucher');
	add_action('wp_ajax_make_payment','taste_ajax_make_payment');
}