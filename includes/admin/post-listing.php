<?php 
/**
 *  post-listing.php 
 *  add venue column to post listing 
 *  ***** add bulk assign capability
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

// set up the venue column in the pots listing
function taste_add_venues_column_posts( $columns ) {
  $columns['venues'] = __( 'Venues' );
  return $columns;
}
add_filter( 'manage_posts_columns', 'taste_add_venues_column_posts' );

// get the data for the rows
function taste_venues_column_posts( $column, $post_id ) {
	global $wpdb; 
  // Venue column
  if ( 'venues' === $column ) {
		$venue_names = 'Not assigned';
		// get the venue from the venue_products table
		$venue_rows = $wpdb->get_results($wpdb->prepare("
			SELECT v.name 
			FROM {$wpdb->prefix}taste_venues_posts vp
			JOIN {$wpdb->prefix}taste_venue v ON v.venue_id = vp.venue_id
			WHERE vp.post_id = %d
			", $post_id), ARRAY_A
		);
		if (count($venue_rows)) {
			$venue_list = implode(', ', array_column($venue_rows, 'name'));
			$venue_names = "<div class='venue-data'><strong>$venue_list</strong></div>";
		}
		echo $venue_names;
  }
}
add_action( 'manage_posts_custom_column', 'taste_venues_column_posts', 10, 2);