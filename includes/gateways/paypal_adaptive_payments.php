<?php
/**
 * PayPal Adaptive Payments gateway functionality.
 *
 * @since Appthemer CrowdFunding 1.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * PayPal Adaptive Payments field on frontend submit and edit.
 *
 * @since CrowdFunding 1.1
 *
 * @return void
 */
function atcf_shortcode_submit_field_paypal_adaptive_payments_email( $editing, $campaign ) {
	if ( $editing )
		$paypal_email = $campaign->__get( 'campaign_email' );
?>
	<p class="atcf-submit-campaign-paypal-email">
		<label for="email"><?php _e( 'PayPal Email', 'atcf' ); ?></label>
		<input type="text" name="email" id="email" value="<?php echo $editing ? $paypal_email : null; ?>" />
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_paypal_adaptive_payments_email', 105, 2 );

/**
 * PayPal Adaptive Payments field on backend.
 *
 * @since CrowdFunding 1.1
 *
 * @return void
 */
function atcf_metabox_campaign_info_after_paypal_adaptive_payments( $campaign ) {
	$paypal_email = $campaign->__get( 'campaign_email' );
?>
	<p>
		<label for="campaign_email"><strong><?php _e( 'PayPal Adaptive Payments Email:', 'atcf' ); ?></strong></label><br />
		<input type="text" name="campaign_email" id="campaign_email" value="<?php echo esc_attr( $paypal_email ); ?>" class="regular-text" />
	</p>
<?php
}
add_action( 'atcf_metabox_campaign_info_after', 'atcf_metabox_campaign_info_after_paypal_adaptive_payments' );

/**
 * Validate PayPal Adaptive Payments on the frontend submission (or edit).
 *
 * @since CrowdFunding 1.1
 *
 * @return void
 */
function atcf_campaign_submit_validate_paypal_adaptive_payments( $postdata, $errors ) {
	$email = $postdata[ 'email' ];

	if ( ! isset ( $email ) || ! is_email( $email ) )
		$errors->add( 'invalid-paypal-adaptive-email', __( 'Please make sure your PayPal email address is valid.', 'atcf' ) ); 
}
add_action( 'atcf_campaign_submit_validate', 'atcf_campaign_submit_validate_paypal_adaptive_payments', 10, 2 );
add_action( 'atcf_edit_campaign_validate', 'atcf_campaign_submit_validate_paypal_adaptive_payments', 10, 2 );

/**
 * Save PayPal Adaptive Payments on the frontend submission (or edit).
 *
 * @since CrowdFunding 1.1
 *
 * @return void
 */
function atcf_submit_process_after_paypal_adaptive_payments_save( $campaign, $postdata ) {
	$email = $postdata[ 'email' ];

	update_post_meta( $campaign, 'campaign_email', sanitize_text_field( $email ) );
}
add_action( 'atcf_submit_process_after', 'atcf_submit_process_after_paypal_adaptive_payments_save', 10, 2 );
add_action( 'atcf_edit_campaign_after', 'atcf_submit_process_after_paypal_adaptive_payments_save', 10, 2 );

/**
 * Save PayPal Adaptive Payments on the backend.
 *
 * @since CrowdFunding 1.1
 *
 * @return void
 */
function atcf_metabox_save_paypal_adaptive_payments( $fields ) {
	$fields[] = 'campaign_email';

	return $fields;
}
add_filter( 'edd_metabox_fields_save', 'atcf_metabox_save_paypal_adaptive_payments' );

/**
 * Create a list of receivers
 *
 * @since Appthemer Crowdfunding 1.3
 *
 * @return array $receivers
 */
function atcf_gateway_paypal_adaptive_payments_receivers( $campaign ) {
	global $edd_options;

	$owner           = $edd_options[ 'epap_receivers' ];
	$owner           = explode( '|', $owner );
	$owner_email     = $owner[0];
	$owner_amount    = $owner[1];

	if ( 'flexible' == $campaign->type() ) {
		$owner_amount = $owner_amount + $edd_options[ 'epap_flexible_fee' ];
	}

	$campaign_amount = 100 - $owner_amount;
	$campaign_email  = $campaign->__get( 'campaign_email' );

	$receivers       = array(
		array(
			trim( $campaign_email ),
			absint( $campaign_amount )
		),
		array(
			trim( $owner_email ),
			absint( $owner_amount )
		)
	);

	return apply_filters( 'atcf_gateway_paypal_adaptive_payments_receivers', $receivers, $campaign );
}

/**
 * Process preapproved payments
 *
 * @since Appthemer Crowdfunding 1.1
 *
 * @return void
 */
function atcf_collect_funds_paypal_adaptive_payments( $gateway, $gateway_args, $campaign, $failed_payments ) {
	global $edd_options, $failed_payments;

	if ( ! isset ( $gateway_args[ 'payments' ] ) )
		return;

	foreach ( $gateway_args[ 'payments' ] as $payment ) {
		$charge = epap_process_preapprovals( $payment, atcf_gateway_paypal_adaptive_payments_receivers( $campaign ) );
		
		if ( ! $charge )
			$failed_payments[ $gateway ] = $payment;

		do_action( 'atcf_process_payment_' . $gateway, $payment, $charge );
	}

	return $failed_payments;
}
add_action( 'atcf_collect_funds_paypal_adaptive_payments', 'atcf_collect_funds_paypal_adaptive_payments', 10, 4 );