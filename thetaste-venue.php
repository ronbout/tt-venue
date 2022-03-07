<?php

/*
    Plugin Name: TheTaste Venue Plugin
    Plugin URI: http://thetaste.ie
    Description: Various functionalities for thetaste.ie Venue Portal
		Version: 1.0.0
		Date: 9/15/2020
    Author: Ron Boutilier
    Text Domain: taste-plugin
 */

defined('ABSPATH') or die('Direct script access disallowed.');

define('TASTE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('TASTE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TASTE_PLUGIN_INCLUDES', TASTE_PLUGIN_PATH.'includes');
define('TASTE_PLUGIN_INCLUDES_URL', TASTE_PLUGIN_URL.'includes');
define('TASTE_VENUE_INVOICE_URL', TASTE_PLUGIN_URL . "pdfs/invoice.php");

/* some helpful CONSTANTS */
define('TASTE_PAYMENT_STATUS_PAID', 1);
define('TASTE_PAYMENT_STATUS_ADJ', 2);
define('TASTE_PAYMENT_STATUS_PENDING', 3);

$uploads_info = wp_get_upload_dir();
$uploads_base_url = $uploads_info['baseurl'];
define('TASTE_VENUE_UPLOADS_BASE_URL', $uploads_base_url);


require_once TASTE_PLUGIN_INCLUDES.'/activation-deactivation.php';

register_activation_hook( __FILE__, 'taste_venue_activation' );
register_deactivation_hook( __FILE__, 'taste_venue_deactivation' );

if (is_admin()) {
	require_once TASTE_PLUGIN_INCLUDES.'/admin/list-products-by-venue.php';
	require_once TASTE_PLUGIN_INCLUDES.'/admin/list-posts-by-venue.php';
	require_once TASTE_PLUGIN_INCLUDES.'/admin/venue-admin-menus.php';
	require_once TASTE_PLUGIN_INCLUDES.'/admin/admin-enqueues.php';
	require_once TASTE_PLUGIN_INCLUDES.'/admin/venue-user-fields.php';
	require_once TASTE_PLUGIN_INCLUDES.'/admin/prod-listing.php';
	require_once TASTE_PLUGIN_INCLUDES.'/admin/post-listing.php';
	require_once TASTE_PLUGIN_INCLUDES.'/admin/bulk-product-assign.php';
	require_once TASTE_PLUGIN_INCLUDES.'/metabox/product-metabox.php';
	require_once TASTE_PLUGIN_INCLUDES.'/metabox/post-metabox.php';
	VenueUserFields::get_instance();
}

// enqueues 
//require_once TASTE_PLUGIN_INCLUDES.'/enqueues.php';
require_once TASTE_PLUGIN_INCLUDES.'/ajax/ajax-functions.php';
require_once TASTE_PLUGIN_INCLUDES.'/functions.php';

require_once TASTE_PLUGIN_INCLUDES.'/ajax/outstanding/ajax-functions.php';

/**
 * Campaign manager set up code
 */
// set up page templates
function taste_add_venue_manager_template ($templates) {
	$templates['campaign-manager.php'] = 'Campaign Manager';
	$templates['venue-portal.php'] = 'Venue Portal';
	$templates['venue-profile-page.php'] = 'Venue Profile Page';
	$templates['audit-by-products.php'] = 'Audit By Products';
	return $templates;
	}
add_filter ('theme_page_templates', 'taste_add_venue_manager_template');

function taste_redirect_page_template ($template) {
	if (is_page_template('campaign-manager.php')) {
		$template = plugin_dir_path( __FILE__ ).'page-templates/campaign-manager.php';
	}
	if (is_page_template('venue-portal.php')) {
		$template = plugin_dir_path( __FILE__ ).'page-templates/venue-portal.php';
	}
	if (is_page_template('venue-profile-page.php')) {
		$template = plugin_dir_path( __FILE__ ).'page-templates/venue-profile-page.php';
	}
	if (is_page_template('audit-by-products.php')) {
		$template = plugin_dir_path( __FILE__ ).'page-templates/audit-by-products.php';
	}
	return $template;
}
add_filter ('page_template', 'taste_redirect_page_template');

// make sure the campaign manager login does not redirect to wp-admin
add_action( 'wp_login_failed', 'taste_venue_login_fail' );  // hook failed login

function taste_venue_login_fail( $username ) {
   $referrer = $_SERVER['HTTP_REFERER'];  
   // if there's a valid referrer, and it's not the default log-in screen
   if ( !empty($referrer) && !strstr($referrer,'wp-login') && !strstr($referrer,'wp-admin') ) {
      wp_redirect( $referrer . '?login=failed' );  // let's append some information (login=failed) to the URL for the theme to use
      exit;
   }
}
function taste_venue_query_vars( $qvars ) {
	$qvars[] = 'login';
	return $qvars;
}
add_filter( 'query_vars', 'taste_venue_query_vars' );

/**
 * Dingle Banner Ad
 */

function dingle_banner_ad() {
	?>
	<div>
<a href="https://dingledistillery.ie/" target="_blank">
<img src="https://www.thetaste.ie/wp-content/uploads/2021/09/dinlge-banner-1400.jpg" alt="">
</a>
	</div>
	<?php
}
add_action('__before_main', 'dingle_banner_ad');
//add_action('__before_content', 'dingle_banner_ad');

