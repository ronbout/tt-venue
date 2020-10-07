<?php 
/**
 * 
 *  Redeem a voucher, updating the db and recalc'ing totals
 *  9/15/2020  Ron Boutilier
 * 
 *  Add Unredeem to code 
 *  9/21/2020  Ron Boutilier
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

function redeem_voucher_update($order_list, $product_info, $venue_info, $redeem_flg) {
	global $wpdb;

	$order_item_list = array_column($order_list, 'orderItemId');
	$placeholders = array_fill(0, count($order_item_list), '%s');
	$placeholders = implode(', ', $placeholders);

	// update the database with multiple rows if necessary
	$sql = "UPDATE " . $wpdb->prefix . "woocommerce_order_items
					SET downloaded = '$redeem_flg' where order_item_id in ($placeholders) ";

	$rows_affected = $wpdb->query(
		$wpdb->prepare($sql, $order_item_list)
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
	
	if ($redeem_flg) {
		$email_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id AS orderId, meta_value as email
				FROM " . $wpdb->prefix . "postmeta
				WHERE post_id in ($placeholders)
				AND meta_key = '_billing_email'", $order_id_list
			), ARRAY_A);
	} else {
		$email_rows = array(array("orderId" => $order_id_list[0], "email" => "*** GDPR BLANKED EMAIL ***"));
	}


	// update the redeem amount and recalc as necessary
	$order_qty_list = array_column($order_list, 'orderQty');
	// have to know qty increase to apply to total amounts across products
	$orig_redeem_qty = $product_info['redeem_qty'];
	$redeem_qty = array_reduce($order_qty_list, function ($r, $qty ) use ($redeem_flg) {
		$ret_qty = $redeem_flg ? ($r + $qty) : ($r - $qty);
		return $ret_qty;
	}, $product_info['redeem_qty']);
	$order_cnt = count($order_qty_list);
	$cnt_increase = $redeem_flg ?  -1 * ($order_cnt)  : $order_cnt;
	
	$product_id = $product_info['product_id'];
	$gr_value = $product_info['gr_value'];
	$commission_value = $product_info['commission_value'];
	$vat_value = $product_info['vat_value'];
	$total_sold = $product_info['total_sold'];
	$total_paid = $product_info['total_paid'];

	// $redeem_qty += $order_qty;
	$grevenue = $redeem_qty * $gr_value; 
	$commission = ($grevenue / 100) * $commission_value;
	$vat = ($commission / 100) * $vat_value;
	$payable = $grevenue - ($commission + $vat);
	$balance_due = $payable - $total_paid;
	// for summary section, just adjust based on increase/decrease
	$qty_increase = $redeem_qty - $orig_redeem_qty;
	$revenue_increase = $qty_increase * $gr_value;
	$commission_increase = ($revenue_increase / 100) * $commission_value;
	$vat_increase = ($commission_increase / 100) * $vat_value;
	$payable_increase = $revenue_increase - ($commission_increase + $vat_increase);
	$balance_due_increase = $payable_increase ;

	$grevenue = round($grevenue, 2);
	$commission = round($commission, 2);
	$vat = round($vat, 2);
	$payable = round($payable, 2);
	$balance_due = round($balance_due, 2);

	$currency = get_woocommerce_currency_symbol();

	$hidden_values = "
	<input type='hidden' id='taste-product-id' value='$product_id'>
	<input type='hidden' id='taste-gr-value' value='$gr_value'>
	<input type='hidden' id='taste-commission-value' value='$commission_value'>
	<input type='hidden' id='taste-vat-value' value='$vat_value'>
	<input type='hidden' id='taste-redeem-qty' value='$redeem_qty'>
	<input type='hidden' id='taste-total-sold' value='$total_sold'>
	<input type='hidden' id='taste-total-paid' value='$total_paid'>
	";

	// make adjustments for the totals in the summary section
	$sum_gr_value = $venue_info['revenue'] + $revenue_increase;
	$sum_commission = $venue_info['commission'] + $commission_increase;
	$sum_vat = $venue_info['vat'] + $vat_increase;
	$sum_redeemed_cnt = $venue_info['redeemed_cnt'] + $cnt_increase;
	$sum_redeemed_qty = $venue_info['redeemed_qty'] + $qty_increase;
	$sum_net_payable = $venue_info['net_payable'] + $payable_increase;
	$sum_total_paid = $venue_info['paid_amount'];
	$sum_balance_due = $venue_info['balance_due'] + $balance_due_increase;
	$multiplier = $venue_info['multiplier'];
	
	$sum_hidden_values = "
	<input type='hidden' id='sum-gr-value' value='$sum_gr_value'>
	<input type='hidden' id='sum-commission' value='$sum_commission'>
	<input type='hidden' id='sum-vat' value='$sum_vat'>
	<input type='hidden' id='sum-redeemed-cnt' value='$sum_redeemed_cnt'>
	<input type='hidden' id='sum-redeemed-qty' value='$sum_redeemed_qty'>
	<input type='hidden' id='sum-net-payable' value='$sum_net_payable'>
	<input type='hidden' id='sum-total-paid' value='$sum_total_paid'>
	<input type='hidden' id='sum-balance-due' value='$sum_balance_due'>
	<input type='hidden' id='sum-multiplier' value='$multiplier'>
	";

	$ret_json = array(
		'redeemQty' => $redeem_qty,
		'totalSold' => $total_sold,
		'grevenue' => $currency . ' ' . number_format($grevenue, 2),
		'commission' => $currency . ' ' . number_format($commission, 2),
		'vat' => $currency . ' ' .number_format($vat, 2),
		'payable' => $currency . ' ' . number_format($payable, 2),
		'balanceDue' => $currency . ' ' . number_format($balance_due, 2),
		'emails' => $email_rows,
		'sumGrValue' => $currency . ' ' . num_display($sum_gr_value),
		'sumCommission' => $currency . ' ' . num_display($sum_commission),
		'sumVat'  => $currency . ' ' . num_display($sum_vat),
		'sumRedeemedQty' => $sum_redeemed_qty,
		'sumNetPayable' => $currency . ' ' . num_display($sum_net_payable),
		'sumTotalPaid' => $currency . ' ' . num_display($sum_total_paid),
		'sumBalanceDue' => $currency . ' ' . num_display($sum_balance_due),
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