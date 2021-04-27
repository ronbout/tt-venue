<?php 
/**
 * 
 * Template partial to display a list of vouchers
 * for a given product(s) and list of columns
 * 
 * Author: Ron Boutilier
 * Date: 11/15/2020
 * 
 */
function display_voucher_table($product_ids, $disp_order_cols) {
	global $wpdb;
	$pid_placeholders = array_fill(0, count($product_ids), '%d');
	$pid_placeholders = implode(', ', $pid_placeholders);

	$user = wp_get_current_user();
	$role = $user->roles[0];
	$admin = ('ADMINISTRATOR' === strtoupper($role));

	require_once TASTE_PLUGIN_INCLUDES.'/ajax/outstanding/out-column-data.php';

	$order_item_rows = $wpdb->get_results($wpdb->prepare("
			SELECT 
				im.meta_value AS qty,
				wclook.product_id,
				bf.meta_value AS cust_fname,
				bl.meta_value AS cust_lname,
				be.meta_value AS cust_email, 
				i.order_id, i.order_item_id, i.downloaded,
				wclook.coupon_amount AS coupon_amt,
				wclook.product_net_revenue AS paid_amt,
				o.post_date as order_date
			FROM {$wpdb->prefix}wc_order_product_lookup wclook
			JOIN {$wpdb->prefix}woocommerce_order_itemmeta im ON im.order_item_id = wclook.order_item_id
			LEFT JOIN {$wpdb->prefix}woocommerce_order_items i ON i.order_item_id = wclook.order_item_id
			LEFT JOIN {$wpdb->prefix}posts o ON o.id = wclook.order_id
			LEFT JOIN {$wpdb->prefix}postmeta bf ON bf.post_id = wclook.order_id
			LEFT JOIN {$wpdb->prefix}postmeta bl ON bl.post_id = wclook.order_id
			LEFT JOIN {$wpdb->prefix}postmeta be ON be.post_id = wclook.order_id
			WHERE im.meta_key = '_qty'
			AND bf.meta_key = '_billing_first_name'
			AND bl.meta_key = '_billing_last_name'
			AND be.meta_key = '_billing_email'
			AND o.post_status = 'wc-completed'
			AND o.post_type = 'shop_order'
			AND wclook.product_id in ($pid_placeholders) 
			GROUP BY wclook.product_id, o.id
			ORDER BY wclook.product_id, o.id", $product_ids), ARRAY_A);

	$product_rows = $wpdb->get_results($wpdb->prepare("
		SELECT  pm.post_id, p.post_title,
						v.venue_id, v.name AS venue_name,
						MAX(CASE WHEN pm.meta_key = '_sale_price' then pm.meta_value ELSE NULL END) as price,
						MAX(CASE WHEN pm.meta_key = 'vat' then pm.meta_value ELSE NULL END) as vat,
						MAX(CASE WHEN pm.meta_key = 'commission' then pm.meta_value ELSE NULL END) as commission,
						MAX(CASE WHEN pm.meta_key = 'expired' then pm.meta_value ELSE NULL END) as expired,
						MAX(CASE WHEN pm.meta_key = '_purchase_note' then pm.meta_value ELSE NULL END) as purchase_note

		FROM   {$wpdb->prefix}postmeta pm
		JOIN {$wpdb->prefix}posts p ON p.id = pm.post_id
		LEFT JOIN {$wpdb->prefix}taste_venue_products vp ON vp.product_id = pm.post_id
		LEFT JOIN {$wpdb->prefix}taste_venue v ON v.venue_id = vp.venue_id
		WHERE pm.post_id in ($pid_placeholders)                 
		GROUP BY
			pm.post_id
	", $product_ids), ARRAY_A);

	$product_data = array();
	foreach($product_rows as $product_row) {
		$tmp = array();
		$tmp['product_price'] = $product_row['price'];
		$tmp['vat_val'] = $product_row['vat'];
		$tmp['commission_val'] = $product_row['commission'];
		$tmp['expired_val'] = $product_row['expired'];
		$tmp['tandc_val'] = $product_row['purchase_note'];
		$tmp['product_title'] = $product_row['post_title'];
		$tmp['venue_id'] = $product_row['venue_id'];
		$tmp['venue_name'] = $product_row['venue_name'];
		$tmp['expired_val'] = (strpos($expired_val, 'N') !== false) ? 'N' : 'Y';
		$product_data[$product_row['post_id']] = $tmp;
	}

	/*
	$termsandconditions = str_replace('\r\n','<br>', json_encode($tandc_val));
	$termsandconditions = str_replace('[{"meta_value":"','', $termsandconditions);
	$termsandconditions = str_replace('"}]','', $termsandconditions);
	$termsandconditions = str_replace('(\u20ac80)','', $termsandconditions);
	$termsandconditions = str_replace('<a hef="mailto:','', $termsandconditions);
	$termsandconditions = str_replace('<\/a>','', $termsandconditions);
	$termsandconditions = str_replace('\u20ac','€', $termsandconditions);
	$termsandconditions = str_replace('\u2013','-', $termsandconditions);
	$termsandconditions = str_replace('\u2019','', $termsandconditions);
	*/

	$order_item_rows_and_totals = calc_order_data($order_item_rows, $product_data);

	$order_item_row_data = $order_item_rows_and_totals['rows'];
	$order_totals = $order_item_rows_and_totals['totals'];
	$payable = $order_totals['payable'];

	// display_campaign_header($expired_val, $product_title);
	display_orders_table($order_item_row_data, $order_totals, $outstanding_order_columns, $disp_order_cols );
	// display_terms($termsandconditions);
	display_payments_table($product_ids, $pid_placeholders, $payable);
}

function calc_order_data($order_item_rows, $product_data) {
	// $expired_val, $product_price, $vatval, $commission_val, $product_id
	$order_totals = array(
		'orders' => 0,
		'redeem_qty' => 0,
		'total_sold' => 0,
		'paid_amt' => 0,
		'coupon_amt' => 0,
		'gross_revenue' => 0,
		'commission' => 0,
		'vat' => 0,
		'payable' => 0,
	);

	$order_data = array();
	foreach($order_item_rows as $order_item_row) {
		// get info from product data based on product id
		$product_id = $order_item_row['product_id'];
		$expired_val = $product_data[$product_id]['expired_val'];
		$product_price = $product_data[$product_id]['product_price'];
		$vat_val = $product_data[$product_id]['vat_val'];
		$commission_val = $product_data[$product_id]['commission_val'];

		$order_totals['orders'] += 1;
		$order_totals['total_sold'] += $order_item_row['qty'];
		$redeemed_qty = 0;
		if ('1' === $order_item_row['downloaded']) {
			$redeemed_qty  = $order_item_row['qty'];
			$order_totals['redeem_qty'] += $redeemed_qty;
		}
		$order_totals['paid_amt'] += $order_item_row['paid_amt'];
		$order_totals['coupon_amt'] += $order_item_row['coupon_amt'];

		$tmp = array();
		$tmp['order_id'] = $order_item_row['order_id'];
		$tmp['order_item_id'] = $order_item_row['order_item_id'];
		$tmp['customer_name'] = $order_item_row['cust_fname'] . ' ' . $order_item_row['cust_lname'];
		$tmp['customer_email'] = $order_item_row['cust_email'];
		$tmp['redeemed'] = ('1' === $order_item_row['downloaded']) ? 'Y' : '';
		$tmp['qty'] = $order_item_row['qty'];
		$tmp['product_id'] = $product_id;
		$tmp['price'] = number_format($product_price, 2);
		$tmp['paid_amt'] = number_format(intval($order_item_row['paid_amt']), 2);
		$tmp['coupon_amt'] = number_format(intval($order_item_row['coupon_amt']), 2);
		$tmp['taste_gross_revenue'] = number_format($order_item_row['coupon_amt'] + $order_item_row['paid_amt'], 2);
		$tmp['order_date'] = explode(' ', $order_item_row['order_date'])[0];
		$tmp['expired'] = $expired_val;
		$tmp['venue_name'] = $product_data[$product_id]['venue_name'] ? $product_data[$product_id]['venue_name'] : '------';
		$tmp['venue_id'] = $product_data[$product_id]['venue_id'] ? $product_data[$product_id]['venue_id'] : '-----';
		$order_data[] = $tmp;

		$grevenue = $redeemed_qty * $product_price;
		$commission = ($grevenue / 100) * $commission_val;
		$vat = ($commission / 100) * $vat_val;
		$payable = $grevenue - ($commission + $vat);
	
		$order_totals['gross_revenue'] += $grevenue;
		$order_totals['commission'] += $commission;
		$order_totals['vat'] += $vat;
		$order_totals['payable'] += $payable;
	}



	return array('rows' => $order_data, 'totals' => $order_totals);
}

/*
function display_campaign_header($expired_val, $product_title) {
	?>
	<div class="row">
		<div class="col-md-12">
			<p class="pimage">
			<b>Revenue Campaign : <u><?php echo $pid ?></u> : </b><?php echo $product_title ?></p>

			<b>Campaign Status : </b><?php echo ('N' === $expired_val) ? 'Active' : 'Expired' ?>
			<hr>
			<br>
			<b>Please Note : </b> This management console has 3 unique rules, the first is all payments due to venues are for served customers only, by law TheTaste must be able to complete refunds direct to customers who have not been served. The second change you will notice is as a result of the recent GDPR laws meaning we can only disclose the email addresses of the customers you have served. The final change is due to National Consumer Law meaning we have to allow 14 days after the campaign validity has expired to issue payments.
			<br><br>
			<b>Important : </b> By clicking the Redeem button below you are confirming you have fully served that customer and the customer will receive an automatic email thanking them and asking them to share their experience feedback with us. Fraudulently Redeeming Vouchers will expose details of customers below and break GDPR Laws.
			<br><br>
			<b style="color:red;">You must retain all paper vouchers for this campaign!</b>
			<br><br>
			<b style="color:red;">Fraudulently Redeeming Vouchers will result in a full paper audit of this campaign and Put Your Payment On Hold!</b>
			<br><br>
			<hr>
			<b>Campaign VAT Statement</b><br>
			JFG Digital Ltd T/A TheTaste.ie<br>
			5 Main Street, Rathangan, Co. Kildare<br>
			Company No 548735<br>
			VAT No 3312776JH<br>
			<br>
		</div>
	</div>
	<?php
}
*/

function display_orders_table($order_item_row_data, $order_totals, $outstanding_order_columns, $disp_order_cols  ) {
	?>
	<div class="panel panel-default">
		<div class="panel-heading"><h2 style="text-align: center">CAMPAIGN(S) SUMMARY</h2></div>
		<div class="panel-body">
			<?php
			if (count($order_item_row_data)) {
				?>
				<div class="table-title-action">
					<div><h3>Order Items (<?php echo number_format($order_totals['orders']) ?> Rows)</h3></div>
					<div>
						<a href="#" id ="export-orders" role='button'>
							<button class="btn btn-info">Download CSV</button>
						</a>
					</div>
				</div>
			<div id="voucher-table-container" class="table-fixed-container">
				<table  id="out-order-table" class="table table-striped table-bordered table-fixed">
					<?php display_order_table_heading($outstanding_order_columns, $disp_order_cols) ?>
						<tbody id="voucher-table-body">
						<?php									
							foreach($order_item_row_data as $order_item_row) {
								display_order_table_row($order_item_row, $disp_order_cols);
							}
						?>
					</tbody>
				</table>
			</div>
			<?php 
				display_order_table_summary($order_totals);
			} else {
				echo '<h3>No Orders Found</h3>';
			}
			?>
		</div>
	</div>
	<?php
}

function display_order_table_heading($outstanding_order_columns, $disp_order_cols) {
	// just the table headers
	?>
		<thead>
		<?php 
			foreach($disp_order_cols as $col) {
				echo "<th>$outstanding_order_columns[$col]</th>";
			}
		?>
		</thead>
	<?php
}

function display_order_table_row($order_item_row, $disp_order_cols) {
	?>
	<tr>
	<?php
		foreach($disp_order_cols as $col) {
			echo "<td>$order_item_row[$col]</td>";
		}
	?>
	</tr>
	<?php
}

function display_order_table_summary($order_totals) {
	?>
		<table id="voucher-summary-table" class="table table-striped table-bordered">
			<tbody>
				<tr>
						<td></td>
						<td></td>
				</tr>
				<tr>
						<td class="voucher-summary-header"><b>Venue Gross Revenue</b></td>

						<td class="voucher-summary-data">
							<b>
							<span id="grevenue-display">
								<?php echo get_woocommerce_currency_symbol() ?> <?php echo number_format($order_totals['gross_revenue'], 2)  ?>
							</span>
							</b>
						</td>
				</tr>
				<tr>
						<td class="voucher-summary-header">Commission</td>
						<td class="voucher-summary-data">
							<span id="commission-display">
								<?php echo get_woocommerce_currency_symbol() ?> <?php echo number_format($order_totals['commission'], 2)  ?>
							</span>
						</td>
				</tr>
				<tr>
						<td class="voucher-summary-header">Vat</td>
						<td class="voucher-summary-data">
							<span id="vat-display">
								<?php echo get_woocommerce_currency_symbol() ?> <?php echo number_format($order_totals['vat'], 2)  ?>
							</span>
						</td>
				</tr>
				<tr>
						<td class="voucher-summary-header"><b>Venue Net Payable </b></td>
						<td class="voucher-summary-data">
							<b>
							<span id="payable-display">
								<?php echo get_woocommerce_currency_symbol() ?> <?php echo number_format($order_totals['payable'], 2)  ?>
							</span>
							</b>
						</td>
				</tr>
				<tr>
						<td class="voucher-summary-header"><b>Redeemed</b></td>
						<td class="voucher-summary-data">Served 
							<span id="redeem-qty-display"><?php echo $order_totals['redeem_qty'] ?></span> customers <br> out of a possible <span id="total-sold-display"><?php echo $order_totals['total_sold'] ?>
							</span>
						</td>
				</tr>
				<tr><td colspan="2"> </td></tr>
				<tr>
						<td class="voucher-summary-header"><b>Total Received From Customer</b></td>
						<td class="voucher-summary-data">
							<b>
							<span id="payable-display">
								<?php echo get_woocommerce_currency_symbol() ?> <?php echo number_format($order_totals['paid_amt'], 2)  ?>
							</span>
							</b>
						</td>
				</tr>
				<tr>
						<td class="voucher-summary-header"><b>Total Coupon Amount</b></td>
						<td class="voucher-summary-data">
							<b>
							<span id="payable-display">
								<?php echo get_woocommerce_currency_symbol() ?> <?php echo number_format($order_totals['coupon_amt'], 2)  ?>
							</span>
							</b>
						</td>
				</tr>
			</tbody>
		</table>
		<?php
}

function display_terms($termsandconditions) {
	?>
	<p class="pimage">
	<b>Campaign Terms & Conditions</b> (printed on each voucher)</p>
	<?php
	echo stripslashes($termsandconditions);
}

function display_payments_table($product_ids, $pid_placeholders, $payable) {
	global $wpdb;
	
	$paymentList = $wpdb->get_results($wpdb->prepare("
				SELECT  id, timestamp, pid, amount
				FROM {$wpdb->prefix}offer_payments 
				WHERE pid IN ($pid_placeholders)
				ORDER BY pid, timestamp DESC ", $product_ids), ARRAY_A);

	$total_paid_to_customer = 0;

	?>
	<br><br>
	<div class="panel panel-default">			
		<div class="panel-heading"><h2 style="text-align: center">Payment Transactions </h2></div>
		<div class="panel-body">	
			<?php
			if (count($paymentList)) {
				?>
				<div class="table-title-action">
					<div><h3>Payment Items (<?php echo count($paymentList) ?> Rows)</h3></div>
					<div>
						<a href="#" id="export-payments" role='button'>
							<button class="btn btn-info">Download CSV</button>
						</a>
					</div>
				</div>
			<div id="payment-table-container" class="table-fixed-container">		
			<table id="audit-payment-table" class="table table-striped table-bordered">
				<thead>
					<tr>
						<th>Product Id</th>
						<th>Payment Id</th>
						<th>Payment Date</th>
						<th>Payment Amount</th>
					</tr>
				</thead>
				<tbody id="payment-lines">
					<?php
					
					foreach($paymentList as $payment){ 
						?>
						<tr>
							<td><?php echo $payment['pid'] ?></td>
							<td><?php echo $payment['id'] ?></td>
							<td><?php echo $payment['timestamp'] ?></td>
							<td><?php echo number_format($payment['amount'], 2)	?></td>
						</tr>
						<?php 
							$total_paid_to_customer = $total_paid_to_customer + $payment['amount'];
					}
					?>
				</tbody>
			</table>
			</div>
			<?php 
			} else {
				echo '<h3>No Payments Found</h3>';
			}
			?>
			<br>
			<div class="text-center">
				<b>Balance Due : <span id="balance-due-display"> <?php echo get_woocommerce_currency_symbol() ?> <?php echo number_format($payable - $total_paid_to_customer, 2) ?></span></b>
		</div>
		<br>
		<br><br>
	</div>
	<?php
}
