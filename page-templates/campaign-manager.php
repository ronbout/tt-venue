<?php
/*
Template Name: Campaign Manager
*/

/**
 *  Date:  9/15/2020
 * 	Author: Ron Boutilier
 */
defined('ABSPATH') or die('Direct script access disallowed.');
global $wpdb;

if ( !is_user_logged_in()) {
	
	require_once TASTE_PLUGIN_PATH.'page-templates/thetaste-venue-login.php';
	die();
} else {
	$user_info = get_user_venue_info();
	$user =$user_info['user'];
	$role = $user_info['role'];
	$admin = ('ADMINISTRATOR' === strtoupper($role));

	if ('VENUE' !== strtoupper($role) && !$admin) {
			echo "<h2>Role: $role </h2>";
			die('You must be logged in as a Venue to access this page.');
	}
}

require_once TASTE_PLUGIN_PATH.'page-templates/partials/venue-head.php';
require_once TASTE_PLUGIN_PATH.'page-templates/partials/venue-navbar.php';
require_once TASTE_PLUGIN_INCLUDES.'/ajax/functions.php';

$venue_id = '';
if ($admin) {
	$nav_links = array(
		array(
			'title' => 'Log Out',
			'url' => wp_logout_url(get_site_url()),
			'active' => false
		),
	);
	// check if the Venue ID is in POST from form 
	if (isset($_POST['venue-id'])) {
		$venue_id = $_POST['venue-id'];
		// add the link to return to venue selection
		$venue_select_link = array(
			'title' => 'Venue Selection',
			'url' => get_page_link(),
			'active' => false
		);
		array_unshift($nav_links, $venue_select_link);
	} 
	$display_mode = 'payment';
} else {
	$nav_links = venue_navbar_standard_links($user_info['use_new_campaign'], $user_info['venue_voucher_page']);
	$venue_id = $user->ID;
	$display_mode = 'redeem';
}

?>
<body class="campaign-manager">
	<?php
	
	venue_navbar($nav_links);
	/**
	 * because this is not using the theme styling, I cannot (currently) run wp_head
	 * as a result, this is my wp_localize_script replacement
	**/
	echo "
			<script>
				let tasteVenue = {}
				tasteVenue.ajaxurl = '". admin_url( 'admin-ajax.php' ) . "'
				tasteVenue.security = '" . wp_create_nonce('taste-venue-nonce') . "'
				tasteVenue.displayMode = '" . $display_mode . "'
			</script>
		";
	if (!$venue_id) {
		// display form to select Venue as user is admin w/o a venue selected
		display_venue_select(true, 0, true, get_page_link());
		echo '<script type="text/javascript" src= "' . TASTE_PLUGIN_INCLUDES_URL . '/js/thetaste-venue-select.js"></script>';
		die();
	}
?>
	<main class="container mb-3">

		<?php // get the product listing from db 

			$product_table = $wpdb->prefix."wc_product_meta_lookup";
			$product_order_table = $wpdb->prefix."wc_order_product_lookup";
			$post_meta_table = $wpdb->prefix."postmeta";
			$posts_table = $wpdb->prefix."posts";
			$order_items_table = $wpdb->prefix."woocommerce_order_items";
			$venue_table = $wpdb->prefix."taste_venue";
			$v_p_join_table = $wpdb->prefix."taste_venue_products";
			$offer_payments_table = $wpdb->prefix."offer_payments";
			$payment_table = $wpdb->prefix."taste_venue_payment";
			$payment_products_table = $wpdb->prefix."taste_venue_payment_products";
			$payment_order_xref_table = $wpdb->prefix."taste_venue_payment_order_item_xref";

			// get venue name and cutoff date
			$venue_row = $wpdb->get_results($wpdb->prepare("
				SELECT v.name, v.venue_type, v.historical_cutoff_date
				FROM $venue_table v
				WHERE	v.venue_id = %d", 
			$venue_id));

			$cutoff_date_str = $venue_row[0]->historical_cutoff_date;

			$cutoff_date_str = $cutoff_date_str ? $cutoff_date_str : "2001-01-01";

			/**  temp effective removal of cutoff date by setting to 2001 */
			$cutoff_date_str =  "2001-01-01";

			$product_rows = $wpdb->get_results($wpdb->prepare("
							SELECT pr.product_id, pr.sku, p.post_title, pr.onsale, p.post_date, pm.meta_value AS 'children', 
								UPPER(pm2.meta_value) AS 'expired', pm3.meta_value AS 'price', pm4.meta_value AS 'vat',
								pm5.meta_value AS 'commission', pm6.meta_value AS 'bed_nights', 
								COALESCE(pm7.meta_value, 2) AS 'total_covers',
								SUM(IF(orderp.post_status = 'wc-completed', 1, 0)) AS 'order_cnt', 
								SUM(IF(orderp.post_status = 'wc-completed',plook.product_qty, 0)) AS 'order_qty', 
								SUM(IF(orderp.post_status = 'wc-completed',wc_oi.downloaded, 0)) AS 'redeemed_cnt', 
								SUM(IF(orderp.post_status = 'wc-completed',wc_oi.downloaded * plook.product_qty, 0)) AS 'redeemed_qty'
							FROM $v_p_join_table vp 
							JOIN $product_table pr ON vp.product_id = pr.product_id AND pr.onsale = 1
							JOIN $posts_table p ON vp.product_id =  p.ID
							LEFT JOIN $post_meta_table pm ON vp.product_id = pm.post_id AND pm.meta_key = '_children'
							LEFT JOIN $post_meta_table pm2 ON vp.product_id = pm2.post_id AND pm2.meta_key = 'Expired'
							LEFT JOIN $post_meta_table pm3 ON vp.product_id = pm3.post_id AND pm3.meta_key = '_sale_price'
							LEFT JOIN $post_meta_table pm4 ON vp.product_id = pm4.post_id AND pm4.meta_key = 'vat'
							LEFT JOIN $post_meta_table pm5 ON vp.product_id = pm5.post_id AND pm5.meta_key = 'commission'
							LEFT JOIN $post_meta_table pm6 ON vp.product_id = pm6.post_id AND pm6.meta_key = 'bed_nights'
							LEFT JOIN $post_meta_table pm7 ON vp.product_id = pm7.post_id AND pm7.meta_key = 'total_covers'
							LEFT JOIN $product_order_table plook ON plook.product_id = pr.product_id
							LEFT JOIN $posts_table orderp ON orderp.ID = plook.order_id 
							LEFT JOIN $order_items_table wc_oi ON wc_oi.order_item_id = plook.order_item_id
								AND orderp.post_status = 'wc-completed'
								AND orderp.post_type = 'shop_order'
							WHERE	vp.venue_id = %d
								AND p.post_date >= %s
							GROUP BY pr.product_id
							ORDER BY expired ASC, p.post_date DESC", 
							$venue_id, $cutoff_date_str), ARRAY_A);
						
			$venue_name = $venue_row[0]->name;
			$venue_type = $venue_row[0]->venue_type;
			$type_desc = $venue_type;
			$bed_nights_flg = array_search(null, array_column($product_rows, 'bed_nights')) === false ? true : false;
			switch($venue_type) {
				case 'Restaurant':
				case 'Bar':
					$served_heading = "Total</br>Covers";
					$summ_heading= "Total</br>Covers";
					break;
				case 'Hotel':
					// need to know if we have all bed nights, if so, 
					// we can use that as multiplier per row and use as heading
					$served_heading = $bed_nights_flg ? "Bed</br>Nights" : "Total</br>People";
					$summ_heading= $bed_nights_flg ? "Bed</br>Nights" : "Total</br>People";
					break;	
				case 'Product': 
					$served_heading = "Products</br>Sold";
					$summ_heading= "Products</br>Sold";
					break;
				default: 
					$served_heading = "Products</br>Sold";
					$summ_heading= "Products</br>Sold";
					$type_desc = "Venue";
			}

				// pull out all payments for the product id's returned above
			$product_id_list = array_column($product_rows, 'product_id');
			$placeholders = array_fill(0, count($product_id_list), '%s');
			$placeholders = implode(', ', $placeholders);
			// $payment_rows_old = $wpdb->get_results($wpdb->prepare("
			// 			SELECT  pr.product_id, op.id, op.timestamp, op.pid, op.amount, op.comment
			// 			FROM $product_table pr
			// 			JOIN $offer_payments_table op ON op.pid = pr.product_id
			// 			WHERE pr.product_id IN ($placeholders)
			// 			ORDER BY pr.product_id DESC, op.timestamp ASC ", 
			// 			$product_id_list), ARRAY_A);

						
			$payment_rows = $wpdb->get_results($wpdb->prepare("
				SELECT  pprods.product_id, pay.id, pay.payment_date as timestamp, pprods.product_id as pid, 
						pay.amount as total_amount, pprods.amount, pay.comment, pay.status,
						GROUP_CONCAT(pox.order_item_id) as order_item_ids
				FROM $payment_products_table pprods
					JOIN  $payment_table pay ON pay.id = pprods.payment_id
					JOIN $v_p_join_table vp ON vp.product_id = pprods.product_id
					LEFT JOIN $payment_order_xref_table pox ON pox.payment_id = pay.id
				WHERE pprods.product_id IN ($placeholders)
				GROUP BY pprods.product_id, pay.id
				ORDER BY pprods.product_id DESC, pay.payment_date ASC ", 
				$product_id_list), ARRAY_A);

			// create array w product id's as keys and pay totals as values
			$payment_totals_by_product = calc_payments_by_product($payment_rows);

			// returns array with 'totals' and 'calcs' keys
			$totals_calcs = get_totals_calcs($product_rows, $payment_totals_by_product, $venue_type, $bed_nights_flg);

			$product_calcs = $totals_calcs['calcs'];
			$venue_totals = $totals_calcs['totals'];

		?>
    <section class="headings">
      <h1 class="overview_heading">Overview</h1>
      <h2 class="venue_name"><?php echo $venue_name; ?></h2>
    </section>

		<section id="all-campaigns-container" class="container">
			<?php	
				display_venue_summary($venue_totals, $summ_heading, $venue_type, $cutoff_date_str, $venue_id);
				if (count($product_rows)) {
					if ($admin) { 
						display_mode_toggle($display_mode); 
					}
					display_products_table($product_calcs, $served_heading, $venue_totals, $admin);
				} else {
					echo "<h2>*** No Products Found ***</h2>";
				}

				if ($admin) { 
					display_all_payments($payment_rows, $venue_name, $venue_totals['paid_amount']);
				} 
			?>
		</section>

		<div class="divider"></div>
		<section id="voucher-list-div" class="container">

		</section>
	</main>

	<!-- Selected offers payment modal -->
	<div class="modal fade" id="paySelectedModal" tabindex="-1" data-backdrop="static"  role="dialog" aria-labelledby="paySelectedModalLabel" 					aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="paySelectedModalLabel">Add Payment</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<span for="">Selected offers by ID</span>
					<div class="table-fixed-wrapper">
						<div id="orders-payment-table-container" class="table-fixed-container">
							<table id="orders-payment-table" class="table table-striped table-hover table-bordered table-fixed text-center">
								<thead>
									<tr>
										<th scope="col">Product Id</th>
										<th scope="col">Qty</th>
										<th scope="col">Net Payable</th>
									</tr>
								</thead>
								<tbody>
								</tbody>
								<tfoot>
								</tfoot>
							</table>
						</div>
					</div>
					<form id="orders-payment-add-form">
						<input type="hidden" id="orders-payment-id" name="payment-id" value="">
						<input type="hidden" id="orders-payment-orig-amt" name="payment-orig-amt" value="0">
						<input type="hidden" id="orders-payment-orig-status" name="payment-orig-status" value="0">
						<input type="hidden" id="orders-payment-orig-date" name="payment-orig-date" value="<?php echo date('Y-m-d') ?>">
						<div class="form-group">
							<h5>Payment Status/Type:</h5>
							<div class="form-check form-check-inline">
								<input class="form-check-input payment-status-radio" type="radio" name="payment-status" id="orders-pay-status-paid" 
										value="<?php echo TASTE_PAYMENT_STATUS_PAID ?>" checked>
								<label class="form-check-label" for="orders-pay-status-paid">Paid</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input payment-status-radio" type="radio" name="payment-status" id="orders-pay-status-adj" 
								value="<?php echo TASTE_PAYMENT_STATUS_ADJ ?>">
								<label class="form-check-label" for="orders-pay-status-adj">Adjustment</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input payment-status-radio" type="radio" name="payment-status" id="orders-pay-status-pend" 
								value="<?php echo TASTE_PAYMENT_STATUS_PENDING ?>" disabled>
								<label class="form-check-label" for="orders-pay-status-pend">Pending (disabled)</label>
							</div>
						</div>
						<div class="form-group">
							<label for="orders-payment-date">Transaction date</label>
							<input class="form-control" type="date" id="orders-payment-date" required name="payment-date" value="<?php echo date('Y-m-d') ?>">
						</div>
						<div class="form-group">
							<label for="orders-payment-comment">Description
								<div class="form-check payment-visibility-checkbox-div">
										<input class="form-check-input" type="checkbox" checked id="orders-payment-comment-visible-checkbox"	
												name="payment-comment-visibility">
											<label class="form-check-label" for="orders-payment-comment-visible-checkbox">
												Visible to Venues
											</label>
									</div>
							</label>
							<textarea class="form-control" id="orders-payment-comment" name="payment-comment" placeholder="Add description" rows="3"></textarea>
						</div>
						<div class="form-check" >
							<input class="form-check-input" type="checkbox" checked	id="orders-payment-attach-invoice-checkbox"	
									name="payment-invoice-attachment">
								<label class="form-check-label" for="orders-payment-attach-invoice-checkbox">
									Generate Invoice for this Payment
								</label>
						</div>
					</form>
				</div>
				<div class="modal-footer add-edit-pbo-mode">	 
					<button type="button" class="btn btn-warning" id="orders-payment-clear" >Clear</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
					<button type="submit" form="orders-payment-add-form" id="orders-payment-submit" class="btn btn-primary">Make payment</button>
				</div>
				<div class="modal-footer delete-pbo-mode">	 
					<button type="button" class="btn btn-secondary" id="delete-pbo-cancel-btn" >Cancel</button>
					<button type="button" form="orders-payment-add-form" id="delete-pbo-btn" class="btn btn-danger">Delete payment</button>
				</div>
			</div>
		</div>
  </div>
	<!-- End of selected offers payment modal -->

	<!-- Response Modal (generic for Yes/No questions) -->
	<div class="modal fade" id="responseModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="responseModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title" id="responseModalLabel"><strong>Are you sure?</strong></h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div id="response-modal-msg">This will uncheck all Orders currently selected.</div>
				</div>
				<div class="modal-footer">
					<button type="button" id="response-modal-submit-no" class="btn btn-secondary" data-dismiss="modal">No</button>
					<button type="button" id="response-modal-submit-yes" class="btn btn-primary" data-dismiss="modal">Yes</button>
				</div>
			</div>
		</div>
	</div>

	<div id="spinner-modal" class="modal" data-backdrop="static" tabindex="-1"
		aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body d-flex flex-column justify-content-center align-content-center">
					<p class="py-5 px-3 text-center" id="taste-msg-text">Some sample text</p>
				</div>
			</div>
		</div>
	</div>
	<footer>
	<a href="#" id="topbutton">
	<button type="button">
		<i class="fas fa-angle-up"></i><span>Top</span>
	</button>
	</a>
	</footer>
	<script type="text/javascript" src= "<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/js/thetaste-venue.js?v=2.1"></script>
</body>

</html>

<?php 

/***  START OF FUNCTIONS  ***/

function calc_payments_by_product($payment_rows) {
	$payment_totals_by_product = array();
	foreach ($payment_rows as $payment) {
		// do not process new "adjustment" status
		if (TASTE_PAYMENT_STATUS_ADJ == $payment['status']) {
			continue;
		}
		$product_id = $payment['product_id'];
		if (isset($payment_totals_by_product[$product_id])) {
			$payment_totals_by_product[$product_id] += $payment['amount'];
		} else {
			$payment_totals_by_product[$product_id] = $payment['amount'];
		}
	}
	return $payment_totals_by_product;
}

function get_totals_calcs($ordered_products, $payments, $venue_type, $bed_nights_flg) {
	$venue_totals = array(
		'offers' => 0,
		'redeemed_cnt' => 0,
		'redeemed_qty' => 0,
		'num_served' => 0,
		'order_cnt' => 0,
		'order_qty' => 0,
		'revenue' => 0,
		'commission' => 0,
		'vat' => 0,
		'net_payable' => 0,
		'paid_amount' => 0,
		'balance_due' => 0,
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
		$tmp['vat_rate'] = $product_row['vat'];
		$tmp['commission_rate'] = $product_row['commission'];
		$tmp['price'] = $product_row['price'];
		$tmp['revenue'] = $product_row['price'] * $tmp['redeemed_qty'];
		$tmp['commission'] = round(($tmp['revenue'] / 100) * $product_row['commission'], 2);
		$tmp['vat'] = round(($tmp['commission'] / 100) * $product_row['vat'], 2);
		$tmp['net_payable'] = $tmp['revenue'] - ($tmp['commission'] + $tmp['vat']);
		$tmp['paid_amount'] = empty($payments[$product_id]) ? 0 : $payments[$product_id];
		$tmp['balance_due'] = $tmp['net_payable'] - $tmp['paid_amount'];
		// display qty must now have the multiplier applied per row, 
		// dependent on the venue type and whether bed_nights are present (hotels only)
		switch($venue_type) {
			case 'Restaurant':
			case 'Bar':
				$multiplier = $product_row['total_covers'];
				break;
			case 'Hotel':
				$multiplier = $bed_nights_flg ? $product_row['bed_nights'] : 2;
				break;	
			case 'Product': 
				$multiplier = 1;
				break;
			default: 
				$multiplier = 1;
				$type_desc = "Venue";
		}
		$tmp['num_served'] = $tmp['redeemed_qty'] * $multiplier;
		$tmp['multiplier'] = $multiplier;
		
		// check if this product qualifies for making payments against order items
		$payment_by_order_item_flg = check_payment_by_order_item($tmp, $product_row['post_date'], $payment_rows);
		$tmp['payment_by_order_item'] = $payment_by_order_item_flg;

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

function check_payment_by_order_item($prod_calcs, $prod_date, $payment_rows) {
	// All products should be viewable in Payment mode, just like all are viewable in 
	// Redemption mode
	return true;
	
	// if (!$prod_calcs['balance_due'] || strtotime($prod_date) < strtotime('2020-01-01')) {
	// 	return false;
	// }

	// $prod_id = $prod_calcs['product_id'];
	// $prod_payments = array_filter($payment_rows, function ($payment_row) use ($prod_id) {
	// 	return $prod_id === $payment_row['product_id'];
	// });

	// return ! in_array(NULL, array_column($prod_payments, 'order_item_id'));
}

function display_venue_summary($venue_totals, $summ_heading, $venue_type, $cutoff_date_str, $venue_id) {
	$currency =  get_woocommerce_currency_symbol();
	?>

	<div class="row" id="venue-summary-div">
		<div class="col-md tcard">
			<h3 class="numbers" id="vouchers-total">
					<?php echo $venue_totals['redeemed_qty'] ?>
			<h3>
			<p class="titles">Vouchers Sold</p>
			<div class="eclipse_icon_bg ticket_icon">
				<i class="fas fa-ticket-alt"></i>
			</div>
		</div>
		<div class="col-md tcard">
			<h3 class="numbers" id="served-total">
				<?php echo $venue_totals['num_served'] ?>
			<h3>
			<p class="titles"><?php echo $summ_heading ?></p>
			<div class="eclipse_icon_bg users_icon">
				<i class="fas fa-users"></i>
			</div>
		</div>
		<div class="col-md tcard">
			<h3 class="numbers" id="gr-value-total">
				<?php echo $currency . ' ' . num_display($venue_totals['revenue']) ?>
			<h3>
			<p class="titles">Gross Revenue</p>
			<div class="eclipse_icon_bg money_bill_icon">
				<i class="far fa-money-bill-alt"></i>
			</div>
		</div>
		<div class="col-md tcard">
			<h3 class="numbers" id="net-payable-total">
				<?php echo $currency . ' ' . num_display($venue_totals['net_payable']) ?>
			<h3>
			<p class="titles">Net Payable</p>
			<div class="eclipse_icon_bg cash_register_icon">
				<i class="fas fa-cash-register"></i>
			</div>
		</div>
		<div class="col-md tcard">
			<h3 class="numbers paid-amount-total">
				<?php echo $currency . ' ' . num_display($venue_totals['paid_amount']) ?>
			<h3>
			<p class="titles">Total Payments</p>
			<div class="eclipse_icon_bg coins_icon">
				<i class="fas fa-coins"></i>
			</div>
		</div>
		<div class="col-md tcard">
			<h3 class="numbers"id="balance-due-total">
				<?php echo $currency . ' ' . num_display($venue_totals['balance_due']) ?>
			<h3>
			<p class="titles">Balance Due</p>
			<div class="eclipse_icon_bg balance_scale_icon">
				<i class="fas fa-balance-scale"></i>
			</div>
		</div>
	</div>

	<input type="hidden" id="venue_cutoff_date" value="<?php echo $cutoff_date_str ?>">
	<input type="hidden" id="hidden_venue_id" value="<?php echo $venue_id ?>">
	<div id="summary-hidden-values">
		<input type="hidden" id="sum-gr-value" value="<?php echo $venue_totals['revenue'] ?>">
		<input type="hidden" id="sum-commission" value="<?php echo $venue_totals['commission'] ?>">
		<input type="hidden" id="sum-vat" value="<?php echo $venue_totals['vat'] ?>">
		<input type="hidden" id="sum-redeemed-cnt" value="<?php echo $venue_totals['redeemed_cnt'] ?>">
		<input type="hidden" id="sum-redeemed-qty" value="<?php echo $venue_totals['redeemed_qty'] ?>">
		<input type="hidden" id="sum-num-served" value="<?php echo $venue_totals['num_served'] ?>">
		<input type="hidden" id="sum-net-payable" value="<?php echo $venue_totals['net_payable'] ?>">
	</div>
	<div id="summary-hidden-payment-values">
		<input type="hidden" id="sum-total-paid" value="<?php echo $venue_totals['paid_amount'] ?>">
		<input type="hidden" id="sum-balance-due" value="<?php echo $venue_totals['balance_due'] ?>">
	</div>

	<?php
}

function display_products_table($product_calcs, $served_heading, $venue_totals, $admin) {
	$margin_class = $admin ? 'mt-1' : 'mt-5';
	?>
	<div class="collapse-container product-listing-container <?php echo $margin_class ?>">
		<h3 class="text-center">Campaigns</h3>
		<span class="circle-span" data-placement="top" title="Show / Hide" data-toggle="tooltip">
				<i 
				data-toggle="collapse" 
				data-target="#campaign_listing_collapse" 
				aria-expanded="true" 
				aria-controls="campaign_listing_collapse" 
				class="collapse-icon fas fa-minus-circle"></i>
		</span>
		<div class="collapse show" id="campaign_listing_collapse">
		<div class="d-flex justify-content-between my-3 pr-4">
			<span>
				<h4 class="mt-1">Offers (<?php echo  number_format(count($product_calcs)) ?> Rows)</h4>
			</span>
			<span class="payment-mode-only">
				<button class="btn btn-success mr-2" id="payAllSelected" data-toggle="modal" data-target="#paySelectedModal">
					Pay selected offers
				</button>
				Total Payment for Selected Orders: 
				<span id="select-orders-pay-total">0.00</span>
			</span>
		</div>
			<div class="table-fixed-wrapper mb-5">
				<div id="product-table-container" class="table-fixed-container">
					<table class="table table-striped table-bordered offers_table table-fixed">
						<thead>
							<th scope="col">ID</th>
							<th scope="col">Offer</th>
							<th scope="col">Status</th>
							<th scope="col">Revenue</th>
							<th scope="col"><?php echo $served_heading ?></th>
							<th scope="col">Commission</th>
							<th scope="col">Vat</th>
							<th scope="col">Net</br>Payable</th>
							<th scope="col">Balance</br>Due</th>
							<th scope="col" class="payment-mode-only">Selected</br> Pay Amt</th>
							<th scope="col">Action</th>
						</thead>
						<tbody>
							<?php
								foreach($product_calcs as $product_row) {
									display_product_row($product_row);
								}
							?>
						</tbody>
						<?php display_table_totals($venue_totals) ?>
					</table>
				</div>
			</div>

		</div>
	</div>
	<?php
}

function display_table_totals($venue_totals) {
	?>
		<tfoot>
			<tr>
				<th >&nbsp;</th>
				<th class="table-total-label" >
					Totals:
				</th>
				<th></th>
				<th class="table-nbr" >
					<span id="gr-value-table-total">
						<?php echo num_display($venue_totals['revenue']) ?>
					</span>
				</th>
				<th class="table-nbr" >
					<span id="redeem-qty-display-table-total">
						<?php echo $venue_totals['num_served'] ?>
					</span>
				</th>
				<th class="table-nbr" >
					<span id="commission-display-table-total">
						<?php echo num_display($venue_totals['commission']) ?>
					</span>
				</th>
				<th class="table-nbr" >
					<span id="vat-display-table-total">
						<?php echo num_display($venue_totals['vat']) ?>
					</span>
				</th>
				<th class="table-nbr" >
					<span id="net-payable-table-total">
						<?php echo num_display($venue_totals['net_payable']) ?>
					</span>
				</th>
				<th class="table-nbr" >
					<span id="balance-due-table-total">
						<?php echo num_display($venue_totals['balance_due']) ?>
					</span>
				</th>
				<th>&nbsp; </th>
			</tr>
		</tfoot>
<?php
}

function display_product_row($product_row) {
	$id = $product_row['product_id'];
	$title = $product_row['title'];
	$status = $product_row['status'];
	$revenue = $product_row['revenue'];
	$num_served = $product_row['num_served'];
	$commission = $product_row['commission'];
	$vat = $product_row['vat'];
	$net_payable = $product_row['net_payable'];
	$balance_due = $product_row['balance_due'];
	$multiplier = $product_row['multiplier'];
	$payment_by_order_item = $product_row['payment_by_order_item'];
	$vat_rate = $product_row['vat_rate'];
	$commission_rate = $product_row['commission_rate'];
	$price = $product_row['price'];
	$paid_amount = $product_row['paid_amount'];
											
	$status_display = 'Active' === $status ?
			'<td class="active-prod text-center">
				<i class="fas fa-check-circle"></i><br/>
				Active
			</td>' :
				'<td class="expired-prod text-center">
				<i class="fas fa-times-circle"></i><br/>
				Expired
			</td>
			';

 ?>
	<tr id="product-table-row-<?php echo $id ?>" class="product-info-row" data-multiplier="<?php echo $multiplier ?>" 
			data-vatrate="<?php echo $vat_rate ?>" data-commissionrate="<?php echo $commission_rate ?>" data-price="<?php echo $price ?>"
			data-productid="<?php echo $id?>" data-paidamount="<?php echo $paid_amount ?>" data-balancedue="<?php echo $balance_due ?>"
	>
		<td><?php echo $id ?></td>
		<td><?php echo $title ?></td>
		<?php echo $status_display ?>
		<td class="table-nbr">
			<span id="grevenue-display-<?php echo $id ?>">
				<?php echo num_display($revenue) ?>
			</span>
		</td>
		<td class="table-nbr">
			<span id="redeem-qty-display-<?php echo $id ?>">
				<?php echo $num_served ?>
			</span>
		</td>
		<td class="table-nbr">
			<span id="commission-display-<?php echo $id ?>">
				<?php echo num_display($commission) ?>
			</span>
		</td>
		<td class="table-nbr">
			<span id="vat-display-<?php echo $id ?>">
				<?php echo num_display($vat) ?>
			</span>
		</td>
		<td class="table-nbr">
			<span id="payable-display-<?php echo $id ?>">
				<?php echo num_display($net_payable) ?>
			</span>
		</td>
		<td class="table-nbr">
			<span id="balance-due-display-<?php echo $id ?>">
				<?php echo num_display($balance_due) ?>
			</span>
		</td>
		<td class="table-nbr payment-mode-only">
			<span id="selected-pay-amt-<?php echo $id ?>"><?php echo $payment_by_order_item ? '0.00' : '--' ?></span>
		</td>
		<td class="text-center redeem-mode-only">
			<button data-prod-id="<?php echo $id ?>" data-payments-below="<?php echo $payment_by_order_item ? 'false' : 'true' ?>"
				class="btn btn-primary view_offer product-select-btn">
				<i class="far fa-eye"></i><br/>
				View
			</button>
		</td>
		<td class="text-center payment-mode-only">
			<?php 
			/****
			 * 
			 * 
			 * expired products should still be allowed to make payments against
			 * only products w/ no orders should be "X"'d 
			 * 
			 * 
			 * 
			 */
			if ($payment_by_order_item) {
				?>
				<button data-prod-id="<?php echo $id ?>" data-payments-below="false" class="btn btn-primary view_offer product-select-btn
					product-select-for-payments">
					<i class="far fa-eye"></i><br/>
					Select Orders
				</button>
				<?php
			} else {
				echo 'X';
			}
			?>
		</td>
	</tr>
 <?php
}

function display_all_payments($payment_rows, $venue_name, $payment_total) {
	$currency =  get_woocommerce_currency_symbol();
	?>
	<div class="collapse-container all-payments-container mt-5">
		<h3 class="text-center">All Transactions for <?php echo $venue_name ?></h3>
		<span class="circle-span" data-placement="top" title="Show / Hide" data-toggle="tooltip">
				<i 
				data-toggle="collapse" 
				data-target="#all-payments-collapse" 
				aria-expanded="true" 
				aria-controls="all-payments-collapse" 
				class="collapse-icon fas fa-minus-circle"></i>
		</span>
		<div class="collapse show" id="all-payments-collapse">
			<h4 class="mt-1">Transactions (<span id="all-payments-cnt-disp"><?php echo  number_format(count($payment_rows)) ?></span> Rows)</h4>
			<div class="table-fixed-wrapper mb-5">
				<div id="all-payments-table-container" class="table-fixed-container">
					<table id="all-payments-table" class="table table-striped table-bordered table-fixed"
							data-allpaymentcnt="<?php echo count($payment_rows) ?>">
						<thead>
							<th scope="col" class="sort-by-product">Product</th>
							<th scope="col">Payment ID</th>
							<th scope="col" class="sort-by-date">Date</th>
							<th scope="col">Amount</th>
							<th scope="col">PBO Edit</th>
							<th scope="col">PBO Delete</th>
						</thead>
						<tbody id="all-payment-lines">
							<?php
								foreach($payment_rows as $payment) {
									// disp_all_payment_line is in ajax/functions.php
									echo disp_all_payment_line($payment);
								}
							?>
						</tbody>
						<tfoot>
							<tr>
								<td colspan="2">&nbsp;</td>
								<th>Total:</th>
								<th class="paid-amount-total table-nbr pr-5">
								<?php echo $currency . ' ' . num_display($payment_total) ?>
								</th>
								<td colspan="2">&nbsp;</td>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>
		</div>
	</div>
	<?php 
}

function display_mode_toggle($display_mode) {
	if ("redeem" === $display_mode) {
		$redeem_class = 'toggle-on';
		$payment_disabled = '';
		$payment_class = '';
		$redeem_disabled = 'disabled';
	} else {
		$redeem_class = '';
		$payment_disabled = 'disabled';
		$payment_class = 'toggle-on';
		$redeem_disabled = '';
	}
	
	?>
		<div class="ttoggle-container d-flex align-items-center">
			<div class="row">
				<div class="col-6 ttoggle toggle-div-redeem">
					<button id="toggle-btn-redeem" data-toggle="redeem" <?php echo $redeem_disabled ?> class="toggle-btn <?php echo $redeem_class ?>">Redemptions</button>
				</div>
				<div class="col-6 ttoggle toggle-div-payment">
					<button id="toggle-btn-payment" data-toggle="payment" <?php echo $payment_disabled ?> class="toggle-btn <?php echo $payment_class ?>">Payments</button>
				</div>
			</div>
		</div>
	<?php
}

function num_display ($num) {
	// display number with 2 decimal rounding and formatting
	return number_format(round($num,2), 2);
}

function num_display_no_decs ($num) {
	// display number with 2 decimal rounding and formatting
	return number_format(round($num), 0);
}