<?php
/*
Template Name: Mini Order Info Page
*/

/**
 * 	Page to view info about an Order Item / Order
 *  Date:  4/13/2022
 * 	Author: Ron Boutilier
 */
defined('ABSPATH') or die('Direct script access disallowed.');



require_once TASTE_PLUGIN_PATH.'page-templates/partials/venue-head.php';
// require_once  TASTE_PLUGIN_PATH.'page-templates/partials/venue-navbar.php';

?>

<h1>Order Item Info Page</h1>

<?php

$order_item_id = isset($_GET['order-item-id']) ? $_GET['order-item-id'] : null;

if (!$order_item_id) {
  die("Invalid Access");
}

?>

<h2>Order Item ID: <?php echo $order_item_id ?></h2>
