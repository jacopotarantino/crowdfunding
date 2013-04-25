<?php

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