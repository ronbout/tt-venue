<?php
/*
Template Name: Outstanding Debts
*/

/**
 *  Date:  10/20/2020
 * 	Author: Ron Boutilier
 */
defined('ABSPATH') or die('Direct script access disallowed.');
global $wpdb;

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

require_once TASTE_PLUGIN_PATH.'page-templates/partials/venue-head.php';
?>
<body>
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
		<div class="panel panel-default year-form-container">
			<div class="panel-heading">
				<form class="form-horizontal" id="year-form">
					<div class="form-group">
						<label for="year" class="col-sm-4 control-label">Select Year</label>
						<div class="col-sm-4">
							<select name="year_select" id="year-select" class="form-control">
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
					<div class="form-group">
						<div class="col-sm-2"></div>
						<div class="col-sm-8">
							<label class="radio-inline">
								<input type="radio" name="year_type" id="radio-year-product" value="product" checked> Use Product Year
							</label>
							<label class="radio-inline">
								<input type="radio" name="year_type" id="radio-year-order" value="order"> Use First Order Year
							</label>
						</div>
					</div>
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
		<script type="text/javascript" src= "<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/js/thetaste-outstanding.js"></script>
		</body>
	</html>