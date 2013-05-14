<?php
/**
 * Register Shortcode.
 *
 * [appthemer_crowdfunding_register] creates a log in form for users to log in with.
 *
 * @since Appthemer CrowdFunding 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register Shortcode
 *
 * @since CrowdFunding 1.0
 *
 * @return $form
 */
function atcf_shortcode_register() {
	global $post;

	$user = wp_get_current_user();

	ob_start();

	echo '<div class="atcf-register">';
	echo '<form name="registerform" id="registerform" action="" method="post">';
	do_action( 'atcf_shortcode_register', $user, $post );
	echo '</form>';
	echo '</div>';

	$form = ob_get_clean();

	return $form;
}
add_shortcode( 'appthemer_crowdfunding_register', 'atcf_shortcode_register' );

/**
 * Register form
 *
 * @since CrowdFunding 1.0
 *
 * @return $form
 */
function atcf_shortcode_register_form() {
	global $edd_options;
?>
	<p class="atcf-register-name">
		<label for="user_nicename"><?php _e( 'Your Name', 'atcf' ); ?></label>
		<input type="text" name="displayname" id="displayname" class="input" value="" />
	</p>

	<p class="atcf-register-email">
		<label for="user_login"><?php _e( 'Email Address', 'atcf' ); ?></label>
		<input type="text" name="user_email" id="user_email" class="input" value="" />
	</p>

	<p class="atcf-register-username">
		<label for="user_login"><?php _e( 'Username', 'atcf' ); ?></label>
		<input type="text" name="user_login" id="user_login" class="input" value="" />
	</p>

	<p class="atcf-register-password">
		<label for="user_pass"><?php _e( 'Password', 'atcf' ); ?></label>
		<input type="password" name="user_pass" id="user_pass" class="input" value="" />
	</p>
	
	<p class="atcf-register-submit">
		<input type="submit" name="submit" id="submit" class="<?php echo apply_filters( 'atcf_shortcode_register_button_class', 'button-primary' ); ?>" value="<?php _e( 'Register', 'atcf' ); ?>" />
		<input type="hidden" name="action" value="atcf-register-submit" />
		<?php wp_nonce_field( 'atcf-register-submit' ); ?>
	</p>
<?php
}
add_action( 'atcf_shortcode_register', 'atcf_shortcode_register_form' );

/**
 * Process registration submission.
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

	$errors = apply_filters( 'atcf_register_validate', $errors, $_POST );

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

	do_action( 'atcf_register_process_after', $user_id, $_POST );

	$redirect = apply_filters( 'atcf_register_redirect', isset ( $edd_options[ 'profile_page' ] ) ? get_permalink( $edd_options[ 'profile_page' ] ) : home_url() );

	if ( $user_id ) {
		wp_safe_redirect( $redirect );
		exit();
	} else {
		wp_safe_redirect( home_url() );
		exit();
	}
}
add_action( 'template_redirect', 'atcf_registration_handle' );

/**
 * Register a user.
 *
 * Extract a bit that actually creates the user so it can be called elsewhere
 * (such as on the campaign creation process)
 *
 * @since Appthemer CrowdFunding 1.0
 *
 * @return void
 */
function atcf_register_user( $args = array() ) {
	$defaults = array(
		'user_pass'            => wp_generate_password( 12, false ),
		'show_admin_bar_front' => 'false',
		'role'                 => 'campaign_contributor'
	);

	$args = wp_parse_args( $args, $defaults );
	
	$user_id = wp_insert_user($args);

	$secure_cookie = is_ssl() ? true : false;
	wp_set_auth_cookie( $user_id, true, $secure_cookie );
	wp_new_user_notification( $user_id, $args[ 'user_pass' ] );

	return $user_id;
}