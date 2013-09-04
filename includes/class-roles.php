<?php
/**
 * Some role stuff
 *
 * @since Astoundify Crowdfunding 0.6
 */

class ATCF_Roles {
	public function __construct() {
		$this->add_roles();
		$this->add_caps();
	}

	/**
	 * Add the Campaign Contributor role.
	 *
	 * @since Astoundify Crowdfunding 1.5
	 *
	 * @return void
	 */
	public function add_roles() {
		remove_role( 'campaign_contributor' );

		add_role( 'campaign_contributor', __( 'Campaign Contributor', 'atcf' ), apply_filters( 'atcf_campaign_contributor_role', array(
			'read'                   => true,
			'upload_files'           => true,
			'edit_others_pages'      => true,
			'edit_published_pages'   => true,
			'edit_posts'             => true,
			'publish_posts'          => true,
			'delete_posts'           => true,
			'delete_published_posts' => true,
			'edit_published_posts'   => true
		) ) );
	}

	/**
	 * Add the contributor-specific caps
	 *
	 * @since Astoundify Crowdfunding 1.5
	 *
	 * @return void
	 */
	public function add_caps() {
		global $wp_roles;

		if ( class_exists('WP_Roles') )
			if ( ! isset( $wp_roles ) )
				$wp_roles = new WP_Roles();

		if ( is_object( $wp_roles ) ) {
			$wp_roles->add_cap( 'campaign_contributor', 'level_1' );
			$wp_roles->add_cap( 'campaign_contributor', 'submit_campaigns' );
			$wp_roles->add_cap( 'campaign_contributor', 'edit_product' );
			$wp_roles->add_cap( 'campaign_contributor', 'edit_products' );
			$wp_roles->add_cap( 'campaign_contributor', 'delete_product' );
			$wp_roles->add_cap( 'campaign_contributor', 'delete_products' );
			$wp_roles->add_cap( 'campaign_contributor', 'publish_products' );
			$wp_roles->add_cap( 'campaign_contributor', 'edit_published_products' );
			$wp_roles->add_cap( 'campaign_contributor', 'assign_product_terms' );
		}
	}
}

/**
 * When an image is being uploaded, if we are on the frontend,
 * only allow images to be uploaded.
 *
 * @since Astoundify Crowdfunding 1.5
 *
 * @return void
 */
function atcf_maybe_filter_mimes( $files ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		add_filter( 'upload_mimes', 'atcf_post_mime_types' );
	}

	return $files;
}
add_filter( 'wp_handle_upload_prefilter', 'atcf_maybe_filter_mimes' );

/**
 * Only allow images to be uploaded.
 *
 * @since Astoundify Crowdfunding 1.5
 *
 * @return void
 */
function atcf_post_mime_types( $mimes ) {
	$mimes = array(
	    'jpg|jpeg|jpe' => 'image/jpeg',
	    'gif'          => 'image/gif',
		'png'          => 'image/png',
	);

	return $mimes;
}

/**
 * Redirect users who shouldn't be here.
 *
 * @since Astoundify Crowdfunding 0.7.1
 *
 * @return void
 */
function atcf_prevent_admin_access() {
	if (
		// Look for the presence of /wp-admin/ in the url
		stripos( $_SERVER['REQUEST_URI'], '/wp-admin/' ) !== false
		&&
		// Allow calls to async-upload.php
		stripos( $_SERVER['REQUEST_URI'], 'async-upload.php' ) == false
		&&
		// Allow calls to admin-ajax.php
		stripos( $_SERVER['REQUEST_URI'], 'admin-ajax.php' ) == false
	) {
		if ( current_user_can( 'submit_campaigns' ) && ! current_user_can( 'manage_options' ) && ! defined( 'DOING_AJAX' ) ) {
			wp_safe_redirect( home_url() );
			exit();
		}
	}
}
add_action( 'admin_init', 'atcf_prevent_admin_access', 1000 );

/**
 * Shim default contact methods.
 *
 * @since Astoundify Crowdfunding 0.9
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

function alt_set_only_author( $wp_query ) {
	global $current_user;

	if( is_admin() && ! current_user_can( 'edit_others_posts' ) ) {
		$wp_query->set( 'author', $current_user->ID );
	}
}
add_action( 'pre_get_posts', 'alt_set_only_author' );