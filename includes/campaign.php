<?php
/**
 * Campaigns
 */

/** Global Campaigns *******************************************************/

class ATCF_Campaigns {

	public function __construct() {
		add_action( 'init', array( $this, 'setup' ) );
	}

	/**
	 * Some basic tweaking.
	 *
	 * Set the archive slug, and remove formatting from prices.
	 *
	 * @since Function 2.0
	 */
	function setup() {
		define( 'EDD_SLUG', 'campaigns' );
		
		remove_action( 'edd_purchase_link_top', 'edd_purchase_variable_pricing' );

		add_filter( 'edd_download_labels', array( $this, 'download_labels' ) );
		add_filter( 'edd_default_downloads_name', array( $this, 'download_names' ) );
		add_filter( 'edd_download_supports', array( $this, 'download_supports' ) );
		
		if ( ! is_admin() )
			return;

		add_filter( 'manage_edit-download_columns', array( $this, 'dashboard_columns' ), 11, 1 );
		add_filter( 'edd_download_tag_args', array( $this, 'download_tag_args' ) );

		add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ), 11 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		add_filter( 'edd_metabox_fields_save', array( $this, 'meta_boxes_save' ) );
		add_filter( 'edd_metabox_save_campaign_end_date', 'atcf_campaign_save_end_date' );

		add_action( 'admin_action_atcf-collect-funds', array( $this, 'collect_funds' ) );
		add_filter( 'post_updated_messages', array( $this, 'messages' ) );
	}

	/**
	 * Download label. Change it to "Our Store" for the archives page.
	 *
	 * @since Function 2.0
	 *
	 * @param array $labels The preset labels
	 * @return array $labels The modified labels
	 */
	function download_labels( $labels ) {
		$labels =  apply_filters( 'atcf_campaign_labels', array(
			'name' 				=> __( 'Campaigns', 'atcf' ),
			'singular_name' 	=> __( 'Campaign', 'atcf' ),
			'add_new' 			=> __( 'Add New', 'atcf' ),
			'add_new_item' 		=> __( 'Add New Campaign', 'atcf' ),
			'edit_item' 		=> __( 'Edit Campaign', 'atcf' ),
			'new_item' 			=> __( 'New Campaign', 'atcf' ),
			'all_items' 		=> __( 'All Campaigns', 'atcf' ),
			'view_item' 		=> __( 'View Campaign', 'atcf' ),
			'search_items' 		=> __( 'Search Campaigns', 'atcf' ),
			'not_found' 		=> __( 'No Campaigns found', 'atcf' ),
			'not_found_in_trash'=> __( 'No Campaigns found in Trash', 'atcf' ),
			'parent_item_colon' => '',
			'menu_name' 		=> __( 'Campaigns', 'atcf' )
		) );

		return $labels;
	}

	function download_names( $labels ) {
		$cpt_labels = $this->download_labels( array() );

		$labels = array(
			'singular' => $cpt_labels[ 'singular_name' ],
			'plural'   => $cpt_labels[ 'name' ]
		);

		return $labels;
	}

	/**
	 * Add excerpt support for downloads.
	 *
	 * @since Function 2.0
	 *
	 * @param array $supports The post type supports
	 * @return array $supports The modified post type supports
	 */
	function download_supports( $supports ) {
		$supports[] = 'excerpt';
		$supports[] = 'comments';

		return $supports;
	}

	function download_tag_args( $args ) {
		$args[ 'show_ui' ] = false;

		return $args;
	}

	function dashboard_columns( $columns ) {
		$columns = array(
			'cb'                => '<input type="checkbox"/>',
			'title'             => __( 'Name', 'atcf' ),
			'download_category' => __( 'Categories', 'atcf' ),
			'earnings'          => __( 'Amount Funded', 'atcf' ),
			'date'              => __( 'Expires', 'atcf' )
		);

		return $columns;
	}

	function remove_meta_boxes() {		 
		$boxes = array( 
			'edd_file_download_log' => 'normal',
			'edd_purchase_log' => 'normal',
			'edd_download_stats' => 'side'
		);

		foreach ( $boxes as $box => $context ) {
			remove_meta_box( $box, 'download', $context );
		}
	}

	function add_meta_boxes() {
		global $post;

		$campaign = new ATCF_Campaign( $post );

		if ( $campaign->is_funded() )
			add_meta_box( 'cf_campaign_funds', __( 'Campaign Funds', 'atcf' ), '_cf_metabox_campaign_funds', 'download', 'side', 'high' );

		add_meta_box( 'cf_campaign_stats', __( 'Campaign Stats', 'atcf' ), '_cf_metabox_campaign_stats', 'download', 'side', 'high' );

		add_action( 'edd_meta_box_fields', '_cf_metabox_campaign_info', 5 );
	}

	function meta_boxes_save( $fields ) {
		$fields[] = '_campaign_featured';
		$fields[] = 'campaign_goal';
		$fields[] = 'campaign_email';
		$fields[] = 'campaign_end_date';

		return $fields;
	}

	function collect_funds() {
		$campaign = absint( $_GET[ 'campaign' ] );
		$campaign = new ATCF_Campaign( $campaign );

		/** check nonce */
		if ( ! check_admin_referer( 'atcf-collect-funds' ) ) {
			return wp_safe_redirect( add_query_arg( array( 'post' => $campaign->ID, 'action' => 'edit' ), admin_url( 'post.php' ) ) );
			exit();
		}

		/** check roles */
		if ( ! current_user_can( 'update_core' ) ) {
			return wp_safe_redirect( add_query_arg( array( 'post' => $campaign->ID, 'action' => 'edit', 'message' => 12 ), admin_url( 'post.php' ) ) );
			exit();
		}

		/** check funded */
		if ( ! $campaign->is_funded() ) {
			return wp_safe_redirect( add_query_arg( array( 'post' => $campaign->ID, 'action' => 'edit', 'message' => 11 ), admin_url( 'post.php' ) ) );
			exit();
		}

		/** check gateway */
		if ( ! class_exists( 'PayPalAdaptivePaymentsGateway' ) ) {
			return wp_safe_redirect( add_query_arg( array( 'post' => $campaign->ID, 'action' => 'edit' ), admin_url( 'post.php' ) ) );
			exit();
		}

		$paypal_adaptive = new PayPalAdaptivePaymentsGateway();
		$payments        = $campaign->backers();
		$num_collected   = 0;
		$errors          = null;

		foreach ( $payments as $payment ) {
			$payment_id      = $payment->ID;

			$sender_email    = get_post_meta( $payment_id, '_edd_epap_sender_email', true );
			$amount          = get_post_meta( $payment_id, '_edd_epap_sender_amount', true );
			$paid            = get_post_meta( $payment_id, '_edd_epap_sender_paid', true );
			$preapproval_key = get_post_meta( $payment_id, '_edd_epap_preapproval_key', true );

			$receivers       = array(); // Your list of receivers can be sent in here to override the system receivers
		
			/** Already paid or other error */
			if ( $amount > $paid ) {
				$errors = new WP_Error( 'already-paid-' . $payment_id, __( 'This payment has already been collected.', 'atcf' ) );
				
				continue;
			}

			if ( $payment = $paypal_adaptive->pay_preapprovals( $payment_id, $preapproval_key, $sender_email, $amount, $receivers ) ) {
				$responsecode = strtoupper( $payment[ 'responseEnvelope' ][ 'ack' ] );
				
				if ( $responsecode == 'SUCCESS' || $responsecode == 'SUCCESSWITHWARNING' ) {
					$pay_key = $payment[ 'payKey' ];
					
					add_post_meta( $_GET[ 'payment_id'], '_edd_epap_pay_key', $pay_key );
					add_post_meta( $_GET[ 'payment_id'], '_edd_epap_preapproval_paid', true );

					$num_collected = $num_collected + 1;
					
					edd_update_payment_status( $_GET['payment_id'], 'publish' );
				} else {
					$errors = new WP_Error( 'invalid-response-' . $payment_id, __( 'There was an error collecting funds.', 'atcf' ), $payment );
				}
			} else {
				$errors = new WP_Error( 'payment-error-' . $payment_id, __( 'There was an error.', 'atcf' ) );
			}
		}

		if ( is_wp_error( $errors ) )
			wp_die( $errors->get_error_messages() );
		else {
			return wp_safe_redirect( add_query_arg( array( 'post' => $campaign->ID, 'action' => 'edit', 'message' => 13, 'collected' => $num_collected ), admin_url( 'post.php' ) ) );
			exit();
		}
	}

	function messages( $messages ) {
		$messages[ 'download' ][11] = sprintf( __( 'This %s has not reached its funding goal.', 'atcf' ), strtolower( edd_get_label_singular() ) );
		$messages[ 'download' ][12] = sprintf( __( 'You do not have permission to collect funds for %s.', 'atcf' ), strtolower( edd_get_label_plural() ) );
		$messages[ 'download' ][13] = sprintf( __( '%d payments have been collected for this %s.', 'atcf' ), isset ( $_GET[ 'collected' ] ) ? $_GET[ 'collected' ] : 0, strtolower( edd_get_label_plural() ) );

		return $messages;
	}
}

$cf_campaigns = new ATCF_Campaigns;

function atcf_campaign_save_end_date( $new ) {
	if ( ! isset( $_POST[ 'end-aa' ] ) )
		return;

	$aa = $_POST['end-aa'];
	$mm = $_POST['end-mm'];
	$jj = $_POST['end-jj'];
	$hh = $_POST['end-hh'];
	$mn = $_POST['end-mn'];
	$ss = $_POST['end-ss'];

	$aa = ($aa <= 0 ) ? date('Y') : $aa;
	$mm = ($mm <= 0 ) ? date('n') : $mm;
	$jj = ($jj > 31 ) ? 31 : $jj;
	$jj = ($jj <= 0 ) ? date('j') : $jj;

	$hh = ($hh > 23 ) ? $hh -24 : $hh;
	$mn = ($mn > 59 ) ? $mn -60 : $mn;
	$ss = ($ss > 59 ) ? $ss -60 : $ss;

	$end_date = sprintf( "%04d-%02d-%02d %02d:%02d:%02d", $aa, $mm, $jj, $hh, $mn, $ss );
	
	$valid_date = wp_checkdate( $mm, $jj, $aa, $end_date );
	
	if ( ! $valid_date ) {
		return new WP_Error( 'invalid_date', __( 'Whoops, the provided date is invalid.', 'atcf' ) );
	}

	$end_date = get_gmt_from_date( $end_date );

	return $end_date;
}

function _cf_metabox_campaign_stats() {
	global $post;

	$campaign = new ATCF_Campaign( $post );
?>
	<p>
		<strong><?php _e( 'Current Amount:', 'atcf' ); ?></strong>
		<?php echo $campaign->current_amount(); ?> &mdash; <?php echo $campaign->percent_completed(); ?>
	</p>

	<p>
		<strong><?php _e( 'Backers:' ,'atcf' ); ?></strong>
		<?php echo $campaign->backers_count(); ?>
	</p>

	<p>
		<strong><?php _e( 'Days Remaining:', 'atcf' ); ?></strong>
		<?php echo $campaign->days_remaining(); ?>
	</p>
<?php
}

function _cf_metabox_campaign_funds() {
	global $post;

	$campaign = new ATCF_Campaign( $post );
?>
	<p><?php printf( __( 'This %1$s has reached its funding goal. You may now send the funds to the owner. This will end the %1$s.', 'atcf' ), strtolower( edd_get_label_singular() ) ); ?></p>
	<p><?php _e( '<strong>Make sure their PayPal email is valid</strong>', 'atcf' ); ?></p>
	<a href="<?php echo wp_nonce_url( add_query_arg( array( 'action' => 'atcf-collect-funds', 'campaign' => $campaign->ID ), admin_url() ), 'atcf-collect-funds' ); ?>" class="button"><?php _e( 'Collect Funds', 'atcf' ); ?></a>
<?php
}

function _cf_metabox_campaign_info() {
	global $post, $edd_options, $wp_locale;

	/** Verification Field */
	wp_nonce_field( 'cf', 'cf-save' );
	
	$campaign = new ATCF_Campaign( $post );

	$end_date = $campaign->end_date();

	$jj = mysql2date( 'd', $end_date, false );
	$mm = mysql2date( 'm', $end_date, false );
	$aa = mysql2date( 'Y', $end_date, false );
	$hh = mysql2date( 'H', $end_date, false );
	$mn = mysql2date( 'i', $end_date, false );
	$ss = mysql2date( 's', $end_date, false );
?>	
	<p>
		<label for="_campaign_featured">
			<input type="checkbox" name="_campaign_featured" id="_campaign_featured" value="1" <?php checked( 1, $campaign->featured() ); ?> />
			<?php _e( 'Featured campaign', 'atcf' ); ?>
		</label>
	</p>

	<p>
		<label for="campaign_goal"><strong><?php _e( 'Goal:', 'atcf' ); ?></strong></label><br />	
		<?php if ( ! isset( $edd_options[ 'currency_position' ] ) || $edd_options[ 'currency_position' ] == 'before' ) : ?>
			<?php echo edd_currency_filter( '' ); ?><input type="text" name="campaign_goal" id="campaign_goal" value="<?php echo edd_format_amount( $campaign->goal(false) ); ?>" style="width:80px" />
		<?php else : ?>
			<input type="text" name="campaign_goal" id="campaign_goal" value="<?php echo edd_format_amount( $campaign->goal(false) ); ?>" style="width:80px" /><?php echo edd_currency_filter( '' ); ?>
		<?php endif; ?>
	</p>

	<p>
		<label for="campaign_goal"><strong><?php _e( 'PayPal Email:', 'atcf' ); ?></strong></label><br />
		<input type="text" name="campaign_email" id="campaign_email" value="<?php echo $campaign->paypal_email(); ?>" class="regular-text" />
	</p>

	<style>#end-aa { width: 3.4em } #end-jj, #end-hh, #end-mn { width: 2em; }</style>

	<p>
		<strong><?php _e( 'End Date:', 'atcf' ); ?></strong><br />

		<select id="end-mm" name="end-mm">
			<?php for ( $i = 1; $i < 13; $i = $i + 1 ) : $monthnum = zeroise($i, 2); ?>
				<option value="<?php echo $monthnum; ?>" <?php selected( $monthnum, $mm ); ?>>
				<?php printf( '%1$s-%2$s', $monthnum, $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) ); ?>
				</option>
			<?php endfor; ?>
		</select>

		<input type="text" id="end-jj" name="end-jj" value="<?php echo $jj; ?>" size="2" maxlength="2" autocomplete="off" />, 
		<input type="text" id="end-aa" name="end-aa" value="<?php echo $aa; ?>" size="4" maxlength="4" autocomplete="off" /> @
		<input type="text" id="end-hh" name="end-hh" value="<?php echo $hh; ?>" size="2" maxlength="2" autocomplete="off" /> :
		<input type="text" id="end-mn" name="end-mn" value="<?php echo $mn; ?>" size="2" maxlength="2" autocomplete="off" />
		<input type="hidden" id="end-ss" name="end-ss" value="<?php echo $ss; ?>" />
		<input type="hidden" id="campaign_end_date" name="campaign_end_date" />
	</p>
<?php
}

/** Single Campaign *******************************************************/

class ATCF_Campaign {
	public $ID;
	public $data;

	function __construct( $post ) {
		$this->data = get_post( $post );
		$this->ID   = $this->data->ID;
	}

	public function __get( $key ) {
		$meta = apply_filters( 'cf_campaign_meta_' . $key, $this->data->__get( $key ) );

		return $meta;
	}

	public function goal( $formatted = true ) {
		$goal = $this->__get( 'campaign_goal' );

		if ( ! is_numeric( $goal ) )
			return 0;

		if ( $formatted )
			return edd_currency_filter( edd_format_amount( $goal ) );

		return $goal;
	}

	public function paypal_email() {
		return $this->__get( 'campaign_email' );
	}

	public function end_date() {
		return $this->__get( 'campaign_end_date' );
	}

	public function featured() {
		return $this->__get( '_campaign_featured' );
	}

	public function backers() {
		global $edd_logs;

		$backers = $edd_logs->get_logs( $this->ID, 'sale' );

		return $backers;
	}

	public function backers_count() {
		$backers = $this->backers();
		
		if ( ! $backers )
			return 0;

		return count( $backers );
	}

	public function days_remaining() {
		$expires = new DateTime( $this->end_date() );

		$now     = new DateTime();
		$diff    = $now->diff( $expires );

		if ( $diff->invert )
			return 0;

		$return = $diff->format( '%a' );

		return $return;
	}

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

	public function current_amount( $formatted = true ) {
		$total = edd_get_download_earnings_stats( $this->ID );
		
		if ( $formatted )
			return edd_currency_filter( edd_format_amount( $total ) );

		return $total;
	}

	public function is_active() {
		return false;
		$active  = true;

		$expires = new DateTime( $this->end_date() );

		$now     = new DateTime();
		$diff    = $now->diff( $expires );

		if ( $diff->invert )
			$active = false;

		if ( $this->__get( '_campaign_expired' ) )
			$active = false;

		return apply_filters( 'atcf_campaign_active', $active, $this );
	}

	public function is_funded() {
		if ( $this->current_amount(false) >= $this->goal(false) )
			return true;

		return false;
	}
}

/** Frontend Query *******************************************************/

class ATCF_Campaign_Query extends WP_Query {
	/**
	 * Extend WP_Query with some predefined defaults to query
	 * only download items.
	 *
	 * @since Function 2.0
	 *
	 * @param array $args
	 * @return void
	 */
	function __construct( $args = array() ) {
		$defaults = array(
			'post_type'      => array( 'download' ),
			'posts_per_page' => 9,
			'no_found_rows'  => true
		);

		$args = wp_parse_args( $args, $defaults );

		parent::__construct( $args );
	}
}

/** Frontend Submission *******************************************************/

function atcf_shortcode_submit_process() {
	global $edd_options;
	
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;
	
	if ( empty( $_POST['action' ] ) || ( 'cf-campaign-submit' !== $_POST[ 'action' ] ) )
		return;

	if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'cf-campaign-submit' ) )
		return;

	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
	}

	$errors           = null;
	$prices           = array();
	$edd_files        = array();
	$upload_overrides = array( 'test_form' => false );

	$title     = $_POST[ 'title' ];
	$goal      = $_POST[ 'goal' ];
	$length    = $_POST[ 'length' ];
	$category  = $_POST[ 'cat' ];
	$content   = $_POST[ 'description' ];
	$excerpt   = $_POST[ 'excerpt' ];

	$image     = $_FILES[ 'image' ];

	$rewards   = $_POST[ 'rewards' ];
	$files     = $_FILES[ 'files' ];

	$email     = $_POST[ 'email' ];

	/** Check Title */
	if ( empty( $title ) )
		$errors = new WP_Error( 'invalid-title', __( 'Please add a title to this campaign.', 'atcf' ) );

	/** Check Goal */
	if ( ! is_numeric( $goal ) )
		$errors = new WP_Error( 'invalid-goal', sprintf( __( 'Please enter a valid goal amount. All goals are set in the %s currency.', 'atcf' ), $edd_options[ 'currency' ] ) );

	/** Check Length */
	$length = absint( $length );

	if ( $length < 14 )
		$length = 14;
	else if ( $length > 42 )
		$length = 42;

	$end_date = new DateTime();
	$end_date = $end_date->add( new DateInterval( sprintf( 'P%sD', $length ) ) );
	$end_date = get_gmt_from_date( $end_date->format( 'Y-m-d H:i:s' ) );

	/** Check Category */
	$category = absint( $category );

	/** Check Content */
	if ( empty( $content ) )
		$errors = new WP_Error( 'invalid-content', __( 'Please add content to this campaign.', 'atcf' ) );

	/** Check Excerpt */
	if ( empty( $excerpt ) )
		$excerpt = null;

	/** Check Image */
	if ( empty( $image ) )
		$errors = new WP_Error( 'invalid-previews', __( 'Please add a campaign image.', 'atcf' ) );

	/** Check Rewards */
	if ( empty( $rewards ) )
		$errors = new WP_Error( 'invalid-rewards', __( 'Please add at least one reward to the campaign.', 'atcf' ) );

	/** Check Email */
	if ( ! is_email( $email ) )
		$errors = new WP_Error( 'invalid-email', __( 'Please provide a valid PayPal email address.', 'atcf' ) );

	if ( is_wp_error( $errors ) )
		wp_die( $errors->get_error_message() );

	$args = apply_filters( 'atcf_campaign_submit_data', array(
		'post_type'    => 'download',
		'post_status'  => 'pending',
		'post_title'   => $title,
		'post_content' => $content,
		'post_excerpt' => $excerpt,
		'tax_input'    => array(
			'download_category' => array( $category )
		)
	), $_POST );

	$campaign = wp_insert_post( $args, true );

	/** Extra Campaign Information */
	add_post_meta( $campaign, 'goal', $goal );
	add_post_meta( $campaign, 'email', $email );
	add_post_meta( $campaign, 'end_date', $end_date );
	
	foreach ( $rewards as $key => $reward ) {
		$edd_files[] = array(
			'name'      => $reward[ 'price' ],
			'condition' => $key
		);

		$prices[] = array(
			'name'   => $reward[ 'description' ],
			'amount' => $reward[ 'price' ]
		);
	}

	if ( ! empty( $files ) ) {		
		foreach ( $files[ 'name' ] as $key => $value ) {
			if ( $files[ 'name' ][$key] ) {
				$file = array(
					'name'     => $files[ 'name' ][$key],
					'type'     => $files[ 'type' ][$key],
					'tmp_name' => $files[ 'tmp_name' ][$key],
					'error'    => $files[ 'error' ][$key],
					'size'     => $files[ 'size' ][$key]
				);

				$upload = wp_handle_upload( $file, $upload_overrides );

				if ( isset( $upload[ 'url' ] ) )
					$edd_files[$key]['file'] = $upload[ 'url' ];
				else
					unset($files[$key]);
			}
		}
	}

	if ( $image ) {
		$upload = wp_handle_upload( $images, $upload_overrides );

		$attachment = array(
			'guid'           => $upload[ 'url' ], 
			'post_mime_type' => $upload[ 'type' ],
			'post_title'     => $upload[ 'file' ],
			'post_content' => '',
			'post_status' => 'inherit'
		);

		$attach_id = wp_insert_attachment( $attachment, $upload[ 'file' ], $campaign );		
		
		wp_update_attachment_metadata( 
			$attach_id, 
			wp_generate_attachment_metadata( $attach_id, $upload[ 'file' ] ) 
		);

		add_post_meta( $campaign, '_thumbnail_id', $attach_id );
	}

	/** EDD Stuff */
	add_post_meta( $campaign, '_variable_pricing', 1 );
	add_post_meta( $campaign, '_edd_price_options_mode', 1 );
	add_post_meta( $campaign, '_edd_hide_purchase_link', 'on' );
	
	add_post_meta( $campaign, 'edd_variable_prices', $prices );
	add_post_meta( $campaign, 'edd_download_files', $edd_files );

	do_action( 'atcf_submit_process_after', $campaign, $_POST );
}
add_action( 'template_redirect', 'atcf_shortcode_submit_process' );

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

	if ( ! $prices )
		return;

	do_action( 'atcf_campaign_contribute_options', $prices, $type, $download_id );

	add_action( 'edd_after_price_options', $download_id );
}
add_action( 'edd_purchase_link_top', 'atcf_purchase_variable_pricing' );