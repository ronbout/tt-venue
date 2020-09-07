<?php 

defined('ABSPATH') or die('Direct script access disallowed.');

/**
 *  ACTIVATION CODE 
 *  Add venue role, venue table, and new venue-products table
 */
function taste_add_venue_role() {
	add_role( 'venue', __('Venue'), array( 'read' => true, 'level_0' => true ) );
}


function taste_add_venue_table() {
	global $wpdb;
	$venue_table = $wpdb->prefix.'taste_venue';
	$user_table = $wpdb->prefix.'users';

	$sql = "CREATE TABLE $venue_table (
			venue_id BIGINT(20) UNSIGNED NOT NULL,
			name VARCHAR(80) NOT NULL,
			description VARCHAR(255),
			city VARCHAR(100),
			venue_type ENUM ('Restaurant', 'Pub', 'Hotel', 'Cafe', 'Other'),
			voucher_pct FLOAT,
			PRIMARY KEY (venue_id),
			UNIQUE KEY (name),
			KEY (venue_type)
		)";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta($sql);
}

function taste_add_venue_product_table() {
	global $wpdb;
	$venue_products_table = $wpdb->prefix.'taste_venue_products';
	$venue_table = $wpdb->prefix.'taste_venue';
	$products_table = $wpdb->prefix.'wc_product_meta_lookup';

	$sql = "CREATE TABLE $venue_products_table (
			venue_id BIGINT(20) UNSIGNED NOT NULL,
			product_id BIGINT(20) NOT NULL,
			PRIMARY KEY  (venue_id, product_id),
			UNIQUE KEY (product_id)
		)";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta($sql);
}

function taste_venue_activation() {

	taste_add_venue_role();
	
	taste_add_venue_table();

	taste_add_venue_product_table();
}
/**** END OF ACTIVATION CODE ****/

/**
 *  Remove venue role upon plugin de-activation
 */
function taste_remove_venue_role() {
	remove_role( 'venue' );
}

/**
 * DEACTIVATION CODE
 */
function taste_venue_deactivation() {
	taste_remove_venue_role();

	/**
	 *  *** NO!!!  DO NOT WANT TO LOSE INFO UNLESS SPECIFICALLY CHOSEN BY USER  ***
	 * remove table for one to many (venue to vouchers)
	 * 
	 */
}

/**** END OF DEACTIVATION CODE ****/