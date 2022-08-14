<?php
/*
Template Name: Venue Lost Password
*/

/**
 * 	The Lost Password page in the Venue system
 *  Date:  08/12/2022
 * 	Author: Ron Boutilier
 *
 */
defined('ABSPATH') or die('Direct script access disallowed.');

require_once TASTE_PLUGIN_PATH.'page-templates/partials/venue-head.php';
require_once TASTE_PLUGIN_PATH.'page-templates/partials/venue-navbar.php';

// check for form update 
$user_msg = '<p>Lost your password?</p>
<p>Please enter your username or email address. You will receive a link to create a new password via email.</p>';
$alert_type = 'alert-success';
$display_form = true;
$form_type = "lost";
$user_id = '';

if ( isset( $_POST['venue_reset_password'], $_POST['user_login'] ) ) {
	$nonce_value = wc_get_var( $_REQUEST['venue-lost-password-nonce'], '');
	if ( wp_verify_nonce( $nonce_value, 'lost_password' ) ) {
		$success = WC_Shortcode_My_Account::retrieve_password();
	
		if ( $success ) {
			$display_form = false;
			$user_msg = '<p>A password reset email has been sent to the email address on file for your account, but may take several minutes to show up in your inbox.</p><p>Please wait at least 10 minutes before attempting another reset.</p>';
		} else {
			$display_form = true;
			$user_msg = "<p>Unable to identify User Name or Email. <br>Please try again or contact info@thetaste.ie</p>";
			$alert_type = 'alert-danger';
		}
	}
}

if ( isset( $_GET['key'], $_GET['id'] ) ) {
	$user_id = absint( $_GET['id'] );
	$rp_key = $_GET['key'];

	$logged_in_user_id = get_current_user_id();
	if ( $logged_in_user_id && $logged_in_user_id !== $user_id ) {
		$display_form = false;
		$user_msg = '<p>This password reset key is for a different user account. Please log out and try again.</p>';
		$alert_type = 'alert-danger';
	} else {
		$userdata               = get_userdata( $user_id );
		$rp_login               = $userdata ? $userdata->user_login : '';
		$user                   = check_password_reset_key( $rp_key, $rp_login );
		
		if ( is_wp_error( $user ) ) {
			$alert_type = 'alert-danger';
			$user_msg =  '<p>This key is invalid or has already been used. Please reset your password again if needed.</p>';
			$alert_type = 'alert-danger';
		} else {
			$form_type = "reset";
		}
	}
}

// check for password update 
if (isset($_POST['venue_reset_password_submit'], $_POST['reset_password_venue_id'])) {
	$nonce_value = wc_get_var( $_REQUEST['venue-reset-password-nonce'], '');
	$venue_id = $_POST['reset_password_venue_id'];
	if ( wp_verify_nonce( $nonce_value, 'reset_password' ) ) {
		$update_result = reset_venue_password($venue_id);
	
		if ($update_result['error']) {
			$user_msg =  'Error updating password. ' . $update_result['error'];
			$alert_type = 'alert-danger';
		} else {
			wp_safe_redirect( add_query_arg( 'password-reset', 'true', get_site_url(null, '/venue-portal')));
		}
	} else {
		$user_msg = "Invalid Security check.  Please try again.";
		$alert_type = 'alert-danger';
	}
}

?>

<body>
  <?php venue_navbar([]);  ?>
  <div class="container-fluid h-100">
    <div class="row h-100">
      <div class="col-xl-4" id="left">
        <div id="thetaste-logo-link" class="text-center">
          <h1 class="heading">WELCOME TO IRELAND’S AWARD WINNING</h1>
          <h2 class="heading2">FOOD, DRINK & TRAVEL DIGITAL MAGAZINE</h2>
        </div>
      </div>
      <div class="col-xl-8" id="right">
        <div class="col-md-8 col-xl-9 bg-white shadow-lg mt-3 mb-3 mt-sm-3 login_div text-center">
          <i class="fas fa-user"></i>
          <?php 
						if ($display_form) {
							venue_display_form($form_type, $user_msg, $user_id);
						} else {
							echo $user_msg;
						}
					?>
        </div>
      </div>
    </div>
  </div>
	<script type="text/javascript" src= "<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/js/thetaste-change-password.js"></script>
</body>

</html>
		 
<?php 

function venue_display_form($form_type, $user_msg, $user_id) {
	if ( "reset" === $form_type ) {
		venue_password_reset_form($user_id);
	} else {
		venue_lost_password_form($user_msg);
	}
}

function venue_lost_password_form($user_msg) {
	?>
		<form method="post" class="woocommerce-ResetPassword lost_reset_password" >

			<?php echo $user_msg ?>
			
			<div class="form-group row">
				<label for="user_login" class="col-sm-4 col-form-label">Username or email:</label>
				<div class="col-sm-8">
					<input type="text" class="form-control" name="user_login" id="user_login" autocomplete="username" required >
				</div>
			</div>

			<div class="form-group row login-submit d-flex justify-content-center">
				<input type="hidden" name="venue_reset_password" value="true" />
				<input type="submit" name="wp-submit" id="wp-submit" class="btn button-primary py-2 px-5" value="Reset Password" />
			</div>
			<?php wp_nonce_field( 'lost_password', 'venue-lost-password-nonce' ); ?>
		</form>
	<?php
}

function venue_password_reset_form($user_id) {
	$action = get_site_url(null, '/venue-lost-password');
	?>
	<form method="post" action="<?php echo $action ?>" id="venue-password-change-form" class="venue-change-password-form form-horizontal my-3">
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
				<button type="submit" name="venue_reset_password_submit" class="btn btn-secondary" id="venue_reset_password_submit">
					Update Password
				</button>
			</div>
			<?php wp_nonce_field( 'reset_password', 'venue-reset-password-nonce' ); ?>
				<input type="hidden" name="reset_password_venue_id" value="<?php echo $user_id ?>" />
			<div class="col-sm-6">
				<span id="venue-pass-match-error-msg" style="color:red;"></span>
			</div>
		</div>
	</form>
	<?php
}

	
function reset_venue_password($venue_id) {
	global $wpdb;

	$pass1                = ! empty( $_POST['password_1'] ) ? $_POST['password_1'] : ''; 
	$pass2                = ! empty( $_POST['password_2'] ) ? $_POST['password_2'] : ''; 

	$current_user       = get_user_by( 'id', $venue_id );

	$user     = new stdClass();
	$user->user_pass = $pass1;
	$user->ID = $venue_id;

	$upd_result = wp_update_user( $user );
	if (is_wp_error($upd_result)) {
		return array('error' => "unknown error updating password.");
	}

	return array('success' => true);

}

