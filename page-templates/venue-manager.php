<?php
/*
Template Name: Venue Manager
*/

global $wpdb;

	if ( !is_user_logged_in()) {
		wp_login_form();
		die();
	} else {
		$user = wp_get_current_user();
		$role = $user->roles[0];
		$admin = ('ADMINISTRATOR' === strtoupper($role));

		if ('VENUE' !== strtoupper($role) && !$admin) {
				echo "<h2>Role: $role </h2>";
				die('You must be logged in as a Venue to access this page.');
		}
	}
?>

<!DOCTYPE HTML>

<html>

<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<?php
	/**
	 * because this is not using the theme styling, I cannot (currently) run wp_head
	 * as a result, this is my wp_localize_script replacement
	**/
	echo "
			<script>
				let tasteVenue = {}
				tasteVenue.ajaxurl = '". admin_url( 'admin-ajax.php' ) . "'
				tasteVenue.security = '" . wp_create_nonce('taste-venue-nonce') . "'
			</script>
		";
	?>

			
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
	<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>

	<script type="text/javascript" src= "<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/js/thetaste-venue.js"></script>
	<link rel="stylesheet" href="<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/css/thetaste-venue.css">
	<title><?php _e('Venue Portal'); ?></title>
</head>

<body>
<?php 
	if ($admin) {
		// check if the Venue ID is in POST from form 
		if (isset($_POST['venue-id'])) {
			$venue_id = $_POST['venue-id'];
		} else {
			// display form to select Venue
			display_venue_select();
			die();
		}
	} else {
		$venue_id = $user->ID;
	}
?>
	<section>
		</br>
		</br>
		<div class="container">

		<center>
			<a href="<?php echo get_site_url() ?>">
				<img src="http://thetaste.ie/wp/wp-content/uploads/2017/12/thetaste-site-homepage-logo5.png">
			</a>
		</center>
		<br><br>
		<center>
		<b>WELCOME TO IRELAND’S AWARD WINNING FOOD, DRINK & TRAVEL DIGITAL MAGAZINE</b>
		<br><br>
		<span style="font-size:12px;">19.6M READERS WORLDWIDE <b>|</b> 10K ARTICLES <b>|</b> €10M GENERATED FOR THE IRISH HOSPITALITY INDUSTRY <b>|</b> 726K REGISTERED MEMBERS <b>|</b> 200K+ TASTE EXPERIENCES SOLD <b>|</b> 300K SOCIAL MEDIA FOLLOWERS <b>|</b> WINNER OF BEST DIGITAL FOOD MAGAZINE IN THE WORLD <b>|</b> WINNER OF OUTSTANDING SMALL BUSINESS IN IRELAND</span></center>
		<br><br>

		<?php // get the product listing from db 


			$product_table = $wpdb->prefix."wc_product_meta_lookup";
			$product_order_table = $wpdb->prefix."wc_order_product_lookup";
			$post_meta_table = $wpdb->prefix."postmeta";
			$posts_table = $wpdb->prefix."posts";
			$order_items_table = $wpdb->prefix."woocommerce_order_items";
			$venue_table = $wpdb->prefix."taste_venue";
			$v_p_join_table = $wpdb->prefix."taste_venue_products";

			// get venue name 
			$venue_row = $wpdb->get_results($wpdb->prepare("
			SELECT v.name
			FROM $venue_table v
			WHERE	v.venue_id = %d", 
			$venue_id));

			$venue_name = $venue_row[0]->name;

			$product_rows = $wpdb->get_results($wpdb->prepare("
							SELECT pr.product_id, pr.sku, p.post_title, pr.onsale, p.post_date, 
										 pm.meta_value AS 'children', pm2.meta_value AS 'expired',
										 COUNT(plook.order_id) AS 'orderCnt',
										 SUM(wc_oi.downloaded) AS 'redeemed'
							FROM $v_p_join_table vp 
							JOIN $product_table pr ON vp.product_id = pr.product_id
							JOIN $posts_table p ON vp.product_id =  p.ID
							LEFT JOIN $post_meta_table pm ON vp.product_id = pm.post_id AND pm.meta_key = '_children'
							LEFT JOIN $post_meta_table pm2 ON vp.product_id = pm2.post_id AND pm2.meta_key = 'Expired'
							LEFT JOIN $product_order_table plook ON plook.product_id = pr.product_id
							JOIN $posts_table orderp ON orderp.ID = plook.order_id AND orderp.post_status = 'wc-completed'
							LEFT JOIN $order_items_table wc_oi ON wc_oi.order_item_id = plook.order_item_id
							WHERE	vp.venue_id = %d
							GROUP BY pr.product_id
							ORDER BY pr.onsale, p.post_date DESC", 
							$venue_id), ARRAY_A);

			// first thing to do is order the rows by most recent
			// but also grouping related products under the group,
			// using the date of the group for the order
			// *** GROUPING INFO NOT CURRENTLY RETURNING FROM SQL 
			// *** KEEPING LOGIC IN CASE THAT CHANGES
			$ordered_products = order_product_table($product_rows);
		?>

		<div class="panel panel-default">
			<div class="panel-heading text-center"">
						<h2>Welcome <?php echo $venue_name; ?></h2>
						<h3>Venue Products</h3>
			</div>
			<div class="panel-body">
				<?php
				if (count($product_rows)) {
					display_products_table($ordered_products);
				} else {
					echo "<h3>No Products Found</h3>";
				}
				?>
			</div>
		</div>
		<div id="voucher-list-div" class="container">

		</div>
	</section>
	<div id="taste-modal-layer">
			<div id="taste-msg-box" class="modalContainer">
				<div>
					<p id="taste-msg-text">Some sample text</p>
					<div id="taste-msg-close" class="btn btn-close">Close</div>
				</div>
			</div><!-- taste-msg-box -->
		</div><!-- taste-modal-layer -->
</body>

</html>

<?php 

function display_products_table($ordered_products) {
	?>
	<table class="table table-striped table-bordered">
		<thead>
			<th>Prod ID</th>
			<th>Title</th>
			<th>Status</th>
			<th>Post Date</th>
			<th>Quantity<br/>Redeemed</th>
			<th>Redeem</th>
		</thead>
		<tbody>
			<?php
				foreach($ordered_products as $product_row) {
					$title = $product_row['post_title'];
					$status = ("N" === $product_row['expired']) ? "Active" : "Expired";
					$date = str_replace('-', '<span>&#8209;</span>', explode(' ', $product_row['post_date'])[0]);
					$qty = "{$product_row['redeemed']} / ({$product_row['orderCnt']})";
					$redeem = "<button data-prod-id='" . $product_row['product_id'] . "' class='btn btn-primary product-select-btn'>Select</button>";
					if ( count($product_row['child_list'])) {
						$qty = '';
						$redeem = "Group";
					}
					display_product_row($product_row['product_id'], $title, $status, $date, $qty, $redeem);
					// if children, display them now
					// if ( count($product_row['child_list']) ) {
					// 	foreach($product_row['child_list'] as $child_row) {
					// 		$title = $child_row['post_title'];
					// 		$status = ("N" === $child_row['expired']) ? "Active" : "Expired";
					// 		$type = "Pkg Item";
					// 		$qty = "{$child_row['redeemed']} / ({$child_row['orderCnt']})";
					// 		$redeem = "<button data-prod-id='" . $child_row['product_id'] . "' class='btn btn-primary product-select-btn'>Select</button>";
					// 		display_product_row($child_row['product_id'], "--- $title", $status, $type, $qty, $redeem);
					// 	}
					// }
				}
			?>
		</tbody>
	</table>
	<?php
}

function display_product_row($id, $title, $status, $date, $qty, $redeem) {
 ?>
	<tr>
		<td><?php echo $id ?></td>
		<td><?php echo $title ?></td>
		<td><?php echo $status ?></td>
		<td><?php echo $date ?></td>
		<td><?php echo $qty ?></td>
		<td><?php echo $redeem ?></td>
	</tr>
 <?php
}

function order_product_table($product_rows) {
	$ordered_products = array();
	// DEPRECATED FOR NOW
	// loop through the group rows (groups come first), 
	// creating a child_list subarray on each group
	// removing those children from the product_rows 
	// so they do not appear twice
	// then resort based on post date
	while (count($product_rows)) {
		$row = array_shift($product_rows);
		$row['child_list'] = array();
		// some products have children but are not groupings??
		// check 'onsale'
		if ($row['children'] && !$row['onsale']) {
			// unserialize the children and put the 
			// corresponding elements in a subarray
			$children_list = unserialize($row['children']);
			foreach ($children_list as $child_id) {
				$key = array_search($child_id, array_column($product_rows, 'product_id'));
				if (false !== $key) {
					unset($product_rows[$key]['children']);
					$row['child_list'][] = $product_rows[$key];
					array_splice($product_rows, $key, 1);
				}
			}
		}
		unset($row['children']);
		$ordered_products[] = $row;
	}

	usort($ordered_products, function($a, $b) {
		return $b['post_date'] <=> $a['post_date'];
	});

	return $ordered_products;
}

function display_venue_select() {
	global $wpdb;
	// build list of venues 
	$venue_rows = $wpdb->get_results("
		SELECT venue_id, name, description, venue_type
		FROM " . $wpdb->prefix . "taste_venue
		ORDER BY venue_type, name
	", ARRAY_A);
	?>
	<div id="venue-form-container">
	<form method="post" action="" id="venue-select-form">
		<label for="venue-select">Choose a Venue:</label>
		<select name="venue-id" id="venue-select" class="form-control">
			<option value=0>Select a Venue</option>
		<?php 
			foreach ($venue_rows as $venue_row) {
				echo "<option value={$venue_row['venue_id']}>{$venue_row['name']}</option>";
			}
		?>
		</select>
		<br/>
		<button type="submit" id="select-venue-btn" disabled class="btn btn-default">Submit</button>
	</form>
	</div>

<?php
}