<?php
/**
 * Campaigns
 *
 * All data related to campaigns. This includes wrangling various EDD
 * things, adding extra stuff, etc. There are two main classes:
 *
 * ATCF_Campaigns - Mostly admin things, and changing some settings of EDD
 * ATCF_Campaign  - A singular campaign. Includes getter methods for accessing a single campaign's info
 *
 * @since AT_CrowdFunding 0.1-alpha
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Global Campaigns *******************************************************/

/** Start me up! */
$cf_campaigns = new ATCF_Campaigns;

class ATCF_Campaigns {

	/**
	 * Start things up.
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup' ) );
	}

	/**
	 * Some basic tweaking.
	 *
	 * Set the archive slug, and remove formatting from prices.
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @return void
	 */
	function setup() {
		define( 'EDD_SLUG', apply_filters( 'atcf_edd_slug', 'campaigns' ) );
		
		remove_action( 'edd_purchase_link_top', 'edd_purchase_variable_pricing' );

		add_action( 'init', array( $this, 'endpoints' ) );

		add_filter( 'edd_download_labels', array( $this, 'download_labels' ) );
		add_filter( 'edd_default_downloads_name', array( $this, 'download_names' ) );
		add_filter( 'edd_download_supports', array( $this, 'download_supports' ) );

		do_action( 'atcf_campaigns_actions' );
		
		if ( ! is_admin() )
			return;

		add_filter( 'manage_edit-download_columns', array( $this, 'dashboard_columns' ), 11, 1 );
		add_filter( 'manage_download_posts_custom_column', array( $this, 'dashboard_column_item' ), 11, 2 );
		
		add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ), 11 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		add_filter( 'edd_metabox_fields_save', array( $this, 'meta_boxes_save' ) );
		add_filter( 'edd_metabox_save_campaign_end_date', 'atcf_campaign_save_end_date' );

		add_action( 'admin_action_atcf-collect-funds', array( $this, 'collect_funds' ) );
		add_filter( 'post_updated_messages', array( $this, 'messages' ) );

		do_action( 'atcf_campaigns_actions_admin' );
	}

	/**
	 * Add Endpoint for backers. This allows us to monitor
	 * the query to create "fake" URLs for seeing backers.
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @return void
	 */
	function endpoints() {
		add_rewrite_endpoint( 'backers', EP_ALL );
	}

	/**
	 * Download labels. Change it to "Campaigns".
	 *
	 * @since AT_CrowdFunding 0.1-alpha
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

	/**
	 * Further change "Download" & "Downloads" to "Campaign" and "Campaigns"
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @param array $labels The preset labels
	 * @return array $labels The modified labels
	 */
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
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @param array $supports The post type supports
	 * @return array $supports The modified post type supports
	 */
	function download_supports( $supports ) {
		$supports[] = 'excerpt';
		$supports[] = 'comments';

		return $supports;
	}

	/**
	 * Download Columns
	 *
	 * Add "Amount Funded" and "Expires" to the main campaign table listing. 
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @param array $supports The post type supports
	 * @return array $supports The modified post type supports
	 */
	function dashboard_columns( $columns ) {
		$columns = array(
			'cb'                => '<input type="checkbox"/>',
			'title'             => __( 'Name', 'atcf' ),
			'download_category' => __( 'Categories', 'atcf' ),
			'funded'            => __( 'Amount Funded', 'atcf' ),
			'date'              => __( 'Expires', 'atcf' )
		);

		return $columns;
	}

	/**
	 * Download Column Items
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @param array $supports The post type supports
	 * @return array $supports The modified post type supports
	 */
	function dashboard_column_item( $column, $post_id ) {
		$campaign = atcf_get_campaign( $post_id );

		switch ( $column ) {
			case 'funded' :
				$funded = $campaign->amount_funded();

				echo $funded;
				break;
			default : 
				break;
		}
	}

	/**
	 * Remove some metaboxes that we don't need to worry about. Sales
	 * and download stats, aren't really important. 
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @return void
	 */
	function remove_meta_boxes() {
		$boxes = array( 
			'edd_file_download_log' => 'normal',
			'edd_purchase_log'      => 'normal',
			'edd_download_stats'    => 'side'
		);

		foreach ( $boxes as $box => $context ) {
			remove_meta_box( $box, 'download', $context );
		}
	}

	/**
	 * Add our custom metaboxes.
	 *
	 * - Collect Funds
	 * - Campaign Stats
	 * - Campaign Video
	 *
	 * As well as some other information plugged into EDD in the Download Configuration
	 * metabox that already exists.
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @return void
	 */
	function add_meta_boxes() {
		global $post;

		$campaign = new ATCF_Campaign( $post );

		if ( $campaign->is_funded() && class_exists( 'PayPalAdaptivePaymentsGateway' ) )
			add_meta_box( 'cf_campaign_funds', __( 'Campaign Funds', 'atcf' ), '_atcf_metabox_campaign_funds', 'download', 'side', 'high' );

		add_meta_box( 'atcf_campaign_stats', __( 'Campaign Stats', 'atcf' ), '_atcf_metabox_campaign_stats', 'download', 'side', 'high' );
		add_meta_box( 'atcf_campaign_video', __( 'Campaign Video', 'atcf' ), '_atcf_metabox_campaign_video', 'download', 'normal', 'high' );

		add_action( 'edd_meta_box_fields', '_atcf_metabox_campaign_info', 5 );
	}

	/**
	 * Campaign Information
	 *
	 * Hook in to EDD and add a few more things that will be saved. Use
	 * this so we are already cleared/validated.
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @param array $fields An array of fields to save
	 * @return array $fields An updated array of fields to save
	 */
	function meta_boxes_save( $fields ) {
		$fields[] = '_campaign_featured';
		$fields[] = 'campaign_goal';
		$fields[] = 'campaign_email';
		$fields[] = 'campaign_end_date';
		$fields[] = 'campaign_video';
		$fields[] = 'campaign_location';
		$fields[] = 'campaign_author';

		return $fields;
	}

	/**
	 * Collect Funds
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @return void
	 */
	function collect_funds() {
		global $edd_options;

		$campaign = absint( $_GET[ 'campaign' ] );
		$campaign = new ATCF_Campaign( $campaign );

		/** check gateway */
		if ( ! class_exists( 'PayPalAdaptivePaymentsGateway' ) ) {
			return wp_safe_redirect( add_query_arg( array( 'post' => $campaign->ID, 'action' => 'edit' ), admin_url( 'post.php' ) ) );
			exit();
		}

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

		$paypal_adaptive = new PayPalAdaptivePaymentsGateway();
		$payments        = $campaign->backers();
		$num_collected   = 0;
		$errors          = null;

		$owner           = $edd_options[ 'epap_receivers' ];
		$owner           = explode( '|', $owner );
		$owner_email     = $owner[0];
		$owner_amount    = $owner[1];

		$campaign_amount = 100 - $owner_amount;
		$campaign_email  = $campaign->paypal_email();

		$receivers       = array(
			array(
				'email'  => trim( $campaign_email ),
				'amount' => absint( $campaign_amount )
			),
			array(
				'email'  => trim( $owner_email ),
				'amount' => absint( $owner_amount )
			)
		);

		foreach ( $payments as $payment ) {
			$payment_id      = $payment->ID;

			$sender_email    = get_post_meta( $payment_id, '_edd_epap_sender_email', true );
			$amount          = get_post_meta( $payment_id, '_edd_epap_sender_amount', true );
			$paid            = get_post_meta( $payment_id, '_edd_epap_sender_paid', true );
			$preapproval_key = get_post_meta( $payment_id, '_edd_epap_preapproval_key', true );
		
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
			update_post_meta( $this->ID, '_campaign_expired', 1 );
			return wp_safe_redirect( add_query_arg( array( 'post' => $campaign->ID, 'action' => 'edit', 'message' => 13, 'collected' => $num_collected ), admin_url( 'post.php' ) ) );
			exit();
		}
	}

	/**
	 * Custom messages for various actions when managing campaigns.
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @param array $messages An array of messages to display
	 * @return array $messages An updated array of messages to display
	 */
	function messages( $messages ) {
		$messages[ 'download' ][11] = sprintf( __( 'This %s has not reached its funding goal.', 'atcf' ), strtolower( edd_get_label_singular() ) );
		$messages[ 'download' ][12] = sprintf( __( 'You do not have permission to collect funds for %s.', 'atcf' ), strtolower( edd_get_label_plural() ) );
		$messages[ 'download' ][13] = sprintf( __( '%d payments have been collected for this %s.', 'atcf' ), isset ( $_GET[ 'collected' ] ) ? $_GET[ 'collected' ] : 0, strtolower( edd_get_label_plural() ) );

		return $messages;
	}
}

/**
 * Filter the expiration date for a campaign.
 *
 * A hidden/fake input field so the filter is triggered, then
 * add all the other date fields together to create the MySQL date.
 *
 * @since AT_CrowdFunding 0.1-alpha
 *
 * @param string $date
 * @return string $end_date Formatted date
 */
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

/**
 * Campaign Stats Box
 *
 * These are read-only stats/info for the current campaign.
 *
 * @since AT_CrowdFunding 0.1-alpha
 *
 * @return void
 */
function _atcf_metabox_campaign_stats() {
	global $post;

	$campaign = new ATCF_Campaign( $post );

	do_action( 'atcf_metabox_campaign_stats_before', $campaign );
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
	do_action( 'atcf_metabox_campaign_stats_after', $campaign );
}

/**
 * Campaign Collect Funds Box
 *
 * If a campaign is fully funded (or expired and fully funded) show this box.
 * Includes a button to collect funds.
 *
 * @since AT_CrowdFunding 0.1-alpha
 *
 * @return void
 */
function _atcf_metabox_campaign_funds() {
	global $post;

	$campaign = new ATCF_Campaign( $post );

	do_action( 'atcf_metabox_campaign_funds_before', $campaign );
?>
	<p><?php printf( __( 'This %1$s has reached its funding goal. You may now send the funds to the owner. This will end the %1$s.', 'atcf' ), strtolower( edd_get_label_singular() ) ); ?></p>

	<?php if ( '' != $campaign->paypal_email() ) : ?>
	<p><?php printf( __( 'Make sure <code>%s</code> is a valid PayPal email address.', 'atcf' ), $campaign->paypal_email() ); ?></p>
	<p><a href="<?php echo wp_nonce_url( add_query_arg( array( 'action' => 'atcf-collect-funds', 'campaign' => $campaign->ID ), admin_url() ), 'atcf-collect-funds' ); ?>" class="button button-primary"><?php _e( 'Collect Funds', 'atcf' ); ?></a></p>
	<?php else : ?>
	<p><?php printf( __( 'Please assign a valid PayPal email address to this %s to enable fund collection.', 'atcf' ), strtolower( edd_get_label_singular() ) ); ?>
	<?php endif; ?>
<?php
	do_action( 'atcf_metabox_campaign_funds_after', $campaign );
}

/**
 * Campaign Video Box
 *
 * oEmbed campaign video.
 *
 * @since AT_CrowdFunding 0.1-alpha
 *
 * @return void
 */
function _atcf_metabox_campaign_video() {
	global $post;

	$campaign = new ATCF_Campaign( $post );

	do_action( 'atcf_metabox_campaign_video_before', $campaign );
?>
	<input type="text" name="campaign_video" id="campaign_video" class="widefat" value="<?php echo esc_url( $campaign->video() ); ?>" />
	<p class="description"><?php _e( 'oEmbed supported video links.', 'atcf' ); ?></p>
<?php
	do_action( 'atcf_metabox_campaign_video_after', $campaign );
}

/**
 * Campaign Configuration
 *
 * Hook into EDD Download Information and add a bit more stuff.
 * These are all things that can be updated while the campaign runs/before
 * being published.
 *
 * @since AT_CrowdFunding 0.1-alpha
 *
 * @return void
 */
function _atcf_metabox_campaign_info() {
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

	do_action( 'atcf_metabox_campaign_info_before', $campaign );
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
			<input type="text" name="campaign_goal" id="campaign_goal" value="<?php echo edd_format_amount($campaign->goal(false) ); ?>" style="width:80px" /><?php echo edd_currency_filter( '' ); ?>
		<?php endif; ?>
	</p>

	<p>
		<label for="campaign_location"><strong><?php _e( 'Location:', 'atcf' ); ?></strong></label><br />
		<input type="text" name="campaign_location" id="campaign_location" value="<?php echo esc_attr( $campaign->location() ); ?>" class="regular-text" />
	</p>

	<p>
		<label for="campaign_author"><strong><?php _e( 'Author:', 'atcf' ); ?></strong></label><br />
		<input type="text" name="campaign_author" id="campaign_author" value="<?php echo esc_attr( $campaign->author() ); ?>" class="regular-text" />
	</p>

	<p>
		<label for="campaign_email"><strong><?php _e( 'PayPal Email:', 'atcf' ); ?></strong></label><br />
		<input type="text" name="campaign_email" id="campaign_email" value="<?php echo esc_attr( $campaign->paypal_email() ); ?>" class="regular-text" />
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

		<input type="text" id="end-jj" name="end-jj" value="<?php echo esc_attr( $jj ); ?>" size="2" maxlength="2" autocomplete="off" />, 
		<input type="text" id="end-aa" name="end-aa" value="<?php echo esc_attr( $aa ); ?>" size="4" maxlength="4" autocomplete="off" /> @
		<input type="text" id="end-hh" name="end-hh" value="<?php echo esc_attr( $hh ); ?>" size="2" maxlength="2" autocomplete="off" /> :
		<input type="text" id="end-mn" name="end-mn" value="<?php echo esc_attr( $mn ); ?>" size="2" maxlength="2" autocomplete="off" />
		<input type="hidden" id="end-ss" name="end-ss" value="<?php echo esc_attr( $ss ); ?>" />
		<input type="hidden" id="campaign_end_date" name="campaign_end_date" />
	</p>
<?php
	do_action( 'atcf_metabox_campaign_video_after', $campaign );
}

/**
 * Goal Save
 *
 * Sanitize goal before it is saved, to remove commas.
 *
 * @since AT_CrowdFunding 0.1-alpha
 *
 * @return string $price The formatted price
 */
function atcf_sanitize_goal_save( $price ) {
	global $edd_options;

	$thousands_sep = isset( $edd_options[ 'thousands_separator' ] ) ? $edd_options[ 'thousands_separator' ] : ',';
	$decimal_sep   = isset( $edd_options[ 'decimal_separator'   ] ) ? $edd_options[ 'decimal_separator' ]   : '.';

	if ( $thousands_sep == ',' ) {
		$price = str_replace( ',', '', $price );
	}

	return $price;
}
add_filter( 'edd_metabox_save_campaign_goal', 'atcf_sanitize_goal_save' );

/** Single Campaign *******************************************************/

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
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @param string $key The meta key to fetch
	 * @return string $meta The fetched value
	 */
	public function __get( $key ) {
		$meta = apply_filters( 'atcf_campaign_meta_' . $key, $this->data->__get( $key ) );

		return $meta;
	}

	/**
	 * Campaign Featured
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Featured
	 */
	public function featured() {
		return $this->__get( '_campaign_featured' );
	}

	/**
	 * Campaign Goal
	 *
	 * @since AT_CrowdFunding 0.1-alpha
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
	 * Campaign Location
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Location
	 */
	public function location() {
		return $this->__get( 'campaign_location' );
	}

	/**
	 * Campaign Author
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Author
	 */
	public function author() {
		return $this->__get( 'campaign_author' );
	}

	/**
	 * Campaign PayPal Email
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign PayPal Email
	 */
	public function paypal_email() {
		return $this->__get( 'campaign_email' );
	}

	/**
	 * Campaign End Date
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign End Date
	 */
	public function end_date() {
		return mysql2date( get_option( 'date_format' ), $this->__get( 'campaign_end_date' ), false );
	}

	/**
	 * Campaign Video
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Video
	 */
	public function video() {
		return $this->__get( 'campaign_video' );
	}

	/**
	 * Campaign Backers
	 *
	 * Use EDD logs to get all sales. This includes both preapproved
	 * payments (if they have Plugin installed) or standard payments.
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Backers
	 */
	public function backers() {
		global $edd_logs;

		$backers = $edd_logs->get_connected_logs( array(
			'post_parent' => $this->ID, 
			'log_type'    => 'preapproval',
			'post_status' => array( 'publish' )
		) );

		return $backers;
	}

	/**
	 * Campaign Backers Count
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @return int Campaign Backers Count
	 */
	public function backers_count() {
		$backers = $this->backers();
		
		if ( ! $backers )
			return 0;

		return count( $backers );
	}

	/**
	 * Campaign Backers Per Price
	 *
	 * Get all of the backers, then figure out what they purchased. Increment
	 * a counter for each price point, so they can be displayed elsewhere. 
	 * Not 100% because keys can change in EDD, but it's the best way I think.
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @return array $totals The number of backers for each price point
	 */
	public function backers_per_price() {
		$backers = $this->backers();
		$prices  = edd_get_variable_prices( $this->ID );
		$totals  = array();

		if ( empty( $backers ) )
			return $totals;

		foreach ( $backers as $log ) {
			$payment_id = get_post_meta( $log->ID, '_edd_log_payment_id', true );
			$cart_items = edd_get_payment_meta_cart_details( $payment_id );
			
			foreach ( $cart_items as $item ) {
				$price_id = $item[ 'item_number' ][ 'options' ][ 'price_id' ];

				if ( ! isset( $totals[$price_id] ) )
					$totals[$price_id] = 1;
				else
					$totals[$price_id] = $totals[$price_id] + 1;
			}
		}

		return $totals;
	}

	/**
	 * Campaign Days Remaining
	 *
	 * Calculate the end date, minus today's date, and output a number.
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @return int The number of days remaining
	 */
	public function days_remaining() {
		$expires = new DateTime( $this->end_date() );
		$now     = new DateTime();

		if ( $now > $expires )
			return 0;

		$diff = $expires->getTimestamp() - $now->getTimestamp();

		if ( $diff < 0 )
			return 0;

		$days = $diff / 86400;

		return floor( $days );
	}

	/**
	 * Campaign Percent Completed
	 *
	 * MATH!
	 *
	 * @since AT_CrowdFunding 0.1-alpha
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
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @param boolean $formatted Return formatted currency or not
	 * @return sting $total The amount funded (currency formatted or not)
	 */
	public function current_amount( $formatted = true ) {
		$total   = 0;
		$backers = $this->backers();

		if ( empty( $backers ) )
			return $total;

		foreach ( $backers as $backer ) {
			$payment_id = get_post_meta( $backer->ID, '_edd_log_payment_id', true );
			$total      = $total + edd_get_payment_amount( $payment_id );
		}
		
		if ( $formatted )
			return edd_currency_filter( edd_format_amount( $total ) );

		return $total;
	}

	/**
	 * Campaign Active
	 *
	 * Check if the campaign has expired based on time, or it has
	 * manually been expired (via meta)
	 *
	 * @since AT_CrowdFunding 0.1-alpha
	 *
	 * @param boolean $formatted Return formatted currency or not
	 * @return sting $percent The percent completed (formatted with a % or not)
	 */
	public function is_active() {
		$active  = true;

		if ( $this->days_remaining() == 0 )
			$active = false;

		if ( $this->__get( '_campaign_expired' ) )
			$active = false;

		return apply_filters( 'atcf_campaign_active', $active, $this );
	}

	/**
	 * Campaign Funded
	 *
	 * @since AT_CrowdFunding 0.1-alpha
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

/** Frontend Submission *******************************************************/

/**
 * Process shortcode submission.
 *
 * @since AT_CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_process() {
	global $edd_options, $post;
	
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;
	
	if ( empty( $_POST['action' ] ) || ( 'atcf-campaign-submit' !== $_POST[ 'action' ] ) )
		return;

	if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'atcf-campaign-submit' ) )
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
	$location  = $_POST[ 'location' ];
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

	do_action( 'atcf_campaign_submit_validate', $_POST, $errors );

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
	add_post_meta( $campaign, 'campaign_goal', apply_filters( 'edd_metabox_save_edd_price', $goal ) );
	add_post_meta( $campaign, 'campaign_email', sanitize_text_field( $email ) );
	add_post_meta( $campaign, 'campaign_end_date', sanitize_text_field( $end_date ) );
	add_post_meta( $campaign, 'campaign_location', sanitize_text_field( $location ) );
	
	foreach ( $rewards as $key => $reward ) {
		$edd_files[] = array(
			'name'      => $reward[ 'price' ],
			'condition' => $key
		);

		$prices[] = array(
			'name'   => sanitize_text_field( $reward[ 'description' ] ),
			'amount' => apply_filters( 'edd_metabox_save_edd_price', $reward[ 'price' ] )
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

	if ( '' != $image[ 'name' ] ) {
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

		add_post_meta( $campaign, '_thumbnail_id', absint( $attach_id ) );
	}

	/** EDD Stuff */
	add_post_meta( $campaign, '_variable_pricing', 1 );
	add_post_meta( $campaign, '_edd_price_options_mode', 1 );
	add_post_meta( $campaign, '_edd_hide_purchase_link', 'on' );
	
	add_post_meta( $campaign, 'edd_variable_prices', $prices );
	add_post_meta( $campaign, 'edd_download_files', $edd_files );

	do_action( 'atcf_submit_process_after', $campaign, $_POST );

	$redirect = apply_filters( 'atcf_submit_campaign_success_redirect', add_query_arg( array( 'success' => 'true' ), get_permalink() ) );
	wp_safe_redirect( $redirect );
	exit();
}
add_action( 'template_redirect', 'atcf_shortcode_submit_process' );

/**
 * Price Options Heading
 *
 * @since AT_CrowdFunding 0.1-alpha
 *
 * @param string $heading Price options heading
 * @return string Modified price options heading
 */
function atcf_edd_price_options_heading( $heading ) {
	return __( 'Reward Options:', 'atcf' );
}
add_filter( 'edd_price_options_heading', 'atcf_edd_price_options_heading' );

/**
 * Reward toggle text
 *
 * @since AT_CrowdFunding 0.1-alpha
 *
 * @param string $heading Reward toggle text
 * @return string Modified reward toggle text
 */
function atcf_edd_variable_pricing_toggle_text( $text ) {
	return __( 'Enable multiple reward options', 'atcf' );
}
add_filter( 'edd_variable_pricing_toggle_text', 'atcf_edd_variable_pricing_toggle_text' );