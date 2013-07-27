<?php
/**
 * Shipping
 *
 * Since EDD is for digital goods, it does not collect shipping information by
 * default. This remedies that, by adding shipping fields on checkout.
 *
 * @since Astoundify Crowdfunding 0.1-alpha
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Do any items in the cart require shipping?
 *
 * @since Astoundify Crowdfunding 0.9
 *
 * @return void
 */
function atcf_shipping_cart_shipping() {
	$cart_items = edd_get_cart_contents();
	$needs      = false;

	if( ! empty( $cart_items ) ) {
		foreach ( $cart_items as $key => $item ) {
			$campaign = atcf_get_campaign( $item['id'] );

			if ( $campaign->needs_shipping() )
				$needs = true;
		}
	}

	return apply_filters( 'atcf_shipping_cart_shipping', $needs );
}

/**
 * Add the HTML fields.
 *
 * @since Astoundify Crowdfunding 0.1-alpha
 *
 * @return void
 */
function atcf_shipping_address_fields() {
	if ( ! atcf_shipping_cart_shipping() )
		return;

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

		<p id="atcf-edd-address-1-wrap">
			<label class="edd-label"><?php _e('Shipping Address', 'edd'); ?></label>
			<span class="edd-description"><?php _e( 'Where should we send any physical goods?', 'atcf' ); ?></span>
			<input type="text" name="shipping_address" class="shipping-address edd-input required" placeholder="<?php _e('Address line 1', 'edd'); ?>"/>
		</p>

		<p id="atcf-edd-address-2-wrap">
			<label class="edd-label"><?php _e('Shipping Address Line 2', 'edd'); ?></label>
			<input type="text" name="shipping_address_2" class="shipping-address-2 edd-input required" placeholder="<?php _e('Address line 2', 'edd'); ?>"/>
		</p>

		<p id="atcf-edd-address-city">
			<label class="edd-label"><?php _e('Shipping City', 'edd'); ?></label>
			<input type="text" name="shipping_city" class="shipping-city edd-input required" placeholder="<?php _e('City', 'edd'); ?>"/>
		</p>

		<p>
			<label class="edd-label"><?php _e('Shipping Country', 'edd'); ?></label>
			<select name="shipping_country" class="shipping-country edd-select required">
				<?php 
				$countries = edd_get_country_list();
				foreach( $countries as $country_code => $country ) {
				  echo '<option value="' . $country_code . '">' . $country . '</option>';
				}
				?>
			</select>
		</p>

		<p>
			<label class="edd-label"><?php _e('Shipping State / Province', 'edd'); ?></label>
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
		</p>
		<p>
			<label class="edd-label"><?php _e('Shipping Zip / Postal Code', 'edd'); ?></label>
			<input type="text" size="4" name="shipping_zip" class="shipping-zip edd-input required" placeholder="<?php _e('Zip / Postal code', 'edd'); ?>"/>
		</p>

	</fieldset>
	<?php
	echo ob_get_clean();
}
add_action( 'edd_purchase_form_before_submit', 'atcf_shipping_address_fields', 1 );

/**
 * Validate shipping information
 *
 * @since Astoundify Crowdfunding 0.1-alpha
 *
 * @param arrray $valid_data An existing array of valid data
 * @param array $data The form $_POST data
 * @return void
 */
function atcf_shipping_validate_meta( $valid_data, $data ) {
	if ( ! atcf_shipping_cart_shipping() )
		return $valid_data;

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
		edd_set_error( 'invalid_shipping_zip', __( 'The zip code you entered for your shipping address is invalid.', 'atcf' ) );
}
add_action( 'edd_checkout_error_checks', 'atcf_shipping_validate_meta', 10, 2 );

/**
 * Save payment meta.
 *
 * @since Astoundify Crowdfunding 0.1-alpha
 *
 * @param arrray $payment_meta Array of payment meta about to be saved
 * @return array $payment_meta An updated array of payment meta
 */
function atcf_shipping_save_meta( $payment_meta ) {
	if ( ! atcf_shipping_cart_shipping() )
		return $payment_meta;

	$payment_meta[ 'shipping' ][ 'shipping_address' ]   = isset( $_POST[ 'shipping_address' ] )   ? sanitize_text_field( $_POST[ 'shipping_address' ] )   : '';
	$payment_meta[ 'shipping' ][ 'shipping_address_2' ] = isset( $_POST[ 'shipping_address_2' ] ) ? sanitize_text_field( $_POST[ 'shipping_address_2' ] ) : '';
	$payment_meta[ 'shipping' ][ 'shipping_city' ]      = isset( $_POST[ 'shipping_city' ] )      ? sanitize_text_field( $_POST[ 'shipping_city' ] )      : '';
	$payment_meta[ 'shipping' ][ 'shipping_country' ]   = isset( $_POST[ 'shipping_country' ] )   ? sanitize_text_field( $_POST[ 'shipping_country' ] )   : '';
	$payment_meta[ 'shipping' ][ 'shipping_state' ]     = isset( $_POST[ 'shipping_state' ] )     ? sanitize_text_field( $_POST[ 'shipping_state' ] )     : '';
	$payment_meta[ 'shipping' ][ 'shipping_zip' ]       = isset( $_POST[ 'shipping_zip' ] )	      ? sanitize_text_field( $_POST[ 'shipping_zip' ] )       : '';

	return $payment_meta;
}
add_filter( 'edd_payment_meta', 'atcf_shipping_save_meta' );

/**
 * Display shipping payment meta.
 *
 * @since Astoundify Crowdfunding 0.1-alpha
 *
 * @param arrray $payment_meta Array of payment meta about to be saved
 * @return void
 */
function atcf_payment_view_details( $payment_meta ) {
	if ( ! isset ( $payment_meta[ 'shipping' ] ) )
		return;

	$shipping = $payment_meta[ 'shipping' ];
?>
	<li>
		<?php echo $shipping[ 'shipping_address' ]; ?><br />
		<?php echo isset ( $shipping[ 'shipping_address_2' ] ) ? $shipping[ 'shipping_address_2' ] : ''; ?><br />
		<?php echo $shipping[ 'shipping_city' ]; ?>, <?php echo isset ( $shipping[ 'shipping_state' ] ) ? $shipping[ 'shipping_state' ] : ''; ?> <?php echo $shipping[ 'shipping_zip' ]; ?><br />
		<?php echo $shipping[ 'shipping_country' ]; ?>
	</li>
<?php
}
add_action( 'edd_payment_personal_details_list', 'atcf_payment_view_details' );