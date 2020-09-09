<?php 

	// Enqueue Jobs Page Stylesheets and Scripts

	add_action('admin_enqueue_scripts', 'taste_venue_load_admin_resources');

	function taste_venue_load_admin_resources($page) {
		if (in_array($page, array("user-new-php", "user-edit-php", "product_page_venue-assign-products"))) {
			wp_enqueue_style( 'taste-admin-css', TASTE_PLUGIN_INCLUDES_URL."/css/thetaste-admin.css" );
			wp_enqueue_script( 'taste-admin-js', TASTE_PLUGIN_INCLUDES_URL . '/js/thetaste-admin.js', array('jquery-ui-autocomplete'), false, true);
		}
	}