<?php 
/**
 *  assign-products.php 
 *  admin menu page for assigning 
 * products (vouchers) to a venue 
 * 
 */
 
defined('ABSPATH') or die('Direct script access disallowed.');

function taste_assign_products() {
	?>
	<div class="wrap">
		<h2>Assign Products to Venues</h2>
		<?php
		if (isset($_POST['venue-id'])) {
			$venue_id = $_POST['venue-id'];
			display_select_products($venue_id);
		} else {
			// display form to select Venue
			display_venue_select();
		}
		?>
	</div>
	<?php
}

function display_select_products($venue_id) {
	?>
	<h1><?php echo $venue_id ?></h2>
	<?php
}


function display_venue_select() {
	global $wpdb;
	// build list of venues 
	$venue_rows = $wpdb->get_results($wpdb->prepare("
		SELECT venue_id, name, description, venue_type
		FROM " . $wpdb->prefix . "taste_venue
		ORDER BY venue_type, name
	"), ARRAY_A);
	?>
	<div id="venue-form-container" class="wrap">
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


