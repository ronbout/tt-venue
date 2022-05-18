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

require_once TASTE_PLUGIN_PATH.'page-templates/partials/mini-order-item-display.php';

if ( !is_user_logged_in()) {
	// if the user is not logged in, we will still give them a quick 
	// notice of Redeemable / Not Redeemable and the Venue Name so they
	// can quickly determine whether it is legitimate
	if (isset($_GET['login_venue']) && $_GET['login_venue']) {
		require_once TASTE_PLUGIN_PATH.'page-templates/thetaste-venue-login.php';
		die();
	}
	ob_start();
	non_user_display($order_item_id);
  $non_user_disp = ob_get_clean();
	$user_info = null;
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

$venue_id = '';
if ($admin) {
	// check if the Venue ID is in GET from form 
	if (isset($_GET['venue-id'])) {
		$venue_id = $_GET['venue-id'];
		// get venue name and other info
		$user_info = get_user_venue_info($venue_id);
		$venue_name = $user_info['venue_name'];
		$venue_type = $user_info['venue_type'];
		$use_new_campaign = $user_info['use_new_campaign'];
		$venue_voucher_page = $user_info['venue_voucher_page'];
		$type_desc = $venue_type;
	} 
	
	$navbar_get = "venue-id=$venue_id";
	$admin_select_get = "order-item-id=$order_item_id";
	$nav_links = venue_navbar_standard_links($user_info['use_new_campaign'], $user_info['venue_voucher_page'], 
																						$admin, $navbar_get, $admin_select_get);
} elseif ($user_info) {
	$nav_links = venue_navbar_standard_links($user_info['use_new_campaign'], $user_info['venue_voucher_page']);
	$venue_id = $user->ID;
} else {
	global $wp;
	$query_vars = array(
		'login_venue' => 1,
		'order-item-id' => $order_item_id
	);
	$this_page_url = add_query_arg( $query_vars, home_url( $wp->request ) );
	$nav_links = array(
		array(
			'title' => 'Log In',
			'url' => $this_page_url,
			'active' => false
		),
	);
}

$venue_table = $wpdb->prefix."taste_venue";

echo "
<script>
	let tasteVenue = {}
	tasteVenue.ajaxurl = '". admin_url( 'admin-ajax.php' ) . "'
	tasteVenue.security = '" . wp_create_nonce('taste-venue-nonce') . "'
</script>
";
?>

<body>
  <?php
if (!$venue_id && $user_info) {
	// display form to select Venue as user if admin w/o a venue selected
	display_venue_select(true, 0, true, get_page_link());
	echo '<script type="text/javascript" src= "' . TASTE_PLUGIN_INCLUDES_URL . '/js/thetaste-venue-select.js"></script>';
	die();
}

?>

  <?php venue_navbar($nav_links); ?>
  <div class="container" id="main_wrapper">
    <div class="row">
      <div class="col-sm-12 col-xl-12 dashboard_grid_cols d-flex align-items-center flex-column">
        <h2 class="dashboard_heading mt-5 font-weight-bold text-left"><?php echo $venue_name; ?>
        </h2>
      </div>
    </div>
    <div class="order-item-display-container text-center">
      <?php 
				if ($user_info) {
					echo get_mini_order_item_display($order_item_id, $venue_id, $venue_name); 
				} else {
					echo $non_user_disp;
				}
			?>
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

	<div id="spinner-modal" class="modal" data-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body d-flex flex-column justify-content-center align-content-center">
          <p class="py-5 px-3 text-center" id="taste-msg-text">Some sample text</p>
        </div>
      </div>
    </div>
  </div>
	
  <script type="text/javascript" src="<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/js/thetaste-mini-order.js"></script>
</body>

</html>


<?php 
function non_user_display($order_item_id) {
	$order_item_redeemable_info = get_order_item_redeemable($order_item_id);
	$redeemable = $order_item_redeemable_info['redeemable'];
	$order_item_info = $order_item_redeemable_info['order_item_info'];
?>
<?php
	if (!$order_item_redeemable_info) {
		?>
<h1>
  <strong>Invalid Item</strong>
</h1>
<?php
	} else {
		?>
        <div class="row">
          <div class="col-md-4">
          </div>
          <div class="col-md-4">
            <?php echo get_order_item_card($order_item_info, $order_item_info['venue_name']) ?>
          </div>
          <div class="col-md-4">
          </div>
        </div>
<?php
	}
}

function get_order_item_redeemable($order_item_id) {
	global $wpdb;

	$sql = "
			SELECT plook.order_id, plook.product_id, plook.product_qty,
				plook.date_created AS order_date,	ord_p.post_status AS order_status, prod_p.post_title AS prod_desc, plook.order_item_id,
				ven.venue_id, ven.name AS venue_name, wcoi.downloaded AS redeemed
			FROM {$wpdb->prefix}wc_order_product_lookup plook
				JOIN {$wpdb->prefix}posts prod_p ON prod_p.ID = plook.product_id
				JOIN {$wpdb->prefix}taste_venue_products vprods ON vprods.product_id = plook.product_id
				JOIN {$wpdb->prefix}taste_venue ven ON ven.venue_id = vprods.venue_id
				JOIN {$wpdb->prefix}posts ord_p ON ord_p.ID = plook.order_id
				LEFT JOIN {$wpdb->prefix}woocommerce_order_items wcoi ON wcoi.order_item_id = plook.order_item_id
			WHERE plook.order_item_id = %d
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
	$redeemable = true;
	if ('wc-completed' != $order_item_row[0]['order_status'] || '1' == $order_item_row[0]['redeemed']) {
		$redeemable = false;
	}

	return array(
		'redeemable' => $redeemable,
		'order_item_info' => $order_item_row[0],
	);
}