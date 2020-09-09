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
			$city = !empty($_POST['venue_city']) ? $_POST['venue_city'] : '';
			$type = !empty($_POST['venue_type']) ? $_POST['venue_type'] : 'none';
			$pct = !empty($_POST['venue_pct']) ? $_POST['venue_pct'] : '10';
			require_once TASTE_PLUGIN_PATH . 'page-templates/partials/user-fields-entry.php';
			display_venue_fields_user_forms($role, $name, $desc, $city, $type, $pct);
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
			if ( 'venue' === $user->role && empty( $_POST['venue_name'] ) ) {
				$errors->add( 'required_venue_name_error', __( '<strong>ERROR</strong>: Venue Name is a required field.', 'crf' ) );
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

				if ('venue' === $_POST['role']) {
					$name = stripslashes($_POST['venue_name']);
					$desc = !empty($_POST['venue_desc']) ? stripslashes($_POST['venue_desc']) : 'NULL';
					$city = !empty($_POST['venue_city']) ? stripslashes($_POST['venue_city']) : 'NULL';
					$type = !isset($_POST['venue_type']) || empty($_POST['venue_type'])? 'NULL' : stripslashes($_POST['venue_type']);
					$pct = !empty($_POST['venue_pct']) ? $_POST['venue_pct'] : 'NULLO';

					$sql = "
						INSERT INTO {$wpdb->prefix}taste_venue
						(venue_id, name, description, city, venue_type, voucher_pct)
						VALUES (%d, %s, %s, %s, %s, %f)
						ON DUPLICATE KEY UPDATE
							name = %s,
							description = %s,
							city = %s,
							venue_type = %s,
							voucher_pct = %f
					";

					$field_list = array($user_id, $name, $desc, $city, $type, $pct, $name, $desc, $city, $type, $pct);

					$rows_affected = $wpdb->query(
						$wpdb->prepare($sql, $field_list)
					);

				}
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
			$city = '';
			$type = 'none';
			$pct = 10;

			// if POST has venue data, it takes precedence as it means the user 
			// created info but could not save due to error (missing venue name?)
			if (isset($_POST['venue_name'])) {
				$name = $_POST['venue_name'];
				$desc = $_POST['venue_desc'];
				$city = $_POST['venue_city'];
				$type = !isset($_POST['venue_type']) || empty($_POST['venue_type'])? 'none' : $_POST['venue_type'];
				$pct = $_POST['venue_pct'];
			} elseif ('venue' === $role) {
					// we came in with the user role = venue and should have db row
					$sql = "
					SELECT name, description, city, venue_type, voucher_pct
					FROM {$wpdb->prefix}taste_venue
					WHERE venue_id = %d
				";
				$venue_row = $wpdb->get_results($wpdb->prepare($sql, $user_id), ARRAY_A);
				if (count($venue_row)) {
					$name = $venue_row[0]['name'];
					$desc = $venue_row[0]['description'];
					$city = $venue_row[0]['city'];
					$type = ($venue_row[0]['venue_type']) ? $venue_row[0]['venue_type'] : 'none';
					$pct  = $venue_row[0]['voucher_pct'];
				}
			}

			require_once TASTE_PLUGIN_PATH . 'page-templates/partials/user-fields-entry.php';
			display_venue_fields_user_forms($role, $name, $desc, $city, $type, $pct);
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
