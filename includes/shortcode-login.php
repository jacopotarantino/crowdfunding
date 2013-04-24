<?php
/**
 * Login Shortcode.
 *
 * [appthemer_crowdfunding_login] creates a log in form for users to log in with.
 *
 * @since Appthemer CrowdFunding 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Login Shortcode
 *
 * @since CrowdFunding 1.0
 *
 * @return $form
 */
function atcf_shortcode_login() {
	global $post;

	$user = wp_get_current_user();

	ob_start();

	echo '<div class="atcf-login">';
	do_action( 'atcf_shortcode_login', $user, $post );
	echo '</div>';

	$form = ob_get_clean();

	return $form;
}
add_shortcode( 'appthemer_crowdfunding_login', 'atcf_shortcode_login' );

/**
 * Login form
 *
 * @since CrowdFunding 1.0
 *
 * @return $form
 */
function atcf_shortcode_login_form() {
	global $edd_options;

	wp_login_form( apply_filters( 'atcf_shortcode_login_form_args', array(
		'redirect' => isset ( $edd_options[ 'profile_page' ] ) ? get_permalink( $edd_options[ 'profile_page' ] ) : home_url()
	) ) );
}
add_action( 'atcf_shortcode_login', 'atcf_shortcode_login_form' );

/**
 * Forgot Password/Register links
 *
 * Append helpful links to the bottom of the login form.
 *
 * @since CrowdFunding 1.0
 *
 * @return $form
 */
function atcf_shortcode_login_form_bottom() {
	global $edd_options;

	$add = '<p>
		<a href="' . esc_url( add_query_arg( 'action', 'lostpassword', site_url( 'wp-login.php' ) ) ) . '">' . __( 'Forgot Password', 'atcf' ) . '</a> ';

	if ( isset( $edd_options[ 'register_page' ] ) ) {
		$add .= _x( 'or', 'login form action divider', 'atcf' );
		$add .= ' <a href="' . esc_url( get_permalink( $edd_options[ 'register_page' ] ) ) . '">' . __( 'Register', 'atcf' ) . '</a>';
	}

	$add .= '</p>';

	return $add;
}
add_action( 'login_form_bottom', 'atcf_shortcode_login_form_bottom' );