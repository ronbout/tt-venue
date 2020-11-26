<?php 
/**
 *  post-listing.php 
 *  add venues column to post listing 
 *  as well as bulk edit capability
 * 	and sort by venues
 * 
 *  Author: Ron Boutilier
 *  Date: 11/23/2020
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

// set up the venue column in the pots listing
function taste_add_venues_column_posts( $columns, $post_type ) {
	if ('post' !== $post_type) return $columns;
  $columns['venues'] = __( 'Venues' );
  return $columns;
}
add_filter( 'manage_posts_columns', 'taste_add_venues_column_posts', 10, 2 );

// get the data for the rows
function taste_venues_column_posts( $column, $post_id ) {
	global $wpdb; 
  // Venue column
  if ( 'venues' === $column ) {
		$venue_names = '-- Not assigned --';
		// get the venue from the venue_products table
		$venue_rows = $wpdb->get_results($wpdb->prepare("
			SELECT v.name 
			FROM {$wpdb->prefix}taste_venues_posts vp
			JOIN {$wpdb->prefix}taste_venue v ON v.venue_id = vp.venue_id
			WHERE vp.post_id = %d
			ORDER BY v.name ASC
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

// the bulk edit setup
function taste_venues_posts_bulk_edit_input( $column_name, $post_type ) {
	if ('post' !== $post_type || 'venues' !== $column_name) return;
	?>
	<div class="post-venues-bulk-edit-container">
		<script>
			let postVenuesList = <?php echo json_encode(array()) ?>;
		</script>
		<div class="post-venues-metabox-container">
			<div class="select-post-venues">
				<h3>Attach Venue(s)</h3>
				<?php display_venue_select(false, $venue_id, false); ?>
			</div>
			<div class="display-post-venues">
				<h3>Selected Venues</h3>
				<div id="selected-venues-chips"></div>
				
			</div>
		</div>
		<input type="hidden" id="post-venue-id-list" name="post-venue-id-list" value="">
	</div>
	<?php
}
add_action( 'bulk_edit_custom_box', 'taste_venues_posts_bulk_edit_input', 10, 2 );

// sorting code
function taste_venues_posts_sortable_setup($sort_columns) {
	$sort_columns['venues'] = 'venues';
	return $sort_columns;
}
add_action( 'manage_edit-post_sortable_columns', 'taste_venues_posts_sortable_setup');

// have to define behavior through 
function taste_venues_posts_sortable_using_posts_clauses($clauses, $query) {
	if ( $query->is_main_query() && ( 'venues' === $query->get( 'orderby' )) ) {
		// Get the order query variable - ASC or DESC, make ASC default
		$order = strtoupper( $query->get( 'order' ) );
		$order = 'DESC' === $order ? 'DESC' : 'ASC';

		$clauses['join'] .= "LEFT JOIN wp_taste_venues_posts vps ON wp_posts.id = vps.post_id
												 LEFT JOIN wp_taste_venue venue ON venue.venue_id = vps.venue_id";
		$clauses['groupby'] = "wp_posts.id";
		$clauses['orderby'] = "COALESCE( GROUP_CONCAT( venue.name SEPARATOR ', ' ), '-----') $order";
	}
	return $clauses;
}
add_filter( 'posts_clauses', 'taste_venues_posts_sortable_using_posts_clauses', 10, 2);