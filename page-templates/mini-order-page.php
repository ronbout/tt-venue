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

$order_item_id = isset($_GET['order-item-id']) ? $_GET['order-item-id'] : null;

if (!$order_item_id) {
  die("Invalid URL");
}

if ( !is_user_logged_in()) {
	// if the user is not logged in, we will still give them a quick 
	// notice of Redeemable / Not Redeemable and the Venue Name so they
	// can quickly determine whether it is legitimate
	non_user_display($order_item_id);
	die();
} else {
	$user_info = get_user_venue_info();
	$user = $user_info['user'];
	$role = $user_info['role'];
	$admin = ('ADMINISTRATOR' === strtoupper($role));

	if ('VENUE' !== strtoupper($role) && !$admin) {
		echo "<h2>Role: $role </h2>";
		die('You must be logged in as a Venue to access this page.');
	}
	if (!$admin) {
		$venue_name = $user_info['venue_name'];
		$venue_type = $user_info['venue_type'];
		$use_new_campaign = $user_info['use_new_campaign'];
		$venue_voucher_page = $user_info['venue_voucher_page'];
		$type_desc = $venue_type;
	}

}

require_once TASTE_PLUGIN_PATH.'page-templates/partials/venue-head.php';
require_once TASTE_PLUGIN_PATH.'page-templates/partials/venue-navbar.php';
require_once TASTE_PLUGIN_PATH.'page-templates/partials/mini-order-item-display.php';

$venue_id = '';
if ($admin) {
	$nav_links = array(
		array(
			'title' => 'Log Out',
			'url' => wp_logout_url(get_site_url()),
			'active' => false
		),
	);
	// check if the Venue ID is in GET from form 
	if (isset($_GET['venue-id'])) {
		$venue_id = $_GET['venue-id'];
		// add the link to return to venue selection
		$venue_select_link = array(
			'title' => 'Venue Selection',
			'url' => get_page_link(),
			'active' => false
		);
		array_unshift($nav_links, $venue_select_link);
		// get venue name and other info
		$user_info = get_user_venue_info($venue_id);
		$venue_name = $user_info['venue_name'];
		$venue_type = $user_info['venue_type'];
		$use_new_campaign = $user_info['use_new_campaign'];
		$venue_voucher_page = $user_info['venue_voucher_page'];
		$type_desc = $venue_type;
	} 
} else {
	//$nav_links = venue_navbar_standard_links($user_info['use_new_campaign'], $user_info['venue_voucher_page']);
	$venue_id = $user->ID;
}

$venue_table = $wpdb->prefix."taste_venue";
$navbar_get = $admin ? "venue-id=$venue_id" : "";
$nav_links = venue_navbar_standard_links($user_info['use_new_campaign'], $user_info['venue_voucher_page'], $admin, $navbar_get);
?>

<body>
  <?php
if (!$venue_id) {
	// display form to select Venue as user if admin w/o a venue selected
	display_venue_select(true, 0, true, get_page_link());
	echo '<script type="text/javascript" src= "' . TASTE_PLUGIN_INCLUDES_URL . '/js/thetaste-venue-select.js"></script>';
	die();
}

?>

  <?php venue_navbar($nav_links); ?>
  <div class="container" id="main_wrapper">
    <div class="row pb-4">
      <div class="col-sm-12 col-xl-12 dashboard_grid_cols d-flex align-items-center flex-column">
        <h2 class="dashboard_heading mt-5 font-weight-bold text-left"><?php echo $venue_name; ?>
        </h2>
      </div>
    </div>
    <div class="order-item-display-container text-center">
      <?php echo get_mini_order_item_display($order_item_id, $venue_id, $venue_name) ?>
    </div>
    <br><br>
    <div class="text-center">
      <b>IRELAND’S AWARD WINNING FOOD, DRINK & TRAVEL DIGITAL MAGAZINE</b>
      <br><br>
      <span style="font-size:12px;">19.6M READERS WORLDWIDE <b>|</b> 10K ARTICLES <b>|</b> €10M GENERATED FOR THE IRISH
        HOSPITALITY INDUSTRY <b>|</b> 726K REGISTERED MEMBERS <b>|</b> 200K+ TASTE EXPERIENCES SOLD <b>|</b> 300K SOCIAL
        MEDIA FOLLOWERS <b>|</b> WINNER OF BEST DIGITAL FOOD MAGAZINE IN THE WORLD <b>|</b> WINNER OF OUTSTANDING SMALL
        BUSINESS IN IRELAND</span>
    </div>
  </div>
  <script type="text/javascript" src="<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/js/thetaste-mini-order.js"></script>
</body>

</html>


<?php 
function non_user_display($order_item_id) {
	$order_item_info = get_order_item_redeemable($order_item_id);

	if (!$order_item_info) {
		?>
<h1>
  <strong>NOT</strong> Redeemable
</h1>
<?php
	} else {
		?>
<h1>
  Redeemable by <?php echo $order_item_info['venue_name'] ?>
</h1>
<?php
	}
}

function get_order_item_redeemable($order_item_id) {
	global $wpdb;

	$sql = "
			SELECT plook.order_id, plook.product_id, plook.product_qty,
				plook.date_created AS order_date,	ord_p.post_status AS order_status, prod_p.post_title AS prod_desc,
				ven.venue_id, ven.name AS venue_name, wcoi.downloaded AS redeemed
			FROM {$wpdb->prefix}wc_order_product_lookup plook
				JOIN {$wpdb->prefix}posts prod_p ON prod_p.ID = plook.product_id
				JOIN {$wpdb->prefix}taste_venue_products vprods ON vprods.product_id = plook.product_id
				JOIN {$wpdb->prefix}taste_venue ven ON ven.venue_id = vprods.venue_id
				JOIN {$wpdb->prefix}posts ord_p ON ord_p.ID = plook.order_id
				LEFT JOIN {$wpdb->prefix}woocommerce_order_items wcoi ON wcoi.order_item_id = plook.order_item_id
			WHERE plook.order_item_id = 174493
	";
	
  $order_item_row = $wpdb->get_results($wpdb->prepare($sql, $order_item_id), ARRAY_A); 

	if (!$order_item_row || !count($order_item_row)) {
		return false;
	}

	/***
	 * 
	 * will have to have a more robust test of status to 
	 * deal with partial refunds, whether cash refund or 
	 * store credit.  Cash refunds should be easier IF(!)
	 * our people are attaching to refunds to an item.
	 * 
	 */
	if ('wc-completed' != $order_item_row[0]['order_status'] || '1' == $order_item_row[0]['redeemed']) {
		return false;
	}

	return $order_item_row[0];
}