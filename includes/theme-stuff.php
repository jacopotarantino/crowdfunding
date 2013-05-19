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

/**
 * When a campaign is over, show a message.
 *
 * @since AppThemer Crowdfunding 1.3
 *
 * @return void
 */
function atcf_campaign_notes( $campaign ) {
	$end_date = date( get_option( 'date_format' ), strtotime( $campaign->end_date() ) );

	if ( 'fixed' == $campaign->type() ) {
?>
	<?php if ( ! $campaign->is_active() && ! $campaign->is_funded() ) : ?>
		<div class="edd_errors">
			<p class="edd_error"><?php printf( __( '<strong>Funding Unsuccessful</strong>. This project reached the deadline without achieving its funding goal on %s.', 'atcf' ), $end_date ); ?></p>
		</div>
	<?php elseif ( $campaign->is_funded() && ! $campaign->is_active() ) : ?>
		<div class="edd_errors">
			<p class="edd_error"><?php printf( __( '<strong>Funding Successful</strong>. This project reached its goal before %s.', 'atcf' ), $end_date ); ?></p>
		</div>
	<?php endif; ?>
<?php
	} elseif ( 'flexible' == $campaign->type() ) {
?>
	<?php if ( ! $campaign->is_active() ) : ?>
		<div class="edd_errors">
			<p class="edd_error"><?php printf( __( '<strong>Campaign Complete</strong>. This project has ended on %s. No more contributions can be made.', 'atcf' ), $end_date ); ?></p>
		</div>
	<?php endif; ?>
<?php
	} else {
		do_action( 'atcf_campaign_notes_before_' . $campaign->type(), $campaign );
	}
}
add_action( 'atcf_campaign_before', 'atcf_campaign_notes' );

function atcf_campaign_preview_note() {
	global $post;

	if ( ! is_preview() )
		return;
?>
	<div class="edd_errors">
		<p class="edd_error"><?php printf( __( 'This is a preview of your %1$s. <a href="%2$s">Edit</a>', 'atcf' ), strtolower( edd_get_label_singular() ), add_query_arg( array( 'edit' => true ), get_permalink( $post->ID ) ) ); ?></p>
	</div>
<?php
}
add_action( 'atcf_campaign_before', 'atcf_campaign_preview_note' );

add_action( 'atcf_campaign_before', 'edd_print_errors' );
add_action( 'atcf_shortcode_submit_hidden', 'edd_print_errors' );