<?php 

function redeem_voucher_update($order_list, $product_info) {
	global $wpdb;

	$order_item_list = array_column($order_list, 'orderItemId');
	$placeholders = array_fill(0, count($order_item_list), '%s');
	$placeholders = implode(', ', $placeholders);

	// update the database with multiple rows if necessary
	$rows_affected = $wpdb->query(
		$wpdb->prepare(
				"UPDATE " . $wpdb->prefix . "woocommerce_order_items
				SET downloaded = '1' where order_item_id in ($placeholders) ",$order_item_list
		)
	);

	// if not success set error array and return
	if (!$rows_affected) {
		$ret_json = array('error' => 'Could not update database.');
		echo wp_json_encode($ret_json);
		return;
	}

	$order_id_list = array_column($order_list, 'orderId');
	$placeholders = array_fill(0, count($order_id_list), '%s');
	$placeholders = implode(', ', $placeholders);
	
	$email_rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT post_id AS orderId, meta_value as email
			FROM " . $wpdb->prefix . "postmeta
			WHERE post_id in ($placeholders)
			AND meta_key = '_billing_email'", $order_id_list
		), ARRAY_A);

	// update the redeem amount and recalc as necessary
	$order_qty_list = array_column($order_list, 'orderQty');
	$redeem = array_reduce($order_qty_list, function ($r, $qty ) {
		return $r + $qty;
	}, $product_info['redeem']);

	$product_id = $product_info['product_id'];
	$gr_value = $product_info['gr_value'];
	$commission_value = $product_info['commission_value'];
	$vat_value = $product_info['vat_value'];
	$total = $product_info['total'];
	$total_paid = $product_info['total_paid'];

	$redeem += $order_qty;
	$grevenue = $redeem * $gr_value; 
	$commission = ($grevenue / 100) * $commission_value;
	$vat = ($commission / 100) * $vat_value;
	$payable = $grevenue - ($commission + $vat);
	$balance_due = $payable - $total_paid;

	$grevenue = round($grevenue, 2);
	$commission = round($commission, 2);
	$vat = round($vat, 2);
	$payable = round($payable, 2);
	$balance_due = round($balance_due, 2);

	$currency_symbol = get_woocommerce_currency_symbol();

	$hidden_values = "
	<input type='hidden' id='taste-product-id' value='$product_id'>
	<input type='hidden' id='taste-gr-value' value='$gr_value'>
	<input type='hidden' id='taste-commission-value' value='$commission_value'>
	<input type='hidden' id='taste-vat-value' value='$vat_value'>
	<input type='hidden' id='taste-redeem' value='$redeem'>
	<input type='hidden' id='taste-total' value='$total'>
	<input type='hidden' id='taste-total-paid' value='$total_paid'>
	";

	$ret_json = array(
		'redeem' => $redeem,
		'total' => $total,
		'grevenue' => get_woocommerce_currency_symbol() . ' ' . number_format($grevenue, 2),
		'commission' => get_woocommerce_currency_symbol() . ' ' . number_format($commission, 2),
		'vat' => get_woocommerce_currency_symbol() . ' ' .number_format($vat, 2),
		'payable' => get_woocommerce_currency_symbol() . ' ' . number_format($payable, 2),
		'balanceDue' => get_woocommerce_currency_symbol() . ' ' . number_format($balance_due, 2),
		'emails' => $email_rows,
		'hiddenValues' => $hidden_values
);

	echo wp_json_encode($ret_json);
	return;
}