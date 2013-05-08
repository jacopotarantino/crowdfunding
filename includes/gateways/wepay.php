<?php
/**
 * WePay gateway functionality.
 *
 * @since Appthemer CrowdFunding 1.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

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