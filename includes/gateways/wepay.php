<?php
/**
 * WePay gateway functionality.
 *
 * @since Astoundify Crowdfunding 1.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Process preapproved payments
 *
 * @since Astoundify Crowdfunding 1.3
 *
 * @return void
 */
function atcf_collect_funds_wepay( $gateway, $gateway_args, $campaign, $errors ) {
	global $edd_wepay, $failed_payments;

	if ( ! isset ( $gateway_args[ 'payments' ] ) )
		return;

	foreach ( $gateway_args[ 'payments' ] as $payment ) {
		$charge = $edd_wepay->charge_preapproved( $payment );

		if ( ! $charge )
			$failed_payments[ $gateway ][ 'payments'][] = $payment;

		do_action( 'atcf_process_payment_' . $gateway, $payment, $charge );
	}

	return $failed_payments;
}
add_action( 'atcf_collect_funds_wepay', 'atcf_collect_funds_wepay', 10, 4 );