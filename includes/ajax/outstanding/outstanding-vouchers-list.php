<?php 

/**
 * Template partial to display a list of vouchers
 * for a given product, which will be in the args
 * $product_id, $disp_order_cols are available in containing 
 * code
 */
$user = wp_get_current_user();
$role = $user->roles[0];
$admin = ('ADMINISTRATOR' === strtoupper($role));

require_once TASTE_PLUGIN_INCLUDES.'/ajax/outstanding/out-column-data.php';

$pid = $product_id;
$order_item_rows = $wpdb->get_results($wpdb->prepare("
		SELECT im.meta_value AS qty,
			wclook.product_id AS productID,
			bf.meta_value AS cust_fname,
			bl.meta_value AS cust_lname,
			be.meta_value AS cust_email, 
			i.order_id, i.order_item_id, i.downloaded,
			wclook.coupon_amount,
			wclook.product_net_revenue as paid_amt,
			o.post_date as order_date
		FROM " . $wpdb->prefix . "wc_order_product_lookup wclook
		JOIN " . $wpdb->prefix . "woocommerce_order_itemmeta im ON im.order_item_id = wclook.order_item_id
		LEFT JOIN " . $wpdb->prefix . "woocommerce_order_items i ON i.order_item_id = wclook.order_item_id
		LEFT JOIN " . $wpdb->prefix . "posts o ON o.id = wclook.order_id
		LEFT JOIN " . $wpdb->prefix . "postmeta bf ON bf.post_id = wclook.order_id
		LEFT JOIN " . $wpdb->prefix . "postmeta bl ON bl.post_id = wclook.order_id
		LEFT JOIN " . $wpdb->prefix . "postmeta be ON be.post_id = wclook.order_id
		WHERE im.meta_key = '_qty'
		AND bf.meta_key = '_billing_first_name'
		AND bl.meta_key = '_billing_last_name'
		AND be.meta_key = '_billing_email'
		AND o.post_status = 'wc-completed'
		AND o.post_type = 'shop_order'
		AND wclook.product_id = %d group by o.id", $pid, $pid), ARRAY_A);

$product_row = $wpdb->get_results($wpdb->prepare("
	SELECT  pm.post_id, p.post_title,
					MAX(CASE WHEN pm.meta_key = '_sale_price' then pm.meta_value ELSE NULL END) as price,
					MAX(CASE WHEN pm.meta_key = 'vat' then pm.meta_value ELSE NULL END) as vat,
					MAX(CASE WHEN pm.meta_key = 'commission' then pm.meta_value ELSE NULL END) as commission,
					MAX(CASE WHEN pm.meta_key = 'expired' then pm.meta_value ELSE NULL END) as expired,
					MAX(CASE WHEN pm.meta_key = '_purchase_note' then pm.meta_value ELSE NULL END) as purchase_note

	FROM   {$wpdb->prefix}postmeta pm
	JOIN " . $wpdb->prefix . "posts p ON p.id = %d
	WHERE pm.post_id = %d                    
	GROUP BY
		pm.post_id
", $pid, $pid), ARRAY_A);

$venue_info = $wpdb->get_results($wpdb->prepare("
		SELECT v.venue_id, v.name
		FROM {$wpdb->prefix}taste_venue_products vp
		JOIN {$wpdb->prefix}taste_venue v ON v.venue_id = vp.venue_id
		WHERE vp.product_id = %d		
", $pid), ARRAY_A);

if (null === $venue_info) {
	$venue_name = '-------';
} else {
	$venue_name = $venue_info[0]['name'];
}

$product_price = $product_row[0]['price'];
$vat_val = $product_row[0]['vat'];
$commission_val = $product_row[0]['commission'];
$expired_val = $product_row[0]['expired'];
$tandc_val = $product_row[0]['purchase_note'];
$product_title = $product_row[0]['post_title'];

$termsandconditions = str_replace('\r\n','<br>', json_encode($tandc_val));
$termsandconditions = str_replace('[{"meta_value":"','', $termsandconditions);
$termsandconditions = str_replace('"}]','', $termsandconditions);
$termsandconditions = str_replace('(\u20ac80)','', $termsandconditions);
$termsandconditions = str_replace('<a hef="mailto:','', $termsandconditions);
$termsandconditions = str_replace('<\/a>','', $termsandconditions);
$termsandconditions = str_replace('\u20ac','€', $termsandconditions);
$termsandconditions = str_replace('\u2013','-', $termsandconditions);
$termsandconditions = str_replace('\u2019','', $termsandconditions);

if (strpos($expired_val, 'N') !== false) {
	$expired_val = 'N';
} else {
	$expired_val = 'Y';
}

$order_item_rows_and_totals = calc_order_data($order_item_rows, $expired_val, $product_price, $vat_val, $commission_val, $pid, $venue_name);

$order_item_row_data = $order_item_rows_and_totals['rows'];
$order_totals = $order_item_rows_and_totals['totals'];
$payable = $order_totals['payable'];

display_campaign_header($expired_val, $product_title);
display_orders_table($order_item_row_data, $order_totals, $outstanding_order_columns, $disp_order_cols );
display_terms($termsandconditions);
display_payments_table($pid, $payable);

function calc_order_data($order_item_rows, $expired_val, $product_price, $vat_val, $commission_val, $product_id, $venue_name) {
	$order_totals = array(
		'orders' => 0,
		'redeem_qty' => 0,
		'total_sold' => 0,
	);

	$order_data = array();
	foreach($order_item_rows as $order_item_row) {
		$order_totals['orders'] += 1;
		$order_totals['total_sold'] += $order_item_row['qty'];
		if ('1' === $order_item_row['downloaded']) {
			$order_totals['redeem_qty'] += $order_item_row['qty'];
		}
		$tmp = array();
		$tmp['order_id'] = $order_item_row['order_id'];
		$tmp['order_item_id'] = $order_item_row['order_item_id'];
		$tmp['customer_name'] = $order_item_row['cust_fname'] . ' ' . $order_item_row['cust_lname'];
		$tmp['customer_email'] = $order_item_row['cust_email'];
		$tmp['redeemed'] = ('1' === $order_item_row['downloaded']) ? 'Y' : '';
		$tmp['qty'] = $order_item_row['qty'];
		$tmp['product_id'] = $product_id;
		$tmp['price'] = $product_price;
		$tmp['paid_amt'] = intval($order_item_row['paid_amt']);
		$tmp['coupon_amt'] = intval($order_item_row['coupon_amt']);
		$tmp['gross_revenue'] = $tmp['coupon_amt'] + $tmp['paid_amt'];
		$tmp['order_date'] = explode(' ', $order_item_row['order_date'])[0];
		$tmp['expired'] = $expired_val;
		$tmp['venue_name'] = $venue_name;
		$order_data[] = $tmp;
	}

	$grevenue = $order_totals['redeem_qty'] * $product_price;
	$commission = ($grevenue / 100) * $commission_val;
	$vat = ($commission / 100) * $vat_val;
	$grevenue = round($grevenue, 2);
	$commission = round($commission, 2);
	$vat = round($vat, 2);
	$payable = $grevenue - ($commission + $vat);
	$payable = round($payable, 2);

	$order_totals['gross_revenue'] = $grevenue;
	$order_totals['commission'] = $commission;
	$order_totals['vat'] = $vat;
	$order_totals['payable'] = $payable;

	return array('rows' => $order_data, 'totals' => $order_totals);
}

function display_campaign_header($expired_val, $product_title) {
	?>
	<div class="row">
		<div class="col-md-12">
			<p class="pimage">
			<b>Revenue Campaign : <u><?= $pid ?></u> : </b><?= $product_title ?></p>

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
			Digital Food Ltd T/A TheTaste.ie<br>
			5 Main Street, Rathangan, Co. Kildare<br>
			Company No 548735<br>
			VAT No 3312776JH<br>
			<br>
		</div>
	</div>
	<?php
}

function display_orders_table($order_item_row_data, $order_totals, $outstanding_order_columns, $disp_order_cols  ) {
	?>
	<div class="panel panel-default">
		<div class="panel-heading"><h2 style="text-align: center">CAMPAIGN SUMMARY</h2></div>
		<div class="panel-body">
			<?php
			if (count($order_item_row_data)) {
				?>
				<div id="audit-orders-table-title-action">
					<div><h3>Order Items (<?php echo $order_totals['orders'] ?> Rows)</h3></div>
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
								<?= get_woocommerce_currency_symbol() ?> <?= number_format($order_totals['gross_revenue'], 2)  ?>
							</span>
							</b>
						</td>
				</tr>
				<tr>
						<td class="voucher-summary-header">Commission</td>
						<td class="voucher-summary-data">
							<span id="commission-display">
								<?= get_woocommerce_currency_symbol() ?> <?= number_format($order_totals['commission'], 2)  ?>
							</span>
						</td>
				</tr>
				<tr>
						<td class="voucher-summary-header">Vat</td>
						<td class="voucher-summary-data">
							<span id="vat-display">
								<?= get_woocommerce_currency_symbol() ?> <?= number_format($order_totals['vat'], 2)  ?>
							</span>
						</td>
				</tr>
				<tr>
						<td class="voucher-summary-header"><b>Net Payable </b></td>
						<td class="voucher-summary-data">
							<b>
							<span id="payable-display">
								<?= get_woocommerce_currency_symbol() ?> <?= number_format($order_totals['payable'], 2)  ?>
							</span>
							</b>
						</td>
				</tr>
				<tr>
						<td class="voucher-summary-header"><b>Redeemed</b></td>
						<td class="voucher-summary-data">Served 
							<span id="redeem-qty-display"><?= $order_totals['redeem_qty'] ?></span> customers <br> out of a possible <span id="total-sold-display"><?= $order_totals['total_sold'] ?>
							</span>
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

function display_payments_table($pid, $payable) {
	global $wpdb;
	
	$paymentList = $wpdb->get_results($wpdb->prepare("SELECT  * from " . $wpdb->prefix . "offer_payments where pid = %d", $pid));

	$total_paid_to_customer = 0;

	?>
	<br><br>
	<div class="panel panel-default">			
		<div class="panel-heading"><h2 style="text-align: center">Payment Transactions </h2></div>
		<div class="panel-body">			
			<table class="table table-striped table-bordered">
				<thead>
					<tr>
							<th>Payment Date</th>
							<th>Payment Amount</th>
					</tr>
				</thead>
				<tbody id="payment-lines">
					<?php
					
					foreach($paymentList as $val){ 
						?>
						<tr>
							<td><?= $val->timestamp ?></td>
							<td><?= get_woocommerce_currency_symbol() ?> <?= number_format($val->amount, 2) ?></td>
							<?php $total_paid_to_customer = $total_paid_to_customer + $val->amount ?>
						<?php 
					} 
					?>
					</tr>
				</tbody>
			</table>
			<br>
			<div class="text-center">
				<b>Balance Due : <span id="balance-due-display"> <?= get_woocommerce_currency_symbol() ?> <?= number_format($payable - $total_paid_to_customer, 2) ?></span></b>
		</div>
		<br>
		<br><br>
	</div>
	<?php
}
