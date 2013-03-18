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
		'read' 						=> false,
		'edit_posts' 				=> false,
		'delete_posts' 				=> false
	) );

	if ( class_exists('WP_Roles') )
		if ( ! isset( $wp_roles ) )
			$wp_roles = new WP_Roles();

	$wp_roles->add_cap( 'campaign_contributor', 'submit_campaigns' );
}
add_action( 'admin_init', 'atcf_roles' );