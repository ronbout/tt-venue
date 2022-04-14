<?php
/**
 * 	mini-order-item-display.php
 * 
 *  The mini order display template is a partial template that will give
 *  a summary of information for an order item and allow
 *  the venue to quickly Redeem the order item.  It is currently used
 *  by the mini-order-page.php, but was written so that it could be 
 *  called from anywhere.
 * 
 *  called by:
 *     mini-order-item-page.php
 * 
 * 	4/14/2022  Ron Boutilier
 * 
 */
defined('ABSPATH') or die('Direct script access disallowed.');


function mini_order_item_display($order_item_id, $venue_id, $venue_name) {


  ob_start();
  ?>

  <h2>Order Item Id <?php echo $order_item_id ?></h2>

  <?php
    $order_item_rows = retrieve_order($order_item_id)['rows_by_item'];
    $venue_item_rows = retrieve_order($order_item_id)['rows_by_venue'];

    if (!isset($venue_item_rows[$venue_id])) {
      ?>
        <h2 class="text-danger font-weight-bold">
          This is not a valid Order for <?php echo $venue_name ?>
        </h2>
      <?php
    }

  $ret_disp = ob_get_clean();
  return $ret_disp;
 }

 function retrieve_order($order_item_id) {
    global $wpdb;

    $sql = "
      SELECT plook_o.order_id, plook_o.order_item_id, plook_o.product_id, plook_o.product_qty,
        plook_o.date_created AS order_date,	ord_p.post_status AS order_status, prod_p.post_title,
        ven.venue_id, ven.name AS venue_name
      FROM {$wpdb->prefix}wc_order_product_lookup plook_oi
      JOIN {$wpdb->prefix}wc_order_product_lookup plook_o ON plook_o.order_id = plook_oi.order_id
      JOIN {$wpdb->prefix}posts prod_p ON prod_p.ID = plook_o.product_id
      JOIN {$wpdb->prefix}taste_venue_products vprods ON vprods.product_id = plook_o.product_id
      JOIN {$wpdb->prefix}taste_venue ven ON ven.venue_id = vprods.venue_id
      JOIN {$wpdb->prefix}posts ord_p ON ord_p.ID = plook_oi.order_id
      WHERE plook_oi.order_item_id = %d
    ";

    $orig_item_rows = $wpdb->get_results($wpdb->prepare($sql, $order_item_id), ARRAY_A); 
	  $order_item_rows = array_column($orig_item_rows, null, 'order_item_id');
	  $venue_item_rows = array_column($orig_item_rows, null, 'venue_id');

    return array(
      'rows_by_item' => $order_item_rows,
      'rows_by_venue' => $venue_item_rows,
    );
 }

 