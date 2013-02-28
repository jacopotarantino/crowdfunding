<?php

function atcf_shipping_address_fields() {
	ob_start(); ?>
	<script>
		jQuery(document).ready(function($) {
			$( 'body' ).change( 'select[name=shipping_country]', function() {
				if( $('select[name=shipping_country]').val() == 'US') {
					$('#shipping_state_other').css('display', 'none');
					$('#shipping_state_us').css('display', '');
					$('#shipping_state_ca').css('display', 'none');
				} else if( $('select[name=shipping_country]').val() == 'CA') {
					$('#shipping_state_other').css('display', 'none');
					$('#shipping_state_us').css('display', 'none');
					$('#shipping_state_ca').css('display', '');
				} else {
					$('#shipping_state_other').css('display', '');
					$('#shipping_state_us').css('display', 'none');
					$('#shipping_state_ca').css('display', 'none');
				}
			});
		});
    </script>

	<fieldset id="atcf_shipping_address" class="atcf-shipping-address">
		<legend><?php _e( 'Shipping Address', 'atcf' ); ?></legend>

		<p>
			<input type="text" name="shipping_address" class="shipping-address edd-input required" placeholder="<?php _e('Address line 1', 'edd'); ?>"/>
			<label class="edd-label"><?php _e('Shipping Address', 'edd'); ?></label>
		</p>
		<p>
			<input type="text" name="shipping_address_2" class="shipping-address-2 edd-input required" placeholder="<?php _e('Address line 2', 'edd'); ?>"/>
			<label class="edd-label"><?php _e('Shipping Address Line 2', 'edd'); ?></label>
		</p>
		<p>
			<input type="text" name="shipping_city" class="shipping-city edd-input required" placeholder="<?php _e('City', 'edd'); ?>"/>
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
			<input type="text" size="6" name="shipping_state_other" id="shipping_state_other" class="shipping-state edd-input" placeholder="<?php _e('State / Province', 'edd'); ?>" style="display:none;"/>
            <select name="shipping_state_us" id="shipping_state_us" class="shipping-state edd-select required">
                <?php
                    $states = edd_get_states_list();
                    foreach( $states as $state_code => $state ) {
                        echo '<option value="' . $state_code . '">' . $state . '</option>';
                    }
                ?>
            </select>
            <select name="shipping_state_ca" id="shipping_state_ca" class="shipping-state edd-select required" style="display: none;">
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
			<input type="text" size="4" name="shipping_zip" class="shipping-zip edd-input required" placeholder="<?php _e('Zip / Postal code', 'edd'); ?>"/>
			<label class="edd-label"><?php _e('Shipping Zip / Postal Code', 'edd'); ?></label>
		</p>
	</fieldset>
	<?php
	echo ob_get_clean();
}
add_action( 'edd_purchase_form_user_info', 'atcf_shipping_address_fields' );

function atcf_shipping_validate_meta( $valid_data, $data ) {
	$shipping_info  = array();
	$shipping_info[ 'shipping_address' ]   = isset( $data[ 'shipping_address' ] )   ? sanitize_text_field( $data[ 'shipping_address' ] )   : '';
	$shipping_info[ 'shipping_address_2' ] = isset( $data[ 'shipping_address_2' ] ) ? sanitize_text_field( $data[ 'shipping_address_2' ] ) : '';
	$shipping_info[ 'shipping_city' ]      = isset( $data[ 'shipping_city' ] )      ? sanitize_text_field( $data[ 'shipping_city' ] )      : '';
	$shipping_info[ 'shipping_country' ]   = isset( $data[ 'shipping_country' ] )   ? sanitize_text_field( $data[ 'shipping_country' ] )   : '';
	$shipping_info[ 'shipping_zip' ]       = isset( $data[ 'shipping_zip' ] )	    ? sanitize_text_field( $data[ 'shipping_zip' ] )       : '';

	switch ( $shipping_info[ 'shipping_country'] ) :
		case 'US' :
			$shipping_info[ 'shipping_state' ] = isset( $_POST[ 'shipping_state_us' ] )	  ? sanitize_text_field( $_POST[ 'shipping_state_us' ] )     : '';
			break;
		case 'CA' :
			$shipping_info[ 'shipping_state' ] = isset( $_POST[ 'shipping_state_ca' ] )	  ? sanitize_text_field( $_POST[ 'shipping_state_ca' ] )     : '';
			break;
		default :
			$shipping_info[ 'shipping_state' ] = isset( $_POST[ 'shipping_state_other' ] ) ? sanitize_text_field( $_POST[ 'shipping_state_other' ] ) : '';
			break;
	endswitch;

	if ( '' == $shipping_info[ 'shipping_address' ] || '' == $shipping_info[ 'shipping_city' ] || '' == $shipping_info[ 'shipping_city' ] || '' == $shipping_info[ 'shipping_country' ] || '' == $shipping_info[ 'shipping_zip' ] ) {
		edd_set_error( 'invalid_shipping_info', __( 'Please fill out all required shipping fields.', 'atcf' ) );
	}

	if ( ! edd_purchase_form_validate_cc_zip( $shipping_info[ 'shipping_zip' ], $shipping_info[ 'shipping_country' ] ) )
		edd_set_error( 'invalid_cc_zip', __( 'The zip code you entered for your shipping address is invalid.', 'atcf' ) );
}
add_action( 'edd_checkout_error_checks', 'atcf_shipping_validate_meta', 10, 2);

function atcf_shipping_save_meta( $payment_meta ) {
	$payment_meta[ 'shipping' ][ 'shipping_address' ]   = isset( $_POST[ 'shipping_address' ] )   ? sanitize_text_field( $_POST[ 'shipping_address' ] )   : '';
	$payment_meta[ 'shipping' ][ 'shipping_address_2' ] = isset( $_POST[ 'shipping_address_2' ] ) ? sanitize_text_field( $_POST[ 'shipping_address_2' ] ) : '';
	$payment_meta[ 'shipping' ][ 'shipping_city' ]      = isset( $_POST[ 'shipping_city' ] )      ? sanitize_text_field( $_POST[ 'shipping_city' ] )      : '';
	$payment_meta[ 'shipping' ][ 'shipping_country' ]   = isset( $_POST[ 'shipping_country' ] )   ? sanitize_text_field( $_POST[ 'shipping_country' ] )   : '';
	$payment_meta[ 'shipping' ][ 'shipping_zip' ]       = isset( $_POST[ 'shipping_zip' ] )	      ? sanitize_text_field( $_POST[ 'shipping_zip' ] )       : '';

	return $payment_meta;
}
add_filter( 'edd_payment_meta', 'atcf_shipping_save_meta' );


function atcf_payment_view_details( $payment_meta ) {
	if ( ! isset ( $payment_meta[ 'shipping' ] ) )
		return;

	$shipping     = $payment_meta[ 'shipping' ];
?>
	<li>
		<?php echo $shipping[ 'shipping_address' ]; ?><br />
		<?php echo $shipping[ 'shipping_address_2' ]; ?><br />
		<?php echo $shipping[ 'shipping_city' ]; ?>, <?php echo $shipping[ 'shipping_state' ]; ?> <?php echo $shipping[ 'shipping_zip' ]; ?><br />
		<?php echo $shipping[ 'shipping_country' ]; ?>
	</li>
<?php
}
add_action( 'edd_payment_personal_details_list', 'atcf_payment_view_details' );