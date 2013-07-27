<?php
/**
 * Checkout
 *
 * @since Astoundify Crowdfunding 0.9
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Track number of purchases for each pledge amount.
 *
 * @since Astoundify Crowdfunding 0.9
 *
 * @param int $payment the ID number of the payment
 * @param array $payment_data The payment data for the cart
 * @return void
 */
function atcf_log_pledge_limit( $payment_id, $new_status, $old_status ) {
	global $edd_logs;

	// Make sure that payments are only completed once
	if ( $old_status != 'pending' )
		return;

	// Make sure the payment completion is only processed when new status is complete
	if ( in_array( $new_status, array( 'refunded', 'failed', 'revoked' ) ) )
		return;

	if ( edd_is_test_mode() && ! apply_filters( 'edd_log_test_payment_stats', false ) )
		return;

	$payment_data = edd_get_payment_meta( $payment_id );
	$downloads    = maybe_unserialize( $payment_data['downloads'] );
	
	if ( ! is_array( $downloads ) )
		return;

	foreach ( $downloads as $download ) {
		$variable_pricing = edd_get_variable_prices( $download[ 'id' ] );

		foreach ( $variable_pricing as $key => $value ) {
			$what = $download[ 'options' ][ 'price_id' ];

			if ( $key == $what ) {
				$variable_pricing[ $what ][ 'bought' ] = ( isset ( $variable_pricing[ $what ][ 'bought' ] ) ? $variable_pricing[ $what ][ 'bought' ] : 0 ) + 1;
			}
		}

		update_post_meta( $download[ 'id' ], 'edd_variable_prices', $variable_pricing );
	}
}
add_action( 'edd_update_payment_status', 'atcf_log_pledge_limit', 100, 3 );

function atcf_edd_purchase_form_user_info() {
	if ( ! atcf_theme_supports( 'anonymous-backers' ) )
		return;
?>
	<p id="edd-anon-wrap">
		<label class="edd-label" for="edd-anon">
			<input class="edd-input" type="checkbox" name="edd_anon" id="edd-anon" style="margin-top: -4px; vertical-align: middle;" />
			<?php _e( 'Hide name on backers list?', 'atcf' ); ?>
		</label>
	</p>
<?php
}
add_action( 'edd_purchase_form_user_info', 'atcf_edd_purchase_form_user_info' );

/**
 * Save if the user wants to remain anonymous.
 *
 * This is up to the theme to actually honor.
 *
 * @since Astoundify Crowdfunding 1.2
 *
 * @param arrray $payment_meta Array of payment meta about to be saved
 * @return array $payment_meta An updated array of payment meta
 */
function atcf_anon_save_meta( $payment_meta ) {
	$payment_meta[ 'anonymous' ] = isset ( $_POST[ 'edd_anon' ] ) ? 1 : 0;

	return $payment_meta;
}
add_filter( 'edd_payment_meta', 'atcf_anon_save_meta' );