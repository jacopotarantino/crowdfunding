<?php
/**
 * Custom gateway functionality.
 *
 * @since Astoundify Crowdfunding 1.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * If there is any custom gateway functionality included,
 * and the gateway is active, load the extra files.
 *
 * @since Astoundify Crowdfunding 1.1
 *
 * @return void
 */
function atcf_load_gateway_support() {
	if ( ! class_exists( 'Easy_Digital_Downloads' ) )
		return;

	$crowdfunding    = crowdfunding();
	$active_gateways = edd_get_enabled_payment_gateways();

	foreach ( $active_gateways as $gateway => $gateway_args ) {
		if ( @file_exists( $crowdfunding->includes_dir . 'gateways/' . $gateway . '.php' ) ) {
			require( $crowdfunding->includes_dir . 'gateways/' . $gateway . '.php' );
		}
	}
}
add_action( 'init', 'atcf_load_gateway_support', 1 );

/**
 * Determine if any of the currently active gateways have preapproval
 * functionality. There really isn't a standard way of doing this, so
 * they are manually defined in an array right now.
 * 
 * @since Astoundify Crowdfunding 1.1
 *
 * @return boolean $has_support If any of the currently active gateways support preapproval
 */
function atcf_has_preapproval_gateway() {
	global $edd_options;

	$has_support = false;
	$supports_preapproval = apply_filters( 'atcf_gateways_support_preapproval', array(
		'stripe',
		'paypal_adaptive_payments'
	) );

	$active_gateways = edd_get_enabled_payment_gateways();

	foreach ( $active_gateways as $gateway => $gateway_args ) {
		switch ( $gateway ) {
			case 'stripe' :

				if ( isset ( $edd_options[ 'stripe_preapprove_only' ] ) )
					$has_support = true;

				break;

			case 'paypal_adaptive_payments' : 

				if ( isset( $edd_options[ 'epap_preapproval' ] ) )
					$has_support = true;

				break;

			case 'wepay' :

				if ( isset( $edd_options[ 'wepay_preapprove_only' ] ) )
					$has_support = true;

				break;
				
			default :
				$has_support = $has_support;

		}
	}

	return apply_filters( 'atcf_has_preapproval_gateway', $has_support );
}

function atcf_is_gatweay_active( $gateway ) {
	$active_gateways = edd_get_enabled_payment_gateways();

	return array_key_exists( $gateway, $active_gateways );
}