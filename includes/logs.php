<?php
/**
 * Supplement some log stuff.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 */

/**
 * Create a log for preapproval payments
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @param int $payment_id the ID number of the payment
 * @param string $new_status the status of the payment, probably "publish"
 * @param string $old_status the status of the payment prior to being marked as "complete", probably "pending"
 * @return void
 */
function atcf_pending_purchase( $payment_id, $new_status, $old_status ) {
	global $edd_logs;

	// Make sure that payments are only completed once
	if ( $old_status == 'publish' || $old_status == 'complete' )
		return;

	// Make sure the payment completion is only processed when new status is complete
	if ( $new_status != 'preapproval' && $new_status != 'complete' )
		return;

	if ( edd_is_test_mode() && ! apply_filters( 'edd_log_test_payment_stats', false ) )
		return;

	$payment_data = edd_get_payment_meta( $payment_id );
	$downloads    = maybe_unserialize( $payment_data['downloads'] );
	$user_info    = maybe_unserialize( $payment_data['user_info'] );

	if ( ! is_array( $downloads ) )
		return;

	foreach ( $downloads as $download ) {
		$edd_logs->insert_log( 
			array( 
				'post_parent' => $download[ 'id' ], 
				'log_type'    => 'preapproval' 
			), 
			array(
				'payment_id' => $payment_id 
			) 
		);
	}
}
add_action( 'edd_update_payment_status', 'atcf_pending_purchase', 50, 3 );

/**
 * Create a log type for preapproval payments
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @param array $types An array of valid types
 * @return array $types An updated or array of valid types
 */
function atcf_log_type_preapproval( $types ) {
	$types[] = 'preapproval';

	return $types;
}
add_filter( 'edd_log_types', 'atcf_log_type_preapproval' );