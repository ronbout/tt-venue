<?php
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

    public function taste_user_register($user_id)
    {
				// get values and write to taste_venue table

        // if (!empty($_POST['candidate_id'])) {
        //     update_user_meta($user_id, 'candidate_id', intval($_POST['candidate_id']));
        // }
    }

    /**
     * Back end registration.
     *
     * @param mixed $operation
     */
    public function taste_admin_registration_form($operation)
    {
			if ('add-new-user' !== $operation) {
					// $operation may also be 'add-existing-user'
					return;
			}
			$name = !empty($_POST['venue_name']) ? $_POST['venue_name'] : '';
			$role = !empty($_POST['role']) ? $_POST['role'] : '';
			$this->display_venue_fields_user_forms($role, $name);
		}
		
		    /**
     * Back end registration.
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

		/*
    public function taste_show_extra_profile_fields($user)
    {
        $candid = get_user_meta($user->ID, 'candidate_id', true); ?>
<h3><?php esc_html_e('Candidate Information', 'taste'); ?>
</h3>

<table class="form-table">
	<tr>
		<th><label for="candidate_id"><?php esc_html_e('Candidate Id', 'taste'); ?></label>
		</th>
		<td> <input type="number" min="1" max="99999" step="1" id="candidate_id" name="candidate_id"
				value="<?php echo esc_attr($candid); ?>"
				class="regular-text" />
			<?php
        // echo esc_html(get_user_meta('candidate_id', $user->ID));
        ?>
		</td>
	</tr>
</table>
<?php
		}
		*/

    /*--------------------------------------------*
     * Private Functions
     *--------------------------------------------*/

    private function modify_user_fields()
    {
			// hooks for creating new user in admin
			add_action('user_new_form', [$this, 'taste_admin_registration_form']);
			add_action('edit_user_created_user', [$this, 'taste_user_register']);
			add_action('user_profile_update_errors', [$this, 'taste_user_validation'], 10, 3);
			
			// add_action('personal_options_update', [$this, 'taste_user_register']);
			// add_action('edit_user_profile_update', [$this, 'taste_user_register']);
			// add_action('show_user_profile', [$this, 'taste_show_extra_profile_fields']);
			// add_action('edit_user_profile', [$this, 'taste_show_extra_profile_fields']);
    }

    // private function create_candidate($user, $user_id)
    // {
    //     $person_info = [
    //         'givenName' => $user['first_name'],
    //         'familyName' => $user['last_name'],
		// 				'email1' => $user['user_email']
    //     ];

    //     $person = $this->create_person_post($person_info);
    //     if (property_exists($person, 'error') && $person->error) {
    //         //show_message($person->message);

    //         return false;
    //     }

    //     $cand_resp = $this->create_candidate_post($person->id, $user_id);
    //     if (property_exists($cand_resp, 'error') && $cand_resp->error) {
    //         //show_message($cand_resp->message);
    //     } else {
    //         return $cand_resp->id;
    //     }
    // }

    // private function create_person_post($person_info)
    // {
    //     return fetch_post('persons', '', $person_info);
    // }

    // private function create_candidate_post($person_id, $user_id)
    // {
    //     $candidate_info = ['personId' => $person_id, 'userId' => $user_id]; 

    //     return fetch_post('candidates', '', $candidate_info);
		// }
		
		private function display_venue_fields_user_forms($role, $name, $desc='', $city='', $venue_type='Other', $pct='10') {
			?>
			<div id="new-user-venue-fields" style="display: <?php echo ('venue' === $role) ? 'block' : 'none' ?>">
				<h3><?php esc_html_e('Venue Information'); ?>
				</h3>

				<table class="form-table">
					<tr>
						<th>
							<label for="venue-name">
									<?php esc_html_e('Venue Name'); ?>
									<span class="description">(required)</span>
							</label>
						</th>
						<td>
							<input type="text" id="venue-name" name="venue_name"
								value="<?php echo esc_attr($name); ?>"
								class="regular-text" />
						</td>
					</tr>
					<tr>
						<th><label for="venue-desc"><?php esc_html_e('Description'); ?></label>
						</th>
						<td>
							<input type="text" id="venue-desc" name="venue_desc"
								value="<?php echo esc_attr($desc); ?>"
								class="regular-text" />
						</td>
					</tr>
					<tr>
						<th><label for="venue-city"><?php esc_html_e('City'); ?></label>
						</th>
						<td>
							<input type="text" id="venue-city" name="venue_city"
								value="<?php echo esc_attr($city); ?>"
								class="regular-text" />
						</td>
					</tr>
					<tr>
						<th><label for="venue-type"><?php esc_html_e('Venue Type'); ?></label>
						</th>
						<td>
							<select id="venue_type" name="venue-type">
								<option value="">Select Venue Type</option>
								<option value="Restaurant">Restaurant</option>
								<option value="Pub">Pub</option>
								<option value="Hotel">Hotel</option>
								<option value="Cafe">Cafe</option>
								<option value="Other">Other</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="venue-pct"><?php esc_html_e('Voucher Pct'); ?></label>
						</th>
						<td>
							<input type="number" min=0 max=100 id="venue-pct" name="venue_pct"
								value=<?php echo $pct ?>
							/>
						</td>
					</tr>
				</table>
			</div>
			<?php
		}

    // end class
}
