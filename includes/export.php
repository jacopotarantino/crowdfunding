<?php
/**
 * Export
 *
 * Support exporting data for a specific campaign/download.
 *
 * @since Astoundify Crowdfunding 0.1-alpha
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Export capability shim for Easy Digital Downloads
 *
 * @since Astoundify Crowdfunding 1.3
 *
 * @return boolean If a user can export campaign data.
 */
function atcf_export_capability() {
	return current_user_can( 'submit_campaigns' ) || current_user_can( 'manage_options' );
}
add_filter( 'edd_export_capability', 'atcf_export_capability' );

/**
 * Export action. Monitor backened and create a new export.
 *
 * @since Astoundify Crowdfunding 0.1-alpha
 *
 * @return void
 */
function atcf_export_campaign() {
	$crowdfunding = crowdfunding();
	$campaign_id  = absint( $_POST[ 'edd_export_campaign_id' ] );

	if ( 0 != $campaign_id ) {
		require_once EDD_PLUGIN_DIR . 'includes/admin/reporting/class-export.php';
		require( $crowdfunding->includes_dir . 'class-export-campaigns.php' );

		$campaign_export = new ATCF_Campaign_Export( $campaign_id );

		$campaign_export->export();
	}
}
add_action( 'edd_export_campaign', 'atcf_export_campaign' );

/**
 * Export metabox
 *
 * @since Astoundify Crowdfunding 0.1-alpha
 *
 * @return void
 */
function atcf_campaign_export_box() {
	?>
	<div class="metabox-holder">
		<div id="post-body">
			<div id="post-body-content">
				<div class="postbox">
					<h3><span><?php _e( 'Export Campaign Data', 'atcf'); ?></span></h3>
					<div class="inside">
						<p><?php _e( 'Download a CSV of all pledges recorded.', 'atcf' ); ?></p>
						<p>
							<form method="post">
								<select name="edd_export_campaign_id">
									<option value="0"><?php printf( __( 'Select %s', 'atcf' ), edd_get_label_singular() ); ?></option>

									<?php
									$campaigns = get_posts( array( 'post_type' => 'download', 'posts_per_page' => -1 ) );

									foreach( $campaigns as $campaign ) {
										$_campaign = atcf_get_campaign( $campaign );

										if ( apply_filters( 'atcf_export_filter_completed', ! $_campaign->is_funded() && 'fixed' == $_campaign->type() ) )
											continue;

										echo '<option value="' . $campaign->ID . '">' . $campaign->post_title . '</option>';
									}
									?>
								</select>
								<input type="hidden" name="edd-action" value="export_campaign"/>
								<input type="submit" value="<?php _e( 'Generate CSV', 'atcf' ); ?>" class="button-secondary"/>
							</form>
						</p>
					</div><!-- .inside -->
				</div><!-- .postbox -->
			</div><!-- .post-body-content -->
		</div><!-- .post-body -->
	</div><!-- .metabox-holder -->
	<?php
}
add_action( 'edd_reports_tab_export', 'atcf_campaign_export_box', 1 );