<?php
/**
 * Checkout
 *
 * @since Appthemer CrowdFunding 0.9
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Track number of purchases for each pledge amount.
 *
 * @since Appthemer CrowdFunding 0.9
 *
 * @param int $payment the ID number of the payment
 * @param array $payment_data The payment data for the cart
 * @return void
 */
function atcf_log_pledge_limit( $payment, $payment_data ) {
	$payment_meta     = edd_get_payment_meta( $payment );

	foreach ( $payment_data[ 'cart_details' ] as $key => $item ) {
		$variable_pricing = edd_get_variable_prices( $item[ 'id' ] );

		foreach ( $variable_pricing as $key => $value ) {
			$what = $item[ 'item_number' ][ 'options' ][ 'price_id' ];

			if ( $key == $what ) {
				$variable_pricing[ $what ][ 'bought' ] = ( isset ( $variable_pricing[ $what ][ 'bought' ] ) ? $variable_pricing[ $what ][ 'bought' ] : 0 ) + 1;
			}
		}

		update_post_meta( $item[ 'id' ], 'edd_variable_prices', $variable_pricing );	
	}
}
add_action( 'edd_insert_payment', 'atcf_log_pledge_limit', 10, 2 );