<?php
/*
Template Name: Audit By Products
*/

/**
 *  Date:  10/20/2020
 * 	Author: Ron Boutilier
 */
defined('ABSPATH') or die('Direct script access disallowed.');
global $wpdb;


/***
 * 
 * steal this to run a test agains the offer payments table
 * 
 * are they multiples of the net payable of one order
 * 
 * 
 * get price for product from db and then calc the commission, vat and net payable
 * look to the redeem vouchers list code for SQL and calcs.  Then just divide that number
 * into the payment amount
 * 
 * Run for both individual payments (oldest first)
 * also try the total payments per product...might have been corrected.
 * 
 */



	
require_once TASTE_PLUGIN_PATH.'page-templates/partials/venue-head.php';
require_once TASTE_PLUGIN_PATH.'page-templates/test-products-net-payable.php';


/**
 * 
 * 
 * 
 * end of the temp payment testing
 * 
 * 
 */

 if (false) {
if ( !is_user_logged_in()) {
	
	require_once TASTE_PLUGIN_PATH.'page-templates/thetaste-venue-login.php';
	die();
} else {
	$user = wp_get_current_user();
	$role = $user->roles[0];
	$admin = ('ADMINISTRATOR' === strtoupper($role));

	if (!$admin) {
			echo "<h2>Role: $role </h2>";
			die('You must be logged in as Admin to access this page.');
	}
}

// require_once TASTE_PLUGIN_PATH.'page-templates/partials/venue-head.php';
require_once TASTE_PLUGIN_INCLUDES.'/ajax/outstanding/out-column-data.php';
$year = date('Y');
$month = date('m');
$day = date('d');
$start_year = $year - 1;
$start_date_default =  "$start_year-$month-$day";
$current_date = date('Y-m-d');
?>
<body class="audit-by-products orig-bs3">
	<?php
	// phpinfo();
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
	<main>
		</br>
		</br>
		<div class="container">
		<header>
			<div class="text-center">
				<a href="<?php echo get_site_url() ?>">
						<img src="<?php echo get_site_url() ?>/wp-content/uploads/2017/12/thetaste-site-homepage-logo5.png">
				</a>
			</div>
			<br><br>
			<div class="text-center">
				<b>WELCOME TO IRELAND’S AWARD WINNING FOOD, DRINK & TRAVEL DIGITAL MAGAZINE</b>
			</div>
		</header>
		<br><br>
		<div id="audit-filter-container" class="panel panel-default ">
			<div class="panel-heading">
				<form class="form-horizontal" id="audit-filter-form">
					<?php 
						filter_product_date($start_date_default, $current_date); 
						filter_min_order_date($start_date_default, $current_date);
						filter_recurring_product_check();
						filter_venue_selection();
						filter_product_ids();
						filter_customize_product_columns($outstanding_product_columns, $out_default_product_columns);
						filter_customize_order_columns($outstanding_order_columns, $out_default_order_columns);
					?>
					<div class="form-group">
						<div class="text-center">
							<button type="submit" name="load_products" id="load-products" class="btn btn-primary">Load Products</button>
						</div>
					</div>
				</form>
			</div>
		</div>
		<div id="product-list-div" class="container"></div>
		<div id="voucher-list-div" class="container"></div>
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
		<script type="text/javascript" src= "<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/js/thetaste-audit.js"></script>
		<script type="text/javascript" src= "<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/js/thetaste-venue-select.js"></script>
		</body>
	</html>

<?php
 }
function filter_product_date($start_date_default, $current_date) {
	?>
	<div class="filter-form-row">
		<div class="col-sm-6 form-group">
			<label for="product-select-type" class="col-sm-4 control-label">Product Date</label>
			<div class="col-sm-7">
				<select name="product-select-type" id="product-select-type" class="form-control">
					<option value="all">All</option>
					<option value="year" selected>Year</option>
					<option value="range">Date Range</option>
				</select>
			</div>
		</div>
		<div id="product-year-select-container" class="col-sm-6 form-group">
			<label for="product-year-select" class="col-sm-4 control-label">Select Year</label>
			<div class="col-sm-7">
				<select name="product-year-select" id="product-year-select" class="form-control">
					<option value="2020" selected>2020</option>
					<option value="2019">2019</option>
					<option value="2018">2018</option>
					<option value="2017">2017</option>
					<option value="2016">2016</option>
					<option value="2015">2015</option>
					<option value="2014">2014</option>
				</select>
			</div>
		</div>
		<div id="product-date-range-container" class=" col-sm-6 date-range-container">
			<div class="col-sm-5">
				<input type="text" id="product-date-start" value="<?php echo $start_date_default ?>">
			</div>
			<span class="col-sm-1 text-center">to</span>
			<div class="col-sm-5">
				<input type="text" id="product-date-end" value="<?php echo $current_date ?>">
			</div>
		</div>
	</div>
	<?php
}

function filter_min_order_date($start_date_default, $current_date) {
	?>
	<div class="filter-form-row">
		<div class="col-sm-6 form-group">
			<label for="order-select-type" class="col-sm-4 control-label">Min Order Date</label>
			<div class="col-sm-7">
				<select name="order-select-type" id="order-select-type" class="form-control">
					<option value="all" selected>All</option>
					<option value="year">Year</option>
					<option value="range">Date Range</option>
				</select>
			</div>
		</div>
		<div id="order-year-select-container" class="col-sm-6 form-group">
			<label for="order-year-select" class="col-sm-4 control-label">Select Year</label>
			<div class="col-sm-7">
				<select name="order-year-select" id="order-year-select" class="form-control">
					<option value="2020" selected>2020</option>
					<option value="2019">2019</option>
					<option value="2018">2018</option>
					<option value="2017">2017</option>
					<option value="2016">2016</option>
					<option value="2015">2015</option>
					<option value="2014">2014</option>
				</select>
			</div>
		</div>
		<div id="order-date-range-container" class=" col-sm-6 date-range-container">
			<div class="col-sm-5">
				<input type="text" id="order-date-start" value="<?php echo $start_date_default ?>">
			</div>
			<span class="col-sm-1 text-center">to</span>
			<div class="col-sm-5">
				<input type="text" id="order-date-end" value="<?php echo $current_date ?>">
			</div>
		</div>
	</div>
	<?php
}

function filter_recurring_product_check() {
	?>
	<div class="filter-form-row">
		<div class="col-sm-6 form-group" style="border-right: 1px solid #aaa;">
			<label for="recurring-product-check" class="col-sm-12">
				<div class="col-sm-4 text-right" style="padding-right: 34px;" >
					<input type="checkbox" name="recurring-product-check" id="recurring-product-check">
				</div>
				<span class="col-sm-8">
					Recurring Products Only</br>(Order Date < Product Date)
				</span>
			</label>
		</div>
		<div class="col-sm-6 form-group" style="padding-left: 22px;">
			<label for="balance-select-type" class="col-sm-4 control-label">Balance Due Filter</label>
			<div class="col-sm-7">
				<select name="balance-select-type" id="balance-select-type" class="form-control">
					<option value="any" selected>Any</option>
					<option value="positive">Positive</option>
					<option value="negative">Negative</option>
					<option value="zero">Zero</option>
					<option value="nonzero">Non Zero</option>
				</select>
			</div>
		</div>
	</div>
	<?php
}

function filter_venue_selection() {
	?>
	<div class="filter-form-row">
		<div class="col-sm-6 form-group">
			<label for="venue-select-type" class="col-sm-4 control-label">Venue</label>
			<div class="col-sm-7">
				<select name="venue-select-type" id="venue-select-type" class="form-control">
					<option value="any" selected>Any or Unassigned</option>
					<option value="unassigned">Unassigned Only</option>
					<option value="assigned">Assigned Only - Any Venue</option>
					<option value="venue">Choose Venue</option>
				</select>
			</div>
		</div>
		<div id="venue-select-container" class="col-sm-6 form-group">
			<?php display_venue_select(false, 0, false); ?>
		</div>
	</div>
	<?php
}

function filter_product_ids() {
	?>
	<div class="filter-form-row">
		<div class="col-sm-12 form-group">
			<label for="product-id-entry" class="col-sm-5 control-label">Enter Product ID's (overrides all other filters)</label>
			<div class="col-sm-7">
				<input type="number" id="product-id-entry">
				<button id="add-product-id-btn" class="btn btn-success">Add Product</button>
				<button id="clear-product-list-btn" class="btn btn-warning">Clear List</button>
			</div>
			<textarea name="product-id-list" id="product-id-list" cols="30" rows="2" disabled class="form-control"></textarea>
		</div>
	</div>
	<?php
}

function filter_customize_product_columns($outstanding_product_columns, $out_default_product_columns) {
	?>
	<div id="custom-products-columns-row" class="custom-columns-row filter-form-row">
		<div>
			<button id="custom-products-columns-toggle-btn" class='btn btn-info custom-columns-toggle-btn'>
				Customize Product Columns &nbsp; 
				<span id="custom-products-columns-arrow" class="glyphicon glyphicon-menu-down" aria-hidden="true"></span>
			</button>
		</div>
		<div id="custom-products-columns-list-div" class="custom-columns-list-div">
			<fieldset class="metabox-prefs">
				<?php
					foreach($outstanding_product_columns as $key => $out_col) {
						?>
						<label for="custom-prod-col-<?php echo $key ?>">
							<input type="checkbox" data-colkey="<?php echo $key?>" id="custom-prod-col-<?php echo $key ?>" 
								<?php echo in_array($key, $out_default_product_columns) ? 'checked' : ''; ?> 
								<?php echo ('product_id' === $key) ? 'disabled' : '' ?> >
							<?php echo str_replace('</br>', ' ', $out_col) ?>
						</label>
						<?php
					}
				?>
			</fieldset>
		</div>
	</div>
	<?php
}

function filter_customize_order_columns($outstanding_order_columns, $out_default_order_columns) {
	?>
	<div id="custom-orders-columns-row" class="custom-columns-row filter-form-row">
		<div>
			<button id="custom-orders-columns-toggle-btn" class='btn btn-info custom-columns-toggle-btn'>
				Customize Order Columns &nbsp; 
				<span id="custom-orders-columns-arrow" class="glyphicon glyphicon-menu-down" aria-hidden="true"></span>
			</button>
		</div>
		<div id="custom-orders-columns-list-div" class="custom-columns-list-div">
			<fieldset class="metabox-prefs">
				<?php
					foreach($outstanding_order_columns as $key => $out_col) {
						?>
						<label for="custom-order-col-<?php echo $key ?>">
							<input type="checkbox" data-colkey="<?php echo $key?>" id="custom-order-col-<?php echo $key ?>" 
								<?php echo in_array($key, $out_default_order_columns) ? 'checked' : ''; ?> >
							<?php echo str_replace('</br>', ' ', $out_col) ?>
						</label>
						<?php
					}
				?>
			</fieldset>
		</div>
	</div>
	<?php
}