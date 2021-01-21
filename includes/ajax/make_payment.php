<?php

defined('ABSPATH') or die('Direct script access disallowed.');

function make_payment_update($payment_amount, $product_info, $venue_info) {
	global $wpdb;
	
	$product_id = $product_info['product_id'];

	// update the database 
	$table = "{$wpdb->prefix}offer_payments";
	$data = array(
		'pid' => $product_id,
		'amount' => $payment_amount
	);
	$format = array('%d', '%f');

	$rows_affected = $wpdb->insert($table, $data, $format);

	// if not success set error array and return
	if (!$rows_affected) {
		$ret_json = array('error' => 'Could not update database.');
		echo wp_json_encode($ret_json);
		return;
	}
		
	// update calcs.  some calcs are necessary because all 
	// values must be passed back for hidden section
	$redeem_qty = $product_info['redeem_qty'];
	$gr_value = $product_info['gr_value'];
	$commission_value = $product_info['commission_value'];
	$vat_value = $product_info['vat_value'];
	$total_sold = $product_info['total_sold'];
	$total_paid = $product_info['total_paid'] + $payment_amount;
	$multiplier = $product_info['multiplier'];
	
	// $redeem_qty += $order_qty;
	$grevenue = $redeem_qty * $gr_value; 
	$commission = ($grevenue / 100) * $commission_value;
	$vat = ($commission / 100) * $vat_value;
	$payable = $grevenue - ($commission + $vat);
	$balance_due = $payable - $total_paid;
	$num_served = $redeem_qty * $multiplier;

	$grevenue = round($grevenue, 2);
	$commission = round($commission, 2);
	$vat = round($vat, 2);
	$payable = round($payable, 2);
	$balance_due = round($balance_due, 2);
	
	$currency = get_woocommerce_currency_symbol();
	$pay_calcs = comm_vat_per_payment($payment_amount, $commission_value, $vat_value);
	
	// create new payment line to display
	// to be accurate, get timestamp from inserted payment
	$payment_id = $wpdb->insert_id;
	$pay_row = $wpdb->get_results($wpdb->prepare("
		SELECT timestamp FROM $table WHERE id = %d
	", $payment_id), ARRAY_A);
	$payment_line = "
		<tr>
			<td>$payment_id</td>
			<td>{$pay_row[0]['timestamp']}</td>
			<td>$currency " . num_display($payment_amount) . "</td>
			<td>
			<button	data-paymentamt='$payment_amount'
			data-comm='{$pay_calcs['pay_comm']}' data-vat='{$pay_calcs['pay_vat']}'
			class='btn btn-info print-invoice-btn'>
				View/Print
			</button>
			</td>
		</tr>
	";




	$hidden_values = "
	<input type='hidden' id='taste-product-id' value='$product_id'>
	<input type='hidden' id='taste-product-multiplier' value='$multiplier'>
	<input type='hidden' id='taste-gr-value' value='$gr_value'>
	<input type='hidden' id='taste-commission-value' value='$commission_value'>
	<input type='hidden' id='taste-vat-value' value='$vat_value'>
	<input type='hidden' id='taste-redeem-qty' value='$redeem_qty'>
	<input type='hidden' id='taste-total-sold' value='$total_sold'>
	<input type='hidden' id='taste-total-paid' value='$total_paid'>
	";
	
	// make adjustments for the totals in the summary section
	$sum_gr_value = $venue_info['revenue'];
	$sum_commission = $venue_info['commission'];
	$sum_vat = $venue_info['vat'];
	$sum_redeemed_cnt = $venue_info['redeemed_cnt'];
	$sum_redeemed_qty = $venue_info['redeemed_qty'];
	$sum_num_served = $venue_info['num_served'];
	$sum_net_payable = $venue_info['net_payable'];
	$sum_total_paid = $venue_info['paid_amount'] + $payment_amount;
	$sum_balance_due = $venue_info['balance_due'] - $payment_amount;
	$multiplier = $venue_info['multiplier'];

	$sum_hidden_values = "
	<input type='hidden' id='sum-gr-value' value='$sum_gr_value'>
	<input type='hidden' id='sum-commission' value='$sum_commission'>
	<input type='hidden' id='sum-vat' value='$sum_vat'>
	<input type='hidden' id='sum-redeemed-cnt' value='$sum_redeemed_cnt'>
	<input type='hidden' id='sum-redeemed-qty' value='$sum_redeemed_qty'>
	<input type='hidden' id='sum-num-served' value='$sum_num_served'>
	<input type='hidden' id='sum-net-payable' value='$sum_net_payable'>
	<input type='hidden' id='sum-total-paid' value='$sum_total_paid'>
	<input type='hidden' id='sum-balance-due' value='$sum_balance_due'>
	<input type='hidden' id='sum-multiplier' value='$multiplier'>
	";

	$ret_json = array(
		'balanceDue' => $currency . ' ' . num_display($balance_due),
		'sumGrValue' => $currency . ' ' . num_display($sum_gr_value),
		'sumCommission' => $currency . ' ' . num_display($sum_commission),
		'sumVat'  => $currency . ' ' . num_display($sum_vat),
		'sumRedeemedQty' => $sum_redeemed_qty,
		'sumNumServed' => $sum_num_served,
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

function comm_vat_per_payment($payment, $commission_val, $vat_val) {
	$comm_pct = $commission_val / 100;
	$vat_pct = $vat_val / 100;
	$gross = $payment / (1 - $comm_pct - ($comm_pct * $vat_pct));
	$commission = round($gross * $comm_pct, 2);
	$vat = round($commission * $vat_pct, 2);
	return array(
		'pay_gross' => $gross,
		'pay_comm' => $commission,
		'pay_vat' => $vat,
	);
}