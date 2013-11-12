<?php
/**
 * Supplement some settings stuff.
 *
 * @since Astoundify Crowdfunding 0.7
 */

/**
 * Add pages to settings. Splice and resplice. Ghetto.
 *
 * @since Astoundify Crowdfunding 0.7
 * 
 * @param $settings
 * @return $settings
 */
function atcf_settings_general_pages( $settings ) {
	$pages = get_pages();
	$pages_options = array( 0 => '' ); // Blank option
	if ( $pages ) {
		foreach ( $pages as $page ) {
			$pages_options[ $page->ID ] = $page->post_title;
		}
	}

	$keys = array_keys( $settings );
	$vals = array_values( $settings );

	$spot = array_search( 'failure_page', $keys ) + 1;

	$keys2 = array_splice( $keys, $spot );
	$vals2 = array_splice( $vals, $spot );

	$keys[] = 'faq_page';
	$keys[] = 'submit_page';
	$keys[] = 'submit_success_page';
	$keys[] = 'profile_page';
	$keys[] = 'login_page';
	$keys[] = 'register_page';

	$vals[] =  array(
		'id'      => 'faq_page',
		'name'    => __( 'FAQ Page', 'atcf' ),
		'desc'    => __( 'A page with general information about your site. Fees, etc.', 'atcf' ),
		'type'    => 'select',
		'options' => $pages_options
	);

	$vals[] =  array(
		'id'      => 'submit_page',
		'name'    => __( 'Submit Page', 'atcf' ),
		'desc'    => __( 'The page that contains the <code>[appthemer_crowdfunding_submit]</code> shortcode.', 'atcf' ),
		'type'    => 'select',
		'options' => $pages_options
	);

	$vals[] =  array(
		'id'      => 'submit_success_page',
		'name'    => __( 'Submit Success Page', 'atcf' ),
		'desc'    => __( 'The page that users are redirected to after a success campaign subimssion.', 'atcf' ),
		'type'    => 'select',
		'options' => $pages_options
	);

	$vals[] =  array(
		'id'      => 'profile_page',
		'name'    => __( 'Profile Page', 'atcf' ),
		'desc'    => __( 'The page that contains the <code>[appthemer_crowdfunding_profile]</code> shortcode.', 'atcf' ),
		'type'    => 'select',
		'options' => $pages_options
	);

	$vals[] =  array(
		'id'      => 'login_page',
		'name'    => __( 'Login Page', 'atcf' ),
		'desc'    => __( 'The page that contains the <code>[appthemer_crowdfunding_login]</code> shortcode.', 'atcf' ),
		'type'    => 'select',
		'options' => $pages_options
	);

	$vals[] =  array(
		'id'      => 'register_page',
		'name'    => __( 'Register Page', 'atcf' ),
		'desc'    => __( 'The page that contains the <code>[appthemer_crowdfunding_register]</code> shortcode.', 'atcf' ),
		'type'    => 'select',
		'options' => $pages_options
	);

	return array_merge( array_combine( $keys, $vals ), array_combine( $keys2, $vals2 ) );
}
add_filter( 'edd_settings_general', 'atcf_settings_general_pages' );

/**
 * General settings for Crowdfunding
 *
 * @since Astoundify Crowdfunding 0.9
 * 
 * @param $settings
 * @return $settings
 */
function atcf_settings_general( $settings ) {
	$settings[ 'atcf_settings' ] = array(
		'id'   => 'atcf_settings',
		'name' => '<strong>' . __( 'Astoundify Crowdfunding Settings', 'atcf' ) . '</strong>',
		'desc' => __( 'Configuration related to crowdfunding.', 'atcf' ),
		'type' => 'header'
	);

	$settings[ 'atcf_automatic_process' ] = array(
		'id'      => 'atcf_automatic_process',
		'name'    => __( 'Automatically Start Payment Processing', 'atcf' ),
		'desc'    => __( 'When a campaign is complete and meets the criteria, payments will automatically start processing.', 'atcf' ),
		'type'    => 'checkbox',
		'std'     => 1
	);

	$settings[ 'atcf_to_process' ] = array(
		'id'   => 'atcf_to_process',
		'name' => __( 'Batch Process', 'atcf' ),
		'desc' => __( 'The number of payments per campaign to process each hour.', 'atcf' ),
		'type' => 'text',
		'size' => 'small',
		'std'  => 20
	);

	$settings[ 'atcf_settings_custom_pledge' ] = array(
		'id'      => 'atcf_settings_custom_pledge',
		'name'    => __( 'Custom Pledging', 'atcf' ),
		'desc'    => __( 'Allow arbitrary amounts to be pledged.', 'atcf' ),
		'type'    => 'checkbox',
		'std'     => 1
	);

	$settings[ 'atcf_settings_campaign_minimum' ] = array(
		'id'   => 'atcf_campaign_length_min',
		'name' => __( 'Minimum Campaign Length', 'atcf' ),
		'desc' => __( 'The minimum days a campaign can run for.', 'atcf' ),
		'type' => 'text',
		'size' => 'small',
		'std'  => 14
	);

	$settings[ 'atcf_settings_campaign_maximum' ] = array(
		'id'   => 'atcf_campaign_length_max',
		'name' => __( 'Maximum Campaign Length', 'atcf' ),
		'desc' => __( 'The maximum days a campaign can run for.', 'atcf' ),
		'type' => 'text',
		'size' => 'small',
		'std'  => 42
	);

	$types = atcf_campaign_types();
	$_types = array();

	foreach ( $types as $key => $type ) {
		$_types[ $key ] = $type[ 'title' ] . ' &mdash; <small>' . $type[ 'description' ] . '</small>';
	}

	$settings[ 'atcf_settings_campaign_types' ] = array(
		'id'      => 'atcf_campaign_types',
		'name'    => __( 'Campaign Types', 'atcf' ),
		'desc'    => __( 'Select which campaign types are allowed.', 'atcf' ),
		'type'    => 'multicheck',
		'options' => $_types
	);

	$settings[ 'atcf_settings_require_account' ] = array(
		'id'      => 'atcf_settings_require_account',
		'name'    => __( 'Require Account', 'atcf' ),
		'desc'    => __( 'Require users to be logged in to submit a campaign.', 'atcf' ),
		'type'    => 'checkbox'
	);

	return $settings;
}
add_filter( 'edd_settings_general', 'atcf_settings_general', 100 );