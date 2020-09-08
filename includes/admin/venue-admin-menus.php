<?php 
/**
 *  venu-admin-menus.php 
 *  Sets up the top level admin menu 
 *  and its submenus
 */
 
defined('ABSPATH') or die('Direct script access disallowed.');

 function taste_venue_menu_option() {
	 add_submenu_page(
		'edit.php?post_type=product',
		__('Assign Products to Venue'),
		__('Assign Venue'),
		'manage_options',
		'venue-assign-products',
		'taste_assign_products'
	 );
 }

add_action('admin_menu', 'taste_venue_menu_option');