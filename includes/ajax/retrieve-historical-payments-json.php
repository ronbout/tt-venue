<?php 
/**
 * 	retrieve-historical-payments-json.php
 * 	
 *  Ajax routine for returning a JSON object with 
 * 	payment/orders to account for previous payments 
 *	that were not done under PBO.
 *	This allows for orders to marked off as Paid, 
 *	even though the original payment was not a part of PBO
 *
 *  1/24/2022  Ron Boutilier
 * 
 */
defined('ABSPATH') or die();


require_once TASTE_PLUGIN_INCLUDES.'/ajax/functions.php';

function retrieve_historical_payments_json($venue_id) {
	global $wpdb;

	$payment_table = $wpdb->prefix."taste_venue_payment";
	$payment_products_table = $wpdb->prefix."taste_venue_payment_products";
	$payment_order_xref_table = $wpdb->prefix."taste_venue_payment_order_item_xref";
	$v_p_join_table = $wpdb->prefix."taste_venue_products";
	$product_order_table = $wpdb->prefix."wc_order_product_lookup";
	$postmeta_table = $wpdb->prefix."postmeta";

	$sql = "
			SELECT  pprods.product_id, pm1.meta_value AS comm_val, pm2.meta_value AS price, 
				pm3.meta_value AS vat_val, 
				SUM(pprods.amount) as product_amount
			FROM $payment_products_table pprods
				JOIN $payment_table pay ON pay.id = pprods.payment_id
				JOIN $v_p_join_table vp ON vp.product_id = pprods.product_id
				LEFT JOIN $payment_order_xref_table pox ON pox.payment_id = pay.id
				LEFT JOIN $product_order_table plook ON plook.order_item_id = pox.order_item_id
					AND plook.product_id = pprods.product_id
				LEFT JOIN $postmeta_table pm1 ON pprods.product_id = pm1.post_id AND pm1.meta_key = 'commission'
				LEFT JOIN $postmeta_table pm2 ON pprods.product_id = pm2.post_id AND pm2.meta_key = '_sale_price'
				LEFT JOIN $postmeta_table pm3 ON pprods.product_id = pm3.post_id AND pm3.meta_key = 'vat'
			WHERE vp.venue_id = %d
				AND pay.status = 1
				AND pox.order_item_id IS NULL
			GROUP BY pprods.product_id
			HAVING  GROUP_CONCAT(plook.order_item_id) IS NULL
			ORDER BY pprods.product_id DESC
	";

	$orig_payment_rows = $wpdb->get_results($wpdb->prepare($sql, $venue_id), ARRAY_A);

	$sql = "
			SELECT  pprods.product_id, SUM(plook.product_qty) AS order_cnt
				FROM $payment_products_table pprods
					JOIN $payment_table pay ON pay.id = pprods.payment_id
					JOIN $v_p_join_table vp ON vp.product_id = pprods.product_id
					LEFT JOIN $payment_order_xref_table pox ON pox.payment_id = pay.id
					LEFT JOIN $product_order_table plook ON plook.order_item_id = pox.order_item_id
						AND plook.product_id = pprods.product_id
				WHERE vp.venue_id = %d
					AND pay.status = 2
					AND pox.order_item_id IS NOT NULL
				GROUP BY pprods.product_id
				ORDER BY pprods.product_id DESC
	";


	$historical_payment_rows = $wpdb->get_results($wpdb->prepare($sql, $venue_id), ARRAY_A);
	
	$orig_payment_rows = array_column($orig_payment_rows, null, 'product_id');
	$historical_payment_rows = array_column($historical_payment_rows, null, 'product_id');

	$needed_orders_per_product = calc_needed_orders($orig_payment_rows, $historical_payment_rows);

	$payment_orders_json = build_payment_with_orders($orig_payment_rows, $needed_orders_per_product);

	// var_dump($payment_orders_json);
	// die();

	// ugly workaround for the fact that json_encode will screw up some float numbers
	// 7388.75 => 7388.749999999
	$orig_serialize = ini_get('serialize_precision');
	ini_set('serialize_precision', 10);
	echo wp_json_encode($payment_orders_json, JSON_NUMERIC_CHECK );
	ini_set('serialize_precision', $orig_serialize);
	return;

}

function calc_needed_orders($orig_payment_rows, $historical_payment_rows) {
	// loop through each product with orig payments, calc net payment / order 
	// then compare to historical order cnt to see if more are needed

	$needed_ords_by_product = array();
	foreach ($orig_payment_rows as $prod_id => $pay_row) {
		$net_payable_per_order = round(calc_net_payable($pay_row['price'], $pay_row['vat_val'], $pay_row['comm_val'], 1), 2);
		$payment_amount = $pay_row['product_amount'];
		$historical_order_cnt = (int) round($payment_amount / $net_payable_per_order);

		$prev_historical_order_cnt = isset($historical_payment_rows[$prod_id]) ? $historical_payment_rows['order_cnt'] : 0;

		$needed_historical_order_cnt = $historical_order_cnt - $prev_historical_order_cnt;

		$needed_ords_by_product[$prod_id] = array(
			'needed_orders' => $needed_historical_order_cnt,
			'net_payable' => $net_payable_per_order,
		);

	}
	return $needed_ords_by_product;
}

function build_payment_with_orders($orig_payment_rows, $needed_orders_per_product) {
	global $wpdb;

	$payment_order_xref_table = $wpdb->prefix."taste_venue_payment_order_item_xref";
	$product_order_table = $wpdb->prefix."wc_order_product_lookup";
	$posts_table = $wpdb->prefix."posts";
	$wc_order_item_meta = $wpdb->prefix."woocommerce_order_itemmeta";
	$wc_order_items = $wpdb->prefix."woocommerce_order_items";

	$sql = "
			SELECT im.meta_value AS quan, o.post_date,
				wclook.product_id AS productID,
				i.order_id, i.order_item_id as itemid
			FROM $product_order_table wclook
				JOIN $wc_order_item_meta im ON im.order_item_id = wclook.order_item_id
				LEFT JOIN $wc_order_items i ON i.order_item_id = wclook.order_item_id
				LEFT JOIN $posts_table o ON o.id = wclook.order_id
				LEFT JOIN $payment_order_xref_table poix ON poix.order_item_id = wclook.order_item_id
			WHERE im.meta_key = '_qty'
				AND i.downloaded = 1
				AND o.post_status = 'wc-completed'
				AND o.post_type = 'shop_order'
				AND poix.payment_id IS NULL 
				AND wclook.product_id = %d
			GROUP BY o.id
			ORDER BY o.post_date ASC 
			LIMIT %d
	";
	
	$total_net_payable = 0;
	$total_qty = 0;
	$product_list = array();

	foreach ($needed_orders_per_product as $prod_id => $prod_order_info) {
		$net_payable_per_qty = $prod_order_info['net_payable'];
		$needed_order_cnt = $prod_order_info['needed_orders'];
		if (!$needed_order_cnt) continue;
		$targeted_orders = $wpdb->get_results($wpdb->prepare($sql, $prod_id, $needed_order_cnt), ARRAY_A);
		$prod_qty = 0;
		$prod_net_payable = 0;
		$tmp_order_array = array();
		foreach ($targeted_orders as $order_info) {
			$ord_qty = $order_info['quan'];
			if ($prod_qty + $ord_qty > $needed_order_cnt) break;
			$prod_qty += $ord_qty;
			$total_qty += $ord_qty;
			$net_payable = round($net_payable_per_qty * $ord_qty, 2);
			$prod_net_payable += $net_payable;
			$total_net_payable += $net_payable;
			$tmp_order_array[] = array(
				'orderItemId' => $order_info['itemid'],
				'orderId' => $order_info['order_id'],
				'orderQty' => $ord_qty,
				'orderNetPayable' => $net_payable
			);
		}

		$tmp_array = array(
			'netPayable' => $prod_net_payable,
			'orderQty' => $prod_qty,
			'neededOrderQty' => $needed_order_cnt,
			'orderItemList' => $tmp_order_array
		);
		$product_list[$prod_id] = $tmp_array; 
	}

	return array(
		'totalNetPayable' => $total_net_payable,
		'totalQty' => $total_qty,
		'productList' => $product_list,
		'payStatus' => TASTE_PAYMENT_STATUS_ADJ,
	);
}