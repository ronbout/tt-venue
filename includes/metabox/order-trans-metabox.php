<?php 
/**
 *  order-trans-metabox.php
 * 
 *  create metabox in the Order Admin screen
 *  for listing the transactions related to the order
 * 
 * 	6/25/2022
 *  Ron Boutilier
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

function taste_register_order_meta_box() {
	$post_types = array('shop_order');

	foreach($post_types as $p_type) {
		add_meta_box( 'taste-order-transaction-box', __( 'Order Venue Transactions'), 'taste_display_order_trans_info', $p_type, 'normal', 'high' );
	}

}
add_action( 'add_meta_boxes', 'taste_register_order_meta_box' );



function taste_display_order_trans_info($post_info) {
	global $wpdb;

	if (property_exists($post_info, 'ID')) {
		// need to check for redemptions and payments
		$order_item_rows = $wpdb->get_results($wpdb->prepare("
		SELECT otrans.*, posts.post_title as product_title
			FROM {$wpdb->prefix}taste_order_transactions otrans
				JOIN {$wpdb->prefix}posts posts ON posts.ID = otrans.product_id
			WHERE otrans.order_id = %d 
			AND otrans.trans_type IN ('Redemption', 'Creditor Payment', 'Redemption - From Credit')
			", $post_info->ID), ARRAY_A
		);
		if (count($order_item_rows)) {
			// display redemptions and payments
			?>
				<table class="order-metabox-venue-transactions-table">
          <thead>
            <tr>
              <th scope="col">Item</th>
              <th scope="col">Transaction Type</th>
              <th scope="col">Date</th>
            </tr>
          </thead>
		 		 <tbody>
			<?php
			foreach ($order_item_rows as $order_item_row) {
				if ("Creditor Payment" == $order_item_row['trans_type']) {
					$trans_date = $order_item_row['payment_date'];
				} else {
					$trans_date = $order_item_row['redemption_date'] ? $order_item_row['redemption_date'] : "N/A";
				}
				?>
          <tr title="Order Item Id: <?php echo $order_item_row['order_item_id']?>">
            <td>
              <?php echo substr($order_item_row['product_title'], 0, 80) ?>...
            </td>
            <td>
              <?php echo $order_item_row['trans_type'] ?>
            </td>
            <td>
              <?php echo $trans_date ?>
            </td>
          </tr>
				<?php
			}
			?>
					</tbody>
				</table>
			<?php
		} else {
			?>
				<p>No Venue Transactions Found</p>
			<?php
		}
	}

}
