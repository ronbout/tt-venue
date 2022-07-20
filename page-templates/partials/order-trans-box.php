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


function disp_order_trans_box($order_id, $link_orders=false, $link_prods=true) {

  $order_item_rows = get_order_trans_rows($order_id);
  $prev_order_id = null;
  $next_order_id = null;
  ob_start();
  if ($order_item_rows && count($order_item_rows)) {
    // display redemptions and payments
    ?>
      <table class="order-metabox-venue-transactions-table">
        <thead>
          <tr>
            <th scope="col">Order ID</th>
            <th scope="col">Item ID</th>
            <th scope="col">Item Desc</th>
            <th scope="col">Transaction Type</th>
            <th scope="col">Date</th>
          </tr>
        </thead>
        <tbody>
    <?php
    foreach ($order_item_rows as $order_item_row) {
      $trans_type = $order_item_row['trans_type'];
      $trans_type_display = $trans_type;
      // check for from credit trans, which will be previous order
      if ("Order - From Credit" == $trans_type && !$prev_order_id) {
        $prev_order_id = $order_item_row['coupon_code'];
      }
      $credit_tooltip = "";
      // build order item trans page link
      $order_item_id = $order_item_row['order_item_id'];
      $oi_trans_link = get_admin_url( null, "admin.php?page=view-order-transactions&order-item-id=$order_item_id");
      $order_item_id_disp = "<a href='$oi_trans_link'>$order_item_id</a>";
      $order_item_tooltip = "Transactions List Page for Order Item: $order_item_id";
      // if Taste Credit, get the next orders, if available
      if ("Taste Credit" == $trans_type && !$next_order_id) {
        $credit_coupon_id = $order_item_row['taste_credit_coupon_id'];
        $next_order_id = get_orders_by_coupon($credit_coupon_id);
        $credit_coupon_link = get_edit_post_link($credit_coupon_id );
        $trans_type_display = "<a href='$credit_coupon_link'>Taste Credit</a>";
        $credit_tooltip = "Admin Edit Page for Coupon: $credit_coupon_id" ;
      }
      $product_tooltip = "Admin Edit Page for Product: ${order_item_row['product_id']}";
      $order_tooltip = $link_orders ? "Admin Edit Page for Order: ${order_item_row['order_id']}" : "";
      ?>
        <tr>
          <th scope="row" title="<?php echo $order_tooltip ?>">
          <?php
            if ($link_orders) {
              ?>
                <a href="<?php echo get_edit_post_link($order_item_row['order_id']) ?>" target="_blank" ">
                  <?php echo $order_item_row['order_id'] ?>
                </a>
              <?php
            } else {
              echo $order_item_row['order_id'];
            }
            ?>
          </th>
          <td title="<?php echo $order_item_tooltip ?>">
            <?php echo $order_item_id_disp ?>
          </td>
          <td title="<?php echo $product_tooltip ?>">
          <?php
            if ($link_prods) {
              ?>
                <a href="<?php echo get_edit_post_link($order_item_row['product_id']) ?>" target="_blank" ">
                  <?php echo substr($order_item_row['product_title'], 0, 80) ?>...
                </a>
              <?php
            } else {
              echo substr($order_item_row['product_title'], 0, 80), "...";
            }
            ?>
          </td>
          <td title="<?php echo $credit_tooltip ?>">
            <?php echo $trans_type_display ?>
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

  return array(
    'display' => ob_get_clean(),
    'prev_order_id' => $prev_order_id,
    'next_order_id' => $next_order_id,
  );
}

function get_order_trans_rows($order_id) {
	global $wpdb;

  $order_item_rows = $wpdb->get_results($wpdb->prepare("
    SELECT otrans.*, posts.post_title as product_title
    FROM {$wpdb->prefix}taste_order_transactions otrans
      JOIN {$wpdb->prefix}posts posts ON posts.ID = otrans.product_id
    WHERE otrans.order_id = %d 
    ORDER BY otrans.order_item_id, otrans.transaction_date
    ", $order_id), ARRAY_A
  );
  return $order_item_rows;
}

function get_orders_by_coupon($coupon_id) {
  global $wpdb;

  $order_ids = $wpdb->get_results($wpdb->prepare("
    SELECT GROUP_CONCAT(oc_look.order_id) AS order_ids
    FROM {$wpdb->prefix}wc_order_coupon_lookup oc_look
    WHERE oc_look.coupon_id = %d
    GROUP BY oc_look.coupon_id
    ORDER BY oc_look.date_created
    ", $coupon_id), ARRAY_A
  );
  if (!$order_ids) {
    return '';
  }
  return $order_ids[0]['order_ids'];
}