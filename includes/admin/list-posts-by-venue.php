<?php 
/**
 *  list-posts-by-venue.php 
 *  admin menu page for viewing 
 * 	posts attached to a venue 
 * 
 * 	Author: Ron Boutilier
 * 	Date: 11/24/2020
 */
 
defined('ABSPATH') or die('Direct script access disallowed.');

function taste_view_posts_by_venue() {
	?>
	<div class="wrap">
		<h2>View Posts by Venue</h2>
		<?php

		if (isset($_POST['venue-id'])) {
			$venue_id = $_POST['venue-id'];
			?>
			<div class="admin-back-link">
				<a href="<?php echo admin_url("edit.php?page=venue-view-posts") ?>"><== Return to Venue Selection</a>
			</div>
			<?php
			display_posts($venue_id);
		} else {
			// display form to select Venue
			display_venue_select();
		}
		?>
	</div>
	<?php
}

function get_venue_posts($venue_id) {
	global $wpdb;

	$venue_post_rows = $wpdb->get_results($wpdb->prepare("
		SELECT vens_posts.post_id, posts.post_title, users.display_name as author , posts.post_date
		FROM {$wpdb->prefix}taste_venues_posts vens_posts
		JOIN {$wpdb->prefix}posts posts ON posts.ID = vens_posts.post_id
		LEFT JOIN {$wpdb->prefix}users users ON users.ID = posts.post_author
		WHERE vens_posts.venue_id = %d
		ORDER BY posts.post_date DESC
	", $venue_id), ARRAY_A);

	return $venue_post_rows;
}

function display_post_table($post_rows, $venue_id, $venue_name) {
	?>
	<h3>Posts Assigned to <?php echo $venue_name ?>:</h3>
	<?php
		if (count($post_rows)) {
			?>
			<div class="post-table-div">
			<table id="post-table" class="fixed striped widefat">
				<thead>
					<tr>
						<th class="manage-column">Post Id</th>
						<th class="manage-column">Title</th>
						<th class="manage-column">Date</th>
						<th class="manage-column">Author</th>
					</tr>
				</thead>
				<tbody>
					<?php
						foreach($post_rows as $prod_row) {
							$date = str_replace('-', '<span>&#8209;</span>', explode(' ', $prod_row['post_date'])[0]);
							?>
							<tr>
								<td><?php echo $prod_row['post_id'] ?></td>
								<td><?php echo $prod_row['post_title'] ?></td>
								<td><?php echo $date ?></td>
								<td><?php echo $prod_row['author'] ?></td>
							</tr>
							<?php
						}
					?>
				</tbody>
			</table>
		</div>
		<?php
		} else {
			echo '<h2>No Posts Found</h2>';
		}
}

function display_posts($venue_id) {
	global $wpdb;
	// get venue name 
	$venue_row = $wpdb->get_results($wpdb->prepare("
	SELECT v.name
	FROM {$wpdb->prefix}taste_venue v
	WHERE	v.venue_id = %d", 
	$venue_id));

	$venue_name = $venue_row[0]->name;

	// retrieve posts for this venue
	$venue_post_rows = get_venue_posts($venue_id);


	// display in table w/ delete option
	display_post_table($venue_post_rows, $venue_id, $venue_name);

}

