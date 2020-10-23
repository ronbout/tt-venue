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

	// var_dump($filter_data);
	$sql_filters = build_sql_filters($filter_data);

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
	$payment_table = $wpdb->prefix."offer_payments";

	// $where_clause = ('order' === $year_type) ? " GROUP BY pr.product_id  HAVING YEAR(MIN(plook.date_created)) = '%d'  " :
	// 																				 " WHERE YEAR(p.post_date) = '%d' GROUP BY pr.product_id  ";

	$product_rows = $wpdb->get_results($wpdb->prepare("
					SELECT pr.product_id, pr.sku, p.post_title, pr.onsale, p.post_date, 
						pm2.meta_value AS 'expired', pm3.meta_value AS 'price', pm4.meta_value AS 'vat',
						pm5.meta_value AS 'commission', 
						COUNT(plook.order_id) AS 'order_cnt', SUM(plook.product_qty) AS 'order_qty', 
						SUM(wc_oi.downloaded) AS 'redeemed_cnt', SUM(wc_oi.downloaded * plook.product_qty) AS 'redeemed_qty',
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
					JOIN $posts_table orderp ON orderp.ID = plook.order_id 
						AND orderp.post_status = 'wc-completed'
						AND orderp.post_type = 'shop_order'
					LEFT JOIN $order_items_table wc_oi ON wc_oi.order_item_id = plook.order_item_id
					$where_clause
					GROUP BY pr.product_id
					$having_clause
					ORDER BY p.post_date DESC", 
					$parms), ARRAY_A);
				
	// more efficient just to grab this a separate statement
	$payment_rows = $wpdb->get_results($wpdb->prepare("
			SELECT  pr.product_id, sum(pmnt.amount) as 'total_amount'
			FROM $product_table pr
			JOIN $payment_table pmnt ON pmnt.pid = pr.product_id
			WHERE YEAR(pmnt.timestamp) = '%d'
			GROUP BY pr.product_id", $year), ARRAY_A);

	// create array w product id's as keys and pay totals as values
	$payments = array_combine(array_column($payment_rows, "product_id"), array_column($payment_rows, "total_amount"));

	$ordered_products = order_product_table($product_rows);

	// returns array with 'totals' and 'calcs' keys
	$totals_calcs = get_totals_calcs($ordered_products, $payments);

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
				<div id="out-products-table-title-action">
					<div><h3>Offers</h3></div>
					<div>
						<a href="#" id ="export" role='button'>
							<button class="btn btn-info">Download CSV</button>
						</a>
					</div>
				</div>
				<?php
				display_products_table($product_calcs, $served_heading, $venue_totals);
			} else {
				echo "<h3>No Products Found</h3>";
			}
			?>
		</div>
	</div>

<?php 
return;
}

function build_sql_filters($filter_data) {
	$where_clause = '';
	$having_clause = '';
	$parms = array();

	$prod_select_type = $filter_data['prodSelectType'];
	$order_select_type = $filter_data['orderSelectType'];
	$venue_select_type = $filter_data['venueSelectType'];
	$recurring_product_check = $filter_data['recurringProductCheck'];

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

function get_totals_calcs($ordered_products, $payments) {
	$venue_totals = array(
	'offers' => 0,
	'redeemed_cnt' => 0,
	'redeemed_qty' => 0,
	'order_cnt' => 0,
	'order_qty' => 0,
	'sales_amt' => 0,
	'revenue' => 0,
	'commission' => 0,
	'vat' => 0,
	'net_payable' => 0,
	'paid_amount' => 0,
	'balance_due' => 0,
	'unredeemed_income' => 0,
	'total_income' => 0
	);

	$product_calcs = array();
	foreach($ordered_products as $product_row) {
		$product_id = $product_row['product_id'];
		$tmp = array();
		$tmp['product_id'] = $product_id;
		$tmp['title'] = $product_row['post_title'];
		$tmp['status'] = ("N" === $product_row['expired']) ? "Active" : "Expired";
		$tmp['redeemed_cnt'] = $product_row['redeemed_cnt'];
		$tmp['redeemed_qty'] = $product_row['redeemed_qty'];
		$tmp['order_cnt'] = $product_row['order_cnt'];
		$tmp['order_qty'] = $product_row['order_qty'];
		$tmp['sales_amt'] = $product_row['price'] * $tmp['order_qty'];
		$tmp['revenue'] = $product_row['price'] * $tmp['redeemed_qty'];
		$tmp['view'] = "<button data-prod-id='" . $product_row['product_id'] . "' class='btn btn-primary product-select-btn'>View</button>";
		$tmp['commission'] = ($tmp['revenue'] / 100) * $product_row['commission'];
		$tmp['vat'] = ($tmp['commission'] / 100) * $product_row['vat'];
		$tmp['net_payable'] = $tmp['revenue'] - ($tmp['commission'] + $tmp['vat']);
		$tmp['paid_amount'] = empty($payments[$product_id]) ? 0 : $payments[$product_id];
		$tmp['balance_due'] = $tmp['net_payable'] - $tmp['paid_amount'];
		// new...show min / max date of orders as well as product date
		$tmp['min_order_date'] = explode(' ', $product_row['min_order_date'])[0];
		$tmp['max_order_date'] = explode(' ', $product_row['max_order_date'])[0];
		$tmp['product_date'] = explode(' ', $product_row['post_date'])[0];
		$tmp['venue_name'] = $product_row['venue_name'] ? $product_row['venue_name'] : ' ------- ';
		// new...calculate income from expired, unredeemed orders!!
		$tmp['unredeemed_income'] = ("N" === $product_row['expired']) ? 0 : ($tmp['order_qty'] - $tmp['redeemed_qty']) * $product_row['price'];
		$tmp['total_income'] = $tmp['commission'] + $tmp['unredeemed_income'];
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

function display_venue_summary($venue_totals, $venue_type, $year, $year_type) {
	$currency =  get_woocommerce_currency_symbol();
	$profit_margin = $venue_totals['total_income'] / $venue_totals['sales_amt'] * 100;
	?>
	<div class="v-summary-container">
		<div class="v-summary-section">
			<h3>Vouchers</br>Sold</h3>
			<h3>
				<span id="vouchers-total">
					<?php echo $venue_totals['order_qty'] ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>Vouchers</br>Redeemed</h3>
			<h3>
				<span id="served-total">
					<?php echo $venue_totals['redeemed_qty'] ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>Total</br>Sales</h3>
			<h3>
				<span id="gr-value-total">
					<?php echo $currency . ' ' . num_display($venue_totals['sales_amt']) ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>Venue Gross</br>Payable</h3>
			<h3>
				<span id="gr-value-total">
					<?php echo $currency . ' ' . num_display($venue_totals['revenue']) ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>Total</br>VAT</h3>
			<h3>
				<span id="gr-value-total">
					<?php echo $currency . ' ' . num_display($venue_totals['vat']) ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>Total</br>Commission</h3>
			<h3>
				<span id="gr-value-total">
					<?php echo $currency . ' ' . num_display($venue_totals['commission']) ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>Net</br>Payable</h3>
			<h3>
				<span id="net-payable-total">
					<?php echo $currency . ' ' . num_display($venue_totals['net_payable']) ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>Total</br>Payments</h3>
			<h3>
				<span id="paid-amount-total">
					<?php echo $currency . ' ' . num_display($venue_totals['paid_amount']) ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>Balance</br>Due</h3>
			<h3>
				<span id="balance-due-total">
					<?php echo $currency . ' ' . num_display($venue_totals['balance_due']) ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>UnRedeemed</br>Income</h3>
			<h3>
				<span id="gr-value-total">
					<?php echo $currency . ' ' . num_display($venue_totals['unredeemed_income']) ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>Total</br>Income</h3>
			<h3>
				<span id="gr-value-total">
					<?php echo $currency . ' ' . num_display($venue_totals['total_income']) ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>Income /</br>Sales</h3>
			<h3>
				<span id="gr-value-total">
					<?php echo num_display($profit_margin) ?>
				</span>
			</h3>
		</div>
	</div>

	<div id="summary-hidden-values">
	<input type="hidden" id="outstanding-year" value="<?php echo $year ?>">
	<input type="hidden" id="outstanding-year-type" value="<?php echo $year_type ?>">
	<input type="hidden" id="sum-gr-value" value="<?php echo $venue_totals['revenue'] ?>">
	<input type="hidden" id="sum-commission" value="<?php echo $venue_totals['commission'] ?>">
	<input type="hidden" id="sum-vat" value="<?php echo $venue_totals['vat'] ?>">
	<input type="hidden" id="sum-redeemed-cnt" value="<?php echo $venue_totals['redeemed_cnt'] ?>">
	<input type="hidden" id="sum-redeemed-qty" value="<?php echo $venue_totals['redeemed_qty'] ?>">
	<input type="hidden" id="sum-num-served" value="<?php echo $venue_totals['num_served'] ?>">
	<input type="hidden" id="sum-net-payable" value="<?php echo $venue_totals['net_payable'] ?>">
	<input type="hidden" id="sum-total-paid" value="<?php echo $venue_totals['paid_amount'] ?>">
	<input type="hidden" id="sum-balance-due" value="<?php echo $venue_totals['balance_due'] ?>">
	</div>

	<?php
}

function display_products_table($product_calcs, $venue_totals) {
	?>
	<div id="product-table-container" class="table-fixed-container">
	<table id="out-product-table" class="table table-striped table-bordered table-fixed">
		<thead>
			<th>ID</th>
			<th>Offer</th>
			<th>Status</th>
			<th>Revenue</th>
			<th>Redeemed</th>
			<th>Min Order</br>Date</th>
			<th>Venue Name</th>
			<th>Product</br>Date</th>
			<th>Net</br>Payable</th>
			<th>Balance</br>Due</th>
			<th>Action</th>
		</thead>
		<tbody>
			<?php
				foreach($product_calcs as $product_row) {
					display_product_row($product_row);
				}
			?>
		</tbody>
	</table>
	</div>
	<?php //display_table_totals($venue_totals) ?>
	<?php
}

function display_table_totals($venue_totals) {
?>
	<table class="table table-striped table-bordered table-fixed" style="width: 1091px;">
	<tbody>
		<tr>
			<td style="width: <?php echo ID_TD_WIDTH?>;">&nbsp;</td>
			<td>&nbsp;</td>
			<td class="table-total-label" style="width: <?php echo EXP_TD_WIDTH?>;">
				Totals:
			</td>
			<td class="table-nbr" style="width: <?php echo TOTALS_TD_WIDTH?>;">
				<span id="gr-value-table-total">
					<?php echo num_display($venue_totals['revenue']) ?>
				</span>
			</td>
			<td class="table-nbr" style="width: <?php echo QTY_TD_WIDTH?>;">
				<span id="redeem-qty-display-table-total">
					<?php echo $venue_totals['redeemed-qty'] ?>
				</span>
			</td>
			<td class="table-nbr" style="width: <?php echo COMM_TD_WIDTH?>;">
				<span id="commission-display-table-total">
					<?php echo num_display($venue_totals['commission']) ?>
				</span>
			</td>
			<td class="table-nbr" style="width: <?php echo TOTALS_TD_WIDTH?>;">
				<span id="vat-display-table-total">
					<?php echo num_display($venue_totals['vat']) ?>
				</span>
			</td>
			<td class="table-nbr" style="width: <?php echo TOTALS_TD_WIDTH?>;">
				<span id="net-payable-table-total">
					<?php echo num_display($venue_totals['net_payable']) ?>
				</span>
			</td>
			<td class="table-nbr" style="width: <?php echo TOTALS_TD_WIDTH?>;">
				<span id="balance-due-table-total">
					<?php echo num_display($venue_totals['balance_due']) ?>
				</span>
			</td>
		</tr>
	</tbody>
	</table>
	<?php
}

function display_product_row($product_row) {
	extract($product_row);
	$id = $product_id;
	// determine if the order date is earlier than the product date, indicating a repeating product
	$ord_date = new DateTime($min_order_date);
	$prod_date = new DateTime($product_date);
	$tr_class = ($ord_date < $prod_date) ? 'highlight-row' : '';											
	?>
	<tr class="<?php echo $tr_class ?>">
	<td style="width: <?php echo ID_TD_WIDTH?>;"><?php echo $id ?></td>
	<td><?php echo substr($title, 0, 50) ?></td>
	<td style="width: <?php echo EXP_TD_WIDTH?>;"><?php echo $status ?></td>
	<td class="table-nbr" style="width: <?php echo TOTALS_TD_WIDTH?>;">
		<span id="grevenue-display-<?php echo $id ?>">
			<?php echo num_display($revenue) ?>
		</span>
	</td>
	<td class="table-nbr" style="width: <?php echo QTY_TD_WIDTH?>;">
		<span id="redeem-qty-display-<?php echo $id ?>">
			<?php echo $redeemed_qty ?>
		</span>
	</td>
	<td class="table-nbr" style="width: <?php echo COMM_TD_WIDTH?>;">
		<span id="min_order_date-display-<?php echo $id ?>">
			<?php echo $min_order_date ?>
		</span>
	</td>
	<td class="table-nbr" style="width: <?php echo COMM_TD_WIDTH?>;">
		<span id="max_order_date-display-<?php echo $id ?>">
			<?php echo $venue_name ?>
		</span>
	</td>
	<td class="table-nbr" style="width: <?php echo COMM_TD_WIDTH?>;">
		<span id="product_date-display-<?php echo $id ?>">
			<?php echo $product_date ?>
		</span>
	</td>
	<td class="table-nbr" style="width: <?php echo TOTALS_TD_WIDTH?>;">
		<span id="payable-display-<?php echo $id ?>">
			<?php echo num_display($net_payable) ?>
		</span>
	</td>
	<td class="table-nbr" style="width: <?php echo TOTALS_TD_WIDTH?>;">
		<span id="balance-due-display-<?php echo $id ?>">
			<?php echo num_display($balance_due) ?>
		</span>
	</td>
	<td style="width: <?php echo ACTION_TD_WIDTH?>;"><?php echo $view ?></td>
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
	// display number with 2 decimal rounding and formatting
	return number_format(round($num,2), 2);
}

function num_display_no_decs ($num) {
	// display number with 2 decimal rounding and formatting
	return number_format(round($num), 0);
}
