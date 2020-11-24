<?php 
/**
 *  list-products-by-venue.php 
 *  admin menu page for viewing 
 * 	products attached to a venue 
 * 
 * 	Author: Ron Boutilier
 * 	Date: 10/10/2020
 */
 
defined('ABSPATH') or die('Direct script access disallowed.');

function taste_view_products_by_venue() {
	?>
	<div class="wrap">
		<h2>View Products by Venue</h2>
		<?php

		if (isset($_POST['venue-id'])) {
			$venue_id = $_POST['venue-id'];
			?>
			<div class="admin-back-link">
				<a href="<?php echo admin_url("edit.php?post_type=product&page=venue-view-products") ?>"><== Return to Venue Selection</a>
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

function display_product_table($product_rows, $venue_id, $venue_name) {
	?>
	<h3>Products Assigned to <?php echo $venue_name ?>:</h3>
	<?php
		if (count($product_rows)) {
			?>
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
		} else {
			echo '<h2>No Products Found</h2>';
		}
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


	// display in table w/ delete option
	display_product_table($venue_product_rows, $venue_id, $venue_name);

}

