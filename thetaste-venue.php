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
!defined('TASTE_PAYMENT_STATUS_PAID') && define('TASTE_PAYMENT_STATUS_PAID', 1);
!defined('TASTE_PAYMENT_STATUS_ADJ') && define('TASTE_PAYMENT_STATUS_ADJ', 2);
!defined('TASTE_PAYMENT_STATUS_PENDING') && define('TASTE_PAYMENT_STATUS_PENDING', 3);
!defined('TASTE_PAYMENT_STATUS_PROCESSING') && define('TASTE_PAYMENT_STATUS_PROCESSING', 4);
!defined('TASTE_DEFAULT_PAYMENT_STATUS') && define('TASTE_DEFAULT_PAYMENT_STATUS', TASTE_PAYMENT_STATUS_PENDING);

// we use GROUP_CONCAT in a number of instances.  To ensure that the
// size of that field is always large enough, change it at the session level.
global $wpdb;

$wpdb->query("SET SESSION group_concat_max_len = 30000;");

$uploads_info = wp_get_upload_dir();
$uploads_base_url = $uploads_info['baseurl'];
!defined('TASTE_VENUE_UPLOADS_BASE_URL') && define('TASTE_VENUE_UPLOADS_BASE_URL', $uploads_base_url);


require_once TASTE_PLUGIN_INCLUDES.'/activation-deactivation.php';

register_activation_hook( __FILE__, 'taste_venue_activation' );
register_deactivation_hook( __FILE__, 'taste_venue_deactivation' );

if (is_admin()) {
	require_once TASTE_PLUGIN_INCLUDES.'/admin/list-products-by-venue.php';
	require_once TASTE_PLUGIN_INCLUDES.'/admin/list-posts-by-venue.php';
	// require_once TASTE_PLUGIN_INCLUDES.'/admin/venue-admin-menus.php';
	require_once TASTE_PLUGIN_INCLUDES.'/admin/admin-enqueues.php';
	require_once TASTE_PLUGIN_INCLUDES.'/admin/venue-user-fields.php';
	require_once TASTE_PLUGIN_INCLUDES.'/admin/prod-listing.php';
	require_once TASTE_PLUGIN_INCLUDES.'/admin/post-listing.php';
	require_once TASTE_PLUGIN_INCLUDES.'/admin/bulk-product-assign.php';
	require_once TASTE_PLUGIN_INCLUDES.'/metabox/product-metabox.php';
	require_once TASTE_PLUGIN_INCLUDES.'/metabox/post-metabox.php';
	require_once TASTE_PLUGIN_INCLUDES.'/metabox/order-trans-metabox.php';
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
	$templates['mini-order-page.php'] = 'Mini Order Item Page';
	$templates['venue-change-password.php'] = 'Venue Change Password';
	$templates['venue-lost-password.php'] = 'Venue Lost Password';
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
	if (is_page_template('mini-order-page.php')) {
		$template = plugin_dir_path( __FILE__ ).'page-templates/mini-order-page.php';
	}
	if (is_page_template('venue-change-password.php')) {
		$template = plugin_dir_path( __FILE__ ).'page-templates/venue-change-password.php';
	}
	if (is_page_template('venue-lost-password.php')) {
		$template = plugin_dir_path( __FILE__ ).'page-templates/venue-lost-password.php';
	}
	return $template;
}
add_filter ('page_template', 'taste_redirect_page_template');

// make sure the campaign manager login does not redirect to wp-admin
add_action( 'wp_login_failed', 'taste_venue_login_fail' );  // hook failed login

function taste_venue_login_fail( $username ) {
   $referer = $_SERVER['HTTP_REFERER'];  
   // if there's a valid referer, and it's not the default log-in screen
   if ( !empty($referer) && !strstr($referer,'wp-login') && !strstr($referer,'my-taste-account') && !strstr($referer,'wp-admin') ) {
			$url_path = parse_url($referer, PHP_URL_PATH);
			$url_host = parse_url($referer, PHP_URL_HOST);
			$url_scheme = parse_url($referer, PHP_URL_SCHEME);
			$url_query = parse_url($referer, PHP_URL_QUERY);
			$request_base =  $url_scheme . "://" . $url_host . $url_path;
			$url_query = str_replace('?login=failed', '', $url_query);
			parse_str($url_query, $query_var_array);
			if (!isset($query_var_array['login']) ||  $query_var_array['login'] != 'failed') {
					$query_var_array['login'] = 'failed';
			}
			$redirect = add_query_arg( $query_var_array, $request_base );
      wp_redirect( $redirect );  // let's append some information (login=failed) to the URL if not already present
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
/*
function dingle_banner_ad() {
	?>
<div>
  <a href="https://dingledistillery.ie/" target="_blank">
    <img src="https://www.thetaste.ie/wp-content/uploads/2021/09/dinlge-banner-1400.jpg" alt="">
  </a>
</div>
<?php
}*/
// add_action('__before_main', 'dingle_banner_ad');
//add_action('__before_content', 'dingle_banner_ad');