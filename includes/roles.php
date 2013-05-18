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
		'edit_posts' 				=> true,
		'publish_posts'             => true,
		'delete_posts' 				=> false,
		'upload_files'              => true,
	) );

	if ( class_exists('WP_Roles') )
		if ( ! isset( $wp_roles ) )
			$wp_roles = new WP_Roles();

	$wp_roles->add_cap( 'campaign_contributor', 'submit_campaigns' );
	$wp_roles->add_cap( 'campaign_contributor', 'edit_product' );
	$wp_roles->add_cap( 'campaign_contributor', 'edit_products' );
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
	if ( current_user_can( 'submit_campaigns' ) && ! current_user_can( 'edit_posts' ) && ! ( defined( 'DOING_AJAX') && DOING_AJAX ) ) {
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