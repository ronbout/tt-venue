<?php 

defined('ABSPATH') or die('Direct script access disallowed.');

require_once(plugin_dir_path(__FILE__). 'functions.php');

function taste_ajax_load_vouchers() {

	if (!check_ajax_referer('taste-venue-nonce','security', false)) {
		echo '<h2>Security error loading data.  <br>Please Refresh the page and try again.</h2>';
		wp_die();
	}

	if (!isset($_POST['product_id'])) {
		echo 'No valid product id';
		wp_die();
	}
	
	if (!isset($_POST['cutoff_date'])) {
		echo 'Venue Historical Cutoff Date is required';
		wp_die();
	}

	$product_id = $_POST['product_id'];
	$multiplier = $_POST['multiplier'];
	$cutoff_date = $_POST['cutoff_date'];
	$venue_view = isset($_POST['venue_view']) ? $_POST['venue_view'] : 0;
	$order_payments_checklist = isset($_POST['order_payments_checklist']) ? $_POST['order_payments_checklist'] : array();
	$edit_payment_id = $_POST['edit_payment_id'];

	require_once(plugin_dir_path(__FILE__). 'redeem-vouchers-list.php');
	display_voucher_table($product_id, $multiplier, $cutoff_date, $make_payments_below, $order_payments_checklist, $edit_payment_id, $venue_view);

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
	$redeem_flg = $_POST['redeem_flg'];

	require_once(plugin_dir_path(__FILE__). 'redeem_voucher.php');
	redeem_voucher_update($order_list, $product_info, $venue_info, $redeem_flg);

	wp_die();
}

function taste_ajax_redeem_voucher_mini() {

	if (!check_ajax_referer('taste-venue-nonce','security', false)) {
		echo '<h2>Security error loading data.  <br>Please Refresh the page and try again.</h2>';
		wp_die();
	}

	if (!isset($_POST['order_item_id'])) {
		echo 'Missing valid order item id';
		wp_die();
	}

	$order_item_id = $_POST['order_item_id'];

	require_once(plugin_dir_path(__FILE__). 'redeem_voucher_mini.php');
	redeem_voucher_update_mini($order_item_id);

	wp_die();
}

function taste_ajax_make_payment() {

	if (!check_ajax_referer('taste-venue-nonce','security', false)) {
		echo '<h2>Security error loading data.  <br>Please Refresh the page and try again.</h2>';
		wp_die();
	}

	if (!isset($_POST['payment_info']) || !isset($_POST['product_info'])  || !isset($_POST['venue_info'])) {
		echo 'Missing valid payment amount or product / venue info';
		wp_die();
	}

	$payment_info = $_POST['payment_info'];
	$product_info = $_POST['product_info'];
	$cur_prod_info = isset($_POST['cur_prod_info']) ? $_POST['cur_prod_info'] : array();
	$venue_info = $_POST['venue_info'];

	require_once(plugin_dir_path(__FILE__). 'make_payment.php');
	make_payment_update($payment_info, $product_info, $cur_prod_info, $venue_info);

	wp_die();
}

function taste_ajax_retrieve_payment_json() {
	if (!check_ajax_referer('taste-venue-nonce','security', false)) {
		echo '<h2>Security error loading data.  <br>Please Refresh the page and try again.</h2>';
		wp_die();
	}

	if (!isset($_POST['payment_id']) || !isset($_POST['product_info'])  ) {
		echo 'Missing valid payment id or product info';
		wp_die();
	}

	$payment_id = $_POST['payment_id'];
	$product_info = $_POST['product_info'];

	require_once(plugin_dir_path(__FILE__). 'retrieve-payment-orders-json.php');
	retrieve_payment_orders_info_json($payment_id, $product_info);

	wp_die();
} 

function taste_ajax_retrieve_historical_payments_json() {
	if (!check_ajax_referer('taste-venue-nonce','security', false)) {
		echo '<h2>Security error loading data.  <br>Please Refresh the page and try again.</h2>';
		wp_die();
	}

	if (!isset($_POST['venue_id'])  || !isset($_POST['product_info'])  ) {
		echo 'Missing valid Venue id info';
		wp_die();
	}

	$venue_id = $_POST['venue_id'];
	$product_info = $_POST['product_info'];

	require_once(plugin_dir_path(__FILE__). 'retrieve-historical-payments-json.php');
	retrieve_historical_payments_json($venue_id, $product_info);

	wp_die();
} 

if ( is_admin() ) {
	add_action('wp_ajax_load_vouchers','taste_ajax_load_vouchers');
	add_action('wp_ajax_redeem_voucher','taste_ajax_redeem_voucher');
	add_action('wp_ajax_redeem_voucher_mini','taste_ajax_redeem_voucher_mini');
	add_action('wp_ajax_make_payment','taste_ajax_make_payment');
	add_action('wp_ajax_retrieve_payment_json','taste_ajax_retrieve_payment_json');
	add_action('wp_ajax_retrieve_historical_payments_json','taste_ajax_retrieve_historical_payments_json');
}