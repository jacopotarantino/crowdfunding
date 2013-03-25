<?php
/**
 * Supplement some settings stuff.
 *
 * @since Appthemer CrowdFunding 0.7
 */

/**
 * Add pages to settings. Splice and resplice. Ghetto.
 *
 * @since AppThemer Crowdfunding 0.7
 * 
 * @param $settings
 * @return $settings
 */
function atcf_settings_general( $settings ) {
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

	return array_merge( array_combine( $keys, $vals ), array_combine( $keys2, $vals2 ) );
}
add_filter( 'edd_settings_general', 'atcf_settings_general' );

/**
 * Add settings to set a flexible fee
 *
 * @since AppThemer Crowdfunding 0.7
 * 
 * @param $settings
 * @return $settings
 */
function atcf_settings_gateway( $settings ) {
	$settings[ 'epap_flexible_fee' ] = array(
			'id'   => 'epap_flexible_fee',
			'name' => __( 'Additional Flexible Fee', 'epap' ),
			'desc' => __( '%. <span class="description">If a campaign is flexible, increase commission by this percent.</span>', 'atcf' ),
			'type' => 'text',
			'size' => 'small'
		);

	return $settings;
}
add_filter( 'edd_settings_gateways', 'atcf_settings_gateway', 100 );