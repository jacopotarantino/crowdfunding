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
	$processing = get_option( 'atcf_processing', array() );

	foreach ( $processing as $campaign ) {
		new ATCF_Process_Campaign( $campaign );
	}
}

atcf_process_payments();

class ATCF_Process_Campaign {

	/**
	 *
	 *
	 * @var int
	 */
	var $campaign_id;

	/**
	 * 
	 *
	 * @var object
	 */
	var $campaign;

	/**
	 *
	 *
	 * @var array
	 */
	var $payments = array();

	/**
	 *
	 *
	 * @var array
	 */
	var $failed_payments = array();

	/**
	 *
	 *
	 * @var array
	 */
	var $gateways = array();

	/**
	 *
	 *
	 * @var int
	 */
	var $to_process;

	/**
	 * Constructor. Adds hooks.
	 */
	function __construct( $campaign_id ) {
		$this->to_process      = apply_filters( 'atcf_bulk_process_limit', 25 );

		$this->campaign_id     = $campaign_id;
		$this->campaign        = atcf_get_campaign( $this->campaign_id );

		$this->payments        = $this->campaign->data->__get( '_payment_ids' );
		$this->failed_payments = $this->campaign->data->__get( '_campaign_failed_payments' );

		$this->gateways        = edd_get_enabled_payment_gateways();

		$this->get_payments();
		$this->sort_payments();
		$this->process();
		$this->log_failed();
		$this->cleanup();
	}

	/**
	 * Assign the payments related to this campaign
	 * to a static/duplicate array associated with the campaign.
	 *
	 * This will be modified as payments are processed and used as
	 * our "destructable" list of payments we still need to process.
	 *
	 * If something goes wrong, we always have the actual payments we can 
	 * rebuild the list from.
	 */
	function get_payments() {
		if ( ! empty( $this->payments ) )
			return;

		$backers = $this->campaign->unique_backers();

		foreach ( $backers as $backer ) {
			$payment = get_post( $backer );

			if ( 'preapproval' == get_post_status( $backer ) )
				$this->payments[ $backer ] = $backer;
		}

		update_post_meta( $this->campaign_id, '_payment_ids', $this->payments );
	}

	/**
	 * Sort out our payments for this batch of processing.
	 *
	 * Sort them into gateways, but only do the amount specificed.
	 */
	function sort_payments() {
		$count = 1;

		foreach ( $this->payments as $payment_id ) {
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
	 */
	function process() {

		foreach ( $this->gateways as $gateway => $gateway_args ) {

			if ( ! isset ( $gateway_args[ 'payments' ] ) )
				continue;

			foreach ( $gateway_args[ 'payments' ] as $payment ) {

				// Skip failed payments
				if ( isset( $this->failed_payments[ $gateway ] ) && in_array( $payment, $this->failed_payments[ $gateway ] ) )
					continue;

				// Start the charge from the gateway
				$charge = apply_filters( 'atcf_collect_funds_' . $gateway, false, $payment );

				// If the charge has failed, record it in the failed payments
				if ( ! $charge )
					$this->failed_payments[ $gateway ][ 'payments'][] = $payment;

				// Remove this payment from our master list
				unset( $this->payments[ $payment ] );

				// Allow plugins to do other things when a payment processes
				do_action( 'atcf_process_payment_' . $gateway, $payment, $charge );

			}

		}

	}

	/**
	 * Record notes on failed payments
	 */
	function log_failed() {
		if ( empty( $this->failed_payments ) )
			return;

		foreach ( $this->failed_payments as $gateway => $payments ) {
			
			foreach ( $payments[ 'payments' ] as $payment_id ) {
				edd_insert_payment_note( $payment_id, apply_filters( 'atcf_failed_payment_note', sprintf( __( 'Error processing preapproved payment via %s when collecting funds.', 'atcf' ), $gateway ) ) );

				// Allow plugins to do other things when a payment fails
				do_action( 'atcf_failed_payment', $payment_id, $gateway );
			}

		}
		
	}

	/**
	 * Save what we have done
	 */
	function cleanup() {
		if ( ! empty( $this->failed_payments ) ) {
			update_post_meta( $this->campaign_id, '_campaign_failed_payments', $this->failed_payments );
		}

		if ( ! empty( $this->payments ))  {
			update_post_meta( $this->campaign_id, '_payment_ids', $this->payments );
		}

		if ( empty( $this->payments ) ) {
			add_post_meta( $this->campaign_id, '_campaign_batch_complete', true, true );
		}
	}
}