<?php

$payment_rows = $wpdb->get_results($wpdb->prepare("
		SELECT  venue.name, venue.venue_id, pmnt.id, vp.product_id, pmnt.amount, 
				CAST(pmnt.timestamp AS DATE) AS payment_date
		FROM wp_offer_payments pmnt
		JOIN wp_taste_venue_products vp ON pmnt.pid = vp.product_id
		JOIN wp_taste_venue venue ON venue.venue_id = vp.venue_id
		WHERE amount > 0
		ORDER BY venue.venue_id, vp.product_id, payment_date"), ARRAY_A);


$grand_totals = array(
	'good' => 0,
	'partial' => 0,
	'overpay' => 0,
	'totals_good' => 0,
	'totals_partial' => 0,
	'totals_overpay' => 0,
	'total_payments' => 0,
	'total_products' => 0,
);

// create an empty div with height 100 so that an absolute div can be used later with the totals
?>
<div style="position: absolute; top: 210px; height: 600px; overflow-y: scroll; width: 100%; ">
<?php

$prev_venue_id = 0;
$prev_product_id = 0;
$product_info = array();
$product_payments_total = 0;
foreach($payment_rows as $payment) {
	extract($payment);

	// check if new product and not first.  if so, display
	if ($prev_product_id && $prev_product_id !== $product_id) {
		process_product_totals($product_info, $product_payments_total);
	}
	// check for new venue and display if necessary
	if ($prev_venue_id !== $venue_id) {
		display_new_venue($venue_id, $name, $prev_venue_id);
		$prev_venue_id = $venue_id;

	}
	// check for new product id and get info
	if ($prev_product_id !== $product_id) {
		$product_info = get_product_info($product_id);
		display_new_product($product_id, $product_info);
		$prev_product_id = $product_id;
		$product_payments_total = 0;
		
	}
	// run payment against the product price, commission, vat 
	$payment_results = payment_calc($product_info, $amount);	
	$payment_multiple = $payment_results['payment_multiple'];
	$payment_remainder = $payment_results['payment_remainder'];
	$redeemed_qty = $product_info['redeemed_qty'];

	if ($payment_remainder) {
		$grand_totals['partial'] += 1;
	} else {
		$grand_totals['good'] += 1;
	}
	
	if (($payment_multiple > $redeemed_qty) || ($payment_multiple === $redeemed_qty && $payment_remainder	)) {
		$grand_totals['overpay'] += 1;
	}
	$grand_totals['total_payments'] += 1;
	$product_payments_total += $amount;
	// display result (good/bad, multiple, remainder)
	$heading = "Payment ID: $id : $amount";
	display_results($heading, $payment_results, $product_info);
}

process_product_totals($product_info, $product_payments_total);
echo '</div>';
// display totals
?>
	<div style="display: flex; height: 200px; width: 100%; position: fixed; top: 0; left: 0;">
		<div style="display: inline-block; width: 50%; padding:  20px;">
			<p>Good payment count: <?php echo $grand_totals['good'] ?></p>
			<p>Partial payment count: <?php echo $grand_totals['partial'] ?></p>
			<p>Overpayment payment count: <?php echo $grand_totals['overpay'] ?></p>
			<p>Total Payments: <?php echo $grand_totals['total_payments'] ?></p>
		</div>
		<div style="display: inline-block; width: 50%; padding:  20px;">
			<p>Good total product count: <?php echo $grand_totals['totals_good'] ?></p>
			<p>Partial total product count: <?php echo $grand_totals['totals_partial'] ?></p>
			<p>Overpay total product count: <?php echo $grand_totals['totals_overpay'] ?></p>
			<p>Total products: <?php echo $grand_totals['total_products'] ?></p>
		</div>
	</div>
<?php

function display_new_venue($venue_id, $name, $prev_venue_id) {
	if ($prev_venue_id) {
		echo '</section>';
	}
	?>
	<section style="width: 90%; margin: 10px auto;">
		<h2>Payments for <?php echo $name . ' - ' . $venue_id ?></h2>
	<?php
}

function display_new_product($product_id, $product_info) {
	?>
	<div style="padding-left: 20px;">
		<h3>Product ID: <?php echo $product_id ?></h3>
		<p>Net Payable for 1 order: <?php echo $product_info['payable'] ?></p>
	</div>
	<?php
}

function get_product_info($product_id) {
	global $wpdb;
	
	$product_table = $wpdb->prefix."wc_product_meta_lookup";
	$product_order_table = $wpdb->prefix."wc_order_product_lookup";
	$post_meta_table = $wpdb->prefix."postmeta";
	$posts_table = $wpdb->prefix."posts";
	$order_items_table = $wpdb->prefix."woocommerce_order_items";

	$product_rows = $wpdb->get_results($wpdb->prepare("
				SELECT pr.product_id, pr.sku, p.post_title, pr.onsale, p.post_date, pm.meta_value AS 'children', 
					UPPER(pm2.meta_value) AS 'expired', pm3.meta_value AS 'price', pm4.meta_value AS 'vat',
					pm5.meta_value AS 'commission', pm6.meta_value AS 'bed_nights', 
					COALESCE(pm7.meta_value, 2) AS 'total_covers',
					SUM(IF(orderp.post_status = 'wc-completed', 1, 0)) AS 'order_cnt', 
					SUM(IF(orderp.post_status = 'wc-completed',plook.product_qty, 0)) AS 'order_qty', 
					SUM(IF(orderp.post_status = 'wc-completed',wc_oi.downloaded, 0)) AS 'redeemed_cnt', 
					SUM(IF(orderp.post_status = 'wc-completed',wc_oi.downloaded * plook.product_qty, 0)) AS 'redeemed_qty'
				FROM $product_table pr 
				JOIN $posts_table p ON pr.product_id =  p.ID
				LEFT JOIN $post_meta_table pm ON pr.product_id = pm.post_id AND pm.meta_key = '_children'
				LEFT JOIN $post_meta_table pm2 ON pr.product_id = pm2.post_id AND pm2.meta_key = 'Expired'
				LEFT JOIN $post_meta_table pm3 ON pr.product_id = pm3.post_id AND pm3.meta_key = '_sale_price'
				LEFT JOIN $post_meta_table pm4 ON pr.product_id = pm4.post_id AND pm4.meta_key = 'vat'
				LEFT JOIN $post_meta_table pm5 ON pr.product_id = pm5.post_id AND pm5.meta_key = 'commission'
				LEFT JOIN $post_meta_table pm6 ON pr.product_id = pm6.post_id AND pm6.meta_key = 'bed_nights'
				LEFT JOIN $post_meta_table pm7 ON pr.product_id = pm7.post_id AND pm7.meta_key = 'total_covers'
				LEFT JOIN $product_order_table plook ON plook.product_id = pr.product_id
				LEFT JOIN $posts_table orderp ON orderp.ID = plook.order_id 
				LEFT JOIN $order_items_table wc_oi ON wc_oi.order_item_id = plook.order_item_id
					AND orderp.post_status = 'wc-completed'
					AND orderp.post_type = 'shop_order'
				WHERE	pr.product_id = %d
				GROUP BY pr.product_id
				ORDER BY expired ASC, p.post_date DESC", 
				$product_id), ARRAY_A);
	
	$price = $product_rows[0]['price'];
	$vat_val = $product_rows[0]['vat'];
	$commission_val = $product_rows[0]['commission'];

	$grevenue = $price;
	$commission = ($grevenue / 100) * $commission_val;
	$vat = ($commission / 100) * $vat_val;
	$grevenue = round($grevenue, 2);
	$commission = round($commission, 2);
	$vat = round($vat, 2);
	$payable = $grevenue - ($commission + $vat);
	$payable = round($payable, 2);

	return array(
		'price' => $price,
		'vat' => $vat_val,
		'commission' => $commission_val,
		'redeemed_qty' => $product_rows[0]['redeemed_qty'],
		'payable' => $payable,
	);
}

function payment_calc($product_info, $amount) {
	// calc net payable per order 
	$payable = $product_info['payable'];

	$quotient = $amount / $payable;
	$payment_multiple = floor($quotient);
	$payment_remainder_pct = $quotient - $payment_multiple;
	$payment_remainder = ($payment_remainder_pct > 0.01) ? $amount % $payable : 0;

	return compact('payment_multiple', 'payment_remainder');
}

function display_results($heading, $results, $product_info) {
	$payment_multiple = $results['payment_multiple'];
	$payment_remainder = $results['payment_remainder'];
	$redeemed_qty = $product_info['redeemed_qty'];

	$good_or_bad_color = $payment_remainder ? 'red' : 'green';
	$over_payment_display = (($payment_multiple > $redeemed_qty) ||
													($payment_multiple === $redeemed_qty && $payment_remainder
													)) 	? '<span style="color: orange;">** Over Payment ** </span>' : '';
	?>
		<div style="padding-left: 20px; color: <?php echo $good_or_bad_color ?>;"">
			<h4><?php echo $heading ?></h4>
			<p>Multiple: <?php echo $payment_multiple ?></p>
			<p>Remainder: <?php echo $payment_remainder ? $payment_remainder : 'none' ?></p>
			<p>Redeemed Qty: <?php echo $redeemed_qty . ' ' . $over_payment_display?></p>
		</div>
	<?php
}

function process_product_totals($product_info, $product_payments_total) {
	global $grand_totals;

	$total_results = payment_calc($product_info, $product_payments_total);
	$payment_multiple = $total_results['payment_multiple'];
	$payment_remainder = $total_results['payment_remainder'];
	$redeemed_qty = $product_info['redeemed_qty'];

	if ($payment_remainder) {
		$grand_totals['totals_partial'] += 1;
	} else {
		$grand_totals['totals_good'] += 1;
	}
	
	if (($payment_multiple > $redeemed_qty) || ($payment_multiple === $redeemed_qty && $payment_remainder	)) {
		$grand_totals['totals_overpay'] += 1;
	}
	$grand_totals['total_products'] += 1;

	$heading = "Total Payments for Product $product_id: $product_payments_total";
	display_results($heading, $total_results, $product_info);
}