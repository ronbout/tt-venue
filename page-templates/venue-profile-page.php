<?php
/*
Template Name: Venue Profile Page
*/

/**
 * 	The profile page for venues to update their info
 *  Date:  9/15/2020
 * 	Author: Ron Boutilier
 *
 * Updates Author: Branislav Buna
 * Date: 17/12/2020
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
$venue_table = $wpdb->prefix."taste_venue";

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
?>


<?php venue_navbar($nav_links, true, $venue_name);  ?>
<div class="container-fluid h-100" id="profile_container">
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
                <h2 class="col-heading_profile"><?php echo $venue_name; ?></h2>
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
            <?php display_venue_form($venue_info, $venue_name); ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script type="text/javascript" src= "<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/js/thetaste-profilePage.js"></script>
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
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="description" class="col control-label"><?php esc_html_e('Description'); ?></label>
                    <div class="col-sm-12">
                        <input type="text" id="description" name="description"
                            value="<?php echo esc_attr($desc); ?>" maxlength="255"
                            class="form-control"  />
                    </div>
                </div>
                <div class="form-group">
                    <label for=address1" class="col control-label"><?php esc_html_e('Address Line 1'); ?></label>
                    <div class="col-sm-12">
                        <input type="text" id="address1" name="address1"
                            value="<?php echo esc_attr($address1); ?>" maxlength="120"
                            class="form-control"  />
                    </div>
                </div>
                <div class="form-group">
                    <label for="address2" class="col control-label"><?php esc_html_e('Address Line 2'); ?></label>
                    <div class="col-sm-12">
                        <input type="text" id="address2" name="address2"
                            value="<?php echo esc_attr($address2); ?>" maxlength="120"
                            class="form-control"  />
                    </div>
                </div>
                <div class="form-group">
                    <label for="city" class="col control-label"><?php esc_html_e('City'); ?></label>
                    <div class="col-sm-12">
                        <input type="text" id="city" name="city"
                            value="<?php echo esc_attr($city); ?>" maxlength="100"
                            class="form-control"  />
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="postcode" class="col control-label"><?php esc_html_e('Postcode / ZIP'); ?></label>
                    <div class="col-sm-12">
                    <input type="text" id="postcode" name="postcode" maxlength="20"
                        value="<?php echo esc_attr($postcode); ?>"
                            class="form-control"  />
                    </div>
                </div>
                <div class="form-group">
                    <label for="country" class="col control-label"><?php esc_html_e('Country / Region'); ?></label>
                    <div class="col-sm-12">
                        <input type="text" id="country" name="country"
                            value="<?php echo esc_attr($country); ?>" maxlength="100"
                            class="form-control"  />
                    </div>
                </div>
                <div class="form-group">
                    <label for="state" class="col control-label"><?php esc_html_e('State / County'); ?></label>
                    <div class="col-sm-12">
                        <input type="text" id="state" name="state"
                            value="<?php echo esc_attr($state); ?>" maxlength="100"
                            class="form-control"  />
                    </div>
                </div>
                <div class="form-group">
                    <label for="phone" class="col control-label"><?php esc_html_e('Phone'); ?></label>
                    <div class="col-sm-12">
                        <input type="text" id="phone" name="phone"
                            value="<?php echo esc_attr($phone); ?>" maxlength="100"
                            class="form-control"  />
                    </div>
               </div>
            </div>
        </div>
		<div class="form-group">
    	<div class="col-sm-offset-2 col-sm-10">
				<button type="submit" name="venue_profile_form_submit" class="btn btn-secondary" id="campaing_manager1">Update details</button>
			</div>
		</div>
    </form>
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
