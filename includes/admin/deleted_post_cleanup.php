<?php 
/**
 *  delete_post_cleanup.php 
 *  cleanup venue joining tables
 * 	
 * 	This code is included in the post_listing.php
 * 
 *  Author: Ron Boutilier
 *  Date: 11/23/2020
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

function taste_delete_posts_in_admin_init() {
   if (current_user_can('delete_posts')) {
		 add_action('delete_post', 'taste_clean_venue_table_post_delete');
	 }
}
add_action('admin_init', 'taste_delete_posts_in_admin_init');

function taste_clean_venue_table_post_delete($post_id) {
	global $wpdb;

	if ('post' === get_post_type( $post_id )) {
		$venue_table = "{$wpdb->prefix}taste_venues_posts";
		$wpdb->delete($venue_table, array('post_id' => $post_id), array("%d"));
	}
		
}