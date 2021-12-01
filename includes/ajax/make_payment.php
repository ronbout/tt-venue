<?php

defined('ABSPATH') or die('Direct script access disallowed.');

function make_payment_update($payment_info, $product_info, $cur_prod_info, $venue_info) {
	global $wpdb;

	$user = wp_get_current_user();
	$role = $user->roles[0];
	$user_id = get_current_user_id();
	$admin = ('ADMINISTRATOR' === strtoupper($role));

	// var_dump($payment_info);
	// var_dump(json_decode(html_entity_decode(stripslashes ($payment_info['payment_orig_prods'])), true));
	// die();

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
	$all_payment_id_cnt = $payment_info['all_payment_id_cnt'];
	$payment_orig_prods = json_decode(html_entity_decode(stripslashes ($payment_info['payment_orig_prods'])), true);
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

		$payment_diff = - $payment_amount;
	} elseif ($payment_id) {
		// $ret_json = array('error' => 'Updating a Payment is currently in development');
		// echo wp_json_encode($ret_json);
		// return;
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

	// later code may not have a currently selected product, 
	// or  it may have a product w/ no selected orders
	// so test to be sure ("pay all orders" command, for instance)
	$total_paid = 0;
	$balance_due = 0;
	$payment_line = '';
	$update_cur_prod = 0;
	$cur_prod_ord_list = [];
	if (count($cur_prod_info) && false) {
		$product_id = array_keys($cur_prod_info)[0];
		$cur_prod_info = $cur_prod_info[$product_id];
		if (in_array($product_id, array_keys($product_order_info))) {
			$update_cur_prod = 1;
			if ($edit_mode === 'INSERT') {
				$prod_payment_cnt += 1;
			} elseif ($edit_mode === 'DELETE') {
				$prod_payment_cnt -= 1;
			}
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

		$cur_prod_ord_list = array_column($product_order_info[$product_id]['order_list'], 'orderItemId');
	
		$payment_line = 'DELETE' === $edit_mode ? '' : disp_payment_line($disp_payment_info, $admin, $commission_value);
		}
	}
	// now similar to above but for all included products and the 
	// display of the products table and All Transactions
	$all_payment_lines = '';
	$tmp_cnt = 0;
	foreach ($product_info as $prod_id => &$prod_row_info) {
		$amount = $product_order_info[$prod_id]['amount'];
		
		if ('INSERT' == $edit_mode) {
			$prod_payment_diff = $amount;
		} elseif ('UPDATE' == $edit_mode) {
			$orig_prod_amount = isset($payment_orig_prods[$prod_id]) ? $payment_orig_prods[$prod_id][amount] : 0;
			$prod_payment_diff = $amount - $orig_prod_amount;
		} else {
			$prod_payment_diff = - $amount;
		}
		$orig_amount = $payment_orig_prods[$prod_id];
		$prod_pay_diff = 
		$prod_row_info['balance_due'] = round($prod_row_info['balance_due'] - $prod_payment_diff, 2);
		$prod_row_info['total_paid'] = round($prod_row_info['total_paid'] + $prod_payment_diff, 2);

		$disp_payment_info = $payment_info;
		$disp_payment_info['product_id'] = $prod_id;
		$disp_payment_info['amount'] = $amount;
		$disp_payment_info['total_amount'] = $payment_info['amount'];
		$disp_payment_info['order_item_ids'] = implode(', ', array_column($product_order_info[$prod_id]['order_list'], 'orderItemId'));
		$tmp_cnt += 1;

		if ($edit_mode !== 'DELETE') {
			$all_payment_line = disp_all_payment_line($disp_payment_info);
			$all_payment_lines .= $all_payment_line;
		}
	}
	if ('INSERT' == $edit_mode) {
		$all_payment_cnt += $tmp_cnt;
	} elseif ('UPDATE' == $edit_mode) {
		$all_payment_cnt += $tmp_cnt - $all_payment_id_cnt;
	} else {
		$all_payment_cnt -= $tmp_cnt;
	}

	// if update, check to see if any products are in the orig list, 
	// but not in the payment prod list.  If so, determine the balance
	// due and add to the return prod list.
	if ('UPDATE' == $edit_mode) {
		foreach ($payment_orig_prods as $orig_prod_id => $orig_prod_info) {
			if (isset($product_info[$orig_prod_id])) {
				continue;
			}
			$orig_amount = $orig_prod_info['amount'];
			$balance_due = $orig_prod_info['balancedue'] + $orig_amount;
			$total_paid = $orig_prod_info['totalpaid'] - $orig_amount;
			$tmp_prod_array = array(
				total_paid => $total_paid,
				balance_due => $balance_due,
			);
			$product_info[$orig_prod_id] = $tmp_prod_array;
		}
	}

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
		'allPaymentLine' => $all_payment_lines,
		'editMode' => $edit_mode,
		'hiddenPaymentValues' => $hidden_payment_values,
		'sumHiddenPaymentValues' => $sum_hidden_payment_values,
		'allPaymentCnt' => $all_payment_cnt,
		'prodPaymentCnt' => $prod_payment_cnt,
		'updateCurrentProd' => $update_cur_prod,
		'productInfo' => $product_info,
		'curProdOrdList' => $cur_prod_ord_list,
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

/*
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
*/

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
	$orders_flag = $payment_db_parms['data_fields']['orders_flag'];
	$product_order_info = $payment_db_parms['data_fields']['product_order_info'];
	
	$wpdb->query( "START TRANSACTION" );

	// first delete original, which will cascade to lower tables,
	// then just use the insert code
	$where = array('id' => $payment_id);
	$where_format = array('%d');

	$rows_affected = $wpdb->delete($payment_table, $where, $where_format);
	// if not success set error array and return
	if (!$rows_affected) {
		$ret_json = array('error' => 'Could not update Payment Table. ' . $wpdb->last_error);
		echo wp_json_encode($ret_json);
		$wpdb->query("ROLLBACK");
		return false;
	}
	
	// main payment table:  wp_taste_venue_payment
	$data = array(
		'id' => $payment_id,
		'payment_date' => $payment_date,
		'venue_id' => $venue_id,
		'amount' => $payment_amount,
		'comment' => $comment,
		'comment_visible_venues' => $comment_visible_venues,
		'attach_vat_invoice' => $attach_vat_invoice,
	);

	$format = array('%d', '%s', '%d', '%f', '%s', '%d', '%d');
	$rows_affected = $wpdb->insert($payment_table, $data, $format);	
	// if not success set error array and return
	if (!$rows_affected) {
		$ret_json = array('error' => 'Could not update Payment Table. ' . $wpdb->last_error);
		echo wp_json_encode($ret_json);
		$wpdb->query("ROLLBACK");
		return array('db_status' => false);
	}

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

	return true;
}


function num_display ($num) {
	// display number with 2 decimal rounding and formatting
	return number_format(round($num,2), 2);
}
