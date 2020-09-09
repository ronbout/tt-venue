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

	$post_types = array('product', 'post', 'page');

	foreach($post_types as $p_type) {
		add_meta_box( 'taste-product-venue-box', __( 'Venue'), 'taste_display_product_venue_box', $p_type );
	}

}

add_action( 'add_meta_boxes', 'taste_register_product_meta_box' );

/**
 *  Callback to create the product venue metabox
 */
function taste_display_product_venue_box() {
	echo '<h3>Attach a Venue</h3>';
	// display form to select Venue
	display_venue_select(false);
}
