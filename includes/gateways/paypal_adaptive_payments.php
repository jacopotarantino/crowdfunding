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
		<span class="description"><?php _e( 'The secondary receiver for PayPal Adaptive Payments', 'atcf' ); ?></span>
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
 * Process preapproved payments
 *
 * @since Appthemer Crowdfunding 1.1
 *
 * @return void
 */
function atcf_collect_funds_paypal_pre_approval( $gateway, $gateway_args, $campaign, $errors ) {
	$paypal_adaptive = new PayPalAdaptivePaymentsGateway();
	
	$owner           = $edd_options[ 'epap_receivers' ];
	$owner           = explode( '|', $owner );
	$owner_email     = $owner[0];
	$owner_amount    = $owner[1];

	if ( 'flexible' == $campaign->type() ) {
		$owner_amount = $owner_amount + $edd_options[ 'epap_flexible_fee' ];
	}

	$campaign_amount = 100 - $owner_amount;
	$campaign_email  = $campaign->paypal_email();

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

	foreach ( $gateway_args[ 'payments' ] as $payment ) {
		$payment_id      = $payment;

		$sender_email    = get_post_meta( $payment_id, '_edd_epap_sender_email', true );
		$amount          = get_post_meta( $payment_id, '_edd_epap_amount', true );
		$paid            = get_post_meta( $payment_id, '_edd_epap_paid', true );
		$preapproval_key = get_post_meta( $payment_id, '_edd_epap_preapproval_key', true );

		/** Already paid or other error */
		if ( $paid > $amount ) {
			$errors->add( 'already-paid-' . $payment_id, __( 'This payment has already been collected.', 'atcf' ) );
			
			continue;
		}

		if ( $payment = $paypal_adaptive->pay_preapprovals( $payment_id, $preapproval_key, $sender_email, $amount, $receivers ) ) {
			$responsecode = strtoupper( $payment[ 'responseEnvelope' ][ 'ack' ] );
			
			if ( $responsecode == 'SUCCESS' || $responsecode == 'SUCCESSWITHWARNING' ) {
				$pay_key = $payment[ 'payKey' ];
				
				add_post_meta( $payment_id, '_edd_epap_pay_key', $pay_key );
				add_post_meta( $payment_id, '_edd_epap_preapproval_paid', true );
				
				edd_update_payment_status( $payment_id, 'publish' );
			} else {
				$errors->add( 
					'invalid-response-' . $payment_id, 
					sprintf( 
						__( 'There was an error collecting funds for payment <a href="%1$s">#%2$d</a>. PayPal responded with %3$s', 'atcf' ), 
						admin_url( 'edit.php?post_type=download&page=edd-payment-history&edd-action=edit-payment&purchase_id=' . $payment_id ), 
						$payment_id, 
						'<pre style="max-width: 100%; overflow: scroll; height: 200px;">' . print_r( array_merge( $payment,  compact( 'payment_id', 'preapproval_key', 'sender_email', 'amount', 'receivers' ) ), true ) . '</pre>'
					)
				);
			}
		} else {
			$errors->add( 'payment-error-' . $payment_id, __( 'There was an error.', 'atcf' ) );
		}
	}

	return $errors;
}
add_action( 'atcf_collect_funds_paypal_pre_approval', 'atcf_collect_funds_paypal_pre_approval', 10, 4 );