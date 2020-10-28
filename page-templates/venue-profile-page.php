<?php
/*
Template Name: Venue Profile Page
*/

/**
 * 	The profile page for venues to update their info
 *  Date:  9/15/2020
 * 	Author: Ron Boutilier
 */
defined('ABSPATH') or die('Direct script access disallowed.');

if ( !is_user_logged_in()) {
	require_once TASTE_PLUGIN_PATH.'page-templates/thetaste-venue-login.php';
	die();
} else {
	$user = wp_get_current_user();
	$role = $user->roles[0];
	if ('VENUE' !== strtoupper($role)) {
		echo "<h2>Role: $role </h2>";
		die('You must be logged in as a Venue to access this page.');
	}
	$venue_id = $user->ID;
	// get venue name and other info
	$venue_table = $wpdb->prefix."taste_venue";
	$venue_row = $wpdb->get_results($wpdb->prepare("
		SELECT *
		FROM $venue_table v
		WHERE	v.venue_id = %d", 
	$venue_id), ARRAY_A);
	$venue_name = $venue_row[0]['name'];
	$venue_type = $venue_row[0]['venue_type'];
	$venue_info = $venue_row[0];
	$venue_voucher_page = 'Hotel' === $venue_type ? '/hotelmanager' : '/restaurantmanager';
	$type_desc = $venue_type;
}

// check for form update 
$user_msg = '';
$alert_type = 'alert-success';
if (isset($_POST['venue_profile_form_submit'])) {
	$update_success = update_venue_info($_POST, $venue_table, $venue_id);
	if (false === $update_success) {
		$user_msg =  'Error updating venue.  Please contact info@thetaste.ie';
		$alert_type = 'alert-danger';
	} else {
		$user_msg = 'Venue info was successfully updated';
		$venue_info = $_POST;
	}

}

require_once TASTE_PLUGIN_PATH.'page-templates/partials/venue-head.php';
?>
<body>

<main>
		</br>
		</br>
		<div class="container">
		<header>
			<div class="admin-back-link">
				<a href="<?php echo get_site_url(null, '/venue-portal') ?>"><== Return to Portal</a>
			</div>
			<div class="text-center">
				<a href="<?php echo get_site_url() ?>">
						<img src="<?php echo get_site_url() ?>/wp-content/uploads/2017/12/thetaste-site-homepage-logo5.png">
				</a>
			</div>
			<br><br>
			<div class="text-center">
				<b>WELCOME TO IRELAND’S AWARD WINNING FOOD, DRINK & TRAVEL DIGITAL MAGAZINE</b>
				<br><br>
				<span style="font-size:12px;">19.6M READERS WORLDWIDE <b>|</b> 10K ARTICLES <b>|</b> €10M GENERATED FOR THE IRISH HOSPITALITY INDUSTRY <b>|</b> 726K REGISTERED MEMBERS <b>|</b> 200K+ TASTE EXPERIENCES SOLD <b>|</b> 300K SOCIAL MEDIA FOLLOWERS <b>|</b> WINNER OF BEST DIGITAL FOOD MAGAZINE IN THE WORLD <b>|</b> WINNER OF OUTSTANDING SMALL BUSINESS IN IRELAND</span>
			</div>
		</header>
		<div class="portal-welcome panel panel-default">
			<div id="venue-summary-div" class="panel-heading text-center"">
				<h2><?php echo $venue_name; ?> Profile Page</h2>
			</div>
			<div class="panel-body">
				<?php 
					if ($user_msg) {
						?>
						<div class="alert <?php echo $alert_type ?> alert-dismissible" role="alert">
							<button type="button" class="close" data-dismiss="alert" aria-label="Close">
									<span aria-hidden="true">&times;</span>
							</button>
							<?php echo $user_msg ?>
						</div>
						<?php
					}
				?>
				<?php display_venue_form($venue_info, $venue_name); ?>
			</div>
		</div>
	</main>
</body>
</html>

<?php
function display_venue_form($venue_info, $name) {
	$desc = !empty($venue_info['description']) ? stripslashes($venue_info['description']) : NULL;
	$address1 = !empty($venue_info['address1']) ? stripslashes($venue_info['address1']) : NULL;
	$address2 = !empty($venue_info['address2']) ? stripslashes($venue_info['address2']) : NULL;
	$city = !empty($venue_info['city']) ? stripslashes($venue_info['city']) : NULL;
	$postcode = !empty($venue_info['postcode']) ? stripslashes($venue_info['postcode']) : NULL;
	$state = !empty($venue_info['state']) ? stripslashes($venue_info['state']) : NULL;
	$country = !empty($venue_info['country']) ? stripslashes($venue_info['country']) : NULL;
	$phone = !empty($venue_info['phone']) ? stripslashes($venue_info['phone']) : NULL;
	?>
	<form method="post" class="venue-profile-form form-horizontal">
		<div class="form-group">
			<label for="name" class="col-sm-2 control-label"><?php esc_html_e('Name'); ?></label>
			<div class="col-sm-10">
				<input type="text" id="name" name="name"
					value="<?php echo esc_attr($name); ?>" maxlength="80"
					class="form-control" disabled />
			</div>
		</div>
		<div class="form-group">
			<label for="description" class="col-sm-2 control-label"><?php esc_html_e('Description'); ?></label>
			<div class="col-sm-10">
				<input type="text" id="description" name="description"
					value="<?php echo esc_attr($desc); ?>" maxlength="255"
					class="form-control"  />
			</div>
		</div>
		<div class="form-group">
			<label for=address1" class="col-sm-2 control-label"><?php esc_html_e('Address Line 1'); ?></label>
			<div class="col-sm-10">
				<input type="text" id="address1" name="address1"
					value="<?php echo esc_attr($address1); ?>" maxlength="120"
					class="form-control"  />
			</div>
		</div>
		<div class="form-group">
			<label for="address2" class="col-sm-2 control-label"><?php esc_html_e('Address Line 2'); ?></label>
			<div class="col-sm-10">
				<input type="text" id="address2" name="address2"
					value="<?php echo esc_attr($address2); ?>" maxlength="120"
					class="form-control"  />
			</div>
		</div>
		<div class="form-group">
			<label for="city" class="col-sm-2 control-label"><?php esc_html_e('City'); ?></label>
			<div class="col-sm-10">
				<input type="text" id="city" name="city"
					value="<?php echo esc_attr($city); ?>" maxlength="100"
					class="form-control"  />
			</div>
		</div>
		<div class="form-group">
		<label for="postcode" class="col-sm-2 control-label"><?php esc_html_e('Postcode / ZIP'); ?></label>
			<div class="col-sm-10">
			<input type="text" id="postcode" name="postcode" maxlength="20"
				value="<?php echo esc_attr($postcode); ?>"
					class="form-control"  />
			</div>
		</div>
		<div class="form-group">
			<label for="country" class="col-sm-2 control-label"><?php esc_html_e('Country / Region'); ?></label>
			<div class="col-sm-10">
				<input type="text" id="country" name="country"
					value="<?php echo esc_attr($country); ?>" maxlength="100"
					class="form-control"  />
			</div>
		</div>
		<div class="form-group">
			<label for="state" class="col-sm-2 control-label"><?php esc_html_e('State / County'); ?></label>
			<div class="col-sm-10">
				<input type="text" id="state" name="state"
					value="<?php echo esc_attr($state); ?>" maxlength="100"
					class="form-control"  />
			</div>
		</div>
		<div class="form-group">
			<label for="phone" class="col-sm-2 control-label"><?php esc_html_e('Phone'); ?></label>
  	  <div class="col-sm-10">
				<input type="text" id="phone" name="phone"
					value="<?php echo esc_attr($phone); ?>" maxlength="100"
					class="form-control"  />
			</div>
		</div>
		<div class="form-group">
    	<div class="col-sm-offset-2 col-sm-10">
				<button type="submit" name="venue_profile_form_submit" class="btn btn-primary">Update</button>
			</div>
		</div>
	<?php
}

function update_venue_info($form_info, $venue_table, $venue_id) {
	global $wpdb;
	// update the venue table
	$where = array( 'venue_id' => $venue_id);
	$data = array();

	$desc = !empty($form_info['description']) ? stripslashes($form_info['description']) : NULL;
	$address1 = !empty($form_info['address1']) ? stripslashes($form_info['address1']) : NULL;
	$address2 = !empty($form_info['address2']) ? stripslashes($form_info['address2']) : NULL;
	$city = !empty($form_info['city']) ? stripslashes($form_info['city']) : NULL;
	$postcode = !empty($form_info['postcode']) ? stripslashes($form_info['postcode']) : NULL;
	$state = !empty($form_info['state']) ? stripslashes($form_info['state']) : NULL;
	$country = !empty($form_info['country']) ? stripslashes($form_info['country']) : NULL;
	$phone = !empty($form_info['phone']) ? stripslashes($form_info['phone']) : NULL;
	
	$data['description'] =  $desc;
	$data['address1'] = $address1;
	$data['address2'] = $address2;
	$data['city'] = $city;
	$data['postcode'] = $postcode;
	$data['state'] = $state;
	$data['country'] = $country;
	$data['phone'] = $phone;

	$format = array('%s','%s','%s','%s','%s','%s','%s','%s');
	$rows_affected = $wpdb->update($venue_table, $data, $where, $format);
	return $rows_affected;
	// return true;
}