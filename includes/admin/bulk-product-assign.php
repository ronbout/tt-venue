<?php 
/**
 *  bulk-product-assign.php 
 *  add bulk operation to the product listing
 *  to assign a venue to the selected products
 * 
 * 	9/21/2020  Ron Boutilier
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

          
function venue_bulk_edit_input() {
	global $wpdb;
	// build list of venues 
	$venue_rows = $wpdb->get_results("
		SELECT venue_id, name, description, venue_type
		FROM " . $wpdb->prefix . "taste_venue
		ORDER BY venue_type, name
	", ARRAY_A);
	display_venue_select(false, 0, false, true);
}
add_action( 'woocommerce_product_bulk_edit_start', 'venue_bulk_edit_input' );
		

function venue_bulk_edit_save( $product ) {
		$product_id = $product->get_id();   
		// echo '<h1><pre>', var_dump($_REQUEST), '</pre></h1>';
		// die(); 
   if ( isset( $_REQUEST['venue_id'] ) ) {
				$venue_id = intval($_REQUEST['venue_id']);
				if ($venue_id) {
					insert_venue_product_on_dup($venue_id, $product_id);
				}
    }
}
add_action( 'woocommerce_product_bulk_edit_save', 'venue_bulk_edit_save' );