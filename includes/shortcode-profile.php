<?php
/**
 * Profile Shortcode.
 *
 * [appthemer_crowdfunding_profile] lists relevant information about the current user.
 *
 * @since Appthemer CrowdFunding 0.8
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Base page/form. All fields are loaded through an action,
 * so the form can be extended for ever, fields can be removed, added, etc.
 *
 * @since CrowdFunding 0.8
 *
 * @return $form
 */
function atcf_shortcode_profile() {
	global $post;

	$crowdfunding = crowdfunding();
	$user         = wp_get_current_user();

	if ( ! is_user_logged_in() ) {
		return wp_login_form( apply_filters( 'atcf_shortcode_profile_login_args', array() ) );
	}

	ob_start();

	echo '<div class="atcf-profile">';
	do_action( 'atcf_shortcode_profile', $user );
	echo '</div>';

	$form = ob_get_clean();

	return $form;
}
add_shortcode( 'appthemer_crowdfunding_profile', 'atcf_shortcode_profile' );

/**
 * Profile Information
 *
 * @since CrowdFunding 0.8
 *
 * @return void
 */
function atcf_shortcode_profile_info( $user ) {
	$userinfo = get_userdata( $user->ID );
?>
	<h3 class="atcf-profile-section bio"><?php _e( 'Profile Information', 'atcf' ); ?></h3>

	<?php do_action( 'atcf_shortcode_profile_info_before', $user ); ?>
	<form action="" method="post" class="atcf-submit-campaign" enctype="multipart/form-data">
		<?php do_action( 'atcf_profile_info_fields', $user, $userinfo ); ?>

		<p class="atcf-submit-campaign-submit">
			<input type="submit" value="<?php esc_attr_e( 'Update', 'atcf' ); ?>">
			<input type="hidden" name="action" value="atcf-profile-update" />
			<?php wp_nonce_field( 'atcf-profile-update' ); ?>
		</p>
	</form>
	<?php do_action( 'atcf_shortcode_profile_bio_after', $user ); ?>
<?php
}
add_action( 'atcf_shortcode_profile', 'atcf_shortcode_profile_info', 10, 1 );

/**
 * Nicename
 *
 * @since CrowdFunding 0.8
 *
 * @return void
 */
function atcf_profile_info_fields_nicename( $user, $userinfo ) {
?>
	<p class="atcf-profile-info-first-name">
		<label for="first-name"><?php _e( 'Name', 'atcf' ); ?></label>
		<input type="text" name="nicename" id="nicename" value="<?php echo esc_attr( $user->display_name ); ?>" />
	</p>
<?php
}
add_action( 'atcf_profile_info_fields', 'atcf_profile_info_fields_nicename', 10, 2 );

/**
 * URL
 *
 * @since CrowdFunding 0.8
 *
 * @return void
 */
function atcf_profile_info_fields_url( $user, $userinfo ) {
?>
	<p class="atcf-profile-info-url">
		<label for="url"><?php _e( 'Website/URL', 'atcf' ); ?></label>
		<input type="text" name="url" id="url" value="<?php echo esc_attr( $user->user_url ); ?>" />
	</p>
<?php
}
add_action( 'atcf_profile_info_fields', 'atcf_profile_info_fields_url', 20, 2 );

/**
 * Contact Methods
 *
 * @since CrowdFunding 0.9
 *
 * @return void
 */
function atcf_profile_info_fields_contactmethods( $user, $userinfo ) {
	$methods = _wp_get_user_contactmethods();

	foreach ( $methods as $key => $method ) {
?>
	<p class="atcf-profile-info-<?php echo $key; ?>">
		<label for="<?php echo $key; ?>"><?php printf( __( '%s URL', 'atcf' ), $method ); ?></label>
		<input type="text" name="<?php echo $key; ?>" id="bio" value="<?php echo esc_attr( $user->$key ); ?>" />
	</p>
<?php
	}
}
add_action( 'atcf_profile_info_fields', 'atcf_profile_info_fields_contactmethods', 25, 2 );

/**
 * Biography
 *
 * @since CrowdFunding 0.8
 *
 * @return void
 */
function atcf_profile_info_fields_bio( $user, $userinfo ) {
?>
	<p class="atcf-profile-info-bio">
		<label for="bio"><?php _e( 'Biography', 'atcf' ); ?></label>
		<textarea name="bio" id="bio" rows="4"><?php echo esc_textarea( $user->user_description ); ?></textarea>
	</p>
<?php
}
add_action( 'atcf_profile_info_fields', 'atcf_profile_info_fields_bio', 30, 2 );

/**
 * Campaign History
 *
 * @since CrowdFunding 0.8
 *
 * @return void
 */
function atcf_shortcode_profile_campaigns( $user ) {
	$campaigns = new WP_Query( array(
		'post_type'   => 'download',
		'author' => $user->ID,
		'post_status' => array( 'publish', 'pending', 'draft' ),
		'nopaging'    => true
	) );

	if ( ! $campaigns->have_posts() )
		return;
?>
	<h3 class="atcf-profile-section your-campaigns"><?php _e( 'Your Campaigns', 'atcf' ); ?></h3>

	<ul class="atcf-profile-campaigns">
	<?php while ( $campaigns->have_posts() ) : $campaigns->the_post(); $campaign = atcf_get_campaign( get_post()->ID ); ?>
		<li class="atcf-profile-campaign-overview">
			<?php do_action( 'atcf_profile_campaign_before', $campaign ); ?>

			<h4 class="entry-title">
				<?php the_title(); ?>
			</h4>

			<?php do_action( 'atcf_profile_campaign_after_title', $campaign ); ?>

			<?php if ( 'pending' == get_post()->post_status ) : ?>
				<?php do_action( 'atcf_profile_campaign_pending_before', $campaign ); ?>
				<span class="campaign-awaiting-review"><?php _e( 'This campaign is awaiting review.', 'atcf' ); ?></span>
				<?php do_action( 'atcf_profile_campaign_pending_after', $campaign ); ?>
			<?php elseif ( 'draft' == get_post()->post_status ) : ?>
				<?php do_action( 'atcf_profile_campaign_draft_before', $campaign ); ?>
				<span class="campaign-awaiting-review"><?php printf( __( 'This campaign is a draft. <a href="%s">Finish editing</a> it and submit it for review.', 'atcf' ), add_query_arg( array( 'edit' => true ), get_permalink( get_post()->ID ) ) ); ?></span>
				<?php do_action( 'atcf_profile_campaign_draft_after', $campaign ); ?>			
			<?php else : ?>	
				<?php do_action( 'atcf_profile_campaign_published_before', $campaign ); ?>

				<ul class="actions">
					<li><a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'Permalink to %s', 'fundify' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php _e( 'View', 'atcf' ); ?></a></li>
					<li><a href="<?php the_permalink(); ?>edit/" title="<?php echo esc_attr( sprintf( __( 'Edit %s', 'fundify' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php _e( 'Edit', 'atcf' ); ?></a></li>
					<?php do_action( 'atcf_profile_campaign_actions_all', $campaign ); ?>
				</ul>

				<ul class="actions">
					<?php if ( ! $campaign->is_collected() && ( 'flexible' == $campaign->type() || $campaign->is_funded() ) && atcf_has_preapproval_gateway() ) : ?>
					<li><a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'atcf-request-payout', 'campaign' => $campaign->ID ) ), 'atcf-request-payout' ) ); ?>" title="<?php echo esc_attr( sprintf( __( 'Request Payout for %s', 'fundify' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php _e( 'Request Payout', 'atcf' ); ?></a></li>
					<?php endif; ?>

					<?php if ( ( 'flexible' == $campaign->type() || $campaign->is_funded() ) ) : ?>
					<li><a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'atcf-request-data', 'campaign' => $campaign->ID ) ), 'atcf-request-data' ) ); ?>" title="<?php echo esc_attr( sprintf( __( 'Export data for %s', 'fundify' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php _e( 'Export Data', 'atcf' ); ?></a></li>
					<?php endif; ?>
					<?php do_action( 'atcf_profile_campaign_actions_special', $campaign ); ?>
				</ul>

				<?php do_action( 'atcf_profile_campaign_published_after', $campaign ); ?>
			<?php endif; ?>
			<?php do_action( 'atcf_profile_campaign_after', $campaign ); ?>
		</li>	
	<?php endwhile; wp_reset_query(); ?>
	</ul>
<?php
}
add_action( 'atcf_shortcode_profile', 'atcf_shortcode_profile_campaigns', 20, 1 );

/**
 * Campaign Contributinos
 *
 * @since CrowdFunding 1.4
 *
 * @return void
 */
function atcf_shortcode_profile_contributions( $user ) {
	global $edd_options;

	$contributions = edd_get_payments( array(
		'user' => $user->ID
	) );

	if ( empty( $contributions ) )
		return;
?>
	<h3 class="atcf-profile-section your-campaigns"><?php _e( 'Your Contributions', 'atcf' ); ?></h3>

	<ul class="atcf-profile-contributinos">
		<?php foreach ( $contributions as $contribution ) : ?>
		<?php
			$payment_data = edd_get_payment_meta( $contribution->ID );
			$cart         = edd_get_payment_meta_cart_details( $contribution->ID );
			$key          = edd_get_payment_key( $contribution->ID );
		?>
		<li>
			<?php foreach ( $cart as $download ) : ?>
			<?php printf( _x( '<a href="%s">%s</a> pledge to <a href="%s">%s</a>', 'price for download (payment history)', 'atcf' ), add_query_arg( 'payment_key', $key, get_permalink( $edd_options[ 'success_page' ] ) ), edd_currency_filter( edd_format_amount( $download[ 'price' ] ) ), get_permalink( $download[ 'id' ] ), $download[ 'name' ] ); ?>
			<?php endforeach; ?>
		</li>
		<?php endforeach; ?>
	</ul>
<?php
}
add_action( 'atcf_shortcode_profile', 'atcf_shortcode_profile_contributions', 30, 1 );

/**
 * Process shortcode submission.
 *
 * @since Appthemer CrowdFunding 0.8
 *
 * @return void
 */
function atcf_shortcode_profile_info_process() {
	global $edd_options, $post;

	if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
		return;
	
	if ( empty( $_POST['action' ] ) || ( 'atcf-profile-update' !== $_POST[ 'action' ] ) )
		return;

	if ( ! wp_verify_nonce( $_POST[ '_wpnonce' ], 'atcf-profile-update' ) )
		return;

	$user   = wp_get_current_user();
	$errors = new WP_Error();

	$bio      = esc_attr( $_POST[ 'bio' ] );
	$nicename = esc_attr( $_POST[ 'nicename' ] );
	$url      = esc_url( $_POST[ 'url' ] );

	do_action( 'atcf_shortcode_profile_info_process_validate', $_POST, $errors );

	if ( ! empty ( $errors->errors ) ) // Not sure how to avoid empty instantiated WP_Error
		wp_die( $errors );

	wp_update_user( apply_filters( 'atcf_shortcode_profile_info_process_update', array(
		'ID'               => $user->ID,
		'description'      => $bio,
		'display_name'     => $nicename,
		'user_nicename'    => $user->user_nicename,
		'user_url'         => $url
	) ) );

	foreach ( _wp_get_user_contactmethods() as $method => $name ) {
		if ( isset( $_POST[ $method ] ) )
			update_user_meta( $user->ID, $method, sanitize_text_field( $_POST[ $method ] ) );
	}

	do_action( 'atcf_shortcode_profile_info_process_after', $user, $_POST );

	$redirect = apply_filters( 'atcf_shortcode_profile_info_success_redirect', add_query_arg( array( 'success' => 'true' ), get_permalink() ) 
	);

	wp_safe_redirect( $redirect );
	exit();
}
add_action( 'template_redirect', 'atcf_shortcode_profile_info_process' );

/**
 * Request Payout
 *
 * @since Appthemer CrowdFunding 0.8
 *
 * @return void
 */
function atcf_shortcode_profile_request_payout() {
	global $edd_options, $post;

	if ( 'GET' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
		return;
	
	if ( empty( $_GET[ 'action' ] ) || ( 'atcf-request-payout' !== $_GET[ 'action' ] ) )
		return;

	if ( ! wp_verify_nonce( $_GET[ '_wpnonce' ], 'atcf-request-payout' ) )
		return;

	$user   = wp_get_current_user();
	$errors = new WP_Error();

	$campaign = $_GET[ 'campaign' ];
	$campaign = absint( $campaign );
	$campaign = atcf_get_campaign( $campaign );

	if ( 0 == $campaign->ID || 'download' != $campaign->data->post_type )
		$errors->add( 'no-campaign', __( 'This is not a valid campaign.', 'atcf' ) );

	if ( $user->ID != $campaign->data->post_author )
		$errors->add( 'non-owner', __( 'You are not the author of this campaign, and cannot request a payout.', 'atcf' ) );

	if ( ! empty ( $errors->errors ) )
		wp_die( $errors );

	$message = edd_get_email_body_header();
	$message .= sprintf( __( 'A request for payout has been made for <a href="%s">%s</a>.', 'atcf' ), admin_url( sprintf( 'post.php?post=%s&action=edit', $campaign->ID ) ), $campaign->data->post_title );
	$message .= edd_get_email_body_footer();

	$from_name  = isset( $edd_options['from_name'] ) ? $edd_options['from_name'] : get_bloginfo('name');
	$from_email = isset( $edd_options['from_email'] ) ? $edd_options['from_email'] : get_option('admin_email');

	$subject = apply_filters( 'atcf_request_funds_subject', sprintf( __( 'Payout Request for %s', 'atcf' ), $campaign->data->post_title ), $campaign );

	$headers = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
	$headers .= "Reply-To: ". $from_email . "\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=utf-8\r\n";

	// Allow add-ons to add file attachments
	$attachments = apply_filters( 'atcf_request_funds_attachments', array(), $campaign );

	wp_mail( $from_email, $subject, $message, $headers, $attachments );

	$url = isset ( $edd_options[ 'profile_page' ] ) ? get_permalink( $edd_options[ 'profile_page' ] ) : get_permalink();

	$redirect = apply_filters( 'atcf_shortcode_profile_info_success_redirect', add_query_arg( array( 'emailed' => $campaign->ID, 'success' => 'true' ), $url ) 
	);

	wp_safe_redirect( $redirect );
	exit();
}
add_action( 'template_redirect', 'atcf_shortcode_profile_request_payout' );

/**
 * Request Data
 *
 * @since Appthemer CrowdFunding 0.8
 *
 * @return void
 */
function atcf_shortcode_profile_request_data() {
	global $edd_options, $post;

	if ( 'GET' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
		return;
	
	if ( empty( $_GET[ 'action' ] ) || ( 'atcf-request-data' !== $_GET[ 'action' ] ) )
		return;

	if ( ! wp_verify_nonce( $_GET[ '_wpnonce' ], 'atcf-request-data' ) )
		return;

	$user         = wp_get_current_user();
	$errors       = new WP_Error();

	$crowdfunding = crowdfunding();
	$campaign     = absint( $_GET[ 'campaign' ] );
	$campaign     = atcf_get_campaign( $campaign );

	if ( $user->ID != $campaign->data->post_author )
		$errors->add( 'non-owner', __( 'You are not the author of this campaign, and cannot request the data.', 'atcf' ) );

	if ( ! empty ( $errors->errors ) )
		wp_die( $errors );

	if ( 0 != $campaign->ID ) {
		require_once EDD_PLUGIN_DIR . 'includes/admin/reporting/class-export.php';
		require( $crowdfunding->includes_dir . 'export-campaigns.php' );

		$campaign_export = new ATCF_Campaign_Export( $campaign->ID );

		$campaign_export->export();
	}

	$url = isset ( $edd_options[ 'profile_page' ] ) ? get_permalink( $edd_options[ 'profile_page' ] ) : get_permalink();

	$redirect = apply_filters( 'atcf_shortcode_profile_info_success_redirect', add_query_arg( array( 'exported' => $campaign->ID, 'success' => 'true' ), $url ) 
	);

	wp_safe_redirect( $redirect );
	exit();
}
add_action( 'template_redirect', 'atcf_shortcode_profile_request_data' );

/**
 * Success Message
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_profile_info_before_success() {
	if ( ! isset ( $_GET[ 'success' ] ) )
		return;

	if ( isset ( $_GET[ 'emailed' ] ) )
		$message = apply_filters( 'atcf_shortcode_profile_info_before_success_emailed', __( 'Success! We have been notified of your request.', 'atcf' ) );
	else if ( isset ( $_GET[ 'exported' ] ) )
		$message = apply_filters( 'atcf_shortcode_profile_info_before_success_exported', __( 'Success! Your download should begin shortly.', 'atcf' ) );
	else
		return;
?>
	<p class="edd_success"><?php echo esc_attr( $message ); ?></p>	
<?php
}
add_action( 'atcf_shortcode_profile', 'atcf_shortcode_profile_info_before_success', 1 );