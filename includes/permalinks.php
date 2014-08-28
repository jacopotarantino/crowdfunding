<?php
/**
 * Add Endpoint for backers. This allows us to monitor
 * the query to create "fake" URLs for seeing backers.
 *
 * @since Astoundify Crowdfunding 0.9
 *
 * @return void
 */

/**
 * Add Endpoint for backers. This allows us to monitor
 * the query to create "fake" URLs for seeing backers.
 *
 * @since Astoundify Crowdfunding 0.9
 *
 * @return void
 */
function atcf_endpoints() {
	add_rewrite_endpoint( 'edit', EP_ALL );
	add_rewrite_endpoint( 'widget', EP_ALL );
}
add_action( 'init', 'atcf_endpoints' );

/**
 * Add Endpoint for backers. This allows us to monitor
 * the query to create "fake" URLs for seeing backers.
 *
 * @since Astoundify Crowdfunding 0.9
 *
 * @return void
 */
function atcf_create_permalink( $endpoint, $base_url ) {
	global $wp_rewrite;

	$url = $base_url;

	$url = add_query_arg( array( $endpoint => 1 ), $url );

	return esc_url( $url );
}