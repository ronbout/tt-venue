<?php 
defined('ABSPATH') or die('Direct script access disallowed.');

function display_venue_fields_user_forms($role, $name, $desc, $address1, $address2, $city, $postcode, $state,
																				 $country, $phone, $type, $pct, $paid, $renewal, $cost, $use_new, $creditor) {
			?>
			<div id="user-venue-fields" style="display: <?php echo ('venue' === $role) ? 'block' : 'none' ?>">
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
								aria-required="true" autocapitalize="none" autocorrect="off"
								required maxlength="80"
								class="regular-text" />
						</td>
					</tr>
					<tr>
						<th><label for="venue-desc"><?php esc_html_e('Description'); ?></label>
						</th>
						<td>
							<input type="text" id="venue-desc" name="venue_desc"
								value="<?php echo esc_attr($desc); ?>" maxlength="255"
								class="regular-text" />
						</td>
					</tr>
					<tr>
						<th><label for="venue-address1"><?php esc_html_e('Address Line 1'); ?></label>
						</th>
						<td>
							<input type="text" id="venue-address1" name="venue_address1"
								value="<?php echo esc_attr($address1); ?>" maxlength="120"
								class="regular-text" />
						</td>
					</tr>
					<tr>
						<th><label for="venue-address2"><?php esc_html_e('Address Line 2'); ?></label>
						</th>
						<td>
							<input type="text" id="venue-address2" name="venue_address2"
								value="<?php echo esc_attr($address2); ?>"  maxlength="120"
								class="regular-text" />
						</td>
					</tr>
					<tr>
						<th><label for="venue-city"><?php esc_html_e('City'); ?></label>
						</th>
						<td>
							<input type="text" id="venue-city" name="venue_city"
								value="<?php echo esc_attr($city); ?>" maxlength="100"
								class="regular-text" />
						</td>
					</tr>
					<tr>
						<th><label for="venue-postcode"><?php esc_html_e('Postcode / ZIP'); ?></label>
						</th>
						<td>
							<input type="text" id="venue-postcode" name="venue_postcode"
								value="<?php echo esc_attr($postcode); ?>" maxlength="20"
								class="regular-text" />
						</td>
					</tr>
					<tr>
						<th><label for="venue-country"><?php esc_html_e('Country / Region'); ?></label>
						</th>
						<td>
							<input type="text" id="venue-country" name="venue_country"
								value="<?php echo esc_attr($country); ?>" maxlength="100"
								class="regular-text" />
						</td>
					</tr>
					<tr>
						<th><label for="venue-state"><?php esc_html_e('State / County'); ?></label>
						</th>
						<td>
							<input type="text" id="venue-state" name="venue_state"
								value="<?php echo esc_attr($state); ?>" maxlength="100"
								class="regular-text" />
						</td>
					</tr>
					<tr>
						<th><label for="venue-phone"><?php esc_html_e('Phone'); ?></label>
						</th>
						<td>
							<input type="text" id="venue-phone" name="venue_phone"
								value="<?php echo esc_attr($phone); ?>" maxlength="40"
								class="regular-text" />
						</td>
					</tr>
					<tr>
						<th><label for="venue-type"><?php esc_html_e('Venue Type'); ?></label>
						</th>
						<td>
							<select id="venue-type" name="venue_type">
								<option value="none" disabled <?php echo ('none' === $type) ? 'selected' : ''?>>Select Venue Type</option>
								<option value="Restaurant" <?php echo ('Restaurant' === $type) ? 'selected' : ''?>>Restaurant</option>
								<option value="Hotel" <?php echo ('Hotel' === $type) ? 'selected' : ''?>>Hotel</option>
								<option value="Bar" <?php echo ('Bar' === $type) ? 'selected' : ''?>>Bar</option>
								<option value="Product" <?php echo ('Product' === $type) ? 'selected' : ''?>>Product</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="venue-creditor"><?php esc_html_e('Venue Creditor'); ?></label>
						</th>
						<td>
							<select id="venue-creditor" name="venue_creditor">
								<option value="0" <?php echo ('0' === $creditor) ? 'selected' : ''?>>None</option>
								<?php echo display_creditor_options($creditor) ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="venue-pct"><?php esc_html_e('Voucher Pct'); ?></label>
						</th>
						<td>
							<input type="number" min="0" max="100" id="venue-pct" name="venue_pct"
								value="<?php echo $pct ?>"
							/>
						</td>
					</tr>
					<tr>
						<th><label for="venue-paid"><?php esc_html_e('Paid Membership'); ?></label>
						</th>
						<td>
							<input type="checkbox" id="venue-paid" name="venue_paid"
								<?php echo $paid ? 'checked' : '' ?>
							 />
						</td>
					</tr>
					<tr>
						<th><label for="venue-renewal-date"><?php esc_html_e('Membership Renewal Date'); ?></label>
						</th>
						<td>
							<input type="date" id="venue-renewal-date" name="venue_renewal_date"
								value="<?php echo ($renewal); ?>"
								/>
						</td>
					</tr>
					<tr>
						<th><label for="venue-cost"><?php esc_html_e('Membership Cost'); ?></label>
						</th>
						<td>
							<input type="number" id="venue-cost" min="0" max="1000" name="venue_cost"
								value="<?php echo $cost ?>" 
							/>
						</td>
					</tr>
					<tr>
						<th><label for="venue_use_new"><?php esc_html_e('Use New Campaign Manager'); ?></label>
						</th>
						<td>
							<input type="checkbox" id="venue-use-new" name="venue_use_new"
								<?php echo $use_new ? 'checked' : '' ?>
							 />
						</td>
					</tr>
				</table>
			</div>
			<?php
		}

		function display_creditor_options($creditor=0) {
			global $wpdb;

			$return_options = "";
			$sql = "
				SELECT creditor_id, creditor_name 
				FROM {$wpdb->prefix}taste_venue_creditor
				ORDER BY creditor_name ASC
			";

			$creditor_rows = $wpdb->get_results($sql, ARRAY_A);
			if (!$creditor_rows) {
				return $return_options;
			}
			foreach($creditor_rows as $creditor_row) {
				$creditor_id = $creditor_row['creditor_id'];
				$creditor_name = $creditor_row['creditor_name'];
				$return_options .= "
				<option value='$creditor_id' " . (($creditor_id == $creditor) ? 'selected' : '' ). ">$creditor_name</option>
				";
			}
			return $return_options;
		}