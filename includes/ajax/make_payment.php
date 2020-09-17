<?php

defined('ABSPATH') or die('Direct script access disallowed.');

function make_payment_update($map_amount, $product_info, $venue_info) {
	global $wpdb;
	
	$product_id = $product_info['product_id'];

	// update the database 
	$table = "{$wpdb->prefix}offer_payments";
	$data = array(
		'pid' => $product_id,
		'amount' => $map_amount
	);
	$format = array('%d', '%d');

	$rows_affected = $wpdb->insert($table, $data, $format);

	// if not success set error array and return
	if (!$rows_affected) {
		$ret_json = array('error' => 'Could not update database.');
		echo wp_json_encode($ret_json);
		return;
	}
	
	$currency = get_woocommerce_currency_symbol();
	
	// create new payment line to display
	// to be accurate, get timestamp from inserted payment
	$payment_id = $wpdb->insert_id;
	$pay_row = $wpdb->get_results($wpdb->prepare("
		SELECT timestamp FROM $table WHERE id = %d
	", $payment_id), ARRAY_A);
	$payment_line = "
		<tr>
			<td>{$pay_row[0]['timestamp']}</td>
			<td>$currency " . num_display($map_amount) . "</td>
		</tr>
	";

	// update calcs.  some calcs are necessary because all 
	// values must be passed back for hidden section
	$redeem = $product_info['redeem'];
	$gr_value = $product_info['gr_value'];
	$commission_value = $product_info['commission_value'];
	$vat_value = $product_info['vat_value'];
	$total_sold = $product_info['total_sold'];
	$total_paid = $product_info['total_paid'] + $map_amount;

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

	$hidden_values = "
	<input type='hidden' id='taste-product-id' value='$product_id'>
	<input type='hidden' id='taste-gr-value' value='$gr_value'>
	<input type='hidden' id='taste-commission-value' value='$commission_value'>
	<input type='hidden' id='taste-vat-value' value='$vat_value'>
	<input type='hidden' id='taste-redeem' value='$redeem'>
	<input type='hidden' id='taste-total-sold' value='$total_sold'>
	<input type='hidden' id='taste-total-paid' value='$total_paid'>
	";
	
	// make adjustments for the totals in the summary section
	$sum_gr_value = $venue_info['revenue'];
	$sum_commission = $venue_info['commission'];
	$sum_vat = $venue_info['vat'];
	$sum_redeemed = $venue_info['redeemed'];
	$sum_net_payable = $venue_info['net_payable'];
	$sum_total_paid = $venue_info['paid_amount'] + $map_amount;
	$sum_balance_due = $venue_info['balance_due'] - $map_amount;

	$sum_hidden_values = "
	<input type='hidden' id='sum-gr-value' value='$sum_gr_value'>
	<input type='hidden' id='sum-commission' value='$sum_commission'>
	<input type='hidden' id='sum-vat' value='$sum_vat'>
	<input type='hidden' id='sum-redeemed' value='$sum_redeemed'>
	<input type='hidden' id='sum-net-payable' value='$sum_net_payable'>
	<input type='hidden' id='sum-total-paid' value='$sum_total_paid'>
	<input type='hidden' id='sum-balance-due' value='$sum_balance_due'>
	";

	$ret_json = array(
		'balanceDue' => $currency . ' ' . num_display($balance_due),
		'sumGrValue' => $currency . ' ' . num_display($sum_gr_value),
		'sumCommision' => $currency . ' ' . num_display($sum_commission),
		'sumVat'  => $currency . ' ' . num_display($sum_vat),
		'sumRedeemed' => $currency . ' ' . num_display($sum_redeemed),
		'sumNetPayable' => $currency . ' ' . num_display($sum_net_payable),
		'sumTotalPaid' => $currency . ' ' . num_display($sum_total_paid),
		'sumBalanceDue' => $currency . ' ' . num_display($sum_balance_due),
		'paymentLine' => $payment_line,
		'hiddenValues' => $hidden_values,
		'sumHiddenValues' => $sum_hidden_values
);

	echo wp_json_encode($ret_json);
	return;
}

function num_display ($num) {
	// display number with 2 decimal rounding and formatting
	return number_format(round($num,2), 2);
}