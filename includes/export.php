<?php
/**
 * Export
 *
 * Support exporting data for a specific campaign/download.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Export action. Monitor backened and create a new export.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_export_campaign() {
	$crowdfunding = crowdfunding();
	$campaign_id  = absint( $_POST[ 'edd_export_campaign_id' ] );

	if ( 0 != $campaign_id ) {
		require_once EDD_PLUGIN_DIR . 'includes/admin/reporting/class-export.php';
		require( $crowdfunding->includes_dir . 'export-campaigns.php' );

		$campaign_export = new ATCF_Campaign_Export( $campaign_id );

		$campaign_export->export();
	}
}
add_action( 'edd_export_campaign', 'atcf_export_campaign' );

/**
 * Export metabox
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_campaign_export_box() {
	?>
	<div class="metabox-holder">
		<div id="post-body">
			<div id="post-body-content">
				<div class="postbox">
					<h3><span><?php _e( 'Export Campaign Data', 'edd'); ?></span></h3>
					<div class="inside">
						<p><?php _e( 'Download a CSV of all pledges recorded.', 'edd' ); ?></p>
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
								<input type="submit" value="<?php _e( 'Generate CSV', 'edd' ); ?>" class="button-secondary"/>
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