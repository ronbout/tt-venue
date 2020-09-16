<?php 
/**
 *  prod-listing.php 
 *  add venue column to product listing 
 *  add bulk assign capability
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

// set up the venue column 
function taste_add_venue_column( $columns ) {
  $columns['venue'] = __( 'Venue' );
  return $columns;
}
add_filter( 'manage_product_posts_columns', 'taste_add_venue_column' );

// get the data for the rows
function taste_venue_column( $column, $product_id ) {
	global $wpdb; 
  // Venue column
  if ( 'venue' === $column ) {
		$venue_name = 'Not assigned';
		// get the venue from the venue_products table
		$venue_row = $wpdb->get_results($wpdb->prepare("
			SELECT v.venue_id, v.name 
			FROM {$wpdb->prefix}taste_venue_products vp
			JOIN {$wpdb->prefix}taste_venue v ON v.venue_id = vp.venue_id
			WHERE vp.product_id = %d
			", $product_id), ARRAY_A
		);
		if (count($venue_row)) {
			$venue_name = $venue_row[0]['name'];
		}
		echo $venue_name;
  }
}
add_action( 'manage_product_posts_custom_column', 'taste_venue_column', 10, 2);