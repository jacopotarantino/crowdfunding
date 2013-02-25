<?php

/** Frontend Query *******************************************************/

class ATCF_Campaign_Query extends WP_Query {
	/**
	 * Extend WP_Query with some predefined defaults to query
	 * only download items.
	 *
	 * @since 
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
 * 
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
add_action( 'edd_purchase_link_top', 'atcf_purchase_variable_pricing' );