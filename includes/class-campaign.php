<?php
/**
 * Single Campaign
 *
 * @since Astoundify Crowdfunding 0.1-alpha
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class ATCF_Campaign {
	public $ID;
	public $data;

	function __construct( $post ) {
		$this->data = get_post( $post );
		$this->ID   = $this->data->ID;
	}

	/**
	 * Getter
	 *
	 * @since Astoundify Crowdfunding 0.1-alpha
	 *
	 * @param string $key The meta key to fetch
	 * @return string $meta The fetched value
	 */
	public function __get( $key ) {
		$meta = apply_filters( 'atcf_campaign_meta_' . $key, $this->data->__get( $key ) );

		return $meta;
	}

	/**
	 * Submit field helper.
	 *
	 * Try and gather the correct piece of information when editing/previewing a campaign
	 *
	 * @since Atoundify Crowdfunding 1.6
	 */
	public function submit_field_data( $key ) {
		$data = null;

		switch ( $key ) {
			case 'title' : 
				$data = $this->data->post_title;
			break;

			case 'length' :
				$data = $this->days_remaining();
			break;

			case 'description' :
				$data = wp_richedit_pre( $this->data->post_content );
			break;

			case 'excerpt' :
				$data = apply_filters( 'get_the_excerpt', $this->data->post_excerpt );
			break;

			case 'norewards' :
				$data = $this->is_donations_only();
			break;

			case 'rewards' :
				$data = edd_get_variable_prices( $this->ID );
			break;

			case 'physical' :
				$data = $this->needs_shipping();
			break;
			
			default :
				
			break;
		}

		if ( ! $data && method_exists( __CLASS__, $key ) )
			$data = $this->$key();

		return $data;
	}

	/**
	 * Campaign Featured
	 *
	 * @since Astoundify Crowdfunding 0.1-alpha
	 *
	 * @return sting Campaign Featured
	 */
	public function featured() {
		return $this->__get( '_campaign_featured' );
	}

	/**
	 * Needs Shipping
	 *
	 * @since Astoundify Crowdfunding 0.9
	 *
	 * @return sting Requires Shipping
	 */
	public function needs_shipping() {
		$physical = $this->__get( '_campaign_physical' );

		return apply_filters( 'atcf_campaign_needs_shipping', $physical, $this );
	}

	/**
	 * Campaign Goal
	 *
	 * @since Astoundify Crowdfunding 0.1-alpha
	 *
	 * @param boolean $formatted Return formatted currency or not
	 * @return sting $goal A goal amount (formatted or not)
	 */
	public function goal( $formatted = true ) {
		$goal = $this->__get( 'campaign_goal' );

		if ( ! is_numeric( $goal ) )
			return 0;

		if ( $formatted )
			return edd_currency_filter( edd_format_amount( $goal ) );

		return $goal;
	}

	/**
	 * Campaign Type
	 *
	 * @since Astoundify Crowdfunding 0.7
	 *
	 * @return string $type The type of campaign
	 */
	public function type() {
		$type = $this->__get( 'campaign_type' );

		if ( ! $type )
			$type = atcf_campaign_type_default();

		return $type;
	}

	/**
	 * Campaign Location
	 *
	 * @since Astoundify Crowdfunding 0.1-alpha
	 *
	 * @return sting Campaign Location
	 */
	public function location() {
		return $this->__get( 'campaign_location' );
	}

	/**
	 * Campaign Author
	 *
	 * @since Astoundify Crowdfunding 0.1-alpha
	 *
	 * @return sting Campaign Author
	 */
	public function author() {
		return $this->__get( 'campaign_author' );
	}

	/**
	 * Campaign Contact Email
	 *
	 * @since Astoundify Crowdfunding 0.5
	 *
	 * @return sting Campaign Contact Email
	 */
	public function contact_email() {
		return $this->__get( 'campaign_contact_email' );
	}

	/**
	 * Campaign End Date
	 *
	 * @since Astoundify Crowdfunding 0.1-alpha
	 *
	 * @return sting Campaign End Date
	 */
	public function end_date() {
		return mysql2date( 'Y-m-d h:i:s', $this->__get( 'campaign_end_date' ), false );
	}

	/**
	 * Is Endless
	 *
	 * @since Astoundify Crowdfunding 1.4
	 *
	 * @return boolean
	 */
	public function is_endless() {
		return $this->__get( 'campaign_endless' );
	}

	/**
	 * Is donations only.
	 *
	 * @since Astoundify Crowdfunding 1.5
	 *
	 * @return boolean
	 */
	public function is_donations_only() {
		return $this->__get( 'campaign_norewards' );
	}

	/**
	 * Campaign Video
	 *
	 * @since Astoundify Crowdfunding 0.1-alpha
	 *
	 * @return sting Campaign Video
	 */
	public function video() {
		return $this->__get( 'campaign_video' );
	}

	/**
	 * Campaign Updates
	 *
	 * @since Astoundify Crowdfunding 0.9
	 *
	 * @return sting Campaign Updates
	 */
	public function updates() {
		return $this->__get( 'campaign_updates' );
	}

	/**
	 * Campaign Backers
	 *
	 * Use EDD logs to get all sales. This includes both preapproved
	 * payments (if they have Plugin installed) or standard payments.
	 *
	 * @since Astoundify Crowdfunding 0.1-alpha
	 *
	 * @return sting Campaign Backers
	 */
	public function backers() {
		global $edd_logs;

		$backers = $edd_logs->get_connected_logs( array(
			'post_parent'    => $this->ID, 
			'log_type'       => atcf_has_preapproval_gateway() ? 'preapproval' : 'sale',
			'post_status'    => array( 'publish' ),
			'posts_per_page' => -1
		) );

		if ( ! $backers )
			return array();

		return $backers;
	}

	/**
	 * Campaign Backers Count
	 *
	 * @since Astoundify Crowdfunding 0.1-alpha
	 *
	 * @return int Campaign Backers Count
	 */
	public function backers_count() {
		$prices  = edd_get_variable_prices( $this->ID );
		$total   = 0;

		if ( empty( $prices ) )
			return 0;

		foreach ( $prices as $price ) {
			$total = $total + ( isset ( $price[ 'bought' ] ) ? $price[ 'bought' ] : 0 );
		}
		
		return absint( $total );
	}

	/**
	 * Campaign Backers Per Price
	 *
	 * Get all of the backers, then figure out what they purchased. Increment
	 * a counter for each price point, so they can be displayed elsewhere. 
	 * Not 100% because keys can change in EDD, but it's the best way I think.
	 *
	 * @since Astoundify Crowdfunding 0.1-alpha
	 *
	 * @return array $totals The number of backers for each price point
	 */
	public function backers_per_price() {
		$backers = $this->backers();
		$prices  = edd_get_variable_prices( $this->ID );
		$totals  = array();

		if ( ! is_array( $backers ) )
			$backers = array();

		foreach ( $prices as $price ) {
			$totals[$price[ 'amount' ]] = 0;
		}

		foreach ( $backers as $log ) {
			$payment_id = get_post_meta( $log->ID, '_edd_log_payment_id', true );

			$payment    = get_post( $payment_id );
			
			if ( empty( $payment ) )
				continue;

			$cart_items = edd_get_payment_meta_cart_details( $payment_id );
			
			foreach ( $cart_items as $item ) {
				if ( isset ( $item[ 'item_number' ][ 'options' ][ 'atcf_extra_price' ] ) ) {
					$price_id = $item[ 'price' ] - $item[ 'item_number' ][ 'options' ][ 'atcf_extra_price' ];
				} else
					$price_id = $item[ 'price' ];

				$totals[$price_id] = isset ( $totals[$price_id] ) ? $totals[$price_id] + 1 : 1;
			}
		}

		return $totals;
	}

	/**
	 * Campaign Days Remaining
	 *
	 * Calculate the end date, minus today's date, and output a number.
	 *
	 * @since Astoundify Crowdfunding 0.1-alpha
	 *
	 * @return int The number of days remaining
	 */
	public function days_remaining() {
		$expires = strtotime( $this->end_date() );
		$now     = current_time( 'timestamp' );

		if ( $now > $expires )
			return 0;

		$diff = $expires - $now;

		if ( $diff < 0 )
			return 0;

		$days = $diff / 86400;

		return ceil( $days );
	}

	/**
	 * Campaign Hours Remaining
	 *
	 * Calculate the end date, minus today's date, and output a number.
	 *
	 * @since Astoundify Crowdfunding 1.4
	 *
	 * @return int The hours remaining
	 */
	public function hours_remaining() {
		$expires = strtotime( $this->end_date() );
		$now     = current_time( 'timestamp' );

		if ( $now > $expires )
			return 0;

		$diff = $expires - $now;

		if ( $diff < 0 )
			return 0;

		$days = $diff / ( 60 * 60 );

		return floor( $days );
	}

	/**
	 * Campaign Percent Completed
	 *
	 * MATH!
	 *
	 * @since Astoundify Crowdfunding 0.1-alpha
	 *
	 * @param boolean $formatted Return formatted currency or not
	 * @return sting $percent The percent completed (formatted with a % or not)
	 */
	public function percent_completed( $formatted = true ) {
		$goal    = $this->goal(false);
		$current = $this->current_amount(false);

		if ( 0 == $goal )
			return $formatted ? 0 . '%' : 0;

		$percent = ( $current / $goal ) * 100;
		$percent = round( $percent );

		if ( $formatted )
			return $percent . '%';

		return $percent;
	}

	/**
	 * Current amount funded.
	 *
	 * @since Astoundify Crowdfunding 0.1-alpha
	 *
	 * @param boolean $formatted Return formatted currency or not
	 * @return sting $total The amount funded (currency formatted or not)
	 */
	public function current_amount( $formatted = true ) {
		$total   = 0;
		$backers = $this->backers();

		if ( 0 == $backers )
			return $formatted ? edd_currency_filter( edd_format_amount( 0 ) ) : 0;

		foreach ( $backers as $backer ) {
			$payment_id = get_post_meta( $backer->ID, '_edd_log_payment_id', true );
			$payment    = get_post( $payment_id );
			
			if ( empty( $payment ) )
				continue;

			$total      = $total + edd_get_payment_amount( $payment_id );
		}
		
		if ( $formatted )
			return edd_currency_filter( edd_format_amount( $total ) );

		return $total;
	}

	function failed_payments() {
		return $this->__get( '_campaign_failed_payments' );
	}

	/**
	 * Campaign Active
	 *
	 * Check if the campaign has expired based on time, or it has
	 * manually been expired (via meta)
	 *
	 * @since Astoundify Crowdfunding 0.1-alpha
	 *
	 * @return boolean
	 */
	public function is_active() {
		$active  = true;

		$expires = strtotime( $this->end_date() );
		$now     = current_time( 'timestamp' );

		if ( $now > $expires )
			$active = false;

		if ( $this->__get( '_campaign_expired' ) )
			$active = false;

		if ( $this->is_collected() )
			$active = false;

		if ( $this->is_endless() )
			$active = true;

		return apply_filters( 'atcf_campaign_active', $active, $this );
	}

	/**
	 * Funds Collected
	 *
	 * When funds are collected in bulk, remember that, so we can end the
	 * campaign, and not repeat things.
	 *
	 * @since Astoundify Crowdfunding 0.3-alpha
	 *
	 * @return boolean
	 */
	public function is_collected() {
		return $this->__get( '_campaign_bulk_collected' );
	}

	/**
	 * Campaign Funded
	 *
	 * @since Astoundify Crowdfunding 0.1-alpha
	 *
	 * @return boolean
	 */
	public function is_funded() {
		if ( $this->current_amount(false) >= $this->goal(false) )
			return true;

		return false;
	}
}

function atcf_get_campaign( $campaign ) {
	$campaign = new ATCF_Campaign( $campaign );

	return $campaign;
}

/**
 * Price Options Heading
 *
 * @since Astoundify Crowdfunding 0.1-alpha
 *
 * @param string $heading Price options heading
 * @return string Modified price options heading
 */
function atcf_edd_price_options_heading( $heading ) {
	return __( 'Reward Options:', 'atcf' );
}

/**
 * Reward toggle text
 *
 * @since Astoundify Crowdfunding 0.1-alpha
 *
 * @param string $heading Reward toggle text
 * @return string Modified reward toggle text
 */
function atcf_edd_variable_pricing_toggle_text( $text ) {
	return __( 'Enable multiple reward options', 'atcf' );
}

/**
 * Campaign Types
 *
 * @since Astoundify Crowdfunding 0.9
 */
function atcf_campaign_types() {
	$types = array(
		'fixed'    => array(
			'title'       => __( 'All-or-nothing', 'atcf' ),
			'description' => __( 'Only collect pledged funds when the campaign ends if the set goal is met.', 'atcf' )
		),
		'flexible' => array(
			'title'       => __( 'Flexible', 'atcf' ),
			'description' => __( 'Collect funds pledged at the end of the campaign no matter what.', 'atcf' )
		)
	);

	if ( ! atcf_has_preapproval_gateway() ) {
		$types = array(
			'donation'    => array(
				'title'       => __( 'Donation', 'atcf' ),
				'description' => __( 'Funds will be collected automatically as pledged.', 'atcf' )
			)
		);
	}

	return apply_filters( 'atcf_campaign_types', $types );
}

/**
 * @missing
 *
 * @since Astoundify Crowdfunding unknown
 */
function atcf_campaign_types_active() {
	global $edd_options;

	$types  = atcf_campaign_types();
	$active = isset ( $edd_options[ 'atcf_campaign_types' ] ) ? $edd_options[ 'atcf_campaign_types' ] : null;

	if ( ! $active ) {
		$keys = array();

		foreach ( $types as $key => $type )
			$keys[ $key ] = $type[ 'title' ] . ' &mdash; <small>' . $type[ 'description' ] . '</small>';

		return $keys;
	}

	return $active;
}

/**
 * @missing
 *
 * @since Astoundify Crowdfunding unknown
 */
function atcf_campaign_type_default() {
	$type = apply_filters( 'atcf_campaign_type_default', atcf_has_preapproval_gateway() ? 'fixed' : 'donation' );

	return $type;
}