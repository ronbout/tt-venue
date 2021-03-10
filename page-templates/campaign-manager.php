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
} else {
	$nav_links = venue_navbar_standard_links($user_info['use_new_campaign'], $user_info['venue_voucher_page']);
	$venue_id = $user->ID;
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
			$payment_table = $wpdb->prefix."offer_payments";

			// get venue name 
			$venue_row = $wpdb->get_results($wpdb->prepare("
				SELECT v.name, v.venue_type
				FROM $venue_table v
				WHERE	v.venue_id = %d", 
			$venue_id));

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
							GROUP BY pr.product_id
							ORDER BY expired ASC, p.post_date DESC", 
							$venue_id), ARRAY_A);
						
			// more efficient just to grab this a separate statement
			$payment_rows = $wpdb->get_results($wpdb->prepare("
					SELECT  vp.product_id, sum(pmnt.amount) as 'total_amount'
					FROM $v_p_join_table vp
					JOIN $payment_table pmnt ON pmnt.pid = vp.product_id
					WHERE vp.venue_id = %d
					GROUP BY vp.product_id
					", $venue_id), ARRAY_A);


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

			// create array w product id's as keys and pay totals as values
			$payments = array_combine(array_column($payment_rows, "product_id"), array_column($payment_rows, "total_amount"));

			// the ordering has been simplified from the earlier version
			// and is being done in the SQL statement
			// $ordered_products = order_product_table($product_rows);

			// returns array with 'totals' and 'calcs' keys
			$totals_calcs = get_totals_calcs($product_rows, $payments, $venue_type, $bed_nights_flg);

			$product_calcs = $totals_calcs['calcs'];
			$venue_totals = $totals_calcs['totals'];

		?>
    <section class="headings">
      <h1 class="overview_heading">Overview</h1>
      <h2 class="venue_name"><?php echo $venue_name; ?></h2>
    </section>

		<section id="all-campaigns-container" class="container">
			<?php	
				display_venue_summary($venue_totals, $summ_heading, $venue_type);
				if (count($product_rows)) {
					display_products_table($product_calcs, $served_heading, $venue_totals);
				} else {
					echo "<h2>*** No Products Found ***</h2>";
				}
			?>
		</section>
		<div class="divider"></div>
		<section id="voucher-list-div" class="container">

		</section>
	</main>

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
	<script type="text/javascript" src= "<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/js/thetaste-venue.js"></script>
</body>

</html>

<?php 

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

function display_venue_summary($venue_totals, $summ_heading, $venue_type) {
	$currency =  get_woocommerce_currency_symbol();
	?>

	<div class="row mx-0" id="venue-summary-div">
		<div class="col-md ml-xs-3 ml-s-0 my-2 p-4 cols">
			<h3 class="numbers" id="vouchers-total">
					<?php echo $venue_totals['redeemed_qty'] ?>
			<h3>
			<p class="titles">Vouchers Sold</p>
			<div class="eclipse_icon_bg ticket_icon">
				<i class="fas fa-ticket-alt"></i>
			</div>
		</div>
		<div class="col-md ml-3 my-2 p-4 cols">
			<h3 class="numbers" id="served-total">
				<?php echo $venue_totals['num_served'] ?>
			<h3>
			<p class="titles"><?php echo $summ_heading ?></p>
			<div class="eclipse_icon_bg users_icon">
				<i class="fas fa-users"></i>
			</div>
		</div>
		<div class="col-md ml-3 my-2 p-4 cols">
			<h3 class="numbers" id="gr-value-total">
				<?php echo $currency . ' ' . num_display($venue_totals['revenue']) ?>
			<h3>
			<p class="titles">Gross Revenue</p>
			<div class="eclipse_icon_bg money_bill_icon">
				<i class="far fa-money-bill-alt"></i>
			</div>
		</div>
	</div>
	    
	<div class="row mx-0">
		<div class="col-md ml-xs-3 ml-s-0 my-2 p-4 cols">
			<h3 class="numbers" id="net-payable-total">
				<?php echo $currency . ' ' . num_display($venue_totals['net_payable']) ?>
			<h3>
			<p class="titles">Net Payable</p>
			<div class="eclipse_icon_bg cash_register_icon">
				<i class="fas fa-cash-register"></i>
			</div>
		</div>
		<div class="col-md ml-3 my-2 p-4 cols">
			<h3 class="numbers"id="paid-amount-total">
				<?php echo $currency . ' ' . num_display($venue_totals['paid_amount']) ?>
			<h3>
			<p class="titles">Total Payments</p>
			<div class="eclipse_icon_bg coins_icon">
				<i class="fas fa-coins"></i>
			</div>
		</div>
		<div class="col-md ml-3 my-2 p-4 cols">
			<h3 class="numbers"id="balance-due-total">
				<?php echo $currency . ' ' . num_display($venue_totals['balance_due']) ?>
			<h3>
			<p class="titles">Balance Due</p>
			<div class="eclipse_icon_bg balance_scale_icon">
				<i class="fas fa-balance-scale"></i>
			</div>
		</div>
	</div>

	<div id="summary-hidden-values">
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

function display_products_table($product_calcs, $served_heading, $venue_totals) {
	?>
	<div class="collapse-container product-listing-container mt-5">
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
			<h4 class="mt-1"><?php echo $type_desc ?> Offers (<?php echo  number_format(count($product_calcs)) ?> Rows)</h4>
			<div id="product-table-container" class="table-fixed-container mb-5">
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
						<th scope="col">Action</th>
					</thead>
					<tbody>
						<?php
							foreach($product_calcs as $product_row) {
								extract($product_row);
								display_product_row($product_row['product_id'], $title, $status, $revenue, $num_served, $commission, 
																		$vat, $net_payable, $balance_due, $multiplier);
							}
						?>
					</tbody>
					<?php display_table_totals($venue_totals) ?>
				</table>
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

function display_product_row($id, $title, $status, $revenue, $num_served, $commission, 
														 $vat, $net_payable, $balance_due, $multiplier) {
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
	<tr data-multiplier="<?php echo $multiplier ?>">
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
		<td class="text-center">
			<button data-prod-id="<?php echo $id ?>" class="btn btn-primary view_offer product-select-btn">
				<i class="far fa-eye"></i><br/>
				View
			</button>
		</td>
	</tr>
 <?php
}

/*
function order_product_table($product_rows) {
	// filter by active and expired, then merge 
	// 2nd sort should be by date
	$active_products = array();
	$expired_products = array();
	array_walk($product_rows, function($row, $k) use (&$active_products, &$expired_products) {
		if ("N" === $row['expired']) {
			$active_products[] = $row;
		} else {
			$expired_products[] = $row;
		}
	});
	
	$ordered_products = array_merge($active_products, $expired_products);
	return $ordered_products;
}
*/

function num_display ($num) {
	// display number with 2 decimal rounding and formatting
	return number_format(round($num,2), 2);
}

function num_display_no_decs ($num) {
	// display number with 2 decimal rounding and formatting
	return number_format(round($num), 0);
}