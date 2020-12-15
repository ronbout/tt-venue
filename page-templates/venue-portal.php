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
    <div class="container-fluid h-100">
<!--        <div id="venue-summary-div" class="panel-heading text-center"">-->
<!--            <h2 class="dashboard_heading mt-5">Welcome to Your Dashboard, --><?php //echo $venue_name; ?><!--</h2>-->
<!--        </div>-->
        <div class="row h-100">
            <div class="col-sm-4 col-md-4 animate__animated animate__bounceInLeft dashboard_grid_cols d-flex justify-content-center align-items-center flex-column" id="profile">
                <h2 class="col-heading">Edit your company contact info</h2>

                <a href="<?php echo get_site_url(null, '/venue-profile-page') ?>">
                    <button class="btn btn-primary" id="profile_btn">Profile Information Page</button>
                </a>
            </div>
            <?php
                if ($use_new_campaign) {
                    display_new_portal();
                } else {
                    display_old_portal($venue_voucher_page);
                }
            ?>
    </div>
</body>
</html>

<?php
function display_new_portal() {
	?>
	<div class="col-sm-8 col-md-8 dashboard_grid_cols d-flex justify-content-center align-items-center flex-column animate__animated animate__bounceInRight" id="coming_soon">
		<h2 class="col-heading">Now Available!</h2>
		<p>Manage all your offers</p>
		<a href="<?php echo get_site_url(null, '/campaign-manager') ?>">
			<button class="btn btn-primary" id="campaing_manager">Campaign Manager</button>
		</a>
		<?php display_logout() ?>
	</div>
	<?php
}

function display_old_portal($venue_voucher_page) {
	?>
	<div class="col-sm-4 col-md-4 animate__animated animate__bounceInUp dashboard_grid_cols d-flex justify-content-center align-items-center flex-column" id="original_redemption">
		<h2 class="col-heading">Original Voucher Redemption page</h2>
		<a href="<?php echo get_site_url(null, $venue_voucher_page) ?>" target="_blank">
			<button class="btn btn-primary" id="voucher_btn">Manage Vouchers</button>
		</a>
	</div>
	<div class="col-sm-4 col-md-4 dashboard_grid_cols animate__animated animate__bounceInRight d-flex justify-content-center align-items-center flex-column" id="coming_soon">
		<h2 class="col-heading">Coming Soon!</h2>
		<p class="info_full">A full-service Campaign Manager for all your Offers</p>
		<?php display_logout() ?>
	</div>
	<?php
}

function display_logout() {
	?>
	  <a href="<?php echo wp_logout_url(get_site_url()) ?>" data-toggle="tooltip" data-placement="left" title="Logout" id="logout"><i class="fas 	fa-sign-out-alt"></i>
		</a>
	<?php
}