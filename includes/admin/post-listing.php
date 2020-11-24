<?php 
/**
 *  post-listing.php 
 *  add venue column to post listing 
 *  as well as bulk edit capability
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
		$venue_names = 'Not assigned';
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