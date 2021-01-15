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
<nav class="navbar sticky-top navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="#">
        <img src="<?php echo get_site_url() ?>/wp-content/uploads/2017/12/thetaste-site-homepage-logo5.png" class="img-fluid" style="width: 220px"  alt="" loading="lazy">
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarTogglerDemo01" aria-controls="navbarTogglerDemo01" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarTogglerDemo01">
        <ul class="navbar-nav ml-auto mt-2 mt-lg-0">
            <li class="nav-item active">
                <a class="nav-link" href="<?php echo get_site_url(null, '/venue-portal') ?>">Home</a>
            </li>
            <?php
                if ($use_new_campaign) {
                    display_new_portal_link();
                } else {
                    display_old_portal_link($venue_voucher_page);
                }
            ?>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo get_site_url(null, '/venue-profile-page') ?>">Profile</a>
            </li>
            <li class="nav-item">
                <?php display_logout() ?>
            </li>
        </ul>
    </div>
</nav>
<div class="container id="main_wrapper">
	<div class="row pb-4">
			<div class="col-sm-12 col-xl-12 dashboard_grid_cols d-flex align-items-center flex-column">
					<h2 class="dashboard_heading mt-5 font-weight-bold text-left">Welcome to Your Dashboard, <?php echo $venue_name; ?> </h2>
			</div>
	</div>
	<div class="text-center">
		<a href="<?php echo get_site_url() ?>">
				<img src="<?php echo get_site_url() ?>/wp-content/uploads/2017/12/thetaste-site-homepage-logo5.png">
		</a>
	</div>
	<br><br>
	<div class="text-center">
		<b>IRELAND’S AWARD WINNING FOOD, DRINK & TRAVEL DIGITAL MAGAZINE</b>
		<br><br>
		<span style="font-size:12px;">19.6M READERS WORLDWIDE <b>|</b> 10K ARTICLES <b>|</b> €10M GENERATED FOR THE IRISH HOSPITALITY INDUSTRY <b>|</b> 726K REGISTERED MEMBERS <b>|</b> 200K+ TASTE EXPERIENCES SOLD <b>|</b> 300K SOCIAL MEDIA FOLLOWERS <b>|</b> WINNER OF BEST DIGITAL FOOD MAGAZINE IN THE WORLD <b>|</b> WINNER OF OUTSTANDING SMALL BUSINESS IN IRELAND</span>
	</div>
</div>
<script type="text/javascript" src= "<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/js/thetaste-dashboard.js"></script>
</body>
</html>

<?php
function display_new_portal() {
	?>
	<div class="col-sm-12 col-md-12 dashboard_grid_cols d-flex justify-content-center align-items-center flex-column" id="coming_soon">
        <div class="text-center">
            <h2 class="col-heading d-inline-block">Now Available! <p class="offer">Manage all your offers</p> </h2>
            <a class="d-inline-block" href="<?php echo get_site_url(null, '/campaign-manager') ?>">
                <button class="btn btn-primary" id="campaing_manager">Campaign Manager</button>
            </a>
        </div>
	</div>
	<?php
}

function display_old_portal($venue_voucher_page) {
	?>
	<div class="col-sm-12 col-md-12 dashboard_grid_cols d-flex justify-content-center align-items-center flex-column" id="original_redemption">
		<h2 class="col-heading">Original Voucher Redemption page</h2>
		<a href="<?php echo get_site_url(null, $venue_voucher_page) ?>" target="_blank">
			<button class="btn btn-primary" id="voucher_btn">Manage Vouchers</button>
		</a>
	</div>
	<div class="col-sm-12 col-md-12 d-flex justify-content-center align-items-center flex-column" id="coming_soon">
		<h2 class="col-heading">Coming Soon!</h2>
		<p class="info_full">A full-service Campaign Manager for all your Offers</p>
	</div>
	<?php
}

function display_logout() {
	?>
	  <a class="nav-link" href="<?php echo wp_logout_url(get_site_url()) ?>" data-toggle="tooltip" data-placement="left" title="Logout" id="logout"><i class="fas fa-sign-out-alt"></i></a>

	<?php
}

function display_new_portal_link() {
    ?>
        <li><a class="nav-link" href="<?php echo get_site_url(null, '/campaign-manager') ?>">Campaign Manager</a></li>
    <?php
}

function display_old_portal_link($venue_voucher_page) {
    ?>
        <li class="nav-item"><a class="nav-link" href="<?php echo get_site_url(null, $venue_voucher_page) ?>">Manage Vouchers</a></li>
    <?php
}