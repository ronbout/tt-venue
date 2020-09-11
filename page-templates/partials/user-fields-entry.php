<?php 
defined('ABSPATH') or die('Direct script access disallowed.');

function display_venue_fields_user_forms($role, $name, $desc, $city, $type, $pct, $paid, $renewal, $cost) {
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
							<select id="venue-type" name="venue_type">
								<option value="none" disabled <?php echo ('none' === $type) ? 'selected' : ''?>>Select Venue Type</option>
								<option value="Restaurant" <?php echo ('Restaurant' === $type) ? 'selected' : ''?>>Restaurant</option>
								<option value="Pub" <?php echo ('Pub' === $type) ? 'selected' : ''?>>Pub</option>
								<option value="Hotel" <?php echo ('Hotel' === $type) ? 'selected' : ''?>>Hotel</option>
								<option value="Cafe" <?php echo ('Cafe' === $type) ? 'selected' : ''?>>Cafe</option>
								<option value="Other" <?php echo ('Other' === $type) ? 'selected' : ''?>>Other</option>
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
				</table>
			</div>
			<?php
		}