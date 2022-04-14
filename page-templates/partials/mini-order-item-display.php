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
    
    $order_db_info = retrieve_order($order_item_id);
    $order_item_rows = $order_db_info['rows_by_item'];
    $venue_item_rows = $order_db_info['rows_by_venue'];

    if (!isset($venue_item_rows[$venue_id])) {
      ?>
        <h2 class="text-danger font-weight-bold">
          This is not a valid Order for <?php echo $venue_name ?>
        </h2>
      <?php
    } elseif ($order_item_rows[$order_item_id]['venue_id'] == $venue_id) {
      echo "Here is your Order Item Info: <br>";
      var_dump($order_item_rows[$order_item_id]);
    } else {
      echo "The Order Item you scanned is not a ", $venue_name, "Campaign<br>";
      $item_count = count($venue_item_rows[$venue_id]);
      $msg_item = $item_count > 1 ? 'But these Items on that Order are' 
            : 'But this Item on that Order is';
      echo $msg_item ."  <br>";
      var_dump($venue_item_rows[$venue_id]);
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
  $venue_item_rows = build_venue_rows($orig_item_rows);

  return array(
    'rows_by_item' => $order_item_rows,
    'rows_by_venue' => $venue_item_rows,
  );
}

function build_venue_rows($order_items) {
  $venue_item_rows = [];
  foreach ($order_items as $order_item) {
    $venue_id = $order_item['venue_id'];
    if (!isset($venue_item_rows[$venue_id])) {
      $venue_item_rows[$venue_id] = [];
    }
    $venue_item_rows[$venue_id][] = $order_item;
  }
  return $venue_item_rows;
}

 