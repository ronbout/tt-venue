<?php 

defined('ABSPATH') or die('Direct script access disallowed.');

function taste_ajax_outstanding_load_vouchers() {

	if (!check_ajax_referer('taste-venue-nonce','security', false)) {
		echo '<h2>Security error loading data.  <br>Please Refresh the page and try again.</h2>';
		wp_die();
	}

	if (!isset($_POST['product_ids']) || !isset($_POST['order_columns'])) {
		echo 'No valid product id or missing order columns';
		wp_die();
	}
	$product_ids = $_POST['product_ids'];
	$disp_order_cols = $_POST['order_columns'];

	require_once(plugin_dir_path(__FILE__). 'outstanding-vouchers-list.php');
	display_voucher_table($product_ids, $disp_order_cols);

	wp_die();
}

function taste_ajax_outstanding_load_products() {

	if (!check_ajax_referer('taste-venue-nonce','security', false)) {
		echo '<h2>Security error loading data.  <br>Please Refresh the page and try again.</h2>';
		wp_die();
	}

	if (!isset($_POST['filterData'])) {
		echo 'Missing filter data';
		wp_die();
	}
	$filter_data = $_POST['filterData'];

	require_once(plugin_dir_path(__FILE__). 'outstanding-products.php');
	outstanding_display_product_table($filter_data);

	wp_die();
}



if ( is_admin() ) {
	add_action('wp_ajax_outstanding_load_vouchers','taste_ajax_outstanding_load_vouchers');
	add_action('wp_ajax_outstanding_load_products','taste_ajax_outstanding_load_products');
}