<?php

/**
 *
 * @since Appthemer Crowdfunding 1.1
 */
function atcf_load_gateway_support() {
	$crowdfunding    = crowdfunding();
	$active_gateways = edd_get_enabled_payment_gateways();

	foreach ( $active_gateways as $gateway => $gateway_args ) {
		if ( @file_exists( $crowdfunding->includes_dir . 'gateways/' . $gateway . '.php' ) ) {
			require( $crowdfunding->includes_dir . 'gateways/' . $gateway . '.php' );
		}
	}
}
add_action( 'init', 'atcf_load_gateway_support' );

/**
 *
 * @since Appthemer Crowdfunding 1.1
 */
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