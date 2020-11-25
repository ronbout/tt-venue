<?php 

/**
 * Template partial to display a list of vouchers
 * for a given product, which will be in the args
 */
$user = wp_get_current_user();
$role = $user->roles[0];
$admin = ('ADMINISTRATOR' === strtoupper($role));

// NOTE:  $product_id and $multiplier are in the code which includes this file

$pid = $product_id;
$order_rows = $wpdb->get_results($wpdb->prepare("
		SELECT p.post_title,
			im.meta_value AS quan,
			wclook.product_id AS productID,
			bf.meta_value AS b_fname,
			bl.meta_value AS b_lname,
			be.meta_value AS b_email,
			i.order_id, i.order_item_id as itemid, i.downloaded as downloaded,i.paid as paid
		FROM " . $wpdb->prefix . "wc_order_product_lookup wclook
		JOIN " . $wpdb->prefix . "woocommerce_order_itemmeta im ON im.order_item_id = wclook.order_item_id
		LEFT JOIN " . $wpdb->prefix . "woocommerce_order_items i ON i.order_item_id = wclook.order_item_id
		LEFT JOIN " . $wpdb->prefix . "posts o ON o.id = wclook.order_id
		JOIN " . $wpdb->prefix . "posts p ON p.id = %d
		LEFT JOIN " . $wpdb->prefix . "postmeta bf ON bf.post_id = wclook.order_id
		LEFT JOIN " . $wpdb->prefix . "postmeta bl ON bl.post_id = wclook.order_id
		LEFT JOIN " . $wpdb->prefix . "postmeta be ON be.post_id = wclook.order_id
		WHERE im.meta_key = '_qty'
		AND bf.meta_key = '_billing_first_name'
		AND bl.meta_key = '_billing_last_name'
		AND be.meta_key = '_billing_email'
		AND o.post_status = 'wc-completed'
		AND o.post_type = 'shop_order'
		AND wclook.product_id = %d group by o.id", $pid, $pid));

$product_row = $wpdb->get_results($wpdb->prepare("
	SELECT  pm.post_id, 
					MAX(CASE WHEN pm.meta_key = '_sale_price' then pm.meta_value ELSE NULL END) as price,
					MAX(CASE WHEN pm.meta_key = 'vat' then pm.meta_value ELSE NULL END) as vat,
					MAX(CASE WHEN pm.meta_key = 'commission' then pm.meta_value ELSE NULL END) as commission,
					MAX(CASE WHEN pm.meta_key = 'expired' then pm.meta_value ELSE NULL END) as expired,
					MAX(CASE WHEN pm.meta_key = '_purchase_note' then pm.meta_value ELSE NULL END) as purchase_note

	FROM   {$wpdb->prefix}postmeta pm
	WHERE pm.post_id = %d                    
	GROUP BY
		pm.post_id
", $pid), ARRAY_A);

$product_price = $product_row[0]['price'];
$vat_val = $product_row[0]['vat'];
$commission_val = $product_row[0]['commission'];
$expired_val = $product_row[0]['expired'];
$tandc_val = $product_row[0]['purchase_note'];

$termsandconditions = str_replace('\r\n','<br>', json_encode($tandc_val));
$termsandconditions = str_replace('[{"meta_value":"','', $termsandconditions);
$termsandconditions = str_replace('"}]','', $termsandconditions);
$termsandconditions = str_replace('(\u20ac80)','', $termsandconditions);
$termsandconditions = str_replace('<a hef="mailto:','', $termsandconditions);
$termsandconditions = str_replace('<\/a>','', $termsandconditions);
$termsandconditions = str_replace('\u20ac','€', $termsandconditions);
$termsandconditions = str_replace('\u2013','-', $termsandconditions);
$termsandconditions = str_replace('\u2019','', $termsandconditions);



$activecampaign = 'N';


if(strpos($expired_val, 'N') !== false){
	$expired_val = 'N';
} else{
	$expired_val = 'Y';
}


?>
<div class="row">
<div class="col-md-12">
<p class="pimage">
<b>Revenue Campaign : <u><?= $pid ?></u> : </b><?= $order_rows[0]->post_title ?></p>

<b>Campaign Status : </b>
<?php
if($expired_val == $activecampaign) {
	echo 'Active';
}
Else {
	echo 'Expired';
}
?>

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

// $payable is calc'd inside the table but needs to come out to be used in the payments table
$order_totals = display_orders_table($order_rows, $expired_val, $product_price, $vat_val, $commission_val );
$payable = $order_totals['payable'];
$redeem_qty = $order_totals['redeem_qty'];
$total_sold = $order_totals['total_sold'];
?>
<p class="pimage">
<b>Campaign Terms & Conditions</b> (printed on each voucher)</p>
<?php 

echo stripslashes($termsandconditions);
$total_paid_to_customer = display_payments_table($pid, $payable, $admin);
?>
<div id="hidden-values">
	<input type="hidden" id="taste-product-id" value="<?php echo $pid ?>">
	<input type="hidden" id="taste-product-multiplier" value="<?php echo $multiplier ?>">
	<input type="hidden" id="taste-gr-value" value="<?php echo $product_price ?>">
	<input type="hidden" id="taste-commission-value" value="<?php echo $commission_val ?>">
	<input type="hidden" id="taste-vat-value" value="<?php echo $vat_val ?>">
	<input type="hidden" id="taste-redeem-qty" value="<?php echo $redeem_qty ?>">
	<input type="hidden" id="taste-num-served" value="<?php echo ($redeem_qty * $multiplier) ?>">
	<input type="hidden" id="taste-total-sold" value="<?php echo $total_sold ?>">
	<input type="hidden" id="taste-total-paid" value="<?php echo $total_paid_to_customer ?>">
</div>


<?php
function display_payments_table($pid, $payable, $admin) {
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
			
			<?php 
			if ($admin) {
				?>
				<hr>
				<div class="text-center">
					<div id="make-payment-div">
						<b>For Office Use Only:</b><br><br>
						<b>€</b> <input type="text" id="map-amount" name="MAP_Amount" value="0.00" style="width='100px';">
						<input type="hidden" name="product_id" value="<?= $pid ?>">
						<input type="hidden" name="product_pass" value="<?= $pass ?>"><br><br>
						<button type="button" id="make-payment-btn" class="btn btn-primary">Make a Payment</button>
					</div>
				</div>
				<?php
			}
			?>
			<br><br>
		</div>
	<?php
	return $total_paid_to_customer;
}

function display_orders_table($order_rows, $expired_val, $product_price, $vat_val, $commission_val ) {
	// display the orders table 
	$total_sold = 0;
	$redeem_qty = 0;
	$tproduct = 0;
	?>
	<div class="panel panel-default">
		<div class="panel-heading"><h2 style="text-align: center">CAMPAIGN SUMMARY</h2></div>
		<div class="panel-body">
			<button class="btn btn-success order-redeem-checked-btn" disabled >Redeem Checked</button>
			<div id="voucher-table-container" class="table-fixed-container">
				<table class="table table-striped table-bordered table-fixed">
					<?php display_order_table_heading($order_rows, $expired_val) ?>

						<tbody id="voucher-table-body">

						<?php 
						foreach ($order_rows as $order_item_info) {
							$tproduct = $tproduct + 1;							
							$total_sold = $total_sold + $order_item_info->quan;
							if (1 == $order_item_info->downloaded ) {
								$redeem_qty = $redeem_qty + $order_item_info->quan;
							}
							display_order_table_row($order_item_info, $expired_val);
						}
						?>
						</tbody>
				</table>
			</div>
			<?php 
				$payable = display_order_table_summary($redeem_qty, $total_sold, $product_price, $commission_val, $vat_val)
			?>
		</div>
	</div>
	<?php
	// have to return some of th totals calulated 
	$order_totals = array (
		'payable' => $payable,
		'redeem_qty' => $redeem_qty,
		'total_sold' => $total_sold,
	);
	return $order_totals;
}

function display_order_table_heading($order_rows, $expired_val) {
	// just the table headers
	?>
		<thead>
			<th>
				<?php 
					if ($expired_val === 'N' && in_array('0', array_column($order_rows, 'downloaded'))) {
						?>
							<input type="checkbox" id="checkbox-all">
						<?php
					} else {
						echo '';
					}
				?>
			</th>
			<th>Order ID</th>
			<th>Customer Name</th>
			<th>Customer Email</th>
			<th>Quantity</th>
			<th>Date</th>
		</thead>
	<?php
}

function display_order_table_row($order_item_info, $expired_val) {
	// display the individual  row
	?>
	<tr data-order-id="<?php echo $order_item_info->order_id ?>" 
			data-order-qty="<?php echo $order_item_info->quan ?>" 
			data-order-item-id="<?php echo $order_item_info->itemid ?>"
	>
		<td id="td-check-order-id-<?php echo $order_item_info->order_id ?>">
			<?php 
				if ($order_item_info->downloaded === '0' && $expired_val === 'N') {
					?>
						<input type="checkbox" class="order-redeem-check">
					<?php
				} else {
					echo '';
				}
			?>
		</td>
		<td><?= $order_item_info->order_id ?></td>
		<td><?= $order_item_info->b_fname . ' ' . $order_item_info->b_lname ?></td>
		<?php
					if ($order_item_info->downloaded == '1') {
				?>
					<td><span id="email-display-<?php echo $order_item_info->order_id ?>"><?= $order_item_info->b_email ?></span></td>
				<?php
					} else {
				?>
					<td><span id="email-display-<?php echo $order_item_info->order_id ?>">*** GDPR BLANKED EMAIL ***</span></td>
				<?php
					}
				?>

		<td><?= $order_item_info->quan ?> </td>
		<td id="td-btn-order-id-<?php echo $order_item_info->order_id ?>" class="text-center">
				<?php
				if ($order_item_info->downloaded == '0') {
						if ($expired_val == 'N') {
							echo '<button	class="btn btn-success order-redeem-btn">Redeem</button>';
						}
						else {
								echo 'Not Served / Expired';
						}
				}	else {
					if ($expired_val == 'N') {
						echo '<button	class="btn btn-info order-unredeem-btn">Unredeem</button>';
					} else {
						echo '<b>Served</b>';
					}
					$redeem_qty = $redeem_qty + $order_item_info->quan;
				}
						?>
		</td> 
	</tr>
	<?php
}

function display_order_table_summary($redeem_qty, $total_sold, $product_price, $commission_val, $vat_val) {
	// the totals at the end
	$grevenue = $redeem_qty * $product_price;
	$commission = ($grevenue / 100) * $commission_val;
	$vat = ($commission / 100) * $vat_val;
	$grevenue = round($grevenue, 2);
	$commission = round($commission, 2);
	$vat = round($vat, 2);
	$payable = $grevenue - ($commission + $vat);
	$payable = round($payable, 2);
	?>
		<table id="voucher-summary-table" class="table table-striped table-bordered">
			<tbody>
				<tr>
						<td></td>
						<td></td>
				</tr>
				<tr>
						<td class="voucher-summary-header"><b>Gross Revenue</b></td>

						<td class="voucher-summary-data">
							<b>
							<span id="grevenue-display">
								<?= get_woocommerce_currency_symbol() ?> <?= number_format($grevenue, 2)  ?>
							</span>
							</b>
						</td>
				</tr>
				<tr>
						<td class="voucher-summary-header">Commission</td>
						<td class="voucher-summary-data">
							<span id="commission-display">
								<?= get_woocommerce_currency_symbol() ?> <?= number_format($commission, 2)  ?>
							</span>
						</td>
				</tr>
				<tr>
						<td class="voucher-summary-header">Vat</td>
						<td class="voucher-summary-data">
							<span id="vat-display">
								<?= get_woocommerce_currency_symbol() ?> <?= number_format($vat, 2)  ?>
							</span>
						</td>
				</tr>
				<tr>
						<td class="voucher-summary-header"><b>Net Payable </b></td>
						<td class="voucher-summary-data">
							<b>
							<span id="payable-display">
								<?= get_woocommerce_currency_symbol() ?> <?= number_format($payable, 2)  ?>
							</span>
							</b>
						</td>
				</tr>
				<tr>
						<td class="voucher-summary-header"><b>Redeemed</b></td>
						<td class="voucher-summary-data">Served 
							<span id="redeem-qty-display"><?= $redeem_qty ?></span> customers <br> out of a possible <span id="total-sold-display"><?= $total_sold ?>
							</span>
						</td>
				</tr>
			</tbody>
		</table>
		<?php
		return $payable;
}