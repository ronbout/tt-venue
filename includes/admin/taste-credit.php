<?php
/**
 *  Testing out adding a Taste Credit order status
 *  with ability to auto-generate a coupon
 *  
 *  6/25/2022
 *  Ron Boutilier
 * 
 */

function register_taste_credit_order_status() {
	register_post_status( 'wc-taste-credit', array(
			'label'                     => 'Store Credit',
			'public'                    => true,
			'show_in_admin_status_list' => true,
			'show_in_admin_all_list'    => true,
			'exclude_from_search'       => false,
	) );
}
add_action( 'init', 'register_taste_credit_order_status' );

function add_taste_credit_to_order_statuses( $order_statuses ) {

	$new_order_statuses = array();

	foreach ( $order_statuses as $key => $status ) {

			$new_order_statuses[ $key ] = $status;

			if ( 'wc-processing' === $key ) {
					$new_order_statuses['wc-taste-credit'] = 'Taste Credit';
			}
	}

	return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'add_taste_credit_to_order_statuses' );



function taste_order_credited($order_id, $order) {
  $file1 = "C:/Users/ronbo/Documents/jim-stuff/tmp/write_test_order_status_" . time() . ".txt";

//   $msg1 = serialize(print_r($order, true));

  $new_credit_coupon_id = generate_coupon($order_id, $order->data['total'], $order->data['billing']);
  
  $msg2 = "Taste Credit Coupon #$new_credit_coupon_id has been created for Order #$order_id";
  file_put_contents($file1, $msg2);

}

add_action( "woocommerce_order_status_taste-credit", "taste_order_credited", 10, 2);

function generate_coupon($order_id, $amount, $billing_info) {
  /**
  * Create a coupon programatically
  */
  $coupon_code = $order_id; 
  $amount = $amount;
  $discount_type = 'fixed_cart'; 
  $billing_name = $billing_info['first_name'] . " " . $billing_info['last_name'];
  $coup_desc = "Taste Credit Coupon for Order $order_id, given to $billing_name";
  /* get one year from today in UnixTime*/
  $dt = new DateTime();
  $one_year = DateInterval::createFromDateString('1 year');
  $dt->add($one_year);

  $coupon = array(
  'post_title' => $coupon_code,
  'post_content' => '',
  'post_status' => 'publish',
  'post_excerpt' => $coup_desc,
  'post_author' => 1,
  'post_type' => 'shop_coupon');

  $new_coupon_id = wp_insert_post( $coupon );

  // Add meta
  update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
  update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
  update_post_meta( $new_coupon_id, 'individual_use', 'yes' );
  update_post_meta( $new_coupon_id, 'usage_limit', '1' );
  update_post_meta( $new_coupon_id, 'usage_limit_per_user', '1' );
  update_post_meta( $new_coupon_id, 'limit_usage_to_x_items', '0' );
  update_post_meta( $new_coupon_id, 'usage_count', '0' );
  update_post_meta( $new_coupon_id, 'date_expires', strtotime($dt->format("Y-m-d")));
  update_post_meta( $new_coupon_id, 'free_shipping', 'no' );
  update_post_meta( $new_coupon_id, 'exclude_sale_items', 'no' );

  return $new_coupon_id;
}