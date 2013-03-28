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
 * Biography
 *
 * @since CrowdFunding 0.1-alpha
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
add_action( 'atcf_profile_info_fields', 'atcf_profile_info_fields_bio', 10, 2 );

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
		'post_author' => $user->ID,
		'post_status' => array( 'publish', 'pending' ),
		'nopaging'    => true
	) );
?>
	<h3 class="atcf-profile-section your-campaigns"><?php _e( 'Your Campaigns', 'atcf' ); ?></h3>

	<ul class="atcf-profile-campaigns">
	<?php if ( $campaigns->have_posts() ) : while ( $campaigns->have_posts() ) : $campaigns->the_post(); $campaign = atcf_get_campaign( get_post()->ID ); ?>
		<li class="campaign">
			<h4 class="entry-title">
				<?php the_title(); ?>
			</h4>

			<?php if ( 'pending' == get_post()->post_status ) : ?>
				<span class="campaign-awaiting-review"><?php _e( 'This campaign is awaiting review.', 'atcf' ); ?></span>
			<?php else : ?>	
				<ul class="actions">
					<li><a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'Permalink to %s', 'fundify' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php _e( 'View', 'atcf' ); ?></a></li>
					<li><a href="<?php the_permalink(); ?>edit/" title="<?php echo esc_attr( sprintf( __( 'Edit %s', 'fundify' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php _e( 'Edit', 'atcf' ); ?></a></li>
					<li><a href="<?php the_permalink(); ?>#comments" title="<?php echo esc_attr( sprintf( __( 'Comments for %s', 'fundify' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php _e( 'Comments', 'atcf' ); ?></a></li>
					<li><a href="<?php the_permalink(); ?>backers/" title="<?php echo esc_attr( sprintf( __( 'Backers for %s', 'fundify' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php _e( 'Backers', 'atcf' ); ?></a></li>
				</ul>

				<ul class="actions">
					<?php if ( ( 'flexible' == $campaign->type() || $campaign->is_funded() ) && ! $campaign->is_collected() && class_exists( 'PayPalAdaptivePaymentsGateway' ) ) : ?>
					<li><a href="<?php echo esc_url( add_query_arg( array( 'action' => 'atcf-request-payout', 'campaign' => $campaign->ID ) ) ); ?>" title="<?php echo esc_attr( sprintf( __( 'Request Payout for %s', 'fundify' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php _e( 'Request Payout', 'atcf' ); ?></a></li>
					<?php endif; ?>

					<?php if ( ( 'flexible' == $campaign->type() || $campaign->is_funded() ) ) : ?>
					<li><a href="<?php echo esc_url( add_query_arg( array( 'action' => 'atcf-export-data', 'campaign' => $campaign->ID ) ) ); ?>" title="<?php echo esc_attr( sprintf( __( 'Export data for %s', 'fundify' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php _e( 'Export Data', 'atcf' ); ?></a></li>
					<?php endif; ?>
				</ul>

				<div class="digits">
					<div class="bar"><span style="width: <?php echo $campaign->percent_completed(); ?>"></span></div>
					<ul>
						<li><?php printf( __( '<strong>%s</strong> Funded', 'fundify' ), $campaign->percent_completed() ); ?></li>
						<li><?php printf( __( '<strong>%s</strong> Pledged', 'fundify' ), $campaign->current_amount() ); ?></li>
						<li><?php printf( __( '<strong>%s</strong> Days to Go', 'fundify' ), $campaign->days_remaining() ); ?></li>
					</ul>
				</div>
			<?php endif; ?>
		</li>	
	<?php endwhile; endif; wp_reset_query(); ?>
	</ul>
<?php
}
add_action( 'atcf_shortcode_profile', 'atcf_shortcode_profile_campaigns', 20, 1 );