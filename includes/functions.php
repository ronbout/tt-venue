<?php
/**
 * 	Common functions for thetaste-venue plugin
 * 	
 * 	9/22/2020	Ron Boutilier
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

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

function display_venue_select($display_submit=true, $venue_id = 0, $add_form=true, $bulk_flg=false) {
	global $wpdb;
	// build list of venues 
	$venue_rows = $wpdb->get_results("
		SELECT venue_id, name, description, venue_type
		FROM " . $wpdb->prefix . "taste_venue
		ORDER BY venue_type, name
	", ARRAY_A);

	$first_option = $bulk_flg ?   __( "— No change —", 'woocommerce' ) :  __( "Select A Venue", 'woocommerce' );
	?>
	<div id="venue-form-container" class="wrap">
	<?php echo ($add_form) ?	'<form method="post" action="" id="venue-select-form">' : '' ?>
		<label for="venue-select">Choose a Venue:</label>
		<select name="venue_id" id="venue-select" class="form-control">
			<option value=0 <?php echo (0 === $venue_id) ? 'selected' : ''?> ><?php echo $first_option ?></option>
		<?php 
			foreach ($venue_rows as $venue_row) {
				echo "<option value={$venue_row['venue_id']} " . (($venue_id  === $venue_row['venue_id']) ? 'selected' : '') . ">
					{$venue_row['name']}
				</option>";
			}
		?>
		</select>
		<br/>
		<?php
			if ($display_submit) {
				?>
					<button type="submit" id="select-venue-btn" disabled class="button button-primary">Submit</button>
				<?php
			}
		?>
		<?php echo ($add_form) ?	'</form>' : '' ?>
	</div>

<?php
}