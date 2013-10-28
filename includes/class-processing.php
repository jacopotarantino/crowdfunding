<?php

/*
 * Start the processing so our cron events are attached.
 */
new ATCF_Processing;

/**
 * Processing wrapper to attach items to the cron, or easily disable
 * automatic processing if not needed.
 *
 * @since Astoundify Crowdfunding 1.8
 */
class ATCF_Processing {

	function __construct() {
		global $edd_options;

		add_filter( 'edd_settings_general_sanitize', array( $this, 'reset_cron' ) );
		add_action( 'atcf_check_for_completed_campaigns', array( $this, 'find_completed_campaigns' ) );

		/* Register Cron callbacks */
		if ( isset( $edd_options[ 'atcf_automatic_process' ] ) ) {
			add_action( 'atcf_process_payments', array( $this, 'process_payments' ) );
		}

		if ( ! is_admin() )
			return;

		add_action( 'admin_action_atcf-collect-funds', array( $this, 'collect_funds' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_filter( 'post_updated_messages', array( $this, 'admin_messages' ) );
	}

	function reset_cron( $settings ) {
		if ( ! isset( $settings[ 'atcf_automatic_process' ] ) ) {
			wp_clear_scheduled_hook( 'atcf_process_payments' );
		} else {
			ATCF_Install::cron();
		}

		return $settings;
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
	function process_payments() {
		$processing = get_option( 'atcf_processing', array() );

		if ( empty( $processing ) )
			return;

		foreach ( $processing as $key => $campaign ) {
			new ATCF_Process_Campaign( $campaign );
		}
	}

	/**
	 * Completed Campaigns
	 *
	 * There are multiple criteria that must be met in order to be
	 * qualified to be complete (and therefor ready to be processed).
	 *
	 * One of the following:
	 * - Fixed Funding and Goal Met
	 * - Flexible Funding
	 *
	 * And:
	 * - It has passed its expriation date
	 * - It has not previously been marked '_campaign_expired'
	 * - It is not already in the list of currently processing
	 * - It has not completed batch processing before
	 *
	 * @since Astoundify Crowdfunding 1.8
	 *
	 * @return void
	 */
	function find_completed_campaigns() {
		$now = current_time( 'timestamp', true );

		$active_campaigns = get_posts( array(
			'post_type'             => array( 'download' ),
			'post_status'           => array( 'publish' ),
			'meta_query'            => array(
				array(
					'key'     => '_campaign_expired',
					'compare' => 'NOT EXISTS'
				)
			),
			'nopaging'               => true,
			'cache_results'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false
		) );

		// Currently processing
		$processing = get_option( 'atcf_processing', array() );

		foreach ( $active_campaigns as $campaign ) {

			$campaign        = atcf_get_campaign( $campaign );
			$expiration_date = mysql2date( 'G', $campaign->end_date() );

			// Make sure it is actually expired
			if ( $now > $expiration_date ) {

				// Flag that it has expired to avoid calculations in the future.
				update_post_meta( $campaign->ID, '_campaign_expired', current_time( 'mysql' ) );

				if ( (
					! in_array( $campaign->ID, $processing ) && 
					! $campaign->_campaign_batch_complete
				) && (
					( 'fixed' == $campaign->type() && $campaign->is_funded() ) ||
					'flexible' == $campaign->type()
				) ) {
					$processing[ $campaign->ID ] = $campaign->ID;
				}

				do_action( 'atcf_campaign_expired', $campaign );
			}
		}

		update_option( 'atcf_processing', $processing );
	}

	function add_meta_box() {
		global $post, $edd_options;

		if ( ! is_object( $post ) )
			return;

		$campaign = atcf_get_campaign( $post );

		if ( count( $campaign->unique_backers() ) == 0 )
			return;

		if ( 
			isset( $edd_options[ 'atcf_automatic_process' ] ) && 
			in_array( $campaign->ID, get_option( 'atcf_processing', array() ) ) &&
			! $campaign->__get( '_campaign_batch_complete' )
		)
			add_meta_box( 'atcf_campaign_collect_funds', __( 'Campaign Funds', 'atcf' ), array( $this, 'meta_box_processing' ), 'download', 'side', 'high' );

		if ( 
			! isset( $edd_options[ 'atcf_automatic_process' ] ) &&
			! $campaign->__get( '_campaign_batch_complete' ) &&
			( ( $campaign->type() == 'fixed' && $campaign->is_funded() ) || 
			$campaign->type() == 'flexible' )
		)
			add_meta_box( 'atcf_campaign_collect_funds', __( 'Campaign Funds', 'atcf' ), array( $this, 'meta_box_collect' ), 'download', 'side', 'high' );

		if ( $campaign->failed_payments() )
			add_meta_box( 'atcf_campaign_failed_funds', __( 'Failed Payments', 'atcf' ), array( $this, 'meta_box_failed_payments' ), 'download', 'side', 'high' );
	}

	function meta_box_processing() {
		echo '<p>' . __( 'This campaign is currently being processed.', 'atcf' ) . '</p>';
	}

	function meta_box_collect() {
		global $edd_options, $post;

		$campaign   = atcf_get_campaign( $post );

		$pledges    = count( $campaign->unique_backers() );
		$to_process = isset ( $edd_options[ 'atcf_to_process' ] ) ? $edd_options[ 'atcf_to_process' ] : 20;

		echo '<p>' . sprintf( __( '<strong>There are currently unprocessed for this campaign.</strong> If there are more than %d, you will have to process multiple batches.', 'atcf' ), $pledges, $to_process ) . '</p>';

		if ( 'flexible' == $campaign->type() )
			echo '<p>' . __( 'Once processed, this campaign will automatically be closed.' ) . '</p>';

		echo '<p><a href="' . wp_nonce_url( add_query_arg( array( 'action' => 'atcf-collect-funds', 'campaign' => $campaign->ID ), admin_url() ), 'atcf-collect-funds' ) . '" class="button">' . 
					sprintf( __( 'Collect Payments', 'atcf' ), $pledges <= $to_process ? $pledges : $to_process )
			. '</a></p>';
	}

	function meta_box_failed_payments() {
		global $post;

		$campaign        = atcf_get_campaign( $post );

		$failed_payments = $campaign->failed_payments();
		$count           = 0;
		
		foreach ( $failed_payments as $gateways ) {
			foreach ( $gateways as $gateway ) {
				$count = $count + count( $gateway );
			}
		}
		
		echo '<p><strong>' . sprintf( _n( '%d payment failed to process.', '%d payments failed to process.', $count, 'atcf' ), $count ) . '</strong> <a href="' . esc_url( add_query_arg( array( 'page' => 'edd-reports', 'tab' => 'logs', 'view' => 'gateway_errors', 'post_type' => 'download' ), admin_url( 'edit.php' ) ) ) . '">' . __( 'View gateway errors', 'atcf' ) . '</a>.</p>';

		echo  '<ul>';
			
		foreach ( $failed_payments as $gateway => $payments ) {
			echo '<li><strong>' . edd_get_gateway_admin_label( $gateway ) . '</strong>';

				echo '<ul>';
					foreach ( $payments[ 'payments' ] as $payment ) {
						echo '<li><a href="' . esc_url( admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=edit-payment&purchase_id=' . $payment ) ) . '>">#' . $payment . '</a></li>';
					}
				echo '</ul>';
			echo '</li>';
		}
			
		echo '</ul>';

		echo '<p><a href="' . wp_nonce_url( add_query_arg( array( 'action' => 'atcf-collect-failed-funds', 'campaign' => $campaign->ID, 'status' => 'failed' ), admin_url() ), 'atcf-collect-failed-funds' ) . '" class="button">' . __( 'Retry Failed Funds', 'atcf' ) . '</a></p>';
	}

	/**
	 * Collect Funds
	 *
	 * @since Astoundify Crowdfunding 0.1-alpha
	 *
	 * @return void
	 */
	function collect_funds() {
		global $edd_options, $failed_payments;

		$campaign = absint( $_GET[ 'campaign' ] );
		$campaign = atcf_get_campaign( $campaign );

		$process_failed = isset( $_GET[ 'status' ] ) && $_GET[ 'status' ] == 'failed' ? true : false;

		/** check nonce */
		if ( ! check_admin_referer( 'atcf-collect-funds' ) ) {
			return wp_safe_redirect( add_query_arg( array( 'post' => $campaign->ID, 'action' => 'edit' ), admin_url( 'post.php' ) ) );
			exit();
		}

		/** check roles */
		if ( ! current_user_can( 'update_core' ) ) {
			return wp_safe_redirect( add_query_arg( array( 'post' => $campaign->ID, 'action' => 'edit', 'message' => 12 ), admin_url( 'post.php' ) ) );
			exit();
		}

		new ATCF_Process_Campaign( $campaign->ID, $process_failed );

		// Manually expire the campaign
		add_post_meta( $campaign->ID, '_campaign_expired', current_time( 'mysql' ), true );

		return wp_safe_redirect( add_query_arg( array( 'post' => $campaign->ID, 'action' => 'edit', 'message' => 11 ), admin_url( 'post.php' ) ) );
		exit();
	}

	/**
	 * Custom messages for various actions when manually processing.
	 *
	 * @since Astoundify Crowdfunding 1.8
	 *
	 * @param array $messages An array of messages to display
	 * @return array $messages An updated array of messages to display
	 */
	function admin_messages( $messages ) {
		$messages[ 'download' ][11] = sprintf( __( 'Payments have attempted to be processed. Any errors will appear on this page.', 'atcf' ), strtolower( edd_get_label_singular() ) );
		$messages[ 'download' ][12] = sprintf( __( 'You do not have permission to collect funds for %s.', 'atcf' ), strtolower( edd_get_label_plural() ) );

		return $messages;
	}
}

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
		global $edd_options;

		$this->to_process      = isset ( $edd_options[ 'atcf_to_process' ] ) ? $edd_options[ 'atcf_to_process' ] : 20;
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
		update_post_meta( $this->campaign_id, '_campaign_failed_payments', $this->failed_payments );

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