<?php
/**
 * WePay gateway functionality.
 *
 * @since Appthemer CrowdFunding 1.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * If WePay is being used per-campaign or globally on the site.
 *
 * @since CrowdFunding 1.3
 *
 * @return void
 */
function atcf_gateway_wepay_is_specific() {
	global $edd_options;

	if ( isset ( $edd_options[ 'wepay_access_token' ] ) && '' != $edd_options[ 'wepay_access_token' ] )
		return false;

	return true;
}

/**
 * WePay fields on frontend submit and edit.
 *
 * @since CrowdFunding 1.3
 *
 * @return void
 */
function atcf_shortcode_submit_field_wepay_creds( $atts, $campaign ) {
	if ( $atts[ 'editing' ] ) {
		$access_token = $campaign->__get( 'wepay_access_token' );
		$account_id   = $campaign->__get( 'wepay_account_id' );
	}
?>
	<p class="atcf-submit-campaign-wepay-account-id">
		<label for="wepay_account_id"><?php _e( 'WePay Account ID:', 'atcf' ); ?></label>
		<input type="text" name="wepay_account_id" id="wepay_account_id" value="<?php echo $atts[ 'editing' ] ? $account_id : null; ?>" />
	</p>

	<p class="atcf-submit-campaign-wepay-access-token">
		<label for="wepay_access_token"><?php _e( 'WePay Access Token:', 'atcf' ); ?></label>
		<input type="text" name="wepay_access_token" id="wepay_access_token" value="<?php echo $atts[ 'editing' ] ? $access_token : null; ?>" />
	</p>
<?php
}
if ( atcf_gateway_wepay_is_specific() )
	add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_wepay_creds', 105, 2 );

/**
 * Validate WePay on frontend submission.
 *
 * @since CrowdFunding 1.3
 *
 * @return void
 */
function atcf_campaign_submit_validate_wepay( $postdata, $errors ) {
	$account_id   = $postdata[ 'wepay_account_id' ];
	$access_token = $postdata[ 'wepay_account_token' ];

	if ( ! isset ( $account_id ) || ! isset ( $access_token ) )
		$errors->add( 'invalid-wepay', __( 'Please enter valid WePay credentials.', 'atcf' ) ); 
}
if ( atcf_gateway_wepay_is_specific() )
	add_action( 'atcf_campaign_submit_validate', 'atcf_campaign_submit_validate_wepay', 10, 2 );

/**
 * Save WePay on the frontend
 *
 * @since CrowdFunding 1.3
 *
 * @return void
 */
function atcf_submit_process_after_wepay_save( $campaign, $postdata ) {
	$account_id   = $postdata[ 'wepay_account_id' ];
	$access_token = $postdata[ 'wepay_account_token' ];

	update_post_meta( $campaign, 'wepay_account_id', sanitize_text_field( $account_id ) );
	update_post_meta( $campaign, 'wepay_access_token', sanitize_text_field( $access_token ) );
}
if ( atcf_gateway_wepay_is_specific() )
	add_action( 'atcf_submit_process_after', 'atcf_submit_process_after_wepay_save', 10, 2 );

/**
 * PayPal Adaptive Payments field on backend.
 *
 * @since CrowdFunding 1.1
 *
 * @return void
 */
function atcf_metabox_campaign_info_after_wepay_creds( $campaign ) {
	$access_token = $campaign->__get( 'wepay_access_token' );
	$account_id   = $campaign->__get( 'wepay_account_id' );
?>
	<p>
		<strong><label for="wepay_account_id"><?php _e( 'WePay Account ID:', 'atcf' ); ?></label></strong><br />
		<input type="text" name="wepay_account_id" id="wepay_account_id" class="regular-text" value="<?php echo esc_attr( $account_id ); ?>" />
	</p>

	<p>
		<strong><label for="wepay_access_token"><?php _e( 'WePay Access Token:', 'atcf' ); ?></label></strong><br />
		<input type="text" name="wepay_access_token" id="wepay_access_token" class="regular-text" value="<?php echo esc_attr( $access_token ); ?>" />
	</p>
<?php
}
if ( atcf_gateway_wepay_is_specific() )
	add_action( 'atcf_metabox_campaign_info_after', 'atcf_metabox_campaign_info_after_wepay_creds' );

/**
 * Save WePay on the backend.
 *
 * @since CrowdFunding 1.3
 *
 * @return void
 */
function atcf_metabox_save_wepay( $fields ) {
	$fields[] = 'wepay_account_id';
	$fields[] = 'wepay_access_token';

	return $fields;
}
if ( atcf_gateway_wepay_is_specific() )
	add_filter( 'edd_metabox_fields_save', 'atcf_metabox_save_wepay' );

/**
 * Figure out the WePay account info to send the funds to.
 *
 * @since CrowdFunding 1.3
 *
 * @return $creds
 */
function atcf_gateway_wepay_edd_wepay_get_api_creds( $creds ) {
	$cart_items  = edd_get_cart_contents();
	$campaign_id = null;

	foreach ( $cart_items as $item ) {
		$campaign_id = $item[ 'id' ];

		break;
	}

	$campaign = atcf_get_campaign( $campaign_id );

	$access_token = $campaign->__get( 'wepay_access_token' );
	$account_id   = $campaign->__get( 'wepay_account_id' );

	$creds[ 'access_token' ] = trim( $access_token );
	$creds[ 'account_id' ]   = trim( $account_id );

	return $creds;
}
if ( atcf_gateway_wepay_is_specific() )
	add_filter( 'edd_wepay_get_api_creds', 'atcf_gateway_wepay_edd_wepay_get_api_creds' );

/**
 * Additional WePay settings needed by Crowdfunding
 *
 * @since Appthemer Crowdfunding 1.3
 *
 * @param array $settings Existing WePay settings
 * @return array $settings Modified WePay settings
 */
function atcf_gateway_wepay_settings( $settings ) {

	if ( atcf_gateway_wepay_is_specific() ) {
		$settings[ 'wepay_app_fee' ] = array(
			'id' => 'wepay_app_fee',
			'name'  => __( 'Site Fee', 'atcf' ),
			'desc'  => '% <span class="description">' . __( 'The percentage of each pledge amount the site keeps (on top of WePay fees)', 'atcf' ) . '</span>',
			'type'  => 'text',
			'size'  => 'small'
		);
	}

	return $settings;
}
add_filter( 'edd_gateway_wepay_settings', 'atcf_gateway_wepay_settings' );

/**
 * Calculate a fee to keep for the site.
 *
 * @since CrowdFunding 1.3
 *
 * @return $args
 */
function atcf_gateway_wepay_edd_wepay_checkout_args( $args ) {
	global $edd_options;

	if ( '' == $edd_options[ 'wepay_app_fee' ] )
		return $args;

	$percent  = absint( $edd_options[ 'wepay_app_fee' ] ) / 100;
	$subtotal = edd_get_cart_subtotal();

	$fee = $subtotal * $percent;

	$args[ 'app_fee' ] = $fee;

	return $args;
}
add_filter( 'edd_wepay_checkout_args', 'atcf_gateway_wepay_edd_wepay_checkout_args' );

/**
 * Process preapproved payments
 *
 * @since Appthemer Crowdfunding 1.3
 *
 * @return void
 */
function atcf_collect_funds_wepay( $gateway, $gateway_args, $campaign, $errors ) {
	foreach ( $gateway_args[ 'payments' ] as $payment ) {
		$wepay  = new EDD_WePay_Gateway;

		$charge = $wepay->charge_preapproved( $payment );

		if ( ! $charge )
			$errors->add( 'payment-error-' . $payment, sprintf( __( 'There was an error collecting funds for payment #%d.', 'atcf' ) ), $payment );
	}

	return $errors;
}
add_action( 'atcf_collect_funds_wepay', 'atcf_collect_funds_wepay', 10, 4 );