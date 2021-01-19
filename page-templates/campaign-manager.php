<?php
/*
Template Name: Campaign Manager
*/

/**
 *  Date:  9/15/2020
 * 	Author: Ron Boutilier
 */
defined('ABSPATH') or die('Direct script access disallowed.');

// to make the totals at the bottom of the offers list line up 
// must set some standard td widths.  It has to be a separate 
// table due to difficulty of scrolling a table
// probably should convet to div's or jquery ui datatable
define('TOTALS_TD_WIDTH', '80px');
define('ID_TD_WIDTH', '64px');
define('QTY_TD_WIDTH', '69px');
define('EXP_TD_WIDTH', '65px');
define('COMM_TD_WIDTH', '102px');
define('ACTION_TD_WIDTH', '74px');

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
			'title' => '<i class="fas fa-sign-out-alt"></i>',
			'url' => wp_logout_url(get_site_url()),
			'active' => false,
			'attrs' => ' data-toggle="tooltip" data-placement="left" title="Logout" id="logout" '
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
<body class="campaign-manager orig-bs3">
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
	<main>
		<div class="container">
			<br><br>

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
								pm2.meta_value AS 'expired', pm3.meta_value AS 'price', pm4.meta_value AS 'vat',
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
							ORDER BY p.post_date DESC", 
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

			// first thing to do is order the rows by most recent
			// but also grouping related products under the group,
			// using the date of the group for the order
			// *** GROUPING INFO NOT CURRENTLY RETURNING FROM SQL 
			// *** TO ADD SQL, CONVERT TO 'LEFT' JOIN on posts p
			// *** KEEPING LOGIC IN CASE THAT CHANGES
			$ordered_products = order_product_table($product_rows);

			// returns array with 'totals' and 'calcs' keys
			$totals_calcs = get_totals_calcs($ordered_products, $payments, $venue_type, $bed_nights_flg);

			$product_calcs = $totals_calcs['calcs'];
			$venue_totals = $totals_calcs['totals'];

		?>

		<div class="panel panel-default">
			<div id="venue-summary-div" class="panel-heading text-center"">
						<h2>Welcome <?php echo $venue_name; ?></h2>
						<?php display_venue_summary($venue_totals, $summ_heading, $venue_type) ?>
			</div>
			<div id="product-table-div" class="panel-body">
				<?php
				if (count($product_rows)) {
					echo "<h3>$type_desc Offers</h3>";
					display_products_table($product_calcs, $served_heading, $venue_totals);
				} else {
					echo "<h3>No Products Found</h3>";
				}
				?>
			</div>
		</div>
		<div id="voucher-list-div" class="container">

		</div>
	</main>
	<div id="taste-modal-layer">
		<div id="taste-msg-box" class="modalContainer">
			<div>
				<p id="taste-msg-text">Some sample text</p>
				<div id="taste-msg-close" class="btn btn-close">Close</div>
			</div>
		</div><!-- taste-msg-box -->
	</div><!-- taste-modal-layer -->
	<footer>
	<a href="#" id="topbutton">
		<i class="fas fa-angle-up"></i>
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
		$tmp['view'] = "<button data-prod-id='" . $product_row['product_id'] . "' class='btn btn-primary product-select-btn'>View</button>";
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
	<div class="v-summary-container">
		<div class="v-summary-section">
			<h3>Vouchers</br>Sold</h3>
			<h3>
				<span id="vouchers-total">
					<?php echo $venue_totals['redeemed_qty'] ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3><?php echo $summ_heading ?></h3>
			<h3>
				<span id="served-total">
					<?php echo $venue_totals['num_served'] ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3>Gross</br>Revenue</h3>
			<h3>
				<span id="gr-value-total">
					<?php echo $currency . ' ' . num_display($venue_totals['revenue']) ?>
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
	<div id="product-table-container" class="table-fixed-container">
		<table class="table table-striped table-bordered table-fixed">
			<thead>
				<th>ID</th>
				<th>Offer</th>
				<th>Status</th>
				<th>Revenue</th>
				<th><?php echo $served_heading ?></th>
				<th>Commission</th>
				<th>Vat</th>
				<th>Net</br>Payable</th>
				<th>Balance</br>Due</th>
				<th>Action</th>
			</thead>
			<tbody>
				<?php
					foreach($product_calcs as $product_row) {
						extract($product_row);
						display_product_row($product_row['product_id'], $title, $status, $revenue, $num_served, $commission, 
																$vat, $net_payable, $balance_due, $view, $multiplier);
					}
				?>
			</tbody>
		</table>
	</div>
	<?php display_table_totals($venue_totals) ?>
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
						<?php echo $venue_totals['num_served'] ?>
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
				<td style="width: <?php echo ACTION_TD_WIDTH?>;">&nbsp; </td>
			</tr>
		</tbody>
	</table>
<?php
}

function display_product_row($id, $title, $status, $revenue, $num_served, $commission, 
														 $vat, $net_payable, $balance_due, $view, $multiplier) {
 ?>
	<tr data-multiplier="<?php echo $multiplier ?>">
		<td style="width: <?php echo ID_TD_WIDTH?>;"><?php echo $id ?></td>
		<td><?php echo $title ?></td>
		<td style="width: <?php echo EXP_TD_WIDTH?>;"><?php echo $status ?></td>
		<td class="table-nbr" style="width: <?php echo TOTALS_TD_WIDTH?>;">
			<span id="grevenue-display-<?php echo $id ?>">
				<?php echo num_display($revenue) ?>
			</span>
		</td>
		<td class="table-nbr" style="width: <?php echo QTY_TD_WIDTH?>;">
			<span id="redeem-qty-display-<?php echo $id ?>">
				<?php echo $num_served ?>
			</span>
		</td>
		<td class="table-nbr" style="width: <?php echo COMM_TD_WIDTH?>;">
			<span id="commission-display-<?php echo $id ?>">
				<?php echo num_display($commission) ?>
			</span>
		</td>
		<td class="table-nbr" style="width: <?php echo TOTALS_TD_WIDTH?>;">
			<span id="vat-display-<?php echo $id ?>">
				<?php echo num_display($vat) ?>
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