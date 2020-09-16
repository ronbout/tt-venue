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


require_once TASTE_PLUGIN_INCLUDES.'/activation-deactivation.php';

register_activation_hook( __FILE__, 'taste_venue_activation' );
register_deactivation_hook( __FILE__, 'taste_venue_deactivation' );

if (is_admin()) {
	require_once TASTE_PLUGIN_INCLUDES.'/admin/assign-products.php';
	require_once TASTE_PLUGIN_INCLUDES.'/admin/venue-admin-menus.php';
	require_once TASTE_PLUGIN_INCLUDES.'/admin/admin-enqueues.php';
	require_once TASTE_PLUGIN_INCLUDES.'/admin/venue-user-fields.php';
	//require_once TASTE_PLUGIN_INCLUDES.'/admin/prod-listing.php';
	require_once TASTE_PLUGIN_INCLUDES.'/metabox/product-metabox.php';
	VenueUserFields::get_instance();
}

// enqueues 
//require_once TASTE_PLUGIN_INCLUDES.'/enqueues.php';
require_once TASTE_PLUGIN_INCLUDES.'/ajax/ajax-functions.php';

// set up venue-manager.php as page template 
function taste_add_venue_manager_template ($templates) {
	$templates['venue-manager.php'] = 'Venue Manager';
	return $templates;
	}
add_filter ('theme_page_templates', 'taste_add_venue_manager_template');

function taste_redirect_page_template ($template) {
	if (is_page_template('venue-manager.php')) {
		$template = plugin_dir_path( __FILE__ ).'page-templates/venue-manager.php';
	}
	return $template;
}
add_filter ('page_template', 'taste_redirect_page_template');


