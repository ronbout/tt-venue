<?php

defined('ABSPATH') or die('Direct script access disallowed.');

function make_payment_update($payment_info, $product_info, $cur_prod_info, $venue_info) {
	global $wpdb;

	$user = wp_get_current_user();
	$role = $user->roles[0];
	$user_id = get_current_user_id();
	$admin = ('ADMINISTRATOR' === strtoupper($role));
	
	$orders_flag = $payment_info['orders_flag'];
	$payment_id = $payment_info['id'];
	$payment_amount = $payment_info['amount'];
	$payment_orig_amount = $payment_info['payment_orig_amt'];
	$payment_date = $payment_info['timestamp'];
	$payment_orig_date = $payment_info['payment_orig_date'];
	$payment_comment = $payment_info['comment'];
	$comment_visible_venues = $payment_info['comment_visible_venues'];
	$attach_vat_invoice = $payment_info['attach_vat_invoice'];
	$all_payment_cnt = $payment_info['all_payment_cnt'];
	$prod_payment_cnt = $payment_info['prod_payment_cnt'];

	$product_order_list = json_decode(html_entity_decode(stripslashes ($payment_info['product_order_list'])), true);
	$product_order_info = [];
	foreach ($product_order_list as $prod_orders) {
		$product_order_info[$prod_orders[0]] = array(
			'amount' => $prod_orders[1]['netPayable'],
			'order_qty' => $prod_orders[1]['orderQty'],
			'order_list' => $prod_orders[1]['orderItemList'],
		);
	}

	$delete_mode = 'true' === $payment_info['delete_mode'];

	$payment_db_parms = array(
		'payment_table' => $wpdb->prefix."taste_venue_payment",
		'payment_products_table' => $wpdb->prefix."taste_venue_payment_products",
		'payment_order_xref_table' => $wpdb->prefix."taste_venue_payment_order_item_xref",
		'data_fields' => array(
			'payment_date' => $payment_date,
			'payment_amount' => $payment_amount,
			'comment' => $payment_comment,
			'comment_visible_venues' => $comment_visible_venues,
			'attach_vat_invoice' => $attach_vat_invoice,
			'venue_id' => $venue_info['venue_id'],
			'product_order_info' => $product_order_info,
			'orders_flag' => $orders_flag,
		),
	);

	if ($delete_mode) {
		$ret_json = array('error' => 'Deleting a Payment is currently in development');
		echo wp_json_encode($ret_json);
		return;
		$edit_mode = 'DELETE';
		$db_status = delete_payment($payment_db_parms, $payment_id);
		if (!$db_status) {
			return;
		}
		$prod_payment_cnt -= 1;
		$all_payment_cnt -= 1;

		$payment_diff = - $payment_amount;
	} elseif ($payment_id) {
		$ret_json = array('error' => 'Updating a Payment is currently in development');
		echo wp_json_encode($ret_json);
		return;
		$edit_mode = 'UPDATE';

		$db_status = update_payment($payment_db_parms, $payment_id);
		if (!$db_status) {
			return;
		}

		$payment_diff = $payment_amount  - $payment_orig_amount;
	} else {
		$edit_mode = 'INSERT';

		$db_insert_result = insert_payment($payment_db_parms);
		$db_status = $db_insert_result['db_status'];
		if (!$db_status) {
			return;
		}
		$payment_id =$db_insert_result['payment_id'];
		$prod_payment_cnt += 1;
		$all_payment_cnt += 1;
		
		$payment_info['id'] = $payment_id;
		$payment_diff = $payment_amount;
	}

	/*****  AUDIT TABLE UPDATE ******/
	$payment_audit_table = $wpdb->prefix ."taste_venue_payment_audit";
	$user_id = get_current_user_id();

	$data = array(
		'payment_id' => $payment_id,
		'prev_payment_timestamp' => "INSERT" === $edit_mode ? NULL : $payment_orig_date,
		'payment_timestamp' => $payment_date,
		'user_id' => $user_id,
		'action' => $edit_mode,
		'prev_amount' => "INSERT" === $edit_mode ? NULL : $payment_orig_amount,
		'amount' => "DELETE" === $edit_mode ? NULL : $payment_amount,
		'comment' => $payment_comment
	);
	
	$format = array('%d', '%s', '%s', '%d', '%s', '%f', '%f', '%s');

	$rows_affected = $wpdb->insert($payment_audit_table, $data, $format);	


	// if not success set error array and return
	if (!$rows_affected) {
		$ret_json = array('error' => 'Could not update payment audit table. \n' . $wpdb->last_error);
		echo wp_json_encode($ret_json);
		return;
	}

	$currency = get_woocommerce_currency_symbol();

	/***
	 * 
	 * this needs to be done for all products in a loop
	 * multiple data rows will need to be passed back
	 * 
	 * 
	 */


	// later code may not have a currently selected product, 
	// or  it may have a product w/ no selected orders
	// so test to be sure ("pay all orders" command, for instance)
	$total_paid = 0;
	$balance_due = 0;
	$payment_line = '';
	$update_cur_prod = 0;
	if (count($cur_prod_info)) {
		$product_id = array_keys($cur_prod_info)[0];
		$cur_prod_info = $cur_prod_info[$product_id];
		if (in_array($product_id, array_keys($product_order_info))) {
			$update_cur_prod = 1;
		// need to get the payment amount for the displayed product only
		$cur_payment_diff = $product_order_info[$product_id]['amount'];
		
		$total_paid = $cur_prod_info['total_paid'] + $cur_payment_diff;
		$balance_due = $cur_prod_info['balance_due'] - $cur_payment_diff;
		// for the payment line at the bottom, where 'amount' needs to 
		// only for that product and total amount is entire payment
		$disp_payment_info = $payment_info;
		$disp_payment_info['product_id'] = $product_id;
		$disp_payment_info['amount'] = $cur_payment_diff;
		$disp_payment_info['total_amount'] = $payment_info['amount'];
	
		$payment_line = 'DELETE' === $edit_mode ? '' : disp_payment_line($disp_payment_info, $admin, $commission_value);
		}
	}
/**
 * 
 * if $orders flag, need to redo this.  Each product ID gets its own line in t
 * the All Payments display.  Only the current Product Id should be used for the
 * regular payment line, if any product is currently open.
 * 
 * Will that come through the product ID field
 * 
 * oh shit!!!!  all the above calcs need to be redone as it only applies to one product
 * 
 * 
 */


	$all_payment_line = 'DELETE' === $edit_mode ? '' : disp_all_payment_line($payment_info);


	$hidden_payment_values = "
	<input type='hidden' id='taste-total-paid' value='$total_paid'>
	";
	
	// make adjustments for the totals in the summary section
	$sum_total_paid = $venue_info['paid_amount'] + $payment_diff;
	$sum_balance_due = $venue_info['balance_due'] - $payment_diff;
	
	$sum_hidden_payment_values = "
	<input type='hidden' id='sum-total-paid' value='$sum_total_paid'>
	<input type='hidden' id='sum-balance-due' value='$sum_balance_due'>
	";


	$ret_json = array(
		'balanceDue' => $currency . ' ' . num_display($balance_due),
		'totalPaid' => $currency . ' ' . num_display($total_paid),
		'sumTotalPaid' => $currency . ' ' . num_display($sum_total_paid),
		'sumBalanceDue' => $currency . ' ' . num_display($sum_balance_due),
		'paymentLine' => $payment_line,
		'allPaymentLine' => $all_payment_line,
		'editMode' => $edit_mode,
		'hiddenPaymentValues' => $hidden_payment_values,
		'sumHiddenPaymentValues' => $sum_hidden_payment_values,
		'allPaymentCnt' => $all_payment_cnt,
		'prodPaymentCnt' => $prod_payment_cnt,
		'updateCurrentProd' => $update_cur_prod,
);

	echo wp_json_encode($ret_json);
	return;
}

function insert_payment ($payment_db_parms) {
	global $wpdb;

	$payment_table = $payment_db_parms['payment_table'];
	$payment_products_table = $payment_db_parms['payment_products_table'];
	$payment_order_xref_table = $payment_db_parms['payment_order_xref_table'];
	$payment_date = $payment_db_parms['data_fields']['payment_date'];
	$payment_amount = $payment_db_parms['data_fields']['payment_amount'];
	$comment = $payment_db_parms['data_fields']['comment'];
	$comment_visible_venues = $payment_db_parms['data_fields']['comment_visible_venues'];
	$attach_vat_invoice = $payment_db_parms['data_fields']['attach_vat_invoice'];
	$venue_id = $payment_db_parms['data_fields']['venue_id'];
	$orders_flag = $payment_db_parms['data_fields']['orders_flag'];
	$product_order_info = $payment_db_parms['data_fields']['product_order_info'];
	
	$wpdb->query( "START TRANSACTION" );

	// main payment table:  wp_taste_venue_payment
	$data = array(
		'payment_date' => $payment_date,
		'venue_id' => $venue_id,
		'amount' => $payment_amount,
		'comment' => $comment,
		'comment_visible_venues' => $comment_visible_venues,
		'attach_vat_invoice' => $attach_vat_invoice,
	);

	$format = array('%s', '%d', '%f', '%s', '%d', '%d');
	$rows_affected = $wpdb->insert($payment_table, $data, $format);	
	// if not success set error array and return
	if (!$rows_affected) {
		$ret_json = array('error' => 'Could not update Payment Table. ' . $wpdb->last_error);
		echo wp_json_encode($ret_json);
		$wpdb->query("ROLLBACK");
		return array('db_status' => false);
	}
	$payment_id = $wpdb->insert_id;

	// payment x product_id table: wp_taste_venue_payment_products
	$insert_values = '';
	$insert_parms = [];
	
	foreach ($product_order_info as $prod_id => $prod_info) {
		$insert_values .= '(%d, %d, %f),';
		$insert_parms[] = $payment_id;
		$insert_parms[] = $prod_id;
		$insert_parms[] = $prod_info['amount'];
	}
	$insert_values = rtrim($insert_values, ',');

	$sql = "INSERT into $payment_products_table
						(payment_id, product_id, amount)
					VALUES $insert_values";

	$rows_affected = $wpdb->query(
		$wpdb->prepare($sql, $insert_parms)
	);
	// if not success set error array and return
	if (!$rows_affected) {
		$ret_json = array('error' => 'Could not update Payment Product Table. ' . $wpdb->last_error);
		echo wp_json_encode($ret_json);
		$wpdb->query("ROLLBACK");
		return array('db_status' => false);
	}

	if ($orders_flag) {
		// payment x orders table: wp_taste_venue_payment_order_item_xref
		$insert_values = '';
		$insert_parms = [];
		
		foreach ($product_order_info as $prod_info) {
			foreach($prod_info['order_list'] as $order_info) {
				$insert_values .= '(%d, %d),';
				$insert_parms[] = $payment_id;
				$insert_parms[] = $order_info['orderItemId'];
			}

		}
		$insert_values = rtrim($insert_values, ',');
		
		$sql = "INSERT into $payment_order_xref_table
							(payment_id, order_item_id)
						VALUES $insert_values";

		$rows_affected = $wpdb->query(
			$wpdb->prepare($sql, $insert_parms)
		);
		// if not success set error array and return
		if (!$rows_affected) {
			$ret_json = array('error' => 'Could not update Payment Order Xref Table. ' . $wpdb->last_error);
			echo wp_json_encode($ret_json);
			$wpdb->query("ROLLBACK");
			return array('db_status' => false);
		}
	}


	$wpdb->query( "COMMIT" );

	return array(
		'db_status' => true,
		'payment_id' => $payment_id,
	);
}

function delete_payment ($payment_db_parms, $payment_id) {
	global $wpdb;

	$payment_table = $payment_db_parms['payment_table'];
	$payment_products_table = $payment_db_parms['payment_products_table'];
	$payment_order_xref_table = $payment_db_parms['payment_order_xref_table'];
	
	$wpdb->query( "START TRANSACTION" );
	$where = array('payment_id' => $payment_id);
	$where_format = array('%d');

	// payment x product_id table: wp_taste_venue_payment_products
	$rows_affected = $wpdb->delete($payment_products_table, $where, $where_format);
	// if not success set error array and return
	if (!$rows_affected) {
		$ret_json = array('error' => 'Could not update Payment Product Table.  ' . $wpdb->last_error);
		echo wp_json_encode($ret_json);
		$wpdb->query("ROLLBACK");
		return false;
	}

	// main payment table:  wp_taste_venue_payment
	$where = array('id' => $payment_id);
	$rows_affected = $wpdb->delete($payment_table, $where, $where_format);
	// if not success set error array and return
	if (!$rows_affected) {
		$ret_json = array('error' => 'Could not update Payment Table. ' . $wpdb->last_error);
		echo wp_json_encode($ret_json);
		$wpdb->query("ROLLBACK");
		return false;
	}

	$wpdb->query( "COMMIT" );
	return true;
}

function update_payment ($payment_db_parms, $payment_id) {
	global $wpdb;

	$payment_table = $payment_db_parms['payment_table'];
	$payment_products_table = $payment_db_parms['payment_products_table'];
	$payment_order_xref_table = $payment_db_parms['payment_order_xref_table'];
	$payment_date = $payment_db_parms['data_fields']['payment_date'];
	$payment_amount = $payment_db_parms['data_fields']['payment_amount'];
	$comment = $payment_db_parms['data_fields']['comment'];
	$comment_visible_venues = $payment_db_parms['data_fields']['comment_visible_venues'];
	$attach_vat_invoice = $payment_db_parms['data_fields']['attach_vat_invoice'];
	$venue_id = $payment_db_parms['data_fields']['venue_id'];
	
	$wpdb->query( "START TRANSACTION" );
	
	// main payment table:  wp_taste_venue_payment
	$data = array(
		'payment_date' => $payment_date,
		'venue_id' => $venue_id,
		'amount' => $payment_amount,
		'comment' => $comment,
		'comment_visible_venues' => $comment_visible_venues,
		'attach_vat_invoice' => $attach_vat_invoice,
	);

	$format = array('%s', '%d', '%f', '%s', '%d', '%d');

	$where = array('id' => $payment_id);
	$where_format = array('%d');
	$rows_affected = $wpdb->update($payment_table, $data, $where, $format, $where_format);	
	// if not success set error array and return
	if (!$rows_affected) {
		$ret_json = array('error' => 'Could not update Payment Table. ' . $wpdb->last_error);
		echo wp_json_encode($ret_json);
		$wpdb->query("ROLLBACK");
		return false;
	}

	// payment x product_id table: wp_taste_venue_payment_products
	$data = array(
		'product_id' => $product_id,
		'amount' => $payment_amount_product,
	);

	$format = array('%d', '%f');
	$where = array('payment_id' => $payment_id);
	$rows_affected = $wpdb->update($payment_products_table, $data, $where, $format, $where_format);	
	// if not success set error array and return
	if (!$rows_affected) {
		$ret_json = array('error' => 'Could not update Payment Product Table.  ' . $wpdb->last_error);
		echo wp_json_encode($ret_json);
		$wpdb->query("ROLLBACK");
		return false;
	}
	
	$wpdb->query( "COMMIT" );
	return true;
}


function num_display ($num) {
	// display number with 2 decimal rounding and formatting
	return number_format(round($num,2), 2);
}
