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
			display_products($venue_id);
		} else {
			// display form to select Venue
			display_venue_select();
		}
		?>
	</div>
	<?php
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
		<button type="submit" id="select-venue-btn" disabled class="button button-primary">Submit</button>
	</form>
	</div>

<?php
}

function get_venue_products($venue_id) {
	global $wpdb;

	$venue_product_rows = $wpdb->get_results($wpdb->prepare("
		SELECT ven_prod.product_id, prod_look.sku, posts.post_title, posts.post_date
		FROM {$wpdb->prefix}taste_venue_products ven_prod
		JOIN {$wpdb->prefix}wc_product_meta_lookup prod_look ON prod_look.product_id = ven_prod.product_id
		JOIN {$wpdb->prefix}posts posts ON posts.ID = ven_prod.product_id
		WHERE ven_prod.venue_id = 14693
	"), ARRAY_A);

	return $venue_product_rows;
}

function get_non_venue_products() {
	global $wpdb;

	$product_rows = $wpdb->get_results("
		SELECT prod_look.product_id, prod_look.sku, posts.post_title, posts.post_date
		FROM {$wpdb->prefix}wc_product_meta_lookup prod_look 
		JOIN {$wpdb->prefix}posts posts ON posts.ID = prod_look.product_id
		WHERE prod_look.product_id NOT IN (SELECT product_id FROM {$wpdb->prefix}taste_venue_products)
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
					<th class="manage-column">Remove</th>
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
							<td><button id="remove-prod-<?php echo $prod_row['product_id'] ?> class="remove-prod">X</button></td>
						</tr>
						<?php
					}
				?>
			</tbody>
		</table>
	</div>
	<?php
}

function display_product_select($product_rows) {
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

	// have to send product rows to javascript code for post_form_autocomplete_off(  )// add_action('wp_enqueue_scripts', 'jl_localize_script');
// function jl_localize_script() {
// 	global $query_flag, $q_string, $social_url_placeholder, $social_title_placeholder;
	
// 	wp_localize_script('jl-js', 'jobListing', array(
// 			'security' => wp_create_nonce('jl_ajax_nonce'),
// 			'ajaxurl' => admin_url( 'admin-ajax.php' ),
// 			'queryFlag' => $query_flag,
// 			'query' => $q_string,
// 			'socialURLPlaceholder' => $social_url_placeholder,
// 			'socialTitlePlaceholder' => $social_title_placeholder,
// 			'indeedFlag'	=> false,
// 		));
// }

	// display in table w/ delete option
	display_product_table($venue_product_rows, $venue_id, $venue_name);

	// display select option until autocomplete is working
	display_product_select($product_rows);

	// use autocomplete to search through titles

	// Add product button


	// add to database and table display
}


