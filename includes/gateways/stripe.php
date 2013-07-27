<?php
/**
 * Stripe gateway functionality.
 *
 * @since Astoundify Crowdfunding 1.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Process preapproved payments
 *
 * @since Astoundify Crowdfunding 1.1
 *
 * @return void
 */
function atcf_collect_funds_stripe( $gateway, $gateway_args, $campaign, $failed_payments ) {
	global $failed_payments;

	if ( ! isset ( $gateway_args[ 'payments' ] ) )
		return;

	foreach ( $gateway_args[ 'payments' ] as $payment ) {
		$charge = edds_charge_preapproved( $payment );

		if ( ! $charge )
			$failed_payments[ $gateway ][ 'payments'][] = $payment;

		do_action( 'atcf_process_payment_' . $gateway, $payment, $charge );
	}

	return $failed_payments;
}
add_action( 'atcf_collect_funds_stripe', 'atcf_collect_funds_stripe', 10, 4 );