<?php
/**
 * 	Common functions for thetaste-venue plugin
 * 	
 * 	9/22/2020	Ron Boutilier
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');


require_once TASTE_PLUGIN_PATH.'/page-templates/partials/order-trans-box.php';

function insert_venue_products($venue_id, $prod_ids) {
	global $wpdb;
	// works for an array of products that are NOT currently assigned.
	// used in Venue Assign admin page

	$sql = "INSERT INTO {$wpdb->prefix}taste_venue_products (venue_id, product_id) VALUES ";
	$insert_vals = array();
	foreach($prod_ids as $id) {
		$sql .= " ( %d, %d),";
		$insert_vals[] = $venue_id;
		$insert_vals[] = $id;
	}

	$sql = rtrim($sql, ',');

	$rows_affected = $wpdb->query(
		$wpdb->prepare($sql, $insert_vals));

	return $rows_affected;
}

function insert_post_venues($post_id, $venue_ids) {
	global $wpdb;
	$venues_posts_table = "{$wpdb->prefix}taste_venues_posts";

	// start be deleting all the original venues attached to this post
	// then add in the new list of venues.
	$sql = "DELETE FROM {$venues_posts_table}
					WHERE post_id = %d";

	$rows_affected = $wpdb->query(
		$wpdb->prepare($sql, $post_id)
	);

	$insert_values = '';
	$insert_parms = [];

// echo '<h1><pre>venueIds: ', var_dump($venue_ids), '</pre></h1>';
// echo '<h1><pre>postId: ', var_dump($post_id), '</pre></h1>';
//  die(); 

	foreach($venue_ids as $venue_id) {
		if ($venue_id) {
			$insert_values .= '(%d, %d),';
			$insert_parms[] = intval($venue_id);
			$insert_parms[] = $post_id; 
		}
	}
	$insert_values = rtrim($insert_values, ',');

	$sql = "
		INSERT INTO {$venues_posts_table}
			(venue_id, post_id)
		VALUES
			{$insert_values}
		";

	$wpdb->show_errors();

	$rows_affected = $wpdb->query(
		$wpdb->prepare($sql, $insert_parms)
	);

	return $rows_affected;
}

function insert_venue_product_on_dup($venue_id, $post_id) {
	global $wpdb;
	// works for a single product/post, with check that it already exists

	
	$sql = "
		INSERT INTO {$wpdb->prefix}taste_venue_products
		(venue_id, product_id)
		VALUES (%d, %d)
		ON DUPLICATE KEY UPDATE
			venue_id = %d,
			product_id = %d
		";

	$field_list = array($venue_id, $post_id, $venue_id, $post_id);

	$rows_affected = $wpdb->query(
		$wpdb->prepare($sql, $field_list)
	);

	return $rows_affected;
}

function display_venue_select($display_submit=true, $venue_id = 0, $add_form=true, $form_action='', $bulk_flg=false, $admin_checkbox=false) {
	global $wpdb;

	// because we are now using the GET string in some of our pages, 
	// the query vars need to be preserved even if a form is submitted
	// with the venue select.  This will be handled with hidden inputs
	$hidden_inputs = "";
	if ($add_form) {
		$query_vars = check_query(true);
		foreach($query_vars as $key => $val) {
			if ('venue-id' != $key) {
				$hidden_inputs .= ' <input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($val).'" /> ';
			} 
		}
	}

	$venue_types = array(
		'Restaurant',
		'Hotel',
		'Bar',
		'Product'
	);

	// build list of venues 
	$venue_rows = $wpdb->get_results("
		SELECT venue_id, name, description, venue_type
		FROM " . $wpdb->prefix . "taste_venue
		ORDER BY name
	", ARRAY_A);

	
	$first_option = $bulk_flg ?   __( "— No change —", 'woocommerce' ) :  __( "Select A Venue", 'woocommerce' );
	// have to send venue rows to javascript code for autocomplete
	?>
<script>
let venuesList = <?php echo json_encode($venue_rows) ?>;
let firstOption = "<?php echo $first_option ?>";
</script>

<div id="venue-form-container" class="wrap">
  <label for="venue-type-filter">Filter Venues by Type:</label>
  <select name="venue-type-filter" id="venue-type-select" class="form-control" style="width: 180px;">
    <option value='all'>All Venue Types</option>
    <?php 
			foreach ($venue_types as $venue_type) {
				echo "<option value='$venue_type'>
					$venue_type
				</option>";
			}
		?>
  </select>
  <br />
  <?php echo ($add_form) ?	'<form method="get" action="'. $form_action .'" id="venue-select-form">' : '' ?>
  <label for="venue-select">Choose a Venue:</label>
  <select name="venue-id" id="venue-select" class="form-control" style="width: 280px;">
    <option value=0 <?php echo (0 === $venue_id) ? 'selected' : ''?>><?php echo $first_option ?></option>
    <?php 
			foreach ($venue_rows as $venue_row) {
				echo "<option value={$venue_row['venue_id']} " . (($venue_id  === $venue_row['venue_id']) ? 'selected' : '') . ">
					{$venue_row['name']}
				</option>";
			}
		?>
  </select>
	
	<?php 
		if ($admin_checkbox) {
			?>
			<div class="form-check">
				<input class="form-check-input" type="checkbox" id="cm-imposter-check" name="venue-view">
				<label class="form-check-label" for="cm-imposter-check">
					View as Venue
				</label>
			</div>
			<?php
		}
	?>
	
  <br />
  <?php
			if ($display_submit) {
				?>
  <button type="submit" id="select-venue-btn" disabled class="btn btn-primary button button-primary">Submit</button>
  <?php
			}
		?>
  <?php echo ($add_form) ?	$hidden_inputs . '</form>' : '' ?>
</div>

<?php
}

/**
 * 	get_user_venue_info
 *  gets information about the current
 *  user for the venue pages
 *  RETURN:  array containing the following:
 * 						user - entire user object
 * 						role - of that user
 * 		if the role is 'venue', it will also return:
 * 						venue_id
 * 						venue_name
 * 						venue_type
 * 						use_new_campaign
 * 						venue_voucher_page
 * 						venue_type_desc
 * 	NOTE: verify that the role is venue before 	
 * 				attempting to access the venue array elements
 */
function get_user_venue_info($venue_id = null) {
	global $wpdb;

	if ($venue_id) {
		$user = get_userdata( $venue_id );
	} else {
		$user = wp_get_current_user();
	}

	$role = $user->roles[0];
	if ('VENUE' !== strtoupper($role)) {
		return compact('user', 'role');
	}
	$venue_id = $user->ID;
	// get venue name and other info
	$venue_table = $wpdb->prefix."taste_venue";
	$venue_row = $wpdb->get_results($wpdb->prepare("
		SELECT *
		FROM $venue_table v
		WHERE	v.venue_id = %d", 
	$venue_id),ARRAY_A);
	$venue_name = $venue_row[0]['name'];
	$venue_type = $venue_row[0]['venue_type'];
	$use_new_campaign = $venue_row[0]['use_new_campaign'];
	$venue_voucher_page = 'Hotel' === $venue_type ? '/hotelmanager' : '/restaurantmanager';
	$venue_type_desc = $venue_type;
	$cutoff_date = $venue_row[0]['historical_cutoff_date'];

	return compact('user', 'role', 'venue_id', 'venue_name', 'venue_type', 'use_new_campaign', 'venue_voucher_page', 'venue_type_desc', 'venue_row', 'cutoff_date');
}

function calc_net_payable($product_price, $vat_val, $commission_val, $cnt, $round_flag=true) {
	// if the round flag is set, need to round revenue, commission and VAT 
	// before calculating the payable. Then, eturn the rounded payable
	$grevenue = $cnt * $product_price;
	$commission = ($grevenue / 100) * $commission_val;
	$vat = ($commission / 100) * $vat_val; 
	if ($round_flag) {
		$grevenue = round($grevenue, 2);
		$commission = round($commission, 2);
		$vat = round($vat,2);
	}
	$payable = $grevenue - ($commission + $vat);
	if ($round_flag) {
		$payable = round($payable, 2);
	}
	
	return array(
		'gross_revenue' => $grevenue,
		'commission' => $commission,
		'vat' => $vat,
		'net_payable' => $payable
	);
}

/**
 *	replacement for ceil() if you only want it to 
 *	to consider $dec_prec number of decimals.  
 *	Ex:  ceil(4.0000001) = 5
 *			 ceiling(4.0000001) = 4
 */ 
function ceiling($nbr, $dec_prec=0) {
  if (0 == $dec_prec) {
    return ceil($nbr);
  }
  $ret_nbr = (int) ($nbr * pow(10, $dec_prec));
  $ret_nbr = $ret_nbr / pow(10, $dec_prec);
  return ceil($ret_nbr);
}

function check_query($convert_array=false) {
	// checks and returns the query string if present
	$query_str =  isset($_SERVER['QUERY_STRING']) ? urldecode($_SERVER['QUERY_STRING']) : '';
  if (!$convert_array) {
    return $query_str;
  }
  parse_str($query_str, $query_array);
  return $query_array;
}

// function test_redeem_hook($order_list, $redeem_flg) {
// 	$file1 = "C:/Users/ronbo/Documents/jim-stuff/tmp/write_test_redeem_hook_" . time() . ".txt";

// 	$msg1 = serialize(print_r($order_list, true));

// 	file_put_contents($file1, $msg1);
// }
// add_action('taste_after_redeem', 'test_redeem_hook', 10, 2);