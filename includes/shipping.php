<?php

function atcf_shipping_address_fields() {
	ob_start(); ?>
	<fieldset id="edd_cc_address" class="cc-address">
		<legend><?php _e( 'Shipping Address', 'atcf' ); ?></legend>

		<p>
			<input type="text" name="shipping_address" class="card-address edd-input required" placeholder="<?php _e('Address line 1', 'edd'); ?>"/>
			<label class="edd-label"><?php _e('Shipping Address', 'edd'); ?></label>
		</p>
		<p>
			<input type="text" name="shipping_address_2" class="card-address-2 edd-input required" placeholder="<?php _e('Address line 2', 'edd'); ?>"/>
			<label class="edd-label"><?php _e('Shipping Address Line 2', 'edd'); ?></label>
		</p>
		<p>
			<input type="text" name="shipping_city" class="card-city edd-input required" placeholder="<?php _e('City', 'edd'); ?>"/>
			<label class="edd-label"><?php _e('Shipping City', 'edd'); ?></label>
		</p>
		<p>
			<select name="shipping_country" class="shipping-country edd-select required">
				<?php 
				$countries = edd_get_country_list();
				foreach( $countries as $country_code => $country ) {
				  echo '<option value="' . $country_code . '">' . $country . '</option>';
				}
				?>
			</select>
			<label class="edd-label"><?php _e('Shipping Country', 'edd'); ?></label>
		</p>
		<p>
			<input type="text" size="6" name="shipping_state_other" id="shipping_state_other" class="card-state edd-input" placeholder="<?php _e('State / Province', 'edd'); ?>" style="display:none;"/>
            <select name="shipping_state_us" id="shipping_state_us" class="card-state edd-select required">
                <?php
                    $states = edd_get_states_list();
                    foreach( $states as $state_code => $state ) {
                        echo '<option value="' . $state_code . '">' . $state . '</option>';
                    }
                ?>
            </select>
            <select name="shipping_state_ca" id="shipping_state_ca" class="card-state edd-select required" style="display: none;">
                <?php
                    $provinces = edd_get_provinces_list();
                    foreach( $provinces as $province_code => $province ) {
                        echo '<option value="' . $province_code . '">' . $province . '</option>';
                    }
                ?>
            </select>
			<label class="edd-label"><?php _e('Shipping State / Province', 'edd'); ?></label>
		</p>
		<p>
			<input type="text" size="4" name="shipping_zip" class="card-zip edd-input required" placeholder="<?php _e('Zip / Postal code', 'edd'); ?>"/>
			<label class="edd-label"><?php _e('Shipping Zip / Postal Code', 'edd'); ?></label>
		</p>
	</fieldset>
	<?php
	echo ob_get_clean();
}
add_action('edd_purchase_form_user_info', 'atcf_shipping_address_fields');

// check for errors with out custom fields
function pippin_edd_validate_custom_fields($valid_data, $data) {
	if(!isset($data['edd_phone']) || $data['edd_phone'] == '') {
		// check for a phone number

		edd_set_error( 'invalid_phone', __('Please provide your phone number.', 'pippin_edd') );
	}
}
add_action('edd_checkout_error_checks', 'pippin_edd_validate_custom_fields', 10, 2);

// store the custom field data in the payment meta
function pippin_edd_store_custom_fields($payment_meta) {
	$payment_meta['phone'] = isset($_POST['edd_phone']) ? $_POST['edd_phone'] : '';

	return $payment_meta;
}
add_filter('edd_payment_meta', 'pippin_edd_store_custom_fields');

// show the custom fields in the "View Order Details" popup
function pippin_edd_purchase_details($payment_meta, $user_info) {
	$phone = isset($payment_meta['phone']) ? $payment_meta['phone'] : 'none';
	?>
	<li><?php echo __('Phone:', 'pippin') . ' ' . $phone; ?></li>

	<?php
}
add_action('edd_payment_personal_details_list', 'pippin_edd_purchase_details', 10, 2);