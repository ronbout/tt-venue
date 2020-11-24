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
		__('View Products by Venue'),
		__('Venue'),
		'manage_options',
		'venue-view-products',
		'taste_view_products_by_venue'
	 );
 }

add_action('admin_menu', 'taste_venue_menu_option');