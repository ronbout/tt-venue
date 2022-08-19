<?php
/*
Template Name: Venue Change Password
*/

/**
 * 	The Change Password page in the Venue system
 *  Date:  08/11/2022
 * 	Author: Ron Boutilier
 *
 */
defined('ABSPATH') or die('Direct script access disallowed.');

if ( !is_user_logged_in()) {
	require_once TASTE_PLUGIN_PATH.'page-templates/thetaste-venue-login.php';
	die();
} else {
	$user_info = get_user_venue_info();
	$user = $user_info['user'];
	$role = $user_info['role'];
	$admin = ('ADMINISTRATOR' === strtoupper($role));

	if ('VENUE' !== strtoupper($role) && !$admin) {
		echo "<h2>Role: $role </h2>";
		die('You must be logged in as a Venue to access this page.');
	}
	if (!$admin) {
        $venue_name = $user_info['venue_name'];
        $venue_type = $user_info['venue_type'];
        $venue_info = $user_info['venue_row'][0];
        $use_new_campaign = $user_info['use_new_campaign'];
        $venue_voucher_page = $user_info['venue_voucher_page'];
        $type_desc = $user_info['venue_type'];
	}

}

require_once TASTE_PLUGIN_PATH.'page-templates/partials/venue-head.php';
require_once TASTE_PLUGIN_PATH.'page-templates/partials/venue-navbar.php';

$venue_id = '';
if ($admin) {
	$nav_links = array(
		array(
			'title' => 'Log Out',
			'url' => wp_logout_url(get_site_url()),
			'active' => false
		),
	);
	// check if the Venue ID is in GET from form 
	if (isset($_GET['venue-id'])) {
		$venue_id = $_GET['venue-id'];
		// add the link to return to venue selection
		$venue_select_link = array(
			'title' => 'Venue Selection',
			'url' => get_page_link(),
			'active' => false
		);
		array_unshift($nav_links, $venue_select_link);
		// get venue name and other info
		$user_info = get_user_venue_info($venue_id);
		$venue_name = $user_info['venue_name'];
		$venue_type = $user_info['venue_type'];
        $venue_info = $user_info['venue_row'][0];
		$use_new_campaign = $user_info['use_new_campaign'];
		$venue_voucher_page = $user_info['venue_voucher_page'];
		$type_desc = $venue_type;
	} 
} else {
	//$nav_links = venue_navbar_standard_links($user_info['use_new_campaign'], $user_info['venue_voucher_page']);
	$venue_id = $user->ID;
}

$navbar_get = $admin ? "venue-id=$venue_id" : "";
$nav_links = venue_navbar_standard_links($user_info['use_new_campaign'], $user_info['venue_voucher_page'], $admin, $navbar_get);
?>
<body>
<?php
if (!$venue_id) {
	// display form to select Venue as user is admin w/o a venue selected
	display_venue_select(true, 0, true, get_page_link());
	echo '<script type="text/javascript" src= "' . TASTE_PLUGIN_INCLUDES_URL . '/js/thetaste-venue-select.js"></script>';
	die();
}

// check for form update 
$user_msg = '';
$alert_type = 'alert-success';
if (isset($_POST['venue_change_password_submit'])) {
	$nonce_value = wc_get_var( $_REQUEST['venue-change-password-nonce'], '');
	if ( wp_verify_nonce( $nonce_value, 'change_password' ) ) {
		$update_result = update_venue_password($venue_id);
	
		if ($update_result['error']) {
			$user_msg =  'Error updating password. ' . $update_result['error'];
			$alert_type = 'alert-danger';
		} else {
			$user_msg = 'Venue Password was successfully Changed';
			$venue_info = $_POST;
		}
	} else {
		$user_msg = "Invalid Security check.  Please try again.";
		$alert_type = 'alert-danger';
	}
}
?>


<?php venue_navbar($nav_links, true, $venue_name);  ?>
<div class="container-fluid h-100" id="change_password_container">
	<div class="row" id="profile-row">
			<div class="col-xl-4" id="profile_photo">
<!--            <input type="file" name="prof_photo" id="prof_photo" style="display: none;"/>-->
<!--            <button class="btn btn-primary" name="update_photo" id="update_photo">Choose an image</button>-->
<!--            <img class="img-fluid profile-photo__img" src="--><?php //echo get_site_url() ?><!--/wp-content/plugins/thetaste-venue/assets/img/profile_placeholder.png" alt="business_photo" id="business_photo"/>-->
					<div id="thetaste-logo-link" class="text-center">
							<h1 class="heading_profile">WELCOME TO IRELAND’S AWARD WINNING</h1>
							<h2 class="heading2_profile">FOOD, DRINK & TRAVEL DIGITAL MAGAZINE</h2>
					</div>
			</div>
			<div class="col-xl-8 px-md-6 px-xl-7 new_port" id="profile_edit_form">
					<div id="venue-summary-div" class="panel-heading text-center mt-5">
							<h3 class="col-heading_profile">Change Password</h3>
					</div>
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
					<?php display_venue_change_password_form($venue_info, $venue_name); ?>
		</div>
	</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script type="text/javascript" src= "<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/js/thetaste-change-password.js"></script>
</body>
</html>

<?php
function display_venue_change_password_form($venue_info, $name) {
	$desc = !empty($venue_info['description']) ? stripslashes($venue_info['description']) : NULL;
	$address1 = !empty($venue_info['address1']) ? stripslashes($venue_info['address1']) : NULL;
	$address2 = !empty($venue_info['address2']) ? stripslashes($venue_info['address2']) : NULL;
	$city = !empty($venue_info['city']) ? stripslashes($venue_info['city']) : NULL;
	$postcode = !empty($venue_info['postcode']) ? stripslashes($venue_info['postcode']) : NULL;
	$state = !empty($venue_info['state']) ? stripslashes($venue_info['state']) : NULL;
	$country = !empty($venue_info['country']) ? stripslashes($venue_info['country']) : NULL;
	$phone = !empty($venue_info['phone']) ? stripslashes($venue_info['phone']) : NULL;
	?>
		<form method="post" id="venue-password-change-form" class="venue-change-password-form form-horizontal my-3">
			<div class="form-group row">
				<label for="password_current" class="col-sm-4 col-form-label">Current Password:</label>
				<div class="col-sm-8">
					<input type="password" class="form-control" name="password_current" id="password_current"
							required autocomplete="off">
				</div>
			</div>
			<div class="form-group row">
				<label for="password_1" class="col-sm-4 col-form-label">New Password:</label>
				<div class="col-sm-8">
					<input type="password" class="form-control" name="password_1" id="password_1" 
							required autocomplete="off">
				</div>
			</div>
			<div class="form-group row">
				<label for="password_2" class="col-sm-4 col-form-label">Confirm New Password:</label>
				<div class="col-sm-8">
					<input type="password" class="form-control" name="password_2" id="password_2" 
							required autocomplete="off">
				</div>
			</div>
			<div class="form-group row">
				<div class="col-sm-offset-2 col-sm-4">
					<button type="submit" name="venue_change_password_submit" class="btn btn-secondary" id="venue_change_password_submit">
						Update Password
					</button>
				</div>
				
				<?php wp_nonce_field( 'change_password', 'venue-change-password-nonce' ); ?>
				<div class="col-sm-6">
					<span id="venue-pass-match-error-msg" style="color:red;"></span>
				</div>
			</div>
		</form>
	<?php
}

function update_venue_password($venue_id) {
	global $wpdb;

	$pass_cur             = ! empty( $_POST['password_current'] ) ? $_POST['password_current'] : '';
	$pass1                = ! empty( $_POST['password_1'] ) ? $_POST['password_1'] : ''; 
	$pass2                = ! empty( $_POST['password_2'] ) ? $_POST['password_2'] : ''; 

	$current_user       = get_user_by( 'id', $venue_id );

	if (!wp_check_password( $pass_cur, $current_user->user_pass, $current_user->ID )) {
		return array('error' => "Current Password is Incorrect");
	}

	$user     = new stdClass();
	$user->user_pass = $pass1;
	$user->ID = $venue_id;

	$upd_result = wp_update_user( $user );
	if (is_wp_error($upd_result)) {
		return array('error' => "unknown error updating password.");
	}

	return array('success' => true);

}
