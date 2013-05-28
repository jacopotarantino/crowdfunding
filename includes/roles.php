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
		
	$author = get_role( 'author' );

	$campaign_contributor = add_role( 'campaign_contributor', 'Campaign Contributor', wp_parse_args( array(
		'read' 						=> true,
		'edit_posts' 				=> true,
		'publish_posts'             => true,
		'delete_posts' 				=> false,
		'upload_files'              => true,
	), $author->capabilities ) );

	$contributor = get_role( 'campaign_contributor' );

	$contributor->add_cap( 'submit_campaigns' );
	$contributor->add_cap( 'edit_product' );
	$contributor->add_cap( 'edit_products' );
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