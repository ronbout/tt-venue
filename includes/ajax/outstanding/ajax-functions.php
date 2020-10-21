<?php 

defined('ABSPATH') or die('Direct script access disallowed.');

function taste_ajax_outstanding_load_vouchers() {

	if (!check_ajax_referer('taste-venue-nonce','security', false)) {
		echo '<h2>Security error loading data.  <br>Please Refresh the page and try again.</h2>';
		wp_die();
	}

	if (!isset($_POST['product_id'])) {
		echo 'No valid product id';
		wp_die();
	}
	$product_id = $_POST['product_id'];
	$multiplier = $_POST['multiplier'];

	require_once(plugin_dir_path(__FILE__). 'outstanding-voucher-table.php');
	display_voucher_table($product_id, $multiplier);

	wp_die();
}

function taste_ajax_outstanding_load_products() {

	if (!check_ajax_referer('taste-venue-nonce','security', false)) {
		echo '<h2>Security error loading data.  <br>Please Refresh the page and try again.</h2>';
		wp_die();
	}

	if (!isset($_POST['year_select']) || !isset($_POST['year_type'])) {
		echo 'Missing valid year information';
		wp_die();
	}
	$year = $_POST['year_select'];
	$year_type = $_POST['year_type'];

	require_once(plugin_dir_path(__FILE__). 'outstanding-products.php');
	outstanding_display_product_table($year, $year_type);

	wp_die();
}



if ( is_admin() ) {
	add_action('wp_ajax_outstanding_load_vouchers','taste_ajax_outstanding_load_vouchers');
	add_action('wp_ajax_outstanding_load_products','taste_ajax_outstanding_load_products');
}