<?php
/**
 * Some role stuff
 *
 * @since Appthemer CrowdFunding 0.6
 */

/**
 * Create a campaign contributor role
 *
 * @since Appthemer CrowdFunding 0.6
 *
 * @return void
 */
function atcf_roles() {
	global $wp_roles;

	$campaign_contributor = add_role( 'campaign_contributor', 'Campaign Contributor', array(
		'read' 						=> true,
		'edit_posts' 				=> false,
		'delete_posts' 				=> false
	) );

	if ( class_exists('WP_Roles') )
		if ( ! isset( $wp_roles ) )
			$wp_roles = new WP_Roles();

	$wp_roles->add_cap( 'campaign_contributor', 'submit_campaigns' );
	$wp_roles->add_cap( 'campaign_contributor', 'read' );
}
add_action( 'init', 'atcf_roles' );

/**
 * Redirect users who shouldn't be here.
 *
 * @since Appthemer CrowdFunding 0.7.1
 *
 * @return void
 */
function atcf_prevent_admin_access() {
	if ( current_user_can( 'submit_campaigns' ) && ! current_user_can( 'edit_posts' ) ) {
		wp_safe_redirect( home_url() );
		exit;
	}
}
add_action( 'admin_init', 'atcf_prevent_admin_access', 1 );

/**
 * Shim default contact methods.
 *
 * @since Appthemer CrowdFunding 0.9
 *
 * @return void
 */
function atcf_contactmethods( $contactmethods ) {
	$contactmethods[ 'twitter' ]  = 'Twitter';
	$contactmethods[ 'facebook' ] = 'Facebook';

	unset( $contactmethods[ 'aim' ] );
	unset( $contactmethods[ 'yim' ] );
	unset( $contactmethods[ 'jabber' ] );
	
	return $contactmethods;
}
add_filter( 'user_contactmethods', 'atcf_contactmethods', 10, 1 );

/**
 * Handle any possible registration.
 *
 * @since Appthemer CrowdFunding 1.0
 *
 * @return void
 */
function atcf_registration_handle() {
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;
	
	if ( empty( $_POST['action' ] ) || ( 'atcf-register-submit' !== $_POST[ 'action' ] ) )
		return;

	if ( ! wp_verify_nonce( $_POST[ '_wpnonce' ], 'atcf-register-submit' ) )
		return;

	$errors   = new WP_Error();

	$nicename = isset( $_POST[ 'displayname' ] ) ? esc_attr( $_POST[ 'displayname' ] ) : null;
	$email    = isset( $_POST[ 'user_email' ] ) ? esc_attr( $_POST[ 'user_email' ] ) : null;
	$username = isset( $_POST[ 'user_login' ] ) ? esc_attr( $_POST[ 'user_login' ] ) : null;
	$password = isset( $_POST[ 'user_pass' ] ) ? esc_attr( $_POST[ 'user_pass' ] ) : null;

	/** Check Email */
	if ( empty( $email ) || ! is_email( $email ) )
		$errors->add( 'invalid-email', __( 'Please enter a valid email address.', 'atcf' ) );

	if ( email_exists( $email ) )
		$errors->add( 'taken-email', __( 'That contact email address already exists.', 'atcf' ) );

	/** Check Password */
	if ( empty( $email ) || ! is_email( $email ) )
		$errors->add( 'invalid-password', __( 'Please choose a secure password.', 'atcf' ) );

	if ( ! empty ( $errors->errors ) )
		wp_die( $errors );

	if ( '' == $username )
		$username = $email;

	if ( '' == $nicename )
		$nicename = $username;

	$user_id = atcf_register_user( array(
		'user_login'           => $username, 
		'user_pass'            => $password, 
		'user_email'           => $email,
		'display_name'         => $nicename,
	) );

	if ( $user_id ) {
		wp_safe_redirect( isset ( $edd_options[ 'profile_page' ] ) ? get_permalink( $edd_options[ 'profile_page' ] ) : home_url() );
		exit();
	}
}
add_action( 'template_redirect', 'atcf_registration_handle' );

function atcf_register_user( $args = array() ) {
	$defaults = array(
		'password'             => wp_generate_password( 12, false ),
		'show_admin_bar_front' => 'false',
		'role'                 => 'campaign_contributor'
	);

	$args = wp_parse_args( $args, $defaults );
	
	$user_id  = wp_insert_user($args);

	$secure_cookie = is_ssl() ? true : false;
	wp_set_auth_cookie( $user_id, true, $secure_cookie );
	wp_new_user_notification( $user_id, $password );

	return $user_id;
}