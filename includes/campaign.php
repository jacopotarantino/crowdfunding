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
 * @since Appthemer CrowdFunding 0.1-alpha
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Global Campaigns *******************************************************/

/** Start me up! */
$atcf_campaigns = new ATCF_Campaigns;

class ATCF_Campaigns {

	/**
	 * Start things up.
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
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
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return void
	 */
	function setup() {
		define( 'EDD_SLUG', apply_filters( 'atcf_edd_slug', 'campaigns' ) );
		
		add_filter( 'edd_download_labels', array( $this, 'download_labels' ) );
		add_filter( 'edd_default_downloads_name', array( $this, 'download_names' ) );
		add_filter( 'edd_download_supports', array( $this, 'download_supports' ) );

		do_action( 'atcf_campaigns_actions' );
		
		if ( ! is_admin() )
			return;

		add_filter( 'edd_price_options_heading', 'atcf_edd_price_options_heading' );
		add_filter( 'edd_variable_pricing_toggle_text', 'atcf_edd_variable_pricing_toggle_text' );

		add_filter( 'manage_edit-download_columns', array( $this, 'dashboard_columns' ), 11, 1 );
		add_filter( 'manage_download_posts_custom_column', array( $this, 'dashboard_column_item' ), 11, 2 );
		
		add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ), 11 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		add_filter( 'edd_metabox_fields_save', array( $this, 'meta_boxes_save' ) );
		add_filter( 'edd_metabox_save_campaign_end_date', 'atcf_campaign_save_end_date' );

		add_action( 'edd_download_price_table_head', 'atcf_pledge_limit_head' );
		add_action( 'edd_download_price_table_row', 'atcf_pledge_limit_column', 10, 3 );

		add_action( 'admin_action_atcf-collect-funds', array( $this, 'collect_funds' ) );
		add_filter( 'post_updated_messages', array( $this, 'messages' ) );

		do_action( 'atcf_campaigns_actions_admin' );
	}

	/**
	 * Download labels. Change it to "Campaigns".
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
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
	 * @since Appthemer CrowdFunding 0.1-alpha
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
	 * @since Appthemer CrowdFunding 0.1-alpha
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
	 * @since Appthemer CrowdFunding 0.1-alpha
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
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @param array $supports The post type supports
	 * @return array $supports The modified post type supports
	 */
	function dashboard_column_item( $column, $post_id ) {
		$campaign = atcf_get_campaign( $post_id );

		switch ( $column ) {
			case 'funded' :
				$funded = $campaign->current_amount(true);

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
	 * @since Appthemer CrowdFunding 0.1-alpha
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
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return void
	 */
	function add_meta_boxes() {
		global $post;

		$campaign = new ATCF_Campaign( $post );

		if ( ( 'flexible' == $campaign->type() || $campaign->is_funded() ) && ! $campaign->is_collected() && class_exists( 'PayPalAdaptivePaymentsGateway' ) )
			add_meta_box( 'atcf_campaign_funds', __( 'Campaign Funds', 'atcf' ), '_atcf_metabox_campaign_funds', 'download', 'side', 'high' );

		add_meta_box( 'atcf_campaign_stats', __( 'Campaign Stats', 'atcf' ), '_atcf_metabox_campaign_stats', 'download', 'side', 'high' );
		add_meta_box( 'atcf_campaign_updates', __( 'Campaign Updates', 'atcf' ), '_atcf_metabox_campaign_updates', 'download', 'normal', 'high' );
		add_meta_box( 'atcf_campaign_video', __( 'Campaign Video', 'atcf' ), '_atcf_metabox_campaign_video', 'download', 'normal', 'high' );

		add_action( 'edd_meta_box_fields', '_atcf_metabox_campaign_info', 5 );
	}

	/**
	 * Campaign Information
	 *
	 * Hook in to EDD and add a few more things that will be saved. Use
	 * this so we are already cleared/validated.
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @param array $fields An array of fields to save
	 * @return array $fields An updated array of fields to save
	 */
	function meta_boxes_save( $fields ) {
		$fields[] = '_campaign_featured';
		$fields[] = '_campaign_physical';
		$fields[] = 'campaign_goal';
		$fields[] = 'campaign_email';
		$fields[] = 'campaign_contact_email';
		$fields[] = 'campaign_end_date';
		$fields[] = 'campaign_video';
		$fields[] = 'campaign_location';
		$fields[] = 'campaign_author';
		$fields[] = 'campaign_type';
		$fields[] = 'campaign_updates';

		return $fields;
	}

	/**
	 * Collect Funds
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
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
		$errors          = new WP_Error();

		$owner           = $edd_options[ 'epap_receivers' ];
		$owner           = explode( '|', $owner );
		$owner_email     = $owner[0];
		$owner_amount    = $owner[1];

		if ( 'flexible' == $campaign->type() ) {
			$owner_amount = $owner_amount + $edd_options[ 'epap_flexible_fee' ];
		}

		$campaign_amount = 100 - $owner_amount;
		$campaign_email  = $campaign->paypal_email();

		$receivers       = array(
			array(
				trim( $campaign_email ),
				absint( $campaign_amount )
			),
			array(
				trim( $owner_email ),
				absint( $owner_amount )
			)
		);

		foreach ( $payments as $payment ) {
			$payment_id      = get_post_meta( $payment->ID, '_edd_log_payment_id', true );

			$sender_email    = get_post_meta( $payment_id, '_edd_epap_sender_email', true );
			$amount          = get_post_meta( $payment_id, '_edd_epap_amount', true );
			$paid            = get_post_meta( $payment_id, '_edd_epap_paid', true );
			$preapproval_key = get_post_meta( $payment_id, '_edd_epap_preapproval_key', true );

			/** Already paid or other error */
			if ( $paid > $amount ) {
				$errors->add( 'already-paid-' . $payment_id, __( 'This payment has already been collected.', 'atcf' ) );
				
				continue;
			}

			if ( $payment = $paypal_adaptive->pay_preapprovals( $payment_id, $preapproval_key, $sender_email, $amount, $receivers ) ) {
				$responsecode = strtoupper( $payment[ 'responseEnvelope' ][ 'ack' ] );
				
				if ( $responsecode == 'SUCCESS' || $responsecode == 'SUCCESSWITHWARNING' ) {
					$pay_key = $payment[ 'payKey' ];
					
					add_post_meta( $payment_id, '_edd_epap_pay_key', $pay_key );
					add_post_meta( $payment_id, '_edd_epap_preapproval_paid', true );

					$num_collected = $num_collected + 1;
					
					edd_update_payment_status( $payment_id, 'publish' );
				} else {
					$errors->add( 
						'invalid-response-' . $payment_id, 
						sprintf( 
							__( 'There was an error collecting funds for payment <a href="%1$s">#%2$d</a>. PayPal responded with %3$s', 'atcf' ), 
							admin_url( 'edit.php?post_type=download&page=edd-payment-history&edd-action=edit-payment&purchase_id=' . $payment_id ), 
							$payment_id, 
							'<pre style="max-width: 100%; overflow: scroll; height: 200px;">' . print_r( array_merge( $payment,  compact( 'payment_id', 'preapproval_key', 'sender_email', 'amount', 'receivers' ) ), true ) . '</pre>'
						)
					);
				}
			} else {
				$errors->add( 'payment-error-' . $payment_id, __( 'There was an error.', 'atcf' ) );
			}
		}

		if ( ! empty ( $errors->errors ) ) // Not sure how to avoid empty instantiated WP_Error
			wp_die( $errors );
		else {
			update_post_meta( $this->ID, '_campaign_expired', 1 );
			update_post_meta( $this->ID, '_campaign_bulk_collected', 1 );
			return wp_safe_redirect( add_query_arg( array( 'post' => $campaign->ID, 'action' => 'edit', 'message' => 13, 'collected' => $num_collected ), admin_url( 'post.php' ) ) );
			exit();
		}
	}

	/**
	 * Custom messages for various actions when managing campaigns.
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @param array $messages An array of messages to display
	 * @return array $messages An updated array of messages to display
	 */
	function messages( $messages ) {
		$messages[ 'download' ][11] = sprintf( __( 'This %s has not reached its funding goal.', 'atcf' ), strtolower( edd_get_label_singular() ) );
		$messages[ 'download' ][12] = sprintf( __( 'You do not have permission to collect funds for %s.', 'atcf' ), strtolower( edd_get_label_plural() ) );
		$messages[ 'download' ][13] = sprintf( __( '%d payments have been collected for this %s.', 'atcf' ), isset ( $_GET[ 'collected' ] ) ? $_GET[ 'collected' ] : 0, strtolower( edd_get_label_singular() ) );

		return $messages;
	}
}

/**
 * Filter the expiration date for a campaign.
 *
 * A hidden/fake input field so the filter is triggered, then
 * add all the other date fields together to create the MySQL date.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
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
 * Price row head
 *
 * @since Appthemer CrowdFunding 0.9
 *
 * @return void
 */
function atcf_pledge_limit_head() {
?>
	<th style="width: 30px"><?php _e( 'Limit', 'edd' ); ?></th>
	<th style="width: 30px"><?php _e( 'Purchased', 'edd' ); ?></th>
<?php
}

/**
 * Price row columns
 *
 * @since Appthemer CrowdFunding 0.9
 *
 * @return void
 */
function atcf_pledge_limit_column( $post_id, $key, $args ) {
?>
	<td>
		<input type="text" class="edd_repeatable_name_field" name="edd_variable_prices[<?php echo $key; ?>][limit]" id="edd_variable_prices[<?php echo $key; ?>][limit]" value="<?php echo $args[ 'limit' ]; ?>" style="width:100%" />
	</td>
	<td>
		<input type="text" class="edd_repeatable_name_field" name="edd_variable_prices[<?php echo $key; ?>][bought]" id="edd_variable_prices[<?php echo $key; ?>][bought]" value="<?php echo $args[ 'bought' ]; ?>" readonly style="width:100%" />
	</td>
<?php
}

/**
 * Price row fields
 *
 * @since Appthemer CrowdFunding 0.9
 *
 * @return void
 */
function atcf_price_row_args( $args, $value ) {
	$args[ 'limit' ] = isset( $value[ 'limit' ] ) ? $value[ 'limit' ] : '';
	$args[ 'bought' ] = isset( $value[ 'bought' ] ) ? $value[ 'bought' ] : 0;

	return $args;
}
add_filter( 'edd_price_row_args', 'atcf_price_row_args', 10, 2 );

/**
 * Campaign Stats Box
 *
 * These are read-only stats/info for the current campaign.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
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
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @return void
 */
function _atcf_metabox_campaign_funds() {
	global $post;

	$campaign = new ATCF_Campaign( $post );

	do_action( 'atcf_metabox_campaign_funds_before', $campaign );
?>
	<?php if ( 'fixed' == $campaign->type() ) : ?>
	<p><?php printf( __( 'This %1$s has reached its funding goal. You may now send the funds to the owner. This will end the %1$s.', 'atcf' ), strtolower( edd_get_label_singular() ) ); ?></p>
	<?php else : ?>
	<p><?php printf( __( 'This %1$s is flexible. You may collect the funds at any time. This will end the %1$s.', 'atcf' ), strtolower( edd_get_label_singular() ) ); ?></p>
	<?php endif; ?>

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
 * @since Appthemer CrowdFunding 0.1-alpha
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
 * Campaign Updates Box
 *
 * @since Appthemer CrowdFunding 0.9
 *
 * @return void
 */
function _atcf_metabox_campaign_updates() {
	global $post;

	$campaign = atcf_get_campaign( $post );

	do_action( 'atcf_metabox_campaign_updates_before', $campaign );
?>
	<?php 
		wp_editor( $campaign->updates(), 'campaign_updates', apply_filters( 'atcf_submit_field_updates_editor_args', array( 
			'media_buttons' => false,
			'teeny'         => true,
			'quicktags'     => false,
			'textarea_rows' => 4,
			'editor_css'    => '<style>body { background: white; }</style>',
			'tinymce'       => array(
				'theme_advanced_path'     => false,
				'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
				'plugins'                 => 'paste',
				'paste_remove_styles'     => true
			),
		) ) );
	?>
	<p class="description"><?php _e( 'oEmbed supported video links.', 'atcf' ); ?></p>
<?php
	do_action( 'atcf_metabox_campaign_updates_after', $campaign );
}

/**
 * Campaign Configuration
 *
 * Hook into EDD Download Information and add a bit more stuff.
 * These are all things that can be updated while the campaign runs/before
 * being published.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
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

	$types = atcf_campaign_types();
?>	
	<p>
		<label for="_campaign_featured">
			<input type="checkbox" name="_campaign_featured" id="_campaign_featured" value="1" <?php checked( 1, $campaign->featured() ); ?> />
			<?php _e( 'Featured campaign', 'atcf' ); ?>
		</label>
	</p>

	<p>
		<label for="_campaign_physical">
			<input type="checkbox" name="_campaign_physical" id="_campaign_physical" value="1" <?php checked( 1, $campaign->needs_shipping() ); ?> />
			<?php _e( 'Collect shipping information on checkout', 'atcf' ); ?>
		</label>
	</p>
	
	<p>
		<?php foreach ( atcf_campaign_types_active() as $key => $desc ) : ?>
		<label for="campaign_type[<?php echo esc_attr( $key ); ?>]"><input type="radio" name="campaign_type" id="campaign_type[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $campaign->type() ); ?> /> <strong><?php echo $types[ $key ][ 'title' ]; ?></strong></label><br />
		<?php endforeach; ?>
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

	<p>
		<label for="campaign_email"><strong><?php _e( 'Contact Email:', 'atcf' ); ?></strong></label><br />
		<input type="text" name="campaign_contact_email" id="campaign_contact_email" value="<?php echo esc_attr( $campaign->contact_email() ); ?>" class="regular-text" />
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
 * @since Appthemer CrowdFunding 0.1-alpha
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

/**
 * Updates Save
 *
 * EDD trys to escape this data, and we don't want that.
 *
 * @since Appthemer CrowdFunding 0.9
 */
function atcf_sanitize_campaign_updates( $updates ) {
	$updates = $_POST[ 'campaign_updates' ];
	$updates = wp_kses_post( $updates );

	return $updates;
}
add_filter( 'edd_metabox_save_campaign_updates', 'atcf_sanitize_campaign_updates' );

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
	 * @since Appthemer CrowdFunding 0.1-alpha
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
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Featured
	 */
	public function featured() {
		return $this->__get( '_campaign_featured' );
	}

	/**
	 * Needs Shipping
	 *
	 * @since Appthemer CrowdFunding 0.9
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
	 * @since Appthemer CrowdFunding 0.1-alpha
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
	 * @since Appthemer CrowdFunding 0.7
	 *
	 * @return string $type The type of campaign
	 */
	public function type() {
		$type = $this->__get( 'campaign_type' );

		if ( ! $type )
			atcf_campaign_type_default();

		return $type;
	}

	/**
	 * Campaign Location
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Location
	 */
	public function location() {
		return $this->__get( 'campaign_location' );
	}

	/**
	 * Campaign Author
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Author
	 */
	public function author() {
		return $this->__get( 'campaign_author' );
	}

	/**
	 * Campaign PayPal Email
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign PayPal Email
	 */
	public function paypal_email() {
		return $this->__get( 'campaign_email' );
	}

	/**
	 * Campaign Contact Email
	 *
	 * @since Appthemer CrowdFunding 0.5
	 *
	 * @return sting Campaign Contact Email
	 */
	public function contact_email() {
		return $this->__get( 'campaign_contact_email' );
	}

	/**
	 * Campaign End Date
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign End Date
	 */
	public function end_date() {
		return mysql2date( 'Y-m-d h:i:s', $this->__get( 'campaign_end_date' ), false );
	}

	/**
	 * Campaign Video
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Video
	 */
	public function video() {
		return $this->__get( 'campaign_video' );
	}

	/**
	 * Campaign Updates
	 *
	 * @since Appthemer CrowdFunding 0.9
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
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return sting Campaign Backers
	 */
	public function backers() {
		global $edd_logs;

		$backers = $edd_logs->get_connected_logs( array(
			'post_parent' => $this->ID, 
			'log_type'    => class_exists( 'PayPalAdaptivePaymentsGateway' ) ? 'preapproval' : 'sale',
			'post_status' => array( 'publish' )
		) );

		return $backers;
	}

	/**
	 * Campaign Backers Count
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return int Campaign Backers Count
	 */
	public function backers_count() {
		$backers = $this->backers();
		
		if ( ! $backers )
			return 0;

		return absint( count( $backers ) );
	}

	/**
	 * Campaign Backers Per Price
	 *
	 * Get all of the backers, then figure out what they purchased. Increment
	 * a counter for each price point, so they can be displayed elsewhere. 
	 * Not 100% because keys can change in EDD, but it's the best way I think.
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
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
				$price_id = $item[ 'price' ];

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
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return int The number of days remaining
	 */
	public function days_remaining() {
		$expires = strtotime( $this->end_date() );
		$now     = time();

		if ( $now > $expires )
			return 0;

		$diff = $expires - $now;

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
	 * @since Appthemer CrowdFunding 0.1-alpha
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
	 * @since Appthemer CrowdFunding 0.1-alpha
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

	/**
	 * Campaign Active
	 *
	 * Check if the campaign has expired based on time, or it has
	 * manually been expired (via meta)
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return boolean
	 */
	public function is_active() {
		$active  = true;

		if ( $this->days_remaining() == 0 )
			$active = false;

		if ( $this->__get( '_campaign_expired' ) )
			$active = false;

		if ( $this->is_collected() )
			$active = false;

		return apply_filters( 'atcf_campaign_active', $active, $this );
	}

	/**
	 * Funds Collected
	 *
	 * When funds are collected in bulk, remember that, so we can end the
	 * campaign, and not repeat things.
	 *
	 * @since Appthemer CrowdFunding 0.3-alpha
	 *
	 * @return boolean
	 */
	public function is_collected() {
		return $this->__get( '_campaign_bulk_collected' );
	}

	/**
	 * Campaign Funded
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
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
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_process() {
	global $edd_options, $post;
	
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;
	
	if ( empty( $_POST['action' ] ) || ( 'atcf-campaign-submit' !== $_POST[ 'action' ] ) )
		return;

	if ( ! wp_verify_nonce( $_POST[ '_wpnonce' ], 'atcf-campaign-submit' ) )
		return;

	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/admin.php' );
	}

	$errors           = new WP_Error();
	$prices           = array();
	$edd_files        = array();
	$upload_overrides = array( 'test_form' => false );

	$title     = $_POST[ 'title' ];
	$goal      = $_POST[ 'goal' ];
	$length    = $_POST[ 'length' ];
	$type      = $_POST[ 'campaign_type' ];
	$location  = $_POST[ 'location' ];
	$category  = $_POST[ 'cat' ];
	$content   = $_POST[ 'description' ];
	$excerpt   = $_POST[ 'excerpt' ];
	$author    = $_POST[ 'name' ];
	$shipping  = $_POST[ 'shipping' ];

	$image     = $_FILES[ 'image' ];
	$video     = $_POST[ 'video' ];

	$rewards   = $_POST[ 'rewards' ];
	$files     = $_FILES[ 'files' ];

	$email     = $_POST[ 'email' ];
	
	if ( isset ( $_POST[ 'contact-email' ] ) )
		$c_email = $_POST[ 'contact-email' ];
	else {
		$current_user = wp_get_current_user();
		$c_email = $current_user->user_email;
	}

	/** Check Title */
	if ( empty( $title ) )
		$errors->add( 'invalid-title', __( 'Please add a title to this campaign.', 'atcf' ) );

	/** Check Goal */
	$goal = atcf_sanitize_goal_save( $goal );

	if ( ! is_numeric( $goal ) )
		$errors->add( 'invalid-goal', sprintf( __( 'Please enter a valid goal amount. All goals are set in the %s currency.', 'atcf' ), $edd_options[ 'currency' ] ) );

	/** Check Length */
	$length = absint( $length );

	$min = isset ( $edd_options[ 'atcf_campaign_length_min' ] ) ? $edd_options[ 'atcf_campaign_length_min' ] : 14;
	$max = isset ( $edd_options[ 'atcf_campaign_length_max' ] ) ? $edd_options[ 'atcf_campaign_length_max' ] : 42;

	if ( $length < $min )
		$length = $min;
	else if ( $length > $max )
		$length = $max;

	$end_date = strtotime( sprintf( '+%d day', $length ) );
	$end_date = get_gmt_from_date( date( 'Y-m-d H:i:s', $end_date ) );

	/** Check Category */
	$category = absint( $category );

	/** Check Content */
	if ( empty( $content ) )
		$errors->add( 'invalid-content', __( 'Please add content to this campaign.', 'atcf' ) );

	/** Check Excerpt */
	if ( empty( $excerpt ) )
		$excerpt = null;

	/** Check Image */
	if ( empty( $image ) )
		$errors->add( 'invalid-previews', __( 'Please add a campaign image.', 'atcf' ) );

	/** Check Rewards */
	if ( empty( $rewards ) )
		$errors->add( 'invalid-rewards', __( 'Please add at least one reward to the campaign.', 'atcf' ) );

	/** Check Email */
	if ( ! is_email( $email ) || ! is_email( $c_email ) )
		$errors->add( 'invalid-email', __( 'Please make sure all email addresses are valid.', 'atcf' ) );

	if ( email_exists( $c_email ) && ! isset ( $current_user ) )
		$errors->add( 'invalid-c-email', __( 'That contact email address already exists.', 'atcf' ) );		

	do_action( 'atcf_campaign_submit_validate', $_POST, $errors );

	if ( ! empty ( $errors->errors ) ) // Not sure how to avoid empty instantiated WP_Error
		wp_die( $errors );

	if ( ! isset ( $current_user ) ) {
		$password = wp_generate_password( 12, false );
		
		$user_id  = wp_insert_user( array(
			'user_login'           => $c_email, 
			'user_pass'            => $password, 
			'user_email'           => $c_email,
			'user_nicename'        => $author,
			'display_name'         => $author,
			'show_admin_bar_front' => 'false',
			'role'                 => 'campaign_contributor'
		) );

		$secure_cookie = is_ssl() ? true : false;
		wp_set_auth_cookie( $user_id, true, $secure_cookie );
		wp_new_user_notification( $user_id, $password );
	} else {
		$user_id = $current_user->ID;
	}

	$args = apply_filters( 'atcf_campaign_submit_data', array(
		'post_type'    => 'download',
		'post_status'  => 'pending',
		'post_title'   => $title,
		'post_content' => $content,
		'post_excerpt' => $excerpt,
		'post_author'  => $user_id
	), $_POST );

	$campaign = wp_insert_post( $args, true );

	wp_set_object_terms( $campaign, array( $category ), 'download_category' );

	/** Extra Campaign Information */
	add_post_meta( $campaign, 'campaign_goal', apply_filters( 'edd_metabox_save_edd_price', $goal ) );
	add_post_meta( $campaign, 'campaign_type', sanitize_text_field( $type ) );
	add_post_meta( $campaign, 'campaign_email', sanitize_text_field( $email ) );
	add_post_meta( $campaign, 'campaign_contact_email', sanitize_text_field( $c_email ) );
	add_post_meta( $campaign, 'campaign_end_date', sanitize_text_field( $end_date ) );
	add_post_meta( $campaign, 'campaign_location', sanitize_text_field( $location ) );
	add_post_meta( $campaign, 'campaign_author', sanitize_text_field( $author ) );
	add_post_meta( $campaign, 'campaign_video', esc_url( $video ) );
	add_post_meta( $campaign, '_campaign_physical', sanitize_text_field( $shipping ) );
	
	foreach ( $rewards as $key => $reward ) {
		$edd_files[] = array(
			'name'      => $reward[ 'price' ],
			'condition' => $key
		);

		$prices[] = array(
			'name'   => sanitize_text_field( $reward[ 'description' ] ),
			'amount' => apply_filters( 'edd_metabox_save_edd_price', $reward[ 'price' ] ),
			'limit'  => sanitize_text_field( $reward[ 'limit' ] )
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
		$upload = wp_handle_upload( $image, $upload_overrides );
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

	if ( ! empty( $files ) ) {
		add_post_meta( $campaign, 'edd_download_files', $edd_files );
	}

	do_action( 'atcf_submit_process_after', $campaign, $_POST );

	$url = isset ( $edd_options[ 'submit_page' ] ) ? get_permalink( $edd_options[ 'submit_page' ] ) : get_permalink();

	$redirect = apply_filters( 'atcf_submit_campaign_success_redirect', add_query_arg( array( 'success' => 'true' ), $url ) );
	wp_safe_redirect( $redirect );
	exit();
}
add_action( 'template_redirect', 'atcf_shortcode_submit_process' );

/**
 * Process shortcode submission.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_campaign_edit() {
	global $edd_options, $post;
	
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;
	
	if ( empty( $_POST['action' ] ) || ( 'atcf-campaign-edit' !== $_POST[ 'action' ] ) )
		return;

	if ( ! wp_verify_nonce( $_POST[ '_wpnonce' ], 'atcf-campaign-edit' ) )
		return;

	if ( ! ( $post->post_author == get_current_user_id() || current_user_can( 'manage_options' ) ) )
		return;

	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/admin.php' );
	}

	$errors    = new WP_Error();
	
	$category  = $_POST[ 'cat' ];
	$content   = $_POST[ 'description' ];
	$updates   = $_POST[ 'updates' ];
	$excerpt   = $_POST[ 'excerpt' ];

	$email     = $_POST[ 'email' ];
	$author    = $_POST[ 'name' ];
	$location  = $_POST[ 'location' ];

	if ( isset ( $_POST[ 'contact-email' ] ) )
		$c_email = $_POST[ 'contact-email' ];
	else {
		$current_user = wp_get_current_user();
		$c_email = $current_user->user_email;
	}

	/** Check Category */
	$category = absint( $category );

	/** Check Content */
	if ( empty( $content ) )
		$errors->add( 'invalid-content', __( 'Please add content to this campaign.', 'atcf' ) );

	/** Check Excerpt */
	if ( empty( $excerpt ) )
		$excerpt = null;

	/** Check Email */
	if ( ! is_email( $email ) || ! is_email( $c_email ) )
		$errors->add( 'invalid-email', __( 'Please make sure all email addresses are valid.', 'atcf' ) );

	do_action( 'atcf_edit_campaign_validate', $_POST, $errors );

	if ( ! empty ( $errors->errors ) ) // Not sure how to avoid empty instantiated WP_Error
		wp_die( $errors );

	$args = apply_filters( 'atcf_edit_campaign_data', array(
		'ID'           => $post->ID,
		'post_content' => $content,
		'post_excerpt' => $excerpt,
		'tax_input'    => array(
			'download_category' => array( $category )
		)
	), $_POST );

	$campaign = wp_update_post( $args, true );

	/** Extra Campaign Information */
	update_post_meta( $post->ID, 'campaign_email', sanitize_text_field( $email ) );
	update_post_meta( $post->ID, 'campaign_contact_email', sanitize_text_field( $c_email ) );
	update_post_meta( $post->ID, 'campaign_location', sanitize_text_field( $location ) );
	update_post_meta( $post->ID, 'campaign_author', sanitize_text_field( $author ) );
	update_post_meta( $post->ID, 'campaign_updates', wp_kses_post( $updates ) );

	do_action( 'atcf_edit_campaign_after', $post->ID, $_POST );

	$redirect = apply_filters( 'atcf_submit_campaign_success_redirect', add_query_arg( array( 'success' => 'true' ), get_permalink( $post->ID ) ) );
	wp_safe_redirect( $redirect );
	exit();
}
add_action( 'template_redirect', 'atcf_campaign_edit' );

/**
 * Price Options Heading
 *
 * @since Appthemer CrowdFunding 0.1-alpha
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
 * @since Appthemer CrowdFunding 0.1-alpha
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
 * @since AppThemer Crowdfunding 0.9
 */
function atcf_campaign_types() {
	$types = apply_filters( 'atcf_campaign_types', array(
		'fixed'    => array(
			'title'       => __( 'Fixed Funding', 'atcf' ),
			'description' => __( 'Only collect funds if the goal is met.', 'atcf' )
		),
		'flexible' => array(
			'title'       => __( 'Flexible Funding', 'atcf' ),
			'description' => __( 'Collect funds no matter what. A higher fee may be charged.', 'atcf' )
		)
	) );

	return $types;
}

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

function atcf_campaign_type_default() {
	$type = apply_filters( 'atcf_campaign_type_default', 'fixed' );

	return $type;
}