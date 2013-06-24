<?php
/**
 * PayPal Adaptive Payments gateway functionality.
 *
 * @since Appthemer CrowdFunding 1.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Check the PayPal Adaptive Payments version, and add a notice if
 * it is out of date.
 *
 * @since CrowdFunding 1.3
 *
 * @return boolean
 */
function atcf_gateway_paypal_adaptive_payments_version() {
	if ( version_compare( EDD_EPAP_VERSION, '1.1', '<' ) ) {
		add_action( 'atcf_metabox_campaign_funds_after', 'atcf_gateway_paypal_adaptive_payments_version_notice' );

		return true;
	}

	return false;
}
add_filter( 'atcf_hide_collect_funds_button', 'atcf_gateway_paypal_adaptive_payments_version' );

/**
 * Show a notice if PayPal Adaptive Payments is out of date.
 *
 * @since CrowdFunding 1.3
 *
 * @return void
 */
function atcf_gateway_paypal_adaptive_payments_version_notice() {
	printf( '<p>' . __( '<strong>Note:</strong> Please upgrade your PayPal Adaptive Payments extension before collecting funds.', 'atcf' ) . '</p>' );
}

/**
 * PayPal Adaptive Payments field on frontend submit and edit.
 *
 * @since CrowdFunding 1.1
 *
 * @return void
 */
function atcf_shortcode_submit_field_paypal_adaptive_payments_email( $atts, $campaign ) {
	$paypal_email = null;

	if ( $atts[ 'editing' ] || $atts[ 'previewing' ] )
		$paypal_email = $campaign->__get( 'campaign_email' );
?>
	<p class="atcf-submit-campaign-paypal-email">
		<label for="email"><?php _e( 'PayPal Email', 'atcf' ); ?></label>
		<input type="text" name="email" id="email" value="<?php echo $paypal_email; ?>" />
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_paypal_adaptive_payments_email', 105, 2 );

/**
 * PayPal Adaptive Payments field on backend.
 *
 * @since CrowdFunding 1.1
 *
 * @return void
 */
function atcf_metabox_campaign_info_after_paypal_adaptive_payments( $campaign ) {
	$paypal_email = $campaign->__get( 'campaign_email' );
?>
	<p>
		<label for="campaign_email"><strong><?php _e( 'PayPal Adaptive Payments Email:', 'atcf' ); ?></strong></label><br />
		<input type="text" name="campaign_email" id="campaign_email" value="<?php echo esc_attr( $paypal_email ); ?>" class="regular-text" />
	</p>
<?php
}
add_action( 'atcf_metabox_campaign_info_after', 'atcf_metabox_campaign_info_after_paypal_adaptive_payments' );

/**
 * Validate PayPal Adaptive Payments on the frontend submission (or edit).
 *
 * @since CrowdFunding 1.1
 *
 * @return void
 */
function atcf_campaign_submit_validate_paypal_adaptive_payments( $postdata, $errors ) {
	$email = $postdata[ 'email' ];

	if ( ! isset ( $email ) || ! is_email( $email ) )
		$errors->add( 'invalid-paypal-adaptive-email', __( 'Please make sure your PayPal email address is valid.', 'atcf' ) ); 
}
add_action( 'atcf_campaign_submit_validate', 'atcf_campaign_submit_validate_paypal_adaptive_payments', 10, 2 );
add_action( 'atcf_edit_campaign_validate', 'atcf_campaign_submit_validate_paypal_adaptive_payments', 10, 2 );

/**
 * Save PayPal Adaptive Payments on the frontend submission (or edit).
 *
 * @since CrowdFunding 1.1
 *
 * @return void
 */
function atcf_submit_process_after_paypal_adaptive_payments_save( $campaign, $postdata ) {
	$email = $postdata[ 'email' ];

	update_post_meta( $campaign, 'campaign_email', sanitize_text_field( $email ) );
}
add_action( 'atcf_submit_process_after', 'atcf_submit_process_after_paypal_adaptive_payments_save', 10, 2 );
add_action( 'atcf_edit_campaign_after', 'atcf_submit_process_after_paypal_adaptive_payments_save', 10, 2 );

/**
 * Save PayPal Adaptive Payments on the backend.
 *
 * @since CrowdFunding 1.1
 *
 * @return void
 */
function atcf_metabox_save_paypal_adaptive_payments( $fields ) {
	$fields[] = 'campaign_email';

	return $fields;
}
add_filter( 'edd_metabox_fields_save', 'atcf_metabox_save_paypal_adaptive_payments' );

/**
 * Add settings to set limits
 *
 * @since AppThemer Crowdfunding 1.3
 * 
 * @param $settings
 * @return $settings
 */
function atcf_settings_gateway_paypal_adaptive_payments( $settings ) {
	if ( ! atcf_is_gatweay_active( 'paypal_adaptive_payments' ) )
		return $settings;

	$settings[ 'epap_max_donation' ] = array(
		'id'   => 'epap_max_donation',
		'name' => __( 'Maximum Pledge Amount', 'atcf' ),
		'desc' => '(' . edd_currency_filter( '' ) . ') <span class="description">' . __( 'The maximum amount of money PayPal can accept on your account.', 'atcf' ) . '</span>',
		'type' => 'text',
		'size' => 'small'
	);

	$settings[ 'epap_campaigns_per_year' ] = array(
		'id'   => 'epap_campaigns_per_year',
		'name' => __( 'Maximum Campaigns Per Year', 'atcf' ),
		'desc' => '<span class="description">' . __( 'The maximum amount of campaigns that each user may create per year.', 'atcf' ) . '</span>',
		'type' => 'text',
		'size' => 'small'
	);

	$settings[ 'epap_payments_per_user' ] = array(
		'id'   => 'epap_payments_per_user',
		'name' => __( 'Maximum Payments Per User', 'atcf' ),
		'desc' => '<span class="description">' . __( 'The maximum times a user can contribtue to a single campaign.', 'atcf' ) . '</span>',
		'type' => 'text',
		'size' => 'small'
	);

	return $settings;
}
add_filter( 'edd_settings_gateways', 'atcf_settings_gateway_paypal_adaptive_payments', 200 );

/**
 * Track number or purchases by a registered user.
 *
 * @since Appthemer CrowdFunding 1.3
 *
 * @param int $payment the ID number of the payment
 * @param string $new_status
 * @param string $old_status
 * @return void
 */
function atcf_gateway_pap_log_payments_per_user( $payment_id, $new_status, $old_status ) {
	global $edd_options;

	if ( ! atcf_is_gatweay_active( 'paypal_adaptive_payments' ) )
		return;

	if ( ! isset( $edd_options[ 'epap_payments_per_user' ] ) )
		return;

	if ( $old_status != 'pending' )
		return;

	if ( in_array( $new_status, array( 'refunded', 'failed', 'revoked' ) ) )
		return;

	$gateway   = get_post_meta( $payment_id, '_edd_payment_gateway', true );

	if ( 'paypal_adaptive_payments' != $gateway )
		return;

	$user_id   = get_post_meta( $payment_id, '_edd_payment_user_id', true );
	$user      = get_userdata( $user_id );
	$downloads = edd_get_payment_meta_downloads( $payment_id );

	if ( ! is_array( $downloads ) )
		return;

	$contributed_to = $user->get( 'atcf_contributed_to' );
	
	foreach ( $downloads as $download ) {
		if ( isset ( $contributed_to[ $download[ 'id' ] ] ) ) {
			$contributed_to[ $download[ 'id' ] ] = $contributed_to[ $download[ 'id' ] ] + 1;
		} else {
			$contributed_to[ $download[ 'id' ] ] = 1;
		}
	}

	update_user_meta( $user->ID, 'atcf_contributed_to', $contributed_to );
}
add_action( 'edd_update_payment_status', 'atcf_gateway_pap_log_payments_per_user', 110, 3 );

/**
 * Try to add an item to the cart.
 *
 * If using PAP, and a limit is set, don't let them if the limit is reached.
 *
 * @since Appthemer CrowdFunding 1.3
 *
 * @param int $download_id
 * @param array $options
 * @return boolean
 */
function atcf_gateway_pap_edd_item_in_cart( $download_id, $options ) {
	global $edd_options;

	if ( ! is_user_logged_in() )
		return;

	if ( '' == $edd_options[ 'epap_payments_per_user' ] )
		return;

	$user           = wp_get_current_user();
	$contributed_to = (array) $user->get( 'atcf_contributed_to' );

	if ( ! array_key_exists( $download_id, $contributed_to ) )
		return;

	if ( $contributed_to[ $download_id ] == $edd_options[ 'epap_payments_per_user' ] ) {
		edd_set_error( 'pledge-limit-reached', __( 'You have reached the maximum number of pledges-per-campaign allowed.', 'atcf' ) );

		wp_safe_redirect( get_permalink( $download_id ) );
		exit();
	}
}
add_action( 'edd_pre_add_to_cart', 'atcf_gateway_pap_edd_item_in_cart', 10, 2 );

/**
 * Track campaign submission count per year.
 *
 * If using PAP and they are registered, track their submission.
 *
 * @since Appthemer CrowdFunding 1.3
 *
 * @param int $campaign The ID of hte campaign
 * @param array $postdata
 * @param string $status
 * @return void
 */
function atcf_gateway_pap_submit_process_after( $campaign, $postdata, $status ) {
	if ( 'pending' != $status )
		return;

	$user      = wp_get_current_user();
	$submitted = (array) $user->get( 'atcf_campaigns_created' );
	$year      = date( 'Y' );

	foreach ( $submitted as $years ) {
		if ( isset ( $submitted[ $year ] ) ) {
			$submitted[ $year ] = $submitted[ $year ] + 1;
		} else {
			$submitted[ $year ] = 1;
		}
	}

	update_user_meta( $user->ID, 'atcf_campaigns_created', $submitted );
}
add_action( 'atcf_submit_process_after', 'atcf_gateway_pap_submit_process_after', 10, 3 );

/**
 * Hide the submission form if needed.
 *
 * @since Appthemer CrowdFunding 1.3
 *
 * @param boolean $show
 * @return void
 */
function atcf_gateway_pap_shortcode_submit_hide( $show ) {
	global $edd_options;

	if ( ! is_user_logged_in() )
		return $show;

	if ( ! isset( $edd_options[ 'epap_campaigns_per_year' ] ) )
		return $show;

	$user      = wp_get_current_user();
	$submitted = $user->get( 'atcf_campaigns_created' );
	$year      = date( 'Y' );

	$this_year = isset ( $submitted[ $year ] ) ? $submitted[ $year ] : 0;

	if ( $this_year == $edd_options[ 'epap_campaigns_per_year' ] ) {
		edd_set_error( 'campaign-limit-reached', __( 'You have submitted the maximum number of campaigns allowed for this year.', 'atcf' ) );

		return true;
	}

	return $show;
}
add_filter( 'atcf_shortcode_submit_hide', 'atcf_gateway_pap_shortcode_submit_hide' );

/**
 * If there is a limit, show it on the form.
 *
 * @since Appthemer Crowdfunding 1.3
 *
 * @return void
 */
function atcf_gateway_pap_shortcode_submit_field_rewards_list_before() {
	global $edd_options;

	if ( ! isset( $edd_options[ 'epap_max_donation' ] ) )
		return;

	printf( '<p class="atcf-submit-max-pledge-limit">%s</p>', sprintf( __( '<strong>Note:</strong> There is a %s maximum allowed per reward level.', 'atcf' ), edd_currency_filter( edd_format_amount( $edd_options[ 'epap_max_donation' ] ) ) ) );
}
add_action( 'atcf_shortcode_submit_field_rewards_list_before', 'atcf_gateway_pap_shortcode_submit_field_rewards_list_before' );

/**
 * Process preapproved payments
 * Create a list of receivers
 *
 * @since Appthemer Crowdfunding 1.3
 *
 * @return array $receivers
 */
function atcf_gateway_paypal_adaptive_payments_receivers( $campaign ) {
	global $edd_options;

	$owner           = $edd_options[ 'epap_receivers' ];
	$owner           = explode( '|', $owner );
	$owner_email     = $owner[0];
	$owner_amount    = $owner[1];

	if ( 'flexible' == $campaign->type() ) {
		$owner_amount = $owner_amount + $edd_options[ 'epap_flexible_fee' ];
	}

	$campaign_amount = 100 - $owner_amount;
	$campaign_email  = $campaign->__get( 'campaign_email' );

	$receivers       = array(
		array(
			trim( $campaign_email ),
			absint( $campaign_amount )
		),
		array(
			trim( $owner_email ),
			absint( $owner_amount )
		)
	);

	return apply_filters( 'atcf_gateway_paypal_adaptive_payments_receivers', $receivers, $campaign );
}

/**
 * Process preapproved payments
 *
 * @since Appthemer Crowdfunding 1.1
 *
 * @return void
 */
function atcf_collect_funds_paypal_adaptive_payments( $gateway, $gateway_args, $campaign, $failed_payments ) {
	global $edd_options, $failed_payments;

	if ( ! isset ( $gateway_args[ 'payments' ] ) )
		return;

	foreach ( $gateway_args[ 'payments' ] as $payment ) {
		$charge = epap_process_preapprovals( $payment, atcf_gateway_paypal_adaptive_payments_receivers( $campaign ) );
		
		if ( ! $charge )
			$failed_payments[ $gateway ][ 'payments' ][] = $payment;

		do_action( 'atcf_process_payment_' . $gateway, $payment, $charge );
	}

	return $failed_payments;
}
add_action( 'atcf_collect_funds_paypal_adaptive_payments', 'atcf_collect_funds_paypal_adaptive_payments', 10, 4 );