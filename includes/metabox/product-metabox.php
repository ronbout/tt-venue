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

function taste_register_order_meta_box() {

	$post_types = array('shop_order');

	foreach($post_types as $p_type) {
		add_meta_box( 'taste-order-transaction-box', __( 'Order Venue Transactions'), 'taste_display_order_trans_info', $p_type, 'normal', 'high' );
	}

}
add_action( 'add_meta_boxes', 'taste_register_order_meta_box' );

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
	
	$venue_id = isset($_POST['venue-id']) ? intval($_POST['venue-id']) : 0;
	if (! $venue_id) {
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
	insert_venue_product_on_dup($venue_id, $post_id);

}
add_action('save_post_product', 'taste_save_venue_metabox', 10, 2);

function taste_display_order_trans_info($post_info) {
	global $wpdb;

	if (property_exists($post_info, 'ID')) {
		// need to check for redemptions and payments
		$order_item_rows = $wpdb->get_results($wpdb->prepare("
		SELECT otrans.* 
			FROM {$wpdb->prefix}taste_order_transactions otrans
			WHERE otrans.order_id = %d 
			AND otrans.trans_type IN ('Redemption', 'Creditor Payment')
			", $post_info->ID), ARRAY_A
		);
		if (count($order_item_rows)) {
			// display redemptions and payments

			foreach ($order_item_rows as $order_item_row) {
				?>
					<p>Order Item Id: <?php echo $order_item_row['order_item_id'] . ' - ' . 
						$order_item_row['trans_type'] . ' - ' . $order_item_row['trans_entry_timestamp']?></p>
				<?php
			}
		}
	}

}