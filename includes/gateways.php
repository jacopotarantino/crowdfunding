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
 * Payments Queueueuueue.
 *
 * This is attached to the cron, and simply loops through any campaigns 
 * that are still processing and instaitates the processing class.
 *
 * @since Astoundify Crowdfunding 1.8
 *
 * @return void
 */
function atcf_process_payments() {
	$processing = get_option( 'atcf_processing', array() );

	if ( empty( $processing ) )
		return;

	foreach ( $processing as $key => $campaign ) {
		new ATCF_Process_Campaign( $campaign );
	}
}
add_action( 'atcf_process_payments', 'atcf_process_payments' );

/**
 * Payment Processing
 *
 * Instead of trying to process a campaign's payments all at once
 * manually, we can instead do them in batches every hour until they
 * are complete. This will greatly reduce the load on the server.
 */
class ATCF_Process_Campaign {

	/**
	 * Campaign ID
	 *
	 * @var int
	 */
	var $campaign_id;

	/**
	 * Campaign
	 *
	 * @var object
	 */
	var $campaign;

	/**
	 * Payments to process
	 *
	 * @var array
	 */
	var $payments = array();

	/**
	 * Failed payments (existing and new)
	 *
	 * @var array
	 */
	var $failed_payments = array();

	/**
	 * Active payment gateways
	 *
	 * @var array
	 */
	var $gateways = array();

	/**
	 * The number of payments to process per campaign
	 *
	 * @var int
	 */
	var $to_process;

	/**
	 * If we are only processing failed payments
	 *
	 * @var boolean
	 */
	var $process_failed;

	/**
	 * Get things moving.
	 *
	 * Defines some class variables and starts the processinging.
	 *
	 * @since Astoundify Crowdfunding 1.8
	 *
	 * @return void
	 */
	function __construct( $campaign_id, $process_failed = false ) {
		$this->to_process      = apply_filters( 'atcf_bulk_process_limit', 20 );
		$this->process_failed  = $process_failed;

		$this->campaign_id     = $campaign_id;
		$this->campaign        = atcf_get_campaign( $this->campaign_id );

		$this->payments        = $this->campaign->__get( '_payment_ids' );
		$this->failed_payments = $this->campaign->__get( '_campaign_failed_payments' );

		if ( $this->process_failed )
			$this->payments = $this->failed_payments;

		$this->gateways        = edd_get_enabled_payment_gateways();

		$this->get_payments();
		$this->sort_payments();
		$this->process();
		$this->log_failed();
		$this->cleanup();
	}

	/**
	 * Gather the payments associated with this campaign and create
	 * a list stored as campaign meta.
	 *
	 * This will be modified as payments are processed and used as
	 * our "destructable" list of payments we still need to process.
	 *
	 * If something goes wrong, we always have the actual payments we can 
	 * rebuild the list from.
	 *
	 * @since Astoundify Crowdfunding 1.8
	 *
	 * @return void
	 */
	function get_payments() {
		if ( ! empty( $this->payments ) || $this->process_failed )
			return;

		$backers = $this->campaign->unique_backers();

		foreach ( $backers as $backer ) {
			if ( 'preapproval' == get_post_status( $backer ) )
				$this->payments[ $backer ] = $backer;
		}

		if ( empty( $this->payments ) )
			$this->payments = array();

		update_post_meta( $this->campaign_id, '_payment_ids', $this->payments );
	}

	/**
	 * Sort out our payments for this batch of processing.
	 * Sort them into gateways, but only do the amount specificed.
	 *
	 * @since Astoundify Crowdfunding 1.8
	 *
	 * @return void
	 */
	function sort_payments() {
		if ( $this->process_failed )
			return;

		$count = 1;

		foreach ( $this->payments as $key => $payment_id ) {
			$gateway = get_post_meta( $payment_id, '_edd_payment_gateway', true );

			if ( 'publish' == get_post_field( 'post_status', $payment_id ) || ! $payment_id )
				continue;

			$this->gateways[ $gateway ][ 'payments' ][] = $payment_id;

			if ( $count == $this->to_process )
				break;

			$count++;
		}
	}

	/**
	 * Process the payments.
	 *
	 * If we aren't specifically processing failed payments, skip
	 * any that have previously been marked as failed.
	 *
	 * Try to charge the payment via the gateway callback. If it fails,
	 * add it to the list. No matter what, always remove the payment
	 * from the list of IDs that needs to be processed. If we are only processeing
	 * failed payments, and the charge was not a succeess, remove it.
	 *
	 * @since Astoundify Crowdfunding 1.8
	 *
	 * @return void
	 */
	function process() {

		foreach ( $this->gateways as $gateway => $gateway_args ) {

			if ( ! isset ( $gateway_args[ 'payments' ] ) )
				continue;

			foreach ( $gateway_args[ 'payments' ] as $payment ) {

				// Skip failed payments
				if ( ! $this->process_failed && isset( $this->failed_payments[ $gateway ] ) && in_array( $payment, $this->failed_payments[ $gateway ] ) )
					continue;

				// Start the charge from the gateway
				$charge = apply_filters( 'atcf_collect_funds_' . $gateway, false, $payment, $this->campaign );

				// If the charge has failed, record it in the failed payments
				if ( ! $charge )
					$this->failed_payments[ $gateway ][ 'payments' ][ $payment ] = $payment;

				// Remove this payment from our master list
				unset( $this->payments[ $payment ] );

				if ( $this->process_failed && $charge ) {
					unset( $this->failed_payments[ $gateway ][ 'payments' ][ $payment ] );
				}

				// Allow plugins to do other things when a payment processes
				do_action( 'atcf_process_payment_' . $gateway, $payment, $charge );

			}

		}

	}

	/**
	 * Record notes on failed payments
	 *
	 * Once payments have attempted to be processed, any payments
	 * that still failed shold record a note on that payment.
	 *
	 * @since Astoundify Crowdfunding 1.8
	 *
	 * @return void
	 */
	function log_failed() {
		if ( empty( $this->failed_payments ) )
			return;

		foreach ( $this->failed_payments as $gateway => $payments ) {
			
			foreach ( $payments[ 'payments' ] as $payment_id ) {
				edd_insert_payment_note( $payment_id, apply_filters( 'atcf_failed_payment_note', sprintf( __( 'Error processing preapproved payment via %s when automatically collecting funds.', 'atcf' ), $gateway ) ) );

				// Allow plugins to do other things when a payment fails
				do_action( 'atcf_failed_payment', $payment_id, $gateway );
			}

		}
		
	}

	/**
	 * Save what we have done, and add/remove any flag data
	 * we may need the next time we are processing.
	 *
	 * @since Astoundify Crowdfunding 1.8
	 *
	 * @return void
	 */
	function cleanup() {
		if ( ! empty( $this->failed_payments ) ) {
			update_post_meta( $this->campaign_id, '_campaign_failed_payments', $this->failed_payments );
		} else {
			delete_post_meta( $this->campaign_id, '_campaign_failed_payments' );
		}

		if ( ! empty( $this->payments ) )  {
			update_post_meta( $this->campaign_id, '_payment_ids', $this->payments );
		} else {
			delete_post_meta( $this->campaign_id, '_payment_ids' );
			add_post_meta( $this->campaign_id, '_campaign_batch_complete', true, true );

			$processing = get_option( 'atcf_processing', array() );
			unset( $processing[ $this->campaign_id ] );

			update_option( 'atcf_processing', $processing );
		}
	}
}