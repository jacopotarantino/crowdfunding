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

/**
 * Payments Queueueuueue
 *
 * @since Astoundify Crowdfunding 1.8
 *
 * @return void
 */
function atcf_process_payments() {
	$processing = get_option( 'atcf_processing' );

	new ATCF_Process_Campaign( $processing[0] );
}

class ATCF_Process_Campaign {

	var $campaign_id;
	var $campaign;
	var $payments;

	public function __construct( $campaign_id ) {
		$this->campaign_id = $campaign_id;
		$this->campaign    = atcf_get_campaign( $this->campaign_id );

		$this->payments    = $this->campaign->backers();
	}

	function process() {
		$backers          = $campaign->backers();
		$gateways         = edd_get_enabled_payment_gateways();
		$failed_payments  = array();

		if ( empty( $backers ) ) {
			delete_post_meta( $campaign->ID, '_campaign_failed_payments' );
			
			wp_safe_redirect( add_query_arg( array( 'post' => $campaign->ID, 'action' => 'edit', 'message' => 14 ), admin_url( 'post.php' ) ) );
			exit();
		}

		
		foreach ( $backers as $backer ) {
			$payment_id = get_post_meta( $backer->ID, '_edd_log_payment_id', true );
			$gateway    = get_post_meta( $payment_id, '_edd_payment_gateway', true );

			if ( 'publish' == get_post_field( 'post_status', $payment_id ) || ! $payment_id )
				continue;

			$gateways[ $gateway ][ 'payments' ][] = $payment_id;
		}

		$process = $gateways;
		
		foreach ( $process as $gateway => $gateway_args ) {
			do_action( 'atcf_collect_funds_' . $gateway, $gateway, $gateway_args, $campaign, $failed_payments );
		}

		if ( ! empty( $failed_payments ) ) {
			$failed_count = 0;

			foreach ( $failed_payments as $gateway => $payments ) {
				/** Loop through each gateway's failed payments */
				foreach ( $payments[ 'payments' ] as $payment_id ) {
					edd_insert_payment_note( $payment_id, apply_filters( 'atcf_failed_payment_note', sprintf( __( 'Error processing preapproved payment via %s when collecting funds.', 'atcf' ), $gateway ) ) );

					$failed_count++;

					do_action( 'atcf_failed_payment', $payment_id, $gateway );
				}
			}

			update_post_meta( $campaign->ID, '_campaign_failed_payments', $failed_payments );

			return wp_safe_redirect( add_query_arg( array( 'post' => $campaign->ID, 'action' => 'edit', 'message' => 15, 'failed' => $failed_count ), admin_url( 'post.php' ) ) );
			exit();
		} else {
			update_post_meta( $campaign->ID, '_campaign_bulk_collected', 1 );
			delete_post_meta( $campaign->ID, '_campaign_failed_payments' );

			return wp_safe_redirect( add_query_arg( array( 'post' => $campaign->ID, 'action' => 'edit', 'message' => 13, 'collected' => $campaign->backers_count() ), admin_url( 'post.php' ) ) );
			exit();
		}
	}
}