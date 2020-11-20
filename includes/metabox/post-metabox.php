<?php 
/**
 *  post-metabox.php
 * 
 *  create metabox in the Posts entry screen
 *  for attaching a venue(s) 
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

/**
 * Register meta box.
 */
function taste_register_post_meta_box() {

	$post_types = array('post');

	foreach($post_types as $p_type) {
		add_meta_box( 'taste-post-venues-box', __( 'Venue'), 'taste_display_post_venues_box', $p_type, 'normal', 'high' );
	}

}
add_action( 'add_meta_boxes', 'taste_register_post_meta_box' );

/**
 *  Callback to create the post venues metabox
 */
function taste_display_post_venues_box($post_info) {
	global $wpdb;

	$venue_id = 0;
	$venue_rows = array();
	if (property_exists($post_info, 'ID')) {
		// need to check for current venue assignment
		
		$venue_rows = $wpdb->get_results($wpdb->prepare("
			SELECT vp.venue_id AS venueId, v.name 
			FROM {$wpdb->prefix}taste_venues_posts vp
			JOIN {$wpdb->prefix}taste_venue v ON v.venue_id = vp.venue_id
			WHERE vp.post_id = %d
			", $post_info->ID), ARRAY_A
		);
	}
	
	?>	
		<script>
			let postVenuesList = <?php echo json_encode($venue_rows) ?>;
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
	<?php
}

/**
 *  Set up save of Venues meta box
 */
function taste_save_post_venues_metabox($post_id) {
	global $wpdb;
	if (wp_is_post_autosave($post_id) || wp_is_post_revision( $post_id )) {
		return;
	}
	// echo '<h1><pre>xxPOST: ', var_dump($_POST), '</pre></h1>';
	// echo '<h1><pre>xxREQUEST: ', var_dump($_REQUEST), '</pre></h1>';
	// die(); 
	// might be here through quick/bulk edit.  if so, just return 
	if (!count($_POST)) {
		return;
	}
		
	$venue_ids = isset($_POST['post-venue-id-list']) ? explode(',', $_POST['post-venue-id-list']) : [];
	if (count($venue_ids)) {
		insert_post_venues($post_id, $venue_ids);
	}
	
}
add_action('save_post', 'taste_save_post_venues_metabox', 10, 2);