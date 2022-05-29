<?php 
/**
 * 	outstanding-products.php
 * 	ajax routine for displaying the products table
 *  in the outstanding debts page template
 * 	10/20/2020	Ron Boutilier
 */

defined('ABSPATH') or die('Direct script access disallowed.');

define('TOTALS_TD_WIDTH', '80px');
define('ID_TD_WIDTH', '64px');
define('QTY_TD_WIDTH', '69px');
define('EXP_TD_WIDTH', '65px');
define('COMM_TD_WIDTH', '102px');
define('ACTION_TD_WIDTH', '74px');

function outstanding_display_product_table($filter_data) {
	global $wpdb;
	
	require_once TASTE_PLUGIN_INCLUDES.'/ajax/outstanding/out-column-data.php';

	// var_dump($filter_data);
	// return;
	$sql_filters = build_sql_filters($filter_data);
	$disp_cols = $filter_data['prodCols'];
	// balance due filter cannot be done with sql as only known after calcs
	$balance_due_filter = $filter_data['balanceSelectType'];

	// var_dump($sql_filters);

	$where_clause = $sql_filters['where'];
	$having_clause = $sql_filters['having'];
	$parms = $sql_filters['parms'];
	
	$product_table = $wpdb->prefix."wc_product_meta_lookup";
	$product_order_table = $wpdb->prefix."wc_order_product_lookup";
	$post_meta_table = $wpdb->prefix."postmeta";
	$posts_table = $wpdb->prefix."posts";
	$order_items_table = $wpdb->prefix."woocommerce_order_items";
	$venue_table = $wpdb->prefix."taste_venue";
	$v_p_join_table = $wpdb->prefix."taste_venue_products";
	$payment_table = $wpdb->prefix."taste_venue_payment";
	$payment_products_table = $wpdb->prefix."taste_venue_payment_products";
	$payment_order_xref_table = $wpdb->prefix."taste_venue_payment_order_item_xref";
	$order_trans_table = $wpdb->prefix."taste_order_transactions";

	$sql = "
					SELECT pr.product_id, pr.sku, p.post_title, pr.onsale, p.post_date, 
						pm2.meta_value AS 'expired', pm3.meta_value AS 'price', pm4.meta_value AS 'vat',
						pm5.meta_value AS 'commission', 
						SUM(IF(orderp.post_status = 'wc-completed', 1, 0)) AS 'order_cnt', 
						SUM(IF(orderp.post_status = 'wc-completed',plook.product_qty, 0)) AS 'order_qty', 
						SUM(IF(orderp.post_status = 'wc-completed',wc_oi.downloaded, 0)) 'redeemed_cnt', 
						SUM(IF(orderp.post_status = 'wc-completed',wc_oi.downloaded * plook.product_qty, 0)) AS 'redeemed_qty',
						SUM(IF(orderp.post_status = 'wc-completed',plook.coupon_amount,0)) AS 'coupon_amt',
						SUM(IF(orderp.post_status = 'wc-on-hold', 1, 0)) AS 'credit_refund_order_cnt', 
						SUM(IF(orderp.post_status = 'wc-on-hold',plook.product_qty, 0)) AS 'credit_refund_order_qty', 
						SUM(IF(orderp.post_status = 'wc-on-hold',wc_oi.downloaded, 0)) 'credit_refund_redeemed_cnt', 
						SUM(IF(orderp.post_status = 'wc-on-hold',wc_oi.downloaded * plook.product_qty, 0)) AS 'credit_refund_redeemed_qty',
						SUM(IF(orderp.post_status = 'wc-on-hold',plook.coupon_amount,0)) AS 'credit_refund_coupon_amt',
						SUM(otrans.trans_amount) AS 'credit_refund_amount',
						GROUP_CONCAT(otrans.order_id) AS 'credit_refund_coupon_codes',
						GROUP_CONCAT(otrans.order_item_id) AS 'credit_refund_order_item_ids',
						MIN(plook.date_created) AS 'min_order_date', MAX(plook.date_created) AS 'max_order_date',
						ven.venue_id, ven.name AS 'venue_name'
					FROM $product_table pr 
					JOIN $posts_table p ON pr.product_id =  p.ID
					LEFT JOIN $v_p_join_table venprod ON venprod.product_id = pr.product_id
					LEFT JOIN $venue_table ven ON ven.venue_id = venprod.venue_id
					LEFT JOIN $post_meta_table pm2 ON pr.product_id = pm2.post_id AND pm2.meta_key = 'Expired'
					LEFT JOIN $post_meta_table pm3 ON pr.product_id = pm3.post_id AND pm3.meta_key = '_sale_price'
					LEFT JOIN $post_meta_table pm4 ON pr.product_id = pm4.post_id AND pm4.meta_key = 'vat'
					LEFT JOIN $post_meta_table pm5 ON pr.product_id = pm5.post_id AND pm5.meta_key = 'commission'
					LEFT JOIN $product_order_table plook ON plook.product_id = pr.product_id
					LEFT JOIN $order_trans_table otrans ON otrans.order_item_id = plook.order_item_id AND otrans.trans_type = 'Taste Credit'
					LEFT JOIN $posts_table orderp ON orderp.ID = plook.order_id AND orderp.post_type = 'shop_order'
					LEFT JOIN $order_items_table wc_oi ON wc_oi.order_item_id = plook.order_item_id
					$where_clause
					GROUP BY pr.product_id
					$having_clause
					ORDER BY p.post_date DESC";

	$product_rows = $wpdb->get_results($wpdb->prepare($sql, $parms), ARRAY_A);
				
	// pull out all payments for the product id's returned above
	$product_id_list = array_column($product_rows, 'product_id');
	$placeholders = array_fill(0, count($product_id_list), '%s');
	$placeholders = implode(', ', $placeholders);

	// $orig_payment_table = $wpdb->prefix."offer_payments";
	// $payment_rows = $wpdb->get_results($wpdb->prepare("
	// 		SELECT  pmnt.id, pr.product_id, pmnt.amount, CAST(pmnt.timestamp AS DATE) AS payment_date
	// 		FROM $product_table pr
	// 		JOIN $orig_payment_table pmnt ON pmnt.pid = pr.product_id
	// 		WHERE pr.product_id IN ($placeholders)
	// 		ORDER BY pr.product_id, CAST(pmnt.timestamp AS DATE)", $product_id_list), ARRAY_A);


	$payment_rows = $wpdb->get_results($wpdb->prepare("
			SELECT  pprods.product_id, pay.id, CAST(pay.payment_date AS DATE) as payment_date,
							pay.amount as total_amount, pprods.amount, pay.status
			FROM $product_table pr
				JOIN $payment_products_table pprods ON pprods.product_id = pr.product_id
				JOIN $payment_table pay ON pay.id = pprods.payment_id
			WHERE pr.product_id IN ($placeholders)
				AND pay.status = " . TASTE_PAYMENT_STATUS_PAID . "
			ORDER BY pr.product_id, CAST(pay.payment_date AS DATE) ", 
			$product_id_list), ARRAY_A);

	// create array w product id's as keys and pay totals as values
	$payments = build_payments($payment_rows);
	// $payments = array_combine(array_column($payment_rows, "product_id"), array_column($payment_rows, "total_amount"));

	$ordered_products = order_product_table($product_rows);

	// returns array with 'totals' and 'calcs' keys
	$totals_calcs = get_totals_calcs($ordered_products, $payments, $balance_due_filter);

	$product_calcs = $totals_calcs['calcs'];
	$venue_totals = $totals_calcs['totals'];

?>

	<div class="panel panel-default">
		<div id="venue-summary-div" class="panel-heading text-center"">
					<h2>Year:  <?php echo $year ?></h2>
					<?php display_venue_summary($venue_totals, $venue_type, $year, $year_type) ?>
		</div>
		<div id="product-table-div" class="panel-body">
			<?php
			if (count($product_rows)) {
				?>
				<div><h3>Offers (<?php echo number_format($venue_totals['offers']) ?> Rows)</h3></div>
				<div class="table-title-action">
					<div>
						<button class="btn btn-success product-view-checked-btn" disabled >View Checked Products</button>
					</div>
					<div>
						<a href="#" id ="export-products" role='button'>
							<button class="btn btn-info">Download CSV</button>
						</a>
					</div>
				</div>
				<?php
				display_products_table($product_calcs, $outstanding_product_columns, $disp_cols);
			} else {
				echo "<h3>No Products Found</h3>";
			}
			?>
		</div>
	</div>

<?php 
return;
}

function build_payments($payment_rows) {
	$payments = array();
	foreach($payment_rows as $payment) {
		$prod_id = $payment['product_id'];
		if (isset($payments[$prod_id])) {
			$payments[$prod_id]['total'] += $payment['amount'];
			$payments[$prod_id]['listing'] .= ';' . $payment['id'] . ', ' . $payment['amount'] . ',' . $payment['payment_date'];
		} else {
			$payments[$prod_id] = array('total' => $payment['amount'],
																				 'listing' => $payment['id'] . ', ' . $payment['amount'] . ',' . $payment['payment_date']
			);
		}
	}

	return $payments;
}

function build_sql_filters($filter_data) {
	$where_clause = "WHERE pr.onsale = 1 
		AND ( orderp.post_status = 'wc-completed' OR (orderp.post_status = 'wc-on-hold' AND otrans.id IS NOT NULL )) ";
	$having_clause = '';
	$parms = array();

	$prod_select_type = $filter_data['prodSelectType'];
	$order_select_type = $filter_data['orderSelectType'];
	$venue_select_type = $filter_data['venueSelectType'];
	$recurring_product_check = $filter_data['recurringProductCheck'];
	$prod_id_list = $filter_data['prodIdList'];

	if ($prod_id_list) {
		$product_id_list = explode(', ',$prod_id_list);
		$placeholders = array_fill(0, count($product_id_list), '%s');
		$placeholders = implode(', ', $placeholders);

		$where_clause .= $where_clause ? ' AND ' : 'WHERE ';
		$where_clause .= " pr.product_id IN ($placeholders) ";
		$parms = $product_id_list;
		return array('where' => $where_clause, 'having' => $having_clause, 'parms' => $parms);
	}

	// check venue conditions
	if ('any' !== $venue_select_type) {
		$where_clause .= $where_clause ? ' AND ' : 'WHERE ';
		switch($venue_select_type) {
			case 'unassigned':
				$where_clause .= "ven.venue_id IS NULL";
				break;
			case 'assigned':
				$where_clause .= "ven.venue_id IS NOT NULL";
				break;
			case 'venue':
				$where_clause .= "ven.venue_id = '%d'";
				$parms[] = $filter_data['venueId'];
		}
	}

	// check product year
	if ('all' !== $prod_select_type) {
		$where_clause .= $where_clause ? ' AND ' : 'WHERE ';
		switch($prod_select_type) {
			case 'year':
				$where_clause .= "YEAR(p.post_date) = '%d'";
				$parms[] = $filter_data['prodYear'];
				break;
			case 'range':
				$where_clause .= "p.post_date >= '%s' AND CAST(p.post_date AS DATE) <= '%s'";
				$parms[] = convert_date($filter_data['prodStartDt']);
				$parms[] = convert_date($filter_data['prodEndDt']);
		}
	}
	
	// check order year
	if ('all' !== $order_select_type) {
		$having_clause .= $having_clause ? ' AND ' : 'HAVING ';
		switch($order_select_type) {
			case 'year':
				$having_clause .= "YEAR(MIN(plook.date_created)) = '%d'";
				$parms[] = $filter_data['orderYear'];
				break;
			case 'range':
				$having_clause .= "MIN(plook.date_created) >= '%s' AND CAST(MIN(plook.date_created) AS DATE) <= '%s'";
				$parms[] = convert_date($filter_data['orderStartDt']);
				$parms[] = convert_date($filter_data['orderEndDt']);
		}
	}

	// check recurring product flag
	if ($recurring_product_check) {
		$having_clause .= $having_clause ? ' AND ' : 'HAVING ';
		$having_clause .= "CAST(MIN(plook.date_created) AS DATE) < CAST(p.post_date AS DATE)";
	}

	return array('where' => $where_clause, 'having' => $having_clause, 'parms' => $parms);
}

function convert_date($date_str) {
	// datepicker gives full date string
	$tmp_date = new DateTime(explode('(', $date_str)[0]);
	return $tmp_date->format('Y-m-d');
}

function get_totals_calcs($ordered_products, $payments, $balance_due_filter) {
	$venue_totals = array(
	'offers' => 0,
	'redeemed_cnt' => 0,
	'redeemed_qty' => 0,
	'order_cnt' => 0,
	'order_qty' => 0,
	'sales_amt' => 0,
	'coupon_amt' => 0,
	'net_sales' => 0,
	'revenue' => 0,
	'commission' => 0,
	'vat' => 0,
	'net_payable' => 0,
	'paid_amount' => 0,
	'balance_due' => 0,
	'unredeemed_income' => 0,
	'total_income' => 0,
	'pot_redeemed_qty' => 0,
	'pot_revenue' => 0,
	'pot_commission' => 0,
	'pot_vat' => 0,
	'pot_net_payable' => 0,
	'pot_balance_due' => 0,
	'credit_refund_sales_amt' => 0,
	'credit_refund_net_sales' => 0,
	'total_credit_refund_amt' => 0,
	'used_credit_refund_amt' => 0,
	'remaining_credit_refund_amt' => 0,
	);

	$product_calcs = array();
	foreach($ordered_products as $product_row) {

		$product_id = $product_row['product_id'];
		$tmp = array();
		$tmp['product_id'] = $product_id;
		$tmp['title'] = substr($product_row['post_title'], 0, 50);
		$tmp['sku'] = $product_row['sku'];
		$tmp['status'] = ("N" === $product_row['expired']) ? "Active" : "Expired";
		$tmp['redeemed_cnt'] = $product_row['redeemed_cnt'] ? $product_row['redeemed_cnt'] : 0;
		$tmp['redeemed_qty'] = $product_row['redeemed_qty'] ? $product_row['redeemed_qty'] : 0;
		$tmp['order_cnt'] = $product_row['order_cnt'] ? $product_row['order_cnt'] : 0;
		
		$tmp['order_qty'] = $product_row['order_qty'] ? $product_row['order_qty'] : 0;
		$tmp['sales_amt'] = num_display($product_row['price'] * $tmp['order_qty']);
		$tmp['coupon_amt'] = num_display($product_row['coupon_amt']);
		$tmp['net_sales'] = num_display($tmp['sales_amt'] - $product_row['coupon_amt']);
		$tmp['view'] = "<button data-prod-id='" . $product_row['product_id'] . "' class='btn btn-primary product-select-btn'>View</button>";

		$curr_prod_values = calc_net_payable($product_row['price'], $product_row['vat'], $product_row['commission'], $tmp['redeemed_qty'], true);
		$grevenue = $curr_prod_values['gross_revenue'];
		$commission = $curr_prod_values['commission'];
		$vat = $curr_prod_values['vat'];
		$payable = $curr_prod_values['net_payable'];

		$tmp['revenue'] = $grevenue;
		$tmp['commission'] = $commission;
		$tmp['vat'] = $vat;
		$tmp['net_payable'] = $payable;

		// $tmp['revenue'] = num_display($product_row['price'] * $tmp['redeemed_qty']);
		// $tmp['commission'] = num_display(($tmp['revenue'] / 100) * $product_row['commission']);
		// $tmp['vat'] = num_display(($tmp['commission'] / 100) * $product_row['vat']);
		// $tmp['net_payable'] = num_display($tmp['revenue'] - ($tmp['commission'] + $tmp['vat']));

		$tmp['paid_amount'] = num_display(empty($payments[$product_id]) ? 0 : $payments[$product_id]['total']);
		$tmp['payment_list'] = empty($payments[$product_id]) ? '' : $payments[$product_id]['listing'];
		$tmp['balance_due'] = num_display($tmp['net_payable'] - $tmp['paid_amount']);

		// new section to add "POTENTIAL" amounts that would be any potential 
		// unredeemed orders that could still be redeemed
		if ("N" === $product_row['expired']) {
			$tmp['pot_redeemed_qty'] = $tmp['order_qty'];
			$tmp['pot_revenue'] = num_display($product_row['price'] * $tmp['pot_redeemed_qty']);
			$tmp['pot_commission'] = num_display(($tmp['pot_revenue'] / 100) * $product_row['commission']);
			$tmp['pot_vat'] = num_display(($tmp['pot_commission'] / 100) * $product_row['vat']);
			$tmp['pot_net_payable'] = num_display($tmp['pot_revenue'] - ($tmp['pot_commission'] + $tmp['pot_vat']));
			$tmp['pot_balance_due'] = num_display($tmp['pot_net_payable'] - $tmp['paid_amount']);
		} else {
			$tmp['pot_redeemed_qty'] = $tmp['redeemed_qty'];
			$tmp['pot_revenue'] = $tmp['revenue'];
			$tmp['pot_commission'] = $tmp['commission'];
			$tmp['pot_vat'] = $tmp['vat'];
			$tmp['pot_net_payable'] = $tmp['net_payable'];
			$tmp['pot_balance_due'] = $tmp['balance_due'];
		}

		// new section for dealing with orders that were turned into store credit refunds (coupons)
		$tmp['total_credit_refund_amt'] = num_display($product_row['credit_refund_amount']);
		$credit_order_item_ids = $product_row['credit_refund_order_item_ids'];
		if ($credit_order_item_ids) {
			$tmp['credit_refund_sales_amt'] = num_display($product_row['price'] * $product_row['credit_refund_order_qty']);
			$tmp['credit_refund_net_sales'] = num_display($tmp['credit_refund_sales_amt'] - $product_row['credit_refund_coupon_amt']);
			// need to sum the coupons that have NOT reached their usage limit to get the remaining credit amount
			$credit_refund_amounts = calc_remaining_credit_refund_amount($credit_order_item_ids);
			$remaining_credit_refund_amount = $credit_refund_amounts['remaining_coupon_amount'];
			$used_credit_refund_amount = $credit_refund_amounts['used_coupon_amount'];
			$tmp['used_credit_refund_amt'] = num_display_financial($used_credit_refund_amount);
			$tmp['remaining_credit_refund_amt'] = num_display_financial($remaining_credit_refund_amount);
		} else {
			$tmp['credit_refund_sales_amt'] = 0;
			$tmp['credit_refund_net_sales'] = 0;
			$tmp['used_credit_refund_amt'] = 0;
			$tmp['remaining_credit_refund_amt'] = 0;
		}

		// check against balance due filter 
		if (!balance_due_filter_ok($tmp['balance_due'], $balance_due_filter)) {
			continue;
		}

		// new...show min / max date of orders as well as product date
		$tmp['min_order_date'] = explode(' ', $product_row['min_order_date'])[0];
		$tmp['max_order_date'] = explode(' ', $product_row['max_order_date'])[0];
		$tmp['product_date'] = explode(' ', $product_row['post_date'])[0];
		$tmp['venue_name'] = $product_row['venue_name'] ? $product_row['venue_name'] : ' ------- ';
		$tmp['venue_id'] = $product_row['venue_id'] ? $product_row['venue_id'] : ' ------- ';
		// new...calculate income from expired, unredeemed orders!!
		$tmp['unredeemed_income'] = ("N" === $product_row['expired']) ? 0 : num_display(($tmp['order_qty'] - $tmp['redeemed_qty']) * $product_row['price']);
		$tmp['total_income'] = num_display($tmp['commission'] + $tmp['unredeemed_income']);
		$tmp['profit_margin'] = $tmp['total_income'] / $tmp['sales_amt'] * 100;


		$product_calcs[] = $tmp;

		foreach($venue_totals as $k => &$total) {
			if ($k === 'offers') {
				$total += 1;
			} else {
				$total += $tmp[$k];
			}
		}
	}
	return array('totals' => $venue_totals, 'calcs' => $product_calcs);
}

function calc_remaining_credit_refund_amount($credit_order_item_ids) {
	global $wpdb;

	$order_trans_table = $wpdb->prefix."taste_order_transactions";
	$credit_order_item_list = explode(',',$credit_order_item_ids);
	$placeholders = array_fill(0, count($credit_order_item_list), '%s');
	$placeholders = implode(', ', $placeholders);

	$sql = "
		SELECT 
		COALESCE( SUM(
			IF(pm_usage_count.meta_value = 0 AND 
				CAST( COALESCE(FROM_UNIXTIME(pm_date_expires.meta_value), pm_expiry_date.meta_value) AS DATE ) > CAST(CURDATE() AS DATE ), 
				otrans.trans_amount, 0)), 0) AS 'remaining_coupon_amount',
			COALESCE( SUM(IF(pm_usage_count.meta_value > 0, otrans.trans_amount, 0)), 0) AS 'used_coupon_amount'
			FROM $order_trans_table otrans
			JOIN $wpdb->posts coup_p ON coup_p.post_title = otrans.order_id
			JOIN $wpdb->postmeta pm_usage_count ON pm_usage_count.meta_key = 'usage_count' AND pm_usage_count.post_id = coup_p.ID
			LEFT JOIN $wpdb->postmeta pm_date_expires ON pm_date_expires.meta_key = 'date_expires' AND pm_date_expires.post_id = coup_p.ID
			LEFT JOIN $wpdb->postmeta pm_expiry_date ON pm_expiry_date.meta_key = 'expiry_date' AND pm_expiry_date.post_id = coup_p.ID
			WHERE otrans.trans_type = 'Taste Credit'
			AND otrans.order_item_id IN ($placeholders)
			AND coup_p.post_type = 'shop_coupon' 
			AND coup_p.post_status = 'publish'";

	$credit_refund_amounts = $wpdb->get_results($wpdb->prepare($sql, $credit_order_item_list), ARRAY_A);	

	return $credit_refund_amounts[0];
}

function balance_due_filter_ok ($balance_due, $balance_due_filter) {
	switch($balance_due_filter) {
		case 'any':
			return true;
		case 'positive': 
			return $balance_due > 0;
		case 'negative':
			return $balance_due < 0;
		case 'zero': 
			return $balance_due == 0;
		case 'nonzero': 
			return $balance_due != 0;
		default:
		return true;
	}
}

function display_venue_summary($venue_totals, $venue_type, $year, $year_type) {
	$currency =  get_woocommerce_currency_symbol();
	$profit_margin = $venue_totals['total_income'] / $venue_totals['sales_amt'] * 100;
	?>
	<div class="v-summary-container">
		<div class="v-summary-section">
			<h3>Vouchers Sold</br> => Redeemed</h3>
			<h3>
				<span id="vouchers-total">
					<?php echo number_format($venue_totals['order_qty']) ?> => <?php echo number_format($venue_totals['redeemed_qty']) ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>Gross</br>Sales</h3>
			<h3>
				<span id="gr-value-total">
				<?php echo $currency . ' ' . number_format(num_display($venue_totals['sales_amt']),2) ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>Net</br>Sales</h3>
			<h3>
				<span id="gr-value-total">
					<?php echo $currency . ' ' . number_format(num_display($venue_totals['net_sales']),2) ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>Venue Gross</br>Payable</h3>
			<h3>
				<span id="gr-value-total">
					<?php echo $currency . ' ' . number_format(num_display($venue_totals['revenue']),2) ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>Total</br>VAT</h3>
			<h3>
				<span id="gr-value-total">
					<?php echo $currency . ' ' . number_format(num_display($venue_totals['vat']),2) ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>Total</br>Commission</h3>
			<h3>
				<span id="gr-value-total">
					<?php echo $currency . ' ' . number_format(num_display($venue_totals['commission']),2) ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>Venue Net</br>Payable</h3>
			<h3>
				<span id="net-payable-total">
					<?php echo $currency . ' ' . number_format(num_display($venue_totals['net_payable']),2) ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>Total</br>Payments</h3>
			<h3>
				<span id="paid-amount-total">
					<?php echo $currency . ' ' . number_format(num_display($venue_totals['paid_amount']),2) ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>Balance</br>Due</h3>
			<h3>
				<span id="balance-due-total">
					<?php echo $currency . ' ' . number_format(num_display($venue_totals['balance_due']),2) ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>UnRedeemed</br>Income</h3>
			<h3>
				<span id="gr-value-total">
					<?php echo $currency . ' ' . number_format(num_display($venue_totals['unredeemed_income']),2) ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>Total</br>Income</h3>
			<h3>
				<span id="gr-value-total">
					<?php echo $currency . ' ' . number_format(num_display($venue_totals['total_income']),2) ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>Income /</br>Sales</h3>
			<h3>
				<span id="gr-value-total">
					<?php echo num_display($profit_margin) ?> %
				</span>
			</h3>
		</div>
	</div>
	<?php
}

function display_products_table($product_calcs, $outstanding_product_columns, $disp_cols) {
	?>
	<div id="product-table-container" class="table-fixed-container">
	<table id="out-product-table" class="table table-striped table-bordered table-fixed">
		<thead>
			<th><input type="checkbox" id="checkbox-all"></th>
			<?php 
				foreach($disp_cols as $col) {
					echo "<th>$outstanding_product_columns[$col]</th>";
				}
			?>
			<th>Action</th>
		</thead>
		<tbody>
			<?php									
				foreach($product_calcs as $product_row) {
					display_product_row($product_row, $disp_cols);
				}
			?>
		</tbody>
	</table>
	</div>
	<?php
}

function display_product_row($product_row, $disp_cols) {
	// determine if the order date is earlier than the product date, indicating a repeating product
	$ord_date = new DateTime($product_row['min_order_date']);
	$prod_date = new DateTime($product_row['product_date']);
	$tr_class = ($ord_date < $prod_date) ? 'highlight-row' : '';									
	?>
	<tr class="<?php echo $tr_class ?>">
			<td>
				<input data-productid="<?php echo $product_row['product_id'] ?>" 
							 type="checkbox" class="product-view-check">
			</td>
		<?php
			foreach($disp_cols as $col) {
				echo "<td>$product_row[$col]</td>";
			}
			?>
			<td style="width: <?php echo ACTION_TD_WIDTH?>;"><?php echo $product_row['view'] ?></td>
	</tr>
	<?php
}

function order_product_table($product_rows) {
	// filter by active and expired, then merge 
	// 2nd sort should be by date
	$active_products = array();
	$expired_products = array();
	array_walk($product_rows, function($row, $k) use (&$active_products, &$expired_products) {
		if ("N" === strtoupper($row['expired'])) {
			$active_products[] = $row;
		} else {
			$expired_products[] = $row;
		}
	});

	$ordered_products = array_merge($active_products, $expired_products);
	return $ordered_products;
}

function num_display ($num) {
	// display number with 2 decimal rounding -- NO FORMATTING
	// return number_format(round($num,2), 2);
	return round($num,2);
}

function num_display_financial ($num) {
	return number_format(round($num,2), 2);
}

function num_display_no_decs ($num) {
	// display number with 2 decimal rounding and formatting
	return number_format(round($num), 0);
}
