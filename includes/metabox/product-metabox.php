<?php 
/**
 *  product-metabox.php
 * 
 *  create metabox in the products entry screen
 *  for attaching a venue to that product
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

/**
 * Register meta box.
 */
function taste_register_product_meta_box() {

	// $post_types = array('product', 'post', 'page');
	$post_types = array('product');

	foreach($post_types as $p_type) {
		add_meta_box( 'taste-product-venue-box', __( 'Venue'), 'taste_display_product_venue_box', $p_type, 'normal', 'high' );
	}

}
add_action( 'add_meta_boxes', 'taste_register_product_meta_box' );

/**
 *  Callback to create the product venue metabox
 */
function taste_display_product_venue_box($post_info) {
	global $wpdb;

	echo '<h3>Attach a Venue</h3>';
	$venue_id = 0;
	if (property_exists($post_info, 'ID')) {
		// need to check for current venue assignment
		$venue_row = $wpdb->get_results($wpdb->prepare("
			SELECT venue_id FROM {$wpdb->prefix}taste_venue_products
			WHERE product_id = %d
			", $post_info->ID), ARRAY_A
		);
		if (count($venue_row)) {
			$venue_id = $venue_row[0]['venue_id'];
		}
	}

	// display form to select Venue
	display_venue_select(false, $venue_id, false);
}

/**
 *  Set up save of Venue meta box
 */
function taste_save_venue_metabox($post_id) {
	global $wpdb;
	if (wp_is_post_autosave($post_id) || wp_is_post_revision( $post_id )) {
		return;
	}
	// echo '<h1><pre>POST: ', var_dump($_POST), '</pre></h1>';
	// echo '<h1><pre>REQUEST: ', var_dump($_REQUEST), '</pre></h1>';
	// die(); 
	// might be here through quick/bulk edit.  if so, just return 
	if (!count($_POST)) {
		return;
	}
	if (! isset($_POST['venue_id']) || !$_POST['venue_id']) {
		// have to make sure that no entry exists
		$sql = "
		DELETE FROM {$wpdb->prefix}taste_venue_products
		WHERE product_id = %d
		";

		$rows_affected = $wpdb->query(
			$wpdb->prepare($sql, $post_id)
		);
		return;
	}
	$venue_id = $_POST['venue_id'];
	insert_venue_product_on_dup($venue_id, $post_id);

}
add_action('save_post_product', 'taste_save_venue_metabox', 10, 2);