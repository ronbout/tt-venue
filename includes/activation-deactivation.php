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

	$sql = "CREATE TABLE IF NOT EXISTS $venue_table (
			venue_id BIGINT(20) UNSIGNED NOT NULL,
			name VARCHAR(80) NOT NULL,
			description VARCHAR(255),
			address1 VARCHAR(120),
			address2 VARCHAR(120),
			city VARCHAR(100),
			postcode VARCHAR(20),
			country VARCHAR(100),
			state VARCHAR(100),
			phone VARCHAR(40),
			venue_type ENUM ('Restaurant', 'Bar', 'Hotel', 'Product'),
			voucher_pct FLOAT,
			paid_member TINYINT(1) ZEROFILL NOT NULL DEFAULT 0, 
			member_renewal_date DATE,
			membership_cost DECIMAL(10,2),
			use_new_campaign TINYINT(1) NOT NULL DEFAULT 0,
			historical_cutoff_date DATE NULL DEFAULT NULL,
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

	$sql = "CREATE TABLE IF NOT EXISTS $venue_products_table (
			venue_id BIGINT(20) UNSIGNED NOT NULL,
			product_id BIGINT(20) NOT NULL,
			PRIMARY KEY  (venue_id, product_id),
			UNIQUE KEY (product_id)
		)";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta($sql);
}

function taste_add_venues_posts_table() {
	global $wpdb;
	$venues_posts_table = $wpdb->prefix.'taste_venues_posts';
	$venue_table = $wpdb->prefix.'taste_venue';

	$sql = "CREATE TABLE IF NOT EXISTS $venues_posts_table (
			venue_id BIGINT(20) UNSIGNED NOT NULL,
			post_id BIGINT(20) UNSIGNED NOT NULL,
			PRIMARY KEY  (venue_id, post_id),
			KEY (post_id)
		)";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta($sql);
}

function taste_add_venue_order_redemption_audit_table() {
	global $wpdb;
	$venue_order_redemption_table = $wpdb->prefix.'taste_venue_order_redemption_audit';

	$sql = "CREATE TABLE IF NOT EXISTS $venue_order_redemption_table (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		order_item_id BIGINT(20) NOT NULL,
		timestamp TIMESTAMP NOT NULL DEFAULT current_timestamp(),
		user_id BIGINT(20) UNSIGNED NOT NULL,
		redemption_value TINYINT(1) NOT NULL,
		PRIMARY KEY  (id),
		INDEX order_item_id (order_item_id)
	)";

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta($sql);

}

function taste_add_venue_payment_audit_table() {
	global $wpdb;
	$venue_payment_audit_table = $wpdb->prefix.'taste_venue_payment_audit';

	$sql = "CREATE TABLE IF NOT EXISTS $venue_payment_audit_table (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		payment_id INT(11) NOT NULL,
		entry_timestamp TIMESTAMP NOT NULL DEFAULT current_timestamp(),
		prev_payment_timestamp TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00',
		payment_timestamp TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
		user_id BIGINT(20) UNSIGNED NOT NULL,
		action ENUM('INSERT','UPDATE','DELETE') NOT NULL,
		prev_amount DECIMAL(10,2) NULL DEFAULT NULL,
		amount DECIMAL(10,2) NULL DEFAULT NULL,
		comment VARCHAR(400) NULL DEFAULT NULL,
		PRIMARY KEY (id),
		INDEX payment_id (payment_id)
	)";

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta($sql);
}

function taste_add_payment_tables() {
	global $wpdb;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	$venue_payment_table = $wpdb->prefix.'taste_venue_payment';
	$venue_payment_products_table = $wpdb->prefix.'taste_venue_payment_products';
	$venue_payment_order_item_table = $wpdb->prefix.'taste_venue_payment_order_item_xref';
	
	$sql = "CREATE TABLE IF NOT EXISTS $venue_payment_table (
		`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		`payment_date` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
		`venue_id` BIGINT(20) NULL DEFAULT NULL,
		`amount` DECIMAL(10,2) NOT NULL,
		`comment` VARCHAR(400) NULL DEFAULT NULL COLLATE 'latin1_swedish_ci',
		`comment_visible_venues` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
		`attach_vat_invoice` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
		`status` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
		PRIMARY KEY (`payment_id`) USING BTREE,
		INDEX `venue_id` (`venue_id`) USING BTREE,
		INDEX `payment_id_venue_id` (`payment_id`, `venue_id`) USING BTREE
	)";
	dbDelta($sql);
	
	$sql = "CREATE TABLE IF NOT EXISTS $venue_payment_products_table (
		`payment_id` BIGINT(20) UNSIGNED NOT NULL,
		`product_id` BIGINT(20) UNSIGNED NOT NULL,
		`amount` DECIMAL(10,2) NOT NULL,
		PRIMARY KEY (`payment_id`, `product_id`) USING BTREE,
		INDEX `product_id` (`product_id`) USING BTREE
	)";
	dbDelta($sql);

	$sql = "CREATE TABLE IF NOT EXISTS $venue_payment_order_item_table (
		payment_id INT(11) NOT NULL,
		order_item_id BIGINT(20) UNSIGNED NOT NULL,
		PRIMARY KEY (payment_id, order_item_id),
		INDEX order_item_id (order_item_id) USING BTREE
	)";
	dbDelta($sql);

}

function taste_venue_activation() {

	taste_add_venue_role();
	
	taste_add_venue_table();

	taste_add_venue_product_table();

	taste_add_venues_posts_table();

	taste_add_venue_order_redemption_audit_table();

	taste_add_venue_payment_audit_table();

	taste_add_payment_tables();
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