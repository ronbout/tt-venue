<?php 
/**
 * 	retrieve-payment-orders-json.php
 * 	
 *  Ajax routine for returning a JSON object with 
 * 	payment info, including the new Payment By Order
 * 	data, based on an array of payment ID's.
 *  11/12/2021  Ron Boutilier
 * 
 */

defined('ABSPATH') or die();

function retrieve_payment_orders_info_json($payment_id) {
	global $wpdb;

	$payment_table = $wpdb->prefix."taste_venue_payment";
	$payment_products_table = $wpdb->prefix."taste_venue_payment_products";
	$payment_order_xref_table = $wpdb->prefix."taste_venue_payment_order_item_xref";
	$v_p_join_table = $wpdb->prefix."taste_venue_products";
	$product_order_table = $wpdb->prefix."wc_order_product_lookup";

	$sql = "
			SELECT  pprods.product_id, pay.id AS payment_id, pay.payment_date as timestamp, pprods.product_id as pid, 
				pay.amount as total_amount, pprods.amount as product_amount, pay.comment, pay.status,
				pay.payment_date,
				GROUP_CONCAT(plook.order_item_id) as order_item_ids,
				GROUP_CONCAT(plook.product_qty) as order_item_qty,
				GROUP_CONCAT(plook.order_id) as order_ids
			FROM $payment_products_table pprods
				JOIN $payment_table pay ON pay.id = pprods.payment_id
				JOIN $v_p_join_table vp ON vp.product_id = pprods.product_id
				LEFT JOIN $payment_order_xref_table pox ON pox.payment_id = pay.id
				LEFT JOIN $product_order_table plook ON plook.order_item_id = pox.order_item_id
					AND plook.product_id = pprods.product_id
			WHERE pay.id = %d
			GROUP BY pprods.product_id
			ORDER BY pay.id, pprods.product_id DESC, pay.payment_date ASC
	";

	$payment_rows = $wpdb->get_results($wpdb->prepare($sql, $payment_id), ARRAY_A);

	$payment_info_array = process_payment_info($payment_rows, $payment_id); 
	
	// ugly workaround for the fact that json_encode will screw up some float numbers
	// 7388.75 => 7388.749999999
	$orig_serialize = ini_get('serialize_precision');
	ini_set('serialize_precision', 10);
	echo wp_json_encode($payment_info_array, JSON_NUMERIC_CHECK );
	ini_set('serialize_precision', $orig_serialize);
	return;

}

function process_payment_info($payment_rows, $payment_id) {
	$total_net_payable = $payment_rows[0]['total_amount'];
	$total_qty = 0;
	$product_list = array();
	$orig_payment_date = date('Y-m-d', strtotime($payment_rows[0]['payment_date']));
	$orig_payment_status = $payment_rows[0]['status'];

	foreach($payment_rows as $payment_row) {
		$product_id = $payment_row['product_id'];
		$tmp_order_array = array();
		$net_payable = $payment_row['product_amount'];
		$order_item_id_list = $payment_row['order_item_ids'];
		$order_item_id_array = explode(',', $order_item_id_list);
		$order_id_list = $payment_row['order_ids'];
		$order_id_array = explode(',', $order_id_list);
		$order_qty_list = $payment_row['order_item_qty'];
		$order_qty_array = explode(',', $order_qty_list);
		$order_qty = array_sum($order_qty_array);
		$total_qty += $order_qty;

		$net_pay_per_qty = $net_payable / $order_qty;

		foreach($order_item_id_array as $key => $order_item_id) {
			$tmp_order_array[] = array(
				'orderItemId' => $order_item_id,
				'orderId' => $order_id_array[$key],
				'orderQty' => $order_qty_array[$key],
				'orderNetPayable' => round($order_qty_array[$key] * $net_pay_per_qty, 2)
			);
		}

		$tmp_array = array(
			'netPayable' => $net_payable,
			'orderQty' => $order_qty,
			'orderItemList' => $tmp_order_array
		);
		$product_list[$product_id] = $tmp_array; 
	}
	return array(
		'totalNetPayable' => $total_net_payable,
		'editPaymentId' => $payment_id,
		'editOrigPayDate' => $orig_payment_date,
		'editOrigPayStatus' => $orig_payment_status,
		'totalQty' => $total_qty,
		'productList' => $product_list,
	);
	
}