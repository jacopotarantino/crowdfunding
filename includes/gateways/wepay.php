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
function atcf_collect_funds_wepay( $charged, $payment, $campaign ) {
	global $edd_wepay;

	return $edd_wepay->charge_preapproved( $payment );
}
add_filter( 'atcf_collect_funds_wepay', 'atcf_collect_funds_wepay', 10, 3 );