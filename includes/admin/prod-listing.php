<?php 
/**
 *  prod-listing.php 
 *  add venue column to product listing 
 *  add bulk assign capability
 *  as well as sortability
 * 
 * 	Author: Ron Boutilier
 * 	Date: 10/10/2020
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

// set up the venue column in the products listing
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
		$venue_name = '-- Not assigned --';
		// get the venue from the venue_products table
		$venue_row = $wpdb->get_results($wpdb->prepare("
			SELECT v.venue_id, v.name 
			FROM {$wpdb->prefix}taste_venue_products vp
			JOIN {$wpdb->prefix}taste_venue v ON v.venue_id = vp.venue_id
			WHERE vp.product_id = %d
			", $product_id), ARRAY_A
		);
		if (count($venue_row)) {
			$venue_name = "<div class='venue-data'><strong>{$venue_row[0]['name']}</strong></div>";
		}
		echo $venue_name;
  }
}
add_action( 'manage_product_posts_custom_column', 'taste_venue_column', 10, 2);

// sorting code
function taste_venue_products_sortable_setup($sort_columns) {
	$sort_columns['venue'] = 'venue';
	return $sort_columns;
}
add_action( 'manage_edit-product_sortable_columns', 'taste_venue_products_sortable_setup');

// have to define behavior through 
function taste_venue_product_sortable_using_posts_clauses($clauses, $query) {
	if ( $query->is_main_query() && ( 'venue' === $query->get( 'orderby' )) ) {
		// Get the order query variable - ASC or DESC, make ASC default
		$order = strtoupper( $query->get( 'order' ) );
		$order = 'DESC' === $order ? 'DESC' : 'ASC';

		$clauses['join'] .= "LEFT JOIN wp_taste_venue_products vps ON wp_posts.id = vps.product_id
												 LEFT JOIN wp_taste_venue venue ON venue.venue_id = vps.venue_id";
		$clauses['orderby'] = "COALESCE( venue.name, '-----') $order";
	}
	return $clauses;
}
add_filter( 'posts_clauses', 'taste_venue_product_sortable_using_posts_clauses', 10, 2);