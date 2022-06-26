<?php 
/**
 *  order-trans-box.php
 * 
 *  a general purpose box for for listing the 
 *  transactions related to a given order.  
 * 
 *  It can be used in a number of places, such as the 
 *  Order Trans metabox and as an info box that appears in CM.
 * 
 *  It should have an Admin and Venue version
 * 
 * 	6/25/2022
 *  Ron Boutilier
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

  // need to check for all transactions for this order first
  // then track down the previous order if it came from credit
  // if this order was credited, the new order will need to be pulled.
  // This will probably need to be done recursively as it could include 
  // multiple levels of orders


function disp_order_trans_box($order_id) {
	global $wpdb;

  ob_start();
  $order_item_rows = $wpdb->get_results($wpdb->prepare("
  SELECT otrans.*, posts.post_title as product_title
    FROM {$wpdb->prefix}taste_order_transactions otrans
      JOIN {$wpdb->prefix}posts posts ON posts.ID = otrans.product_id
    WHERE otrans.order_id = %d 
    ORDER BY otrans.order_item_id, otrans.transaction_date
    ", $order_id), ARRAY_A
  );
  if (count($order_item_rows)) {
    // display redemptions and payments
    ?>
      <table class="order-metabox-venue-transactions-table">
        <thead>
          <tr>
            <th scope="col">Item ID</th>
            <th scope="col">Item Desc</th>
            <th scope="col">Transaction Type</th>
            <th scope="col">Date</th>
          </tr>
        </thead>
        <tbody>
    <?php
    foreach ($order_item_rows as $order_item_row) {
      $tooltip_title = "Product: ${order_item_row['product_id']} &#10;";
      $tooltip_title .= $order_item_row['product_title'];
      ?>
        <tr title="<?php echo $tooltip_title ?>">
          <th scope="row">
            <?php echo $order_item_row['order_item_id'] ?>
          </td>
          <td>
            <?php echo substr($order_item_row['product_title'], 0, 80) ?>...
          </td>
          <td>
            <?php echo $order_item_row['trans_type'] ?>
          </td>
          <td>
            <?php echo $order_item_row['transaction_date']  ?>
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
  return ob_get_clean();
}