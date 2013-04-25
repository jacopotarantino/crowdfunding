<?php

function atcf_has_preapproval_gateway() {
	$has_support = false;
	$supports_preapproval = apply_filters( 'atcf_gateways_support_preapproval', array(
		'stripe',
		'paypal_adaptive_payments'
	) );

	$active_gateways = edd_get_enabled_payment_gateways();

	foreach ( $active_gateways as $gateway => $gateway_args ) {
		if ( in_array( $gateway, $supports_preapproval ) ) {
			$has_support = true;
			break;
		}
	}

	return $has_support;
}

/** PayPal Adaptive Payments *******************************************************/

/**
 *
 * @since Appthemer Crowdfunding 1.1
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

/** Stripe Preapproval *******************************************************/

/**
 *
 * @since Appthemer Crowdfunding 1.1
 */
function atcf_edd_purchase_data_before_gateway( $data ) {
	$data[ 'preapprove_only' ] = 1;

	return $data;
}
add_filter( 'edd_purchase_data_before_gateway', 'atcf_edd_purchase_data_before_gateway' );

/**
 *
 * @since Appthemer Crowdfunding 1.1
 */
function atcf_collect_funds_stripe( $gateway, $gateway_args, $campaign, $errors ) {
	foreach ( $gateway_args[ 'payments' ] as $payment ) {
		$charge = edds_charge_preapproved( $payment );

		if ( ! $charge )
			$errors->add( 'payment-error-' . $payment, sprintf( __( 'There was an error collecting funds for payment #%d.', 'atcf' ) ), $payment );
	}

	return $errors;
}
add_action( 'atcf_collect_funds_stripe', 'atcf_collect_funds_stripe', 10, 4 );