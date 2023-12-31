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

function get_mini_order_item_display($order_item_id, $venue_id, $venue_name) {
  ob_start();
  ?>

<div class="container">
  <?php
    $order_db_info = retrieve_order($order_item_id);
    $order_item_rows = $order_db_info['rows_by_item'];
    $venue_item_rows = $order_db_info['rows_by_venue'];

    //  print_r($venue_item_rows); 
    //  print_r($order_item_rows); 
    //  die();

    if (!isset($venue_item_rows[$venue_id])) {
      ?>
  <h2 class="text-danger font-weight-bold">
    This is not a valid Order for <?php echo $venue_name ?>
  </h2>
  <?php
    } elseif ($order_item_rows[$order_item_id]['venue_id'] == $venue_id) {
      $product_id = $order_item_rows[$order_item_id]['product_id'];
      ?>
  <div class="row">
    <div class="col-md-4">
    </div>
    <div class="col-md-4">
      <?php echo get_order_item_card($order_item_rows[$order_item_id]) ?>
    </div>
    <div class="col-md-4">
    </div>
  </div>

  <p> </p>

  <?php
    } else {
      echo "<h4>The Order Item you scanned is not a ", $venue_name, " Campaign</h4>";
    }
    $other_redeemable_items = array();
    foreach($venue_item_rows[$venue_id] as $order_item_info) {
      if ($order_item_info['redeemed'] == 0 && $order_item_info['order_item_id'] !== $order_item_id)  {
        $other_redeemable_items[] = $order_item_info;
      }
    }
      if (count($other_redeemable_items) ) {
        echo "<h3>Other redeemable item(s) on this order:</h3>";
        foreach($other_redeemable_items as $redeemable_item) {
          ?>
        <div class="row">
          <div class="col-md-4">
          </div>
          <div class="col-md-4">
            <?php echo get_order_item_card($redeemable_item) ?>
          </div>
          <div class="col-md-4">
          </div>
        </div>
        <?php
        }
      }
    ?>
</div>
<?php
  $ret_disp = ob_get_clean();
  return $ret_disp;
 }



function get_order_item_card($order_item_info, $disp_btn=true) {
  $product_id = $order_item_info['product_id'];
  $prod_desc = $order_item_info['prod_desc'];
  $order_id = $order_item_info['order_id'];
  $order_item_id = $order_item_info['order_item_id'];
  $redeemed = $order_item_info['redeemed'];
  $status = $order_item_info['order_status'];
  //$payment_id = $order_item_info['payment_id'];

  $redeemable = test_redeemable($order_item_info);
  if ($redeemable) {
    $redeem_status = "Redeemable";
    $status_color = "text-primary";
  } else {
    if ($redeemed) {
      $redeem_status = "Previously Redeemed";
    } else {
      $redeem_status = "Refunded<br><strong>NOT</strong> Redeemable";
    }

    $status_color = "text-danger";
  }
  ob_start();
  ?>
<div class="order-item-card card">
  <div class="card-body">
    <?php echo get_the_post_thumbnail($product_id, array( 200, 200), array('class'=>"card-img-top")) ?>
    <h5 class="card-title">Order ID: <?php echo $order_id ?></h5>
    <h6 class="card-subtitle mb-2 text-muted"><?php echo $prod_desc ?></h6>
    <p class="card-text">
      <h4 id="redeem-status-<?php echo $order_item_id ?>" class="<?php echo $status_color ?>"><?php echo $redeem_status ?></h4>
    </p>
    <?php if ($redeemable && $disp_btn) {
      ?>
      <button type="button" id="redeem-btn-<?php echo $order_item_id ?>" data-order-item-id="<?php echo $order_item_id ?>" class="btn btn-success order-redeem-btn or-display or-status-display-unredeemed">Redeem</button>
      <?php
    }
    ?>
  </div>
</div>
<?php
  return ob_get_clean(); 
}

 