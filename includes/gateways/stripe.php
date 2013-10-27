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
function atcf_collect_funds_stripe( $charged, $payment, $campaign ) {
	return edds_charge_preapproved( $payment );
}
add_action( 'atcf_collect_funds_stripe', 'atcf_collect_funds_stripe', 10, 3 );