<?php

function atcf_roles() {
	global $wp_roles;

	$campaign_contributor = add_role( 'campaign_contributor', 'Campaign Contributor', array(
		'read' 						=> false,
		'edit_posts' 				=> false,
		'delete_posts' 				=> false
	) );
}
add_action( 'admin_init', 'atcf_roles' );

function atcf_prevent_admin_access() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_safe_redirect( home_url() );
		exit;
	}
}
add_action( 'admin_init', 'atcf_prevent_admin_access');