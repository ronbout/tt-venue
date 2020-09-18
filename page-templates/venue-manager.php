<?php
/*
Template Name: Venue Manager
*/

/**
 *  Date:  9/15/2020
 * 	Author: Ron Boutilier
 */
defined('ABSPATH') or die('Direct script access disallowed.');

global $wpdb;

	if ( !is_user_logged_in()) {
		wp_login_form();
		die();
	} else {
		$user = wp_get_current_user();
		$role = $user->roles[0];
		$admin = ('ADMINISTRATOR' === strtoupper($role));

		if ('VENUE' !== strtoupper($role) && !$admin) {
				echo "<h2>Role: $role </h2>";
				die('You must be logged in as a Venue to access this page.');
		}
	}
?>

<!DOCTYPE HTML>

<html>

<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<?php
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
	?>

			
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
	<link rel="stylesheet" href="<?php echo plugin_dir_url(__DIR__) . "assets/css/font-awesome.min.css"?>">
	<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>

	<script type="text/javascript" src= "<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/js/thetaste-venue.js"></script>
	<link rel="stylesheet" href="<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/css/thetaste-venue.css">
	<title><?php _e(the_title()); ?></title>
</head>

<body>
<?php 
	if ($admin) {
		// check if the Venue ID is in POST from form 
		if (isset($_POST['venue-id'])) {
			$venue_id = $_POST['venue-id'];
		} else {
			// display form to select Venue
			display_venue_select();
			die();
		}
	} else {
		$venue_id = $user->ID;
	}
?>
	<section>
		</br>
		</br>
		<div class="container">

		<center>
			<a href="<?php echo get_site_url() ?>">
				<img src="http://thetaste.ie/wp/wp-content/uploads/2017/12/thetaste-site-homepage-logo5.png">
			</a>
		</center>
		<br><br>
		<center>
		<b>WELCOME TO IRELAND’S AWARD WINNING FOOD, DRINK & TRAVEL DIGITAL MAGAZINE</b>
		<br><br>
		<span style="font-size:12px;">19.6M READERS WORLDWIDE <b>|</b> 10K ARTICLES <b>|</b> €10M GENERATED FOR THE IRISH HOSPITALITY INDUSTRY <b>|</b> 726K REGISTERED MEMBERS <b>|</b> 200K+ TASTE EXPERIENCES SOLD <b>|</b> 300K SOCIAL MEDIA FOLLOWERS <b>|</b> WINNER OF BEST DIGITAL FOOD MAGAZINE IN THE WORLD <b>|</b> WINNER OF OUTSTANDING SMALL BUSINESS IN IRELAND</span></center>
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

			$venue_name = $venue_row[0]->name;
			$venue_type = $venue_row[0]->venue_type;
			switch($venue_type) {
				case 'Restaurant':
				case 'Pub':
				case 'Cafe':
					$served_heading = "Tables</br>Booked";
					$summ_heading= "Total</br>Covers";
					$type_desc = "Restaurant";
					break;
				case 'Hotel':
					$served_heading = "Rooms</br>Booked";
					$summ_heading= "People";
					// $summ_heading= "Bed</br>Nights";
					$type_desc = "Hotel";
					break;	
				default: 
					$served_heading = "Orders</br>Served";
					$summ_heading= "Products</br>Sold";
					$type_desc = "Venue";
			}

			$product_rows = $wpdb->get_results($wpdb->prepare("
							SELECT pr.product_id, pr.sku, p.post_title, pr.onsale, p.post_date, pm.meta_value AS 'children', 
								pm2.meta_value AS 'expired', pm3.meta_value AS 'price', pm4.meta_value AS 'vat',
								pm5.meta_value AS 'commission',
								COUNT(plook.order_id) AS 'order_cnt', SUM(wc_oi.downloaded) AS 'redeemed'
							FROM $v_p_join_table vp 
							JOIN $product_table pr ON vp.product_id = pr.product_id
							JOIN $posts_table p ON vp.product_id =  p.ID
							LEFT JOIN $post_meta_table pm ON vp.product_id = pm.post_id AND pm.meta_key = '_children'
							LEFT JOIN $post_meta_table pm2 ON vp.product_id = pm2.post_id AND pm2.meta_key = 'Expired'
							LEFT JOIN $post_meta_table pm3 ON vp.product_id = pm3.post_id AND pm3.meta_key = '_sale_price'
							LEFT JOIN $post_meta_table pm4 ON vp.product_id = pm4.post_id AND pm4.meta_key = 'vat'
							LEFT JOIN $post_meta_table pm5 ON vp.product_id = pm5.post_id AND pm5.meta_key = 'commission'
							LEFT JOIN $product_order_table plook ON plook.product_id = pr.product_id
							JOIN $posts_table orderp ON orderp.ID = plook.order_id 
								AND orderp.post_status = 'wc-completed'
								AND orderp.post_type = 'shop_order'
							LEFT JOIN $order_items_table wc_oi ON wc_oi.order_item_id = plook.order_item_id
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
			$totals_calcs = get_totals_calcs($ordered_products, $payments);

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
	</section>
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
</body>

</html>

<?php 

function get_totals_calcs($ordered_products, $payments) {
	$venue_totals = array(
		'offers' => 0,
		'redeemed' => 0,
		'order_cnt' => 0,
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
		$tmp['redeemed'] = $product_row['redeemed'];
		$tmp['order_cnt'] = $product_row['order_cnt'];
		$tmp['revenue'] = $product_row['price'] * $tmp['redeemed'];
		$tmp['view'] = "<button data-prod-id='" . $product_row['product_id'] . "' class='btn btn-primary product-select-btn'>View</button>";
		$tmp['commission'] = ($tmp['revenue'] / 100) * $product_row['commission'];
		$tmp['vat'] = ($tmp['commission'] / 100) * $product_row['vat'];
		$tmp['net_payable'] = $tmp['revenue'] - ($tmp['commission'] + $tmp['vat']);
		$tmp['paid_amount'] = empty($payments[$product_id]) ? 0 : $payments[$product_id];
		$tmp['balance_due'] = $tmp['net_payable'] - $tmp['paid_amount'];
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
	$multiplier = 'Other' === $venue_type ? 1 : 2;
	$num_served =  $venue_totals['redeemed'] * $multiplier;
	?>
	<div class="v-summary-container">
		<div class="v-summary-section">
			<h3>Vouchers</br>Sold</h3>
			<h3>
				<span id="vouchers-total">
					<?php echo $venue_totals['order_cnt'] ?>
				</span>
			</h3>
		</div>
		<div class="v-summary-section">
			<h3><?php echo $summ_heading ?></h3>
			<h3>
				<span id="served-total">
					<?php echo $num_served ?>
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
		<input type="hidden" id="sum-redeemed" value="<?php echo $venue_totals['redeemed'] ?>">
		<input type="hidden" id="sum-net-payable" value="<?php echo $venue_totals['net_payable'] ?>">
		<input type="hidden" id="sum-total-paid" value="<?php echo $venue_totals['paid_amount'] ?>">
		<input type="hidden" id="sum-balance-due" value="<?php echo $venue_totals['balance_due'] ?>">
		<input type="hidden" id="sum-multiplier" value="<?php echo $multiplier ?>">
	</div>

	<?php
}

function display_products_table($product_calcs, $served_heading, $venue_totals) {
	?>
	<table class="table table-striped table-bordered">
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
					display_product_row($product_row['product_id'], $title, $status, $revenue, $redeemed, $commission, $vat, $net_payable, $balance_due, $view);
				}
			?>
			<tr>
				<?php display_table_totals($venue_totals) ?>
			</tr>
		</tbody>
	</table>
	<?php
}

function display_table_totals($venue_totals) {
	?>
	<td colspan="3" class="table-total-label">
		Totals:
	</td>
	<td class="table-nbr">
		<span id="gr-value-table-total">
			<?php echo num_display($venue_totals['revenue']) ?>
		</span>
	</td>
	<td class="table-nbr">
		<span id="redeem-display-table-total">
			<?php echo $venue_totals['redeemed'] ?>
		</span>
	</td>
	<td class="table-nbr">
		<span id="commission-display-table-total">
			<?php echo num_display($venue_totals['commission']) ?>
		</span>
	</td>
	<td class="table-nbr">
		<span id="vat-display-table-total">
			<?php echo num_display($venue_totals['vat']) ?>
		</span>
	</td>
	<td class="table-nbr">
		<span id="net-payable-table-total">
			<?php echo num_display($venue_totals['net_payable']) ?>
		</span>
	</td>
	<td class="table-nbr">
		<span id="balance-due-table-total">
			<?php echo num_display($venue_totals['balance_due']) ?>
		</span>
	</td>
	<td>&nbsp; </td>
<?php
}

function display_product_row($id, $title, $status, $revenue, $redeemed, $commission, $vat, $net_payable, $balance_due, $view) {
 ?>
	<tr>
		<td><?php echo $id ?></td>
		<td><?php echo $title ?></td>
		<td><?php echo $status ?></td>
		<td class="table-nbr">
			<span id="grevenue-display-<?php echo $id ?>">
				<?php echo num_display($revenue) ?>
			</span>
		</td>
		<td class="table-nbr">
			<span id="redeem-display-<?php echo $id ?>">
				<?php echo $redeemed ?>
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
		<td><?php echo $view ?></td>
	</tr>
 <?php
}

function order_product_table($product_rows) {
	/**
	 * 
	 * filter by active and expired, then merge 
	 * 2nd sort should be by date
	 * 
	 * 
	 */
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

function display_venue_select() {
	global $wpdb;
	// build list of venues 
	$venue_rows = $wpdb->get_results("
		SELECT venue_id, name, description, venue_type
		FROM " . $wpdb->prefix . "taste_venue
		ORDER BY venue_type, name
	", ARRAY_A);
	?>
	<div id="venue-form-container">
	<form method="post" action="" id="venue-select-form">
		<label for="venue-select">Choose a Venue:</label>
		<select name="venue-id" id="venue-select" class="form-control">
			<option value=0>Select a Venue</option>
		<?php 
			foreach ($venue_rows as $venue_row) {
				echo "<option value={$venue_row['venue_id']}>{$venue_row['name']}</option>";
			}
		?>
		</select>
		<br/>
		<button type="submit" id="select-venue-btn" disabled class="btn btn-default">Submit</button>
	</form>
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