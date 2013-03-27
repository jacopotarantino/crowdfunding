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
	do_action( 'atcf_shortcode_submit_after', $user );
	echo '</div>';

	$form = ob_get_clean();

	return $form;
}
add_shortcode( 'appthemer_crowdfunding_profile', 'atcf_shortcode_profile' );

/**
 * Campaign History
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_profile_campaigns( $user ) {
	$campaigns = new WP_Query( array(
		'post_type'   => 'download',
		'post_author' => $user->ID,
		'post_status' => array( 'publish', 'pending' )
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
				<span class="campaign-awaiting-review"><?php _e( 'Your campaign is awaiting review.', 'atcf' ); ?></span>
			<?php else : ?>	
				<ul class="actions">
					<li><a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'Permalink to %s', 'fundify' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php _e( 'View', 'atcf' ); ?></a></li>
					<li><a href="<?php the_permalink(); ?>edit/" title="<?php echo esc_attr( sprintf( __( 'Edit %s', 'fundify' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php _e( 'Edit', 'atcf' ); ?></a></li>
					<li><a href="<?php the_permalink(); ?>#comments" title="<?php echo esc_attr( sprintf( __( 'Comments for %s', 'fundify' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php _e( 'Comments', 'atcf' ); ?></a></li>
					<li><a href="<?php the_permalink(); ?>backers/" title="<?php echo esc_attr( sprintf( __( 'Backers for %s', 'fundify' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php _e( 'Backers', 'atcf' ); ?></a></li>
					<?php if ( ( 'flexible' == $campaign->type() || $campaign->is_funded() ) && ! $campaign->is_collected() && class_exists( 'PayPalAdaptivePaymentsGateway' ) ) : ?>
					<li><a href="#" title="<?php echo esc_attr( sprintf( __( 'Request Payout for %s', 'fundify' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php _e( 'Request Payout', 'atcf' ); ?></a></li>
					<li><a href="#" title="<?php echo esc_attr( sprintf( __( 'Export data for %s', 'fundify' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php _e( 'Export Data', 'atcf' ); ?></a></li>
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
add_action( 'atcf_shortcode_submit_after', 'atcf_shortcode_profile_campaigns', 10, 1 );