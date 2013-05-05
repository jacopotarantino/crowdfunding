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

/**
 * Don't allow multiple pledges to be made at once if
 * it is not set to allow them to. When a single campaign page
 * is loaded (they are browsing again), clear their cart.
 *
 * @since Appthemer CrowdFunding 1.0
 *
 * @return void
 */
function atcf_clear_cart() {
	global $edd_options;

	edd_empty_cart();

	return;
}
add_action( 'atcf_found_single', 'atcf_clear_cart' );

function atcf_edd_purchase_form_user_info() {
	$support = get_theme_support( 'appthemer-crowdfunding' );

	if ( ! $support[0][ 'anonymous-backers' ] )
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
 * @since Appthemer CrowdFunding 1.2
 *
 * @param arrray $payment_meta Array of payment meta about to be saved
 * @return array $payment_meta An updated array of payment meta
 */
function atcf_anon_save_meta( $payment_meta ) {
	$payment_meta[ 'anonymous' ] = isset ( $_POST[ 'edd_anon' ] ) ? 1 : 0;

	return $payment_meta;
}
add_filter( 'edd_payment_meta', 'atcf_anon_save_meta' );