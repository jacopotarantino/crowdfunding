<?php
/**
 * Emails
 *
 * Handle a bit of extra email info.
 *
 * @since Astoundify Crowdfunding 0.1-alpha
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Trigger Purchase Receipt
 *
 * Causes the purchase receipt to be emailed when initially pledged.
 *
 * @since Astoundify Crowdfunding 0.1-alpha
 *
 * @param int $payment_id The ID of the payment
 * @param string $new_status The status we are changing to
 * @param string $old_status The old status we are changing from
 * @return void
 */
function atcf_trigger_pending_purchase_receipt( $payment_id, $new_status, $old_status ) {
	// Make sure we don't send a purchase receipt while editing a payment
	if ( isset( $_POST[ 'edd-action' ] ) && $_POST[ 'edd-action' ] == 'edit_payment' )
		return;

	// Check if the payment was already set to complete
	if ( $old_status == 'publish' || $old_status == 'complete' )
		return; // Make sure that payments are only completed once

	// Make sure the receipt is only sent when new status is preapproval
	if ( $new_status != 'preapproval' )
		return;

	// Send email with secure download link
	atcf_email_pending_purchase_receipt( $payment_id );
}
add_action( 'edd_update_payment_status', 'edd_trigger_purchase_receipt', 10, 3 );

/**
 * Build the purchase email.
 *
 * Figure out who to send to, who it's from, etc.
 *
 * @since Astoundify Crowdfunding 0.1-alpha
 *
 * @param int $payment_id The ID of the payment
 * @param boolean $admin_notice Alert admins, or not
 * @return void
 */
function atcf_email_pending_purchase_receipt( $payment_id, $admin_notice = true ) {
	global $edd_options;

	$payment_data = edd_get_payment_meta( $payment_id );
	$user_info    = maybe_unserialize( $payment_data['user_info'] );

	if ( isset( $user_info['id'] ) && $user_info['id'] > 0 ) {
		$user_data = get_userdata($user_info['id']);
		$name = $user_data->display_name;
	} elseif ( isset( $user_info['first_name'] ) && isset( $user_info['last_name'] ) ) {
		$name = $user_info['first_name'] . ' ' . $user_info['last_name'];
	} else {
		$name = $user_info['email'];
	}

	$message  = edd_get_email_body_header();
	$message .= atcf_get_email_body_content( $payment_id, $payment_data );
	$message .= edd_get_email_body_footer();

	$from_name  = isset( $edd_options['from_name'] ) ? $edd_options['from_name'] : get_bloginfo('name');
	$from_email = isset( $edd_options['from_email'] ) ? $edd_options['from_email'] : get_option('admin_email');

	$subject = apply_filters( 'atcf_pending_purchase_subject', __( 'Your pledge has been received', 'atcf' ), $payment_id );
	$subject = edd_email_template_tags( $subject, $payment_data, $payment_id );

	$headers  = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
	$headers .= "Reply-To: ". $from_email . "\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=utf-8\r\n";

	// Allow add-ons to add file attachments
	$attachments = apply_filters( 'atcf_pending_receipt_attachments', array(), $payment_id, $payment_data );

	wp_mail( $payment_data['email'], $subject, $message, $headers, $attachments );

	if ( $admin_notice ) {
		do_action( 'edd_admin_pending_purchase_notice', $payment_id, $payment_data );
	}
}

/**
 * Get the actual pending email body content. Default text, can be filtered, and will
 * use all template tags that EDD supports.
 *
 * @since Astoundify Crowdfunding 0.1-alpha
 *
 * @param int $payment_id The ID of the payment
 * @param array $payment_data The relevant payment data
 * @return string $email_body The actual email body
 */
function atcf_get_email_body_content( $payment_id = 0, $payment_data = array() ) {
	global $edd_options;

	$downloads = edd_get_payment_meta_downloads( $payment_id );
	$campaign  = '';

	if ( $downloads ) {
		foreach ( $downloads as $download ) {
			$id       = isset( $payment_data[ 'cart_details' ] ) ? $download[ 'id' ] : $download;
			$campaign = get_the_title( $id );
			
			continue;
		}
	}

	$default_email_body = __( 'Dear {name}', 'atcf' ) . "\n\n";
	$default_email_body .= sprintf( __( 'Thank you for your pledging to support %1$s. This email is just to let you know your pledge was processed without a hitch! You will only be charged your pledge amount if the %2$s receives 100% funding.', 'atcf' ), $campaign, strtolower( edd_get_label_singular() ) ) . "\n\n";
	$default_email_body .= "{sitename}";

	$email = $default_email_body;

	$email_body = edd_email_template_tags( $email, $payment_data, $payment_id );

	return apply_filters( 'atcf_pending_purchase_receipt', $email_body, $payment_id, $payment_data );
}