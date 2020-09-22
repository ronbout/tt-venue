<?php 
/**
 *  assign-products.php 
 *  admin menu page for assigning 
 * products (vouchers) to a venue 
 * 
 */
 
defined('ABSPATH') or die('Direct script access disallowed.');

function taste_assign_products() {
	// check for coming here as form submit with products to update
	if (isset($_POST['assign_products_btn']) && isset($_POST['prod_ids'])) {
		$prod_ids = $_POST['prod_ids'];
		$cur_venue_id = $_POST['current_venue_id'];

		if (insert_venue_products($cur_venue_id, $prod_ids)) {
			// success display
			?>
			<div id="setting-error-settings_updated"
				class="updated settings-error notice is-dismissible">
				Products have successfully been assigned
			</div>
			<?php
		}
	}
	?>
	<div class="wrap">
		<h2>Assign Products to Venues</h2>
		<?php

		if (isset($_POST['venue-id'])) {
			$venue_id = $_POST['venue-id'];
			?>
			<div class="admin-back-link">
				<a href="<?php echo admin_url("edit.php?post_type=product&page=venue-assign-products") ?>"><== Return to Venue Selection</a>
			</div>
			<?php
			display_products($venue_id);
		} else {
			// display form to select Venue
			display_venue_select();
		}
		?>
	</div>
	<?php
}

function get_venue_products($venue_id) {
	global $wpdb;

	$venue_product_rows = $wpdb->get_results($wpdb->prepare("
		SELECT ven_prod.product_id, prod_look.sku, posts.post_title, posts.post_date, pmeta.meta_value AS 'expired'
		FROM {$wpdb->prefix}taste_venue_products ven_prod
		JOIN {$wpdb->prefix}wc_product_meta_lookup prod_look ON prod_look.product_id = ven_prod.product_id
		JOIN {$wpdb->prefix}posts posts ON posts.ID = ven_prod.product_id
		LEFT JOIN {$wpdb->prefix}postmeta pmeta ON pmeta.post_id = ven_prod.product_id AND meta_key = 'Expired'
		WHERE ven_prod.venue_id = %d
		ORDER BY posts.post_date DESC
	", $venue_id), ARRAY_A);

	return $venue_product_rows;
}

function get_non_venue_products() {
	global $wpdb;

	$product_rows = $wpdb->get_results("
		SELECT prod_look.product_id AS 'prodId', prod_look.sku, posts.post_title AS 'prodTitle', 
			posts.post_date AS 'prodDate' , pmeta.meta_key AS 'expired'
		FROM {$wpdb->prefix}wc_product_meta_lookup prod_look 
		JOIN {$wpdb->prefix}posts posts ON posts.ID = prod_look.product_id
		LEFT JOIN {$wpdb->prefix}postmeta pmeta ON pmeta.post_id = prod_look.product_id AND meta_key = 'Expired'
		WHERE prod_look.product_id NOT IN (SELECT product_id FROM {$wpdb->prefix}taste_venue_products)
		ORDER BY posts.post_date DESC
		LIMIT 100
	", ARRAY_A);

	return $product_rows;
}

function display_product_table($product_rows, $venue_id, $venue_name) {
	?>
	<h3>Products Assigned to <?php echo $venue_name ?></h3>
	<div class="product-table-div">
		<table id="product-table" class="fixed striped widefat">
			<thead>
				<tr>
					<th class="manage-column">Product Id</th>
					<th class="manage-column">Sku</th>
					<th class="manage-column">Title</th>
					<th class="manage-column">Date</th>
					<th class="manage-column">Expired</th>
				</tr>
			</thead>
			<tbody>
				<?php
					foreach($product_rows as $prod_row) {
						$date = str_replace('-', '<span>&#8209;</span>', explode(' ', $prod_row['post_date'])[0]);
						?>
						<tr>
							<td><?php echo $prod_row['product_id'] ?></td>
							<td><?php echo $prod_row['sku'] ?></td>
							<td><?php echo $prod_row['post_title'] ?></td>
							<td><?php echo $date ?></td>
							<td><?php echo $prod_row['expired'] ?></td>
						</tr>
						<?php
					}
				?>
			</tbody>
		</table>
	</div>
	<?php
}

function display_product_select($product_rows, $venue_id) {
	?>
	<table class="form-table">
		<tr>
			<th>
			<label for="product-input">Search Product by Title</label>
			</th>
			<td>
				<input type="text" id="product-input" name="product_input" class="large-text" />
			</td>
		</tr>
	</table>
	<h3>Selected Products</h3>
	<form method="post" action="">
		<input type="hidden" name="current_venue_id" value="<?php echo $venue_id ?>">
		<table id="selected-products-table" class="fixed striped widefat">
				<thead>
					<tr>
						<th class="manage-column">Product Id</th>
						<th class="manage-column">Sku</th>
						<th class="manage-column">Title</th>
						<th class="manage-column">Date</th>
						<th class="manage-column">Expired</th>
						<th class="manage-column">Remove</th>
					</tr>
				</thead>
				<tbody id="select-products-body">
				</tbody>
		</table>
		<br/>
		<br/>
		<button type="submit" id="assign-products-btn" name="assign_products_btn" class="button button-primary" disabled>Assign Selected Products to Venue</button>
		<button type="submit" id="cancel-btn" name="cancel_btn" class="button button-cancel">Cancel</button>
	</form>
	<?php
	/* *** THIS USES SELECT OPTIONS, NOT AUTOCOMPLETE
	?>
	<div id="product-form-container">
	<form method="post" action="" id="product-select-form">
		<label for="product-select">Choose a Product:</label>
		<select name="product-id" id="product-select" class="form-control">
			<option value=0>Select a Product</option>
		<?php 
			foreach ($product_rows as $product_row) {
				echo "<option value={$product_row['product_id']}>{$product_row['post_title']}</option>";
			}
		?>
		</select>
		<br/>
		<button type="submit" id="select-product-btn" disabled class="button button-primary">Add Product</button>
	</form>
	</div>

	<?php
	*/
}


function display_products($venue_id) {
	global $wpdb;
	// get venue name 
	$venue_row = $wpdb->get_results($wpdb->prepare("
	SELECT v.name
	FROM {$wpdb->prefix}taste_venue v
	WHERE	v.venue_id = %d", 
	$venue_id));

	$venue_name = $venue_row[0]->name;

	// retrieve products for this venue
	$venue_product_rows = get_venue_products($venue_id);
	
	// retrieve last 100 (more?) products NOT already assigned
	$product_rows = get_non_venue_products();

	// need to create list with 'value' and 'label' keys for autocomplete 
	$auto_rows = array_reduce($product_rows, function ($result, $row) {
		$result[] = array_merge($row, array('label' => $row['prodTitle'], 'value' => $row['prodId']));
		return $result;
	}, array());

	// have to send product rows to javascript code for autocomplete
	?>
	<script>
		let products = <?php echo json_encode($auto_rows) ?>
	</script>
	<?php

	// display in table w/ delete option
	display_product_table($venue_product_rows, $venue_id, $venue_name);

	// display autocomplete and selected products table
	display_product_select($product_rows, $venue_id);
}


