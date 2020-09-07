<?php 
/**
 *  venu-admin-menus.php 
 *  Sets up the top level admin menu 
 *  and its submenus
 */
 
defined('ABSPATH') or die('Direct script access disallowed.');

 function taste_venue_menu_option() {
	 add_menu_page(
		__('Venues'), 
		__('Venues'),
		'manage_options',
		'venues',
		'taste_venues',
		'dashicons-store',
		56
	 );

	 add_submenu_page(
		'venues',
		__('Venues'),
		__('All Venues'),
		'manage_options',
		'venues',
		'taste_venues'
	 );
	 	 
	 add_submenu_page(
		'venues',
		__('Add New Venue'),
		__('Add New Venue'),
		'manage_options',
		'venue-new',
		'taste_venue_new'
	 );
	  
	 add_submenu_page(
		'venues',
		__('Assign Products'),
		__('Assign Products'),
		'manage_options',
		'venue-assign-products',
		'taste_assign_products'
	 );
 }

add_action('admin_menu', 'taste_venue_menu_option');