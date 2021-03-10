<?php

defined('ABSPATH') or die('Direct script access disallowed.');

function make_payment_update($payment_info, $product_info, $venue_info) {
	global $wpdb;

	$user = wp_get_current_user();
	$role = $user->roles[0];
	$admin = ('ADMINISTRATOR' === strtoupper($role));
	
	$product_id = $product_info['product_id'];

	$payment_id = $payment_info['id'];
	$payment_amount = $payment_info['amount'];
	$payment_orig_amount = $payment_info['payment_orig_amt'];
	$payment_date = $payment_info['timestamp'];
	$payment_comment = $payment_info['comment'];

	$delete_mode = 'true' === $payment_info['delete_mode'];

	$table = "{$wpdb->prefix}offer_payments";
	$data = array(
		'pid' => $product_id,
		'timestamp' => $payment_date,
		'amount' => $payment_amount,
		'comment' => $payment_comment
	);
	
	$format = array('%d', '%s', '%f', '%s');

	if ($delete_mode) {
		$edit_mode = 'delete';
		$where = array('id' => $payment_id);
		$where_format = array('%d');
		$rows_affected = $wpdb->delete($table, $where, $where_format);

		$payment_diff = - $payment_amount;
	} elseif ($payment_id) {
		$edit_mode = 'edit';

		$where = array('id' => $payment_id);
		$where_format = array('%d');
		$rows_affected = $wpdb->update($table, $data, $where, $format, $where_format);

		$payment_diff = $payment_amount  - $payment_orig_amount;
	} else {
		$edit_mode = 'add';

		$rows_affected = $wpdb->insert($table, $data, $format);	
		$payment_id = $wpdb->insert_id;
		
		$payment_info['id'] = $payment_id;
		$payment_diff = $payment_amount;
	}

	// if not success set error array and return
	if (!$rows_affected) {
		$ret_json = array('error' => 'Could not update database.');
		echo wp_json_encode($ret_json);
		return;
	}
	
	$currency = get_woocommerce_currency_symbol();

	// update calcs.  some calcs are necessary because all 
	// values must be passed back for hidden section
	$redeem_qty = $product_info['redeem_qty'];
	$gr_value = $product_info['gr_value'];
	$commission_value = $product_info['commission_value'];
	$vat_value = $product_info['vat_value'];
	$total_sold = $product_info['total_sold'];
	$total_paid = $product_info['total_paid'] + $payment_diff;
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

	// need to add payment id to payment info as 
	// All Payments line requires Product ID
	$payment_info['product_id'] = $product_id;
	$payment_line = 'delete' === $edit_mode ? '' : disp_payment_line($payment_info, $admin, $commission_value);
	$all_payment_line = 'delete' === $edit_mode ? '' : disp_all_payment_line($payment_info);

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
	$sum_total_paid = $venue_info['paid_amount'] + $payment_diff;
	$sum_balance_due = $venue_info['balance_due'] - $payment_diff;
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
		'totalPaid' => $currency . ' ' . num_display($total_paid),
		'sumGrValue' => $currency . ' ' . num_display($sum_gr_value),
		'sumCommission' => $currency . ' ' . num_display($sum_commission),
		'sumVat'  => $currency . ' ' . num_display($sum_vat),
		'sumRedeemedQty' => $sum_redeemed_qty,
		'sumNumServed' => $sum_num_served,
		'sumNetPayable' => $currency . ' ' . num_display($sum_net_payable),
		'sumTotalPaid' => $currency . ' ' . num_display($sum_total_paid),
		'sumBalanceDue' => $currency . ' ' . num_display($sum_balance_due),
		'paymentLine' => $payment_line,
		'allPaymentLine' => $all_payment_line,
		'editMode' => $edit_mode,
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
