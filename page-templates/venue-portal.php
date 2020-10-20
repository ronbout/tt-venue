<?php
/*
Template Name: Venue Portal
*/

/**
 * 	The entry landing page for all venues
 *  Date:  9/15/2020
 * 	Author: Ron Boutilier
 */
defined('ABSPATH') or die('Direct script access disallowed.');

if ( !is_user_logged_in()) {
	require_once TASTE_PLUGIN_PATH.'page-templates/thetaste-venue-login.php';
	die();
} else {
	$user = wp_get_current_user();
	$role = $user->roles[0];
	if ('VENUE' !== strtoupper($role)) {
		echo "<h2>Role: $role </h2>";
		die('You must be logged in as a Venue to access this page.');
	}
	$venue_id = $user->ID;
	// get venue name and other info
	$venue_table = $wpdb->prefix."taste_venue";
	$venue_row = $wpdb->get_results($wpdb->prepare("
		SELECT v.name, v.venue_type, v.use_new_campaign
		FROM $venue_table v
		WHERE	v.venue_id = %d", 
	$venue_id));
	$venue_name = $venue_row[0]->name;
	$venue_type = $venue_row[0]->venue_type;
	$use_new_campaign = $venue_row[0]->use_new_campaign;
	$venue_voucher_page = 'Hotel' === $venue_type ? '/hotelmanager' : '/restaurantmanager';
	$type_desc = $venue_type;
}

require_once TASTE_PLUGIN_PATH.'page-templates/partials/venue-head.php';
?>
<body>

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
				<br><br>
				<span style="font-size:12px;">19.6M READERS WORLDWIDE <b>|</b> 10K ARTICLES <b>|</b> €10M GENERATED FOR THE IRISH HOSPITALITY INDUSTRY <b>|</b> 726K REGISTERED MEMBERS <b>|</b> 200K+ TASTE EXPERIENCES SOLD <b>|</b> 300K SOCIAL MEDIA FOLLOWERS <b>|</b> WINNER OF BEST DIGITAL FOOD MAGAZINE IN THE WORLD <b>|</b> WINNER OF OUTSTANDING SMALL BUSINESS IN IRELAND</span>
			</div>
		</header>
		<div class="portal-welcome panel panel-default">
			<div id="venue-summary-div" class="panel-heading text-center"">
				<h2>Welcome to Your Dashboard, <?php echo $venue_name; ?></h2>
			</div>
			<div class="portal-links-container panel-body">
				<div class="portal-link">
					<p>Edit your company contact info: </p>
					<a href="<?php echo get_site_url(null, '/venue-profile-page') ?>">
						<button class="btn btn-primary">Profile Information Page</button>
					</a>
				</div>
				<?php if ($use_new_campaign) {
					display_new_portal();
				} else {
					display_old_portal();
				}
				?>
			</div>
		</div>
	</main>
</body>
</html>

<?php
function display_new_portal() {
	?>
	<div class="portal-link coming-soon">
		<h2>Now Available!</h2>
		<p>Manage all your offers</p>
		<a href="<?php echo get_site_url(null, '/campaign-manager') ?>">
			<button class="btn btn-primary">Campaign Manager</button>
		</a>
	</div>
	<?php
}

function display_old_portal() {
	?>
	<div class="portal-link">
		<p>Access the original Voucher Redemption page</p>
		<a href="<?php echo get_site_url(null, $venue_voucher_page) ?>" target="_blank">
			<button class="btn btn-primary">Manage Vouchers</button>
		</a>
	</div>
	<div class="portal-link coming-soon">
		<h2>Coming Soon!</h2>
		<p>A full-service Campaign Manager </br>for all your Offers</p>
	</div>
	<?php
}