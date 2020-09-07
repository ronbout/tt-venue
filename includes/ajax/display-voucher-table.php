<?php 

function display_voucher_table($product_id) {
	global $wpdb;

	$tproduct = 0;
	$redeem = 0;
	$total = 0;
	$myrows1 = $wpdb->get_results($wpdb->prepare("SELECT p.post_title, p.id
		FROM " . $wpdb->prefix . "posts p
		WHERE p.post_type = 'product'
		AND p.post_status = 'publish'
		AND p.id = %d", $product_id));

	if (count($myrows1) > 0) {
		require_once(plugin_dir_path(__FILE__). 'redeem-vouchers-list.php');
	}	else {
		echo  '<div class="alert alert-danger" role="alert">
				<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
				<span class="sr-only">TheTaste Error -</span> Values entered do not match in our system
				</div>';
	}
}
