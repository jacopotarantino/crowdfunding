<?php
/**
 * Theme Stuff
 *
 * Some stuff themes can use, and theme compatability. 
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 */

/**
 * Extend WP_Query with some predefined defaults to query
 * only campaign items.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 */
class ATCF_Campaign_Query extends WP_Query {
	/**
	 * Extend WP_Query with some predefined defaults to query
	 * only campaign items.
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @param array $args
	 * @return void
	 */
	function __construct( $args = array() ) {
		$defaults = array(
			'post_type'      => array( 'download' ),
			'posts_per_page' => get_option( 'posts_per_page' ),
			'no_found_rows'  => true
		);

		$args = wp_parse_args( $args, $defaults );

		parent::__construct( $args );
	}
}

/**
 * Custom output for variable pricing.
 *
 * Themes can hook into `atcf_campaign_contribute_options` to output
 * their own prices, if they choose to implement a custom solution.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 */
function atcf_purchase_variable_pricing( $download_id ) {
	$variable_pricing = edd_has_variable_prices( $download_id );

	if ( ! $variable_pricing )
		return;

	$prices = edd_get_variable_prices( $download_id );
	$type   = edd_single_price_option_mode( $download_id ) ? 'checkbox' : 'radio';

	do_action( 'edd_before_price_options', $download_id ); 

	do_action( 'atcf_campaign_contribute_options', $prices, $type, $download_id );

	add_action( 'edd_after_price_options', $download_id );
}

/**
 * Always show prices in increasing order.
 *
 * @since Appthemer CrowdFunding 0.5.1
 *
 * @see atcf_purchase_variable_pricing
 * @return array
 */
function atcf_sort_variable_prices( $a, $b ) {
	return $a[ 'amount' ] - $b[ 'amount' ];
}

/**
 * Remove output of variable pricing, and add our own system.
 *
 * @since Appthemer CrowdFunding 0.3-alpha
 *
 * @return void
 */
function atcf_theme_variable_pricing() {
	remove_action( 'edd_purchase_link_top', 'edd_purchase_variable_pricing' );
	add_action( 'edd_purchase_link_top', 'atcf_purchase_variable_pricing' );
}

/**
 * Check for theme support, and remove variable pricing display,
 * as we can assume the theme has implemented it somehow else.
 *
 * @since Appthemer CrowdFunding 0.3-alpha
 *
 * @return void
 */
function atcf_theme_custom_variable_pricing() {
	if ( ! current_theme_supports( 'appthemer-crowdfunding' ) )
		return;

	add_action( 'init', 'atcf_theme_variable_pricing' );
}
add_action( 'after_setup_theme', 'atcf_theme_custom_variable_pricing', 100 );