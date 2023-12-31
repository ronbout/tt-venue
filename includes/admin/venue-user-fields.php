<?php

defined('ABSPATH') or die('Direct script access disallowed.');
/**
 * venue-user-fields.php 
 * add venue fields to various user screens 
 * and update the appropriate tables 
 * use js (thetaste-admin.js) to hide/show venue fields based on role
 *
 * */
class VenueUserFields
{
    /*--------------------------------------------*
     * Attributes
     *--------------------------------------------*/

    /** Refers to a single instance of this class. */
    private static $instance = null;

    /**
     * Initializes the plugin by setting localization, filters, and administration functions.
     */
    private function __construct()
    {
        $this->modify_user_fields();
    }

    // end constructor

    /*--------------------------------------------*
     * Constructor
     *--------------------------------------------*/

    /**
     * Creates or returns an instance of this class.
     *
     * @return Foo A single instance of this class.
     */
    public static function get_instance()
    {
        if (null == self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    // end get_instance;

    /*--------------------------------------------*
     *  Public Functions
     *--------------------------------------------*/
		
    /**
     * Back end registration.
     *
     * @param mixed $operation
     */
    public function taste_admin_registration_form($operation)
    {
			if ('add-new-user' !== $operation) {
					// avoid multisite 'add-existing-user'
					return;
			}
			
			$role = !empty($_POST['role']) ? $_POST['role'] : '';
			$name = !empty($_POST['venue_name']) ? $_POST['venue_name'] : '';
			$desc = !empty($_POST['venue_desc']) ? $_POST['venue_desc'] : '';
			$address1 = !empty($_POST['venue_address1']) ? $_POST['venue_address1'] : '';
			$address2 = !empty($_POST['venue_address2']) ? $_POST['venue_address2'] : '';
			$city = !empty($_POST['venue_city']) ? $_POST['venue_city'] : '';
			$postcode = !empty($_POST['venue_postcode']) ? $_POST['venue_postcode'] : '';
			$state = !empty($_POST['venue_state']) ? $_POST['venue_state'] : '';
			$country = !empty($_POST['venue_country']) ? $_POST['venue_country'] : '';
			$phone = !empty($_POST['venue_phone']) ? $_POST['venue_phone'] : '';
			$type = !empty($_POST['venue_type']) ? $_POST['venue_type'] : 'none';
			$creditor = !empty($_POST['venue_creditor']) ? $_POST['venue_creditor'] : '0';
			$pct  = !empty($_POST['venue_pct']) ? $_POST['venue_pct'] : 10;
			$paid = !empty($_POST['venue_paid']) ? $_POST['venue_paid'] : 0;
			$renewal  = !empty($_POST['venue_renewal_date']) ? $_POST['venue_renewal_date'] : '';
			$cost  = !empty($_POST['venue_cost']) ? $_POST['venue_cost'] : '';
			require_once TASTE_PLUGIN_PATH . 'page-templates/partials/user-fields-entry.php';
			display_venue_fields_user_forms($role, $name, $desc, $address1, $address2, $city, $postcode, $state,
																			$country, $phone, $type, $pct, $paid, $renewal, $cost, $creditor, null);
		}
		
		/**
     * Back end validation.
     *
     * @param mixed $errors
     * @param mixed $update
     * @param mixed $user
     */
		public function taste_user_validation($errors, $update, $user)
    {
			// echo '<h1><pre>USER: ', print_r($user), '</pre></h1>';
			// echo '<h1><pre>POST: ', print_r($_POST), '</pre></h1>';
			if ( 'venue' === $user->role ) {
				if ( empty( $_POST['venue_name'] ))  {
					$errors->add( 'required_venue_name_error', __( '<strong>ERROR</strong>: Venue Name is a required field.', 'crf' ) );
				}

				/**
				 * IF PAID MEMBER, renewal and cost are required
				 */

				if (empty($_POST['venue_renewal_date'])) {
					$date_test = false;
				} else {
					$date_test = strtotime($_POST['venue_renewal_date']) > 0 ? strtotime($_POST['venue_renewal_date']) : false;
				}
				if ( !empty($_POST['venue_paid']) && (!$date_test || empty($_POST['venue_cost']) )) {
					$errors->add( 'required_venue_member_error', __( '<strong>ERROR</strong>: Renewal Date and Membership Cost are required for Paid Member Venues', 'crf' ) );
				}
			}
    }

    /**
     * Back end update of venue fields.
     *
     * @param mixed $user_id
     */
    public function taste_user_register($user_id)
    {
			global $wpdb;
			// get values and write to taste_venue table if role is venue

			// echo '<h1><pre>', print_r($_POST), '</pre></h1>';
			// wp_die();

			if ('venue' === $_POST['role']) {

				// check if venue already exists to determine 
				// insert or  update method 

				$venue_table = "{$wpdb->prefix}taste_venue";
				$sql = "SELECT venue_id	FROM $venue_table	WHERE venue_id = %d	";
				$venue_row = $wpdb->get_results($wpdb->prepare($sql, $user_id));

				if (count($venue_row)) {
					$data = array();
					$format = array();
					$insert_flg = false;
					$where = array( 'venue_id' => $user_id);
				} else {
					$data = array('venue_id' => $user_id );
					$format = array('%d');
					$insert_flg = true;
				}

				$name = stripslashes($_POST['venue_name']);
				$desc = !empty($_POST['venue_desc']) ? stripslashes($_POST['venue_desc']) : NULL;
				$address1 = !empty($_POST['venue_address1']) ? stripslashes($_POST['venue_address1']) : NULL;
				$address2 = !empty($_POST['venue_address2']) ? stripslashes($_POST['venue_address2']) : NULL;
				$city = !empty($_POST['venue_city']) ? stripslashes($_POST['venue_city']) : NULL;
				$postcode = !empty($_POST['venue_postcode']) ? stripslashes($_POST['venue_postcode']) : NULL;
				$state = !empty($_POST['venue_state']) ? stripslashes($_POST['venue_state']) : NULL;
				$country = !empty($_POST['venue_country']) ? stripslashes($_POST['venue_country']) : NULL;
				$phone = !empty($_POST['venue_phone']) ? stripslashes($_POST['venue_phone']) : NULL;
				$type = !isset($_POST['venue_type']) || empty($_POST['venue_type'])? NULL : stripslashes($_POST['venue_type']);
				$creditor = !isset($_POST['venue_creditor']) || empty($_POST['venue_creditor'])? 0 : 
									absint( stripslashes($_POST['venue_creditor']));
				$pct = !empty($_POST['venue_pct']) ? $_POST['venue_pct'] : NULL;
				$paid = !empty($_POST['venue_paid']) && $_POST['venue_paid'] === "on" ? 1 : 0;
				$renewal = !empty($_POST['venue_renewal_date']) ? $_POST['venue_renewal_date'] : NULL;
				$cost  = !empty($_POST['venue_cost']) ? $_POST['venue_cost'] : NULL;

				if (0 == $creditor) {
					// must create a new creditor with the same name as venue name
					$creditor_id = $this->create_new_creditor($name);
				} else {
					$creditor_id = $creditor;
				}

				$data['name'] = $name;
				$data['description'] =  $desc;
				$data['address1'] = $address1;
				$data['address2'] = $address2;
				$data['city'] = $city;
				$data['postcode'] = $postcode;
				$data['state'] = $state;
				$data['country'] = $country;
				$data['phone'] = $phone;
				$data['venue_type'] = $type;
				$data['creditor_id'] = $creditor_id;
				$data['voucher_pct'] = $pct;
				$data['paid_member'] = $paid;
				$data['member_renewal_date'] = $renewal;
				$data['membership_cost'] = $cost;

				$format = array_merge($format, array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%f','%d','%s','%f'));

				if ($insert_flg) {
					$rows_affected = $wpdb->insert($venue_table, $data, $format);
				} else  {
					$rows_affected = $wpdb->update($venue_table, $data, $where, $format);
				}
			}
    }

		protected function create_new_creditor($name) {
			global $wpdb;

			$creditor_table = "{$wpdb->prefix}taste_venue_creditor";
			$data = array('creditor_name' => $name);
			$format = array('%s');
			$rows_affected = $wpdb->insert($creditor_table, $data, $format);
			return $wpdb->insert_id;
		}
		
    public function taste_show_venue_fields($user)
    {
			global $wpdb;
			
			// echo '<h1><pre>USER: ', print_r($user), '</pre></h1>';
			// echo '<h1><pre>POST: ', print_r($_POST), '</pre></h1>';
			$user_id = $user->ID;
			$role = $user->roles[0];

			// set up defaults
			$name = '';
			$desc = '';
			$address1 = '';
			$address2 = '';
			$city = '';
			$postcode = '';
			$state = '';
			$country = '';
			$phone = '';
			$type = 'none';
			$pct = 10;
			$paid = 0;
			$renewal  = '';
			$cost  = '';

			// if POST has venue data, it takes precedence as it means the user 
			// created info but could not save due to error (missing venue name?)
			if (isset($_POST['venue_name'])) {
				$name = $_POST['venue_name'];
				$desc = $_POST['venue_desc'];
				$address1 = $_POST['venue_address1'];
				$address2 = $_POST['venue_address2'];
				$city = $_POST['venue_city'];
				$postcode = $_POST['venue_postcode'];
				$state = $_POST['venue_state'];
				$country = $_POST['venue_country'];
				$phone = $_POST['venue_phone'];
				$type = !isset($_POST['venue_type']) || empty($_POST['venue_type'])? 'none' : $_POST['venue_type'];
				$creditor = !isset($_POST['venue_creditor']) || empty($_POST['venue_creditor'])? '0' : $_POST['venue_creditor'];
				$pct = $_POST['venue_pct'];
				$paid = !empty($_POST['venue_paid']) ? $_POST['venue_paid'] : 0;
				$renewal  = !empty($_POST['venue_renewal_date']) ? $_POST['venue_renewal_date'] : '';
				$cost  = !empty($_POST['venue_cost']) ? $_POST['venue_cost'] : '';
			} elseif ('venue' === $role) {
					// we came in with the user role = venue and should have db row
					$sql = "
					SELECT name, description, address1, address2, city, postcode, state, country, phone, venue_type, voucher_pct, 
								 paid_member,	DATE(member_renewal_date) as member_renewal_date, membership_cost, creditor_id
					FROM {$wpdb->prefix}taste_venue
					WHERE venue_id = %d
				";
				$venue_row = $wpdb->get_results($wpdb->prepare($sql, $user_id), ARRAY_A);
				if (count($venue_row)) {
					$name = $venue_row[0]['name'];
					$desc = $venue_row[0]['description'];
					$address1 = $venue_row[0]['address1'];
					$address2 = $venue_row[0]['address2'];
					$city = $venue_row[0]['city'];
					$postcode = $venue_row[0]['postcode'];
					$state = $venue_row[0]['state'];
					$country = $venue_row[0]['country'];
					$phone = $venue_row[0]['phone'];
					$type = ($venue_row[0]['venue_type']) ? $venue_row[0]['venue_type'] : 'none';
					$creditor = ($venue_row[0]['creditor_id']) ? $venue_row[0]['creditor_id'] : '0';
					$pct  = $venue_row[0]['voucher_pct'];
					$paid  = $venue_row[0]['paid_member'];
					$renewal  = ($venue_row[0]['member_renewal_date']) ? $venue_row[0]['member_renewal_date'] : '';
					$cost  = ($venue_row[0]['membership_cost']) ? $venue_row[0]['membership_cost'] : '';
				}
			}

			require_once TASTE_PLUGIN_PATH . 'page-templates/partials/user-fields-entry.php';
			display_venue_fields_user_forms($role, $name, $desc, $address1, $address2, $city, $postcode, $state,
																			$country, $phone, $type, $pct, $paid, $renewal, $cost, $creditor, $user_id);
		}
		

    /*--------------------------------------------*
     * Private Functions
     *--------------------------------------------*/

    private function modify_user_fields()
    {
			// hooks for creating new user in admin
			add_action('user_new_form', [$this, 'taste_admin_registration_form']);
			add_action('user_profile_update_errors', [$this, 'taste_user_validation'], 10, 3);
			add_action('edit_user_created_user', [$this, 'taste_user_register']);
			
			// add_action('personal_options_update', [$this, 'taste_user_register']);
			 add_action('edit_user_profile_update', [$this, 'taste_user_register']);
			// add_action('show_user_profile', [$this, 'taste_show_extra_profile_fields']);
			 add_action('edit_user_profile', [$this, 'taste_show_venue_fields']);
		}
		
    // end class
}
