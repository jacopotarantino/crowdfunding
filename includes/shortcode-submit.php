<?php
/**
 * Shortcode.
 *
 * [appthemer_crowdfunding_submit] creates a submission form.
 *
 * @since AT_CrowdFunding 0.1-alpha
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Base page/form. All fields are loaded through an action,
 * so the form can be extended for ever, fields can be removed, added, etc.
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return $form
 */
function atcf_shortcode_submit() {
	$crowdfunding = crowdfunding();

	wp_enqueue_script( 'atcf-scripts', $crowdfunding->plugin_url . '/assets/js/crowdfunding.js', array( 'jquery' ) );

	wp_localize_script( 'atcf-scripts', 'CrowdFundingL10n', array(
		'oneReward' => __( 'At least one reward is required.', 'atcf' )
	) );

	ob_start();
?>
	<?php do_action( 'atcf_shortcode_submit_before' ); ?>
	<form action="" method="post" class="atcf-submit-campaign" enctype="multipart/form-data">
		<?php do_action( 'atcf_shortcode_submit_fields' ); ?>

		<p class="atcf-submit-campaign-submit">
			<input type="submit" value="<?php _e( 'Submit Project', 'atcf' ); ?>">
			<input type="hidden" name="action" value="atcf-campaign-submit" />
			<?php wp_nonce_field( 'atcf-campaign-submit' ) ?>
		</p>
	</form>
	<?php do_action( 'atcf_shortcode_submit_after' ); ?>
<?php
	$form = ob_get_clean();

	return $form;
}
add_shortcode( 'appthemer_crowdfunding_submit', 'atcf_shortcode_submit' );

/**
 * Campaign Title
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_title() {
?>
	<h3 class="atcf-submit-section campaign-information"><?php _e( 'Campaign Information', 'atcf' ); ?></h3>

	<p class="atcf-submit-title">
		<label for="title"><?php _e( 'Title', 'atcf' ); ?></label>
		<input type="text" name="title" id="title" placeholder="<?php _e( 'Title', 'atcf' ); ?>">
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_title', 10 );

/**
 * Campaign Goal
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_goal() {
	global $edd_options;

	$currencies = edd_get_currencies();
?>
	<p class="atcf-submit-campaign-goal">
		<label for="goal"><?php printf( __( 'Goal (%s)', 'atcf' ), edd_currency_filter( '' ) ); ?></label>
		<input type="text" name="goal" id="goal" placeholder="800">
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_goal', 20 );

/**
 * Campaign Length 
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_length() {
?>
	<p class="atcf-submit-campaign-length">
		<label for="length"><?php _e( 'Length (Days)', 'atcf' ); ?></label>
		<input type="number" min="<?php echo apply_filters( 'apcp_campaign_minimum_length', 14 ); ?>" max="<?php echo apply_filters( 'atcf_campaign_maximum_length', 42 ); ?>" step="1" name="length" id="length" value="14">
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_length', 30 );

/**
 * Campaign Category
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_category() {
?>
	<p class="atcf-submit-campaign-category">
		<label for="category"><?php _e( 'Category', 'atcf' ); ?></label>			
		<?php 
			wp_dropdown_categories( array( 
				'orderby'    => 'name', 
				'hide_empty' => 0,
				'taxonomy'   => 'download_category'
			) );
		?>
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_category', 35 );

/**
 * Campaign Author
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_author() {
?>
	<p class="atcf-submit-campaign-author">
		<label for="length"><?php _e( 'Name/Organization Name', 'atcf' ); ?></label>
		<input type="text" name="name" id="name">
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_author', 36 );

/**
 * Campaign Location
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_location() {
?>
	<p class="atcf-submit-campaign-location">
		<label for="length"><?php _e( 'Location', 'atcf' ); ?></label>
		<input type="text" name="location" id="location">
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_location', 38 );

/**
 * Campaign Description
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_description() {
?>
	<div class="atcf-submit-campaign-description">
		<label for="description"><?php _e( 'Description', 'atcf' ); ?></label>
		<?php 
			wp_editor( '', 'description', apply_filters( 'atcf_submit_field_description_editor_args', array( 
				'media_buttons' => false,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
				),
			) ) ); 
		?>
	</div>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_description', 40 );

/**
 * Campaign Export
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_excerpt() {
?>
	<p class="atcf-submit-campaign-excerpt">
		<label for="excerpt"><?php _e( 'Excerpt', 'atcf' ); ?></label>
		<textarea name="excerpt" id="excerpt"></textarea>
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_excerpt', 50 );

/**
 * Campaign Images
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_images() {
?>
	<p class="atcf-submit-campaign-images">
		<label for="excerpt"><?php _e( 'Preview Image', 'atcf' ); ?></label>
		<input type="file" name="image" id="image" />
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_images', 60 );

/**
 * Campaign Video
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_video() {
?>
	<p class="atcf-submit-campaign-video">
		<label for="length"><?php _e( 'Video URL', 'atcf' ); ?></label>
		<input type="text" name="video" id="video">
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_video', 65 );

/**
 * Campaign Backer Rewards
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_rewards() {
?>
	<h3 class="atcf-submit-section backer-rewards"><?php _e( 'Backer Rewards', 'atcf' ); ?></h3>

	<div class="atcf-submit-campaign-rewards">
		<div class="atcf-submit-campaign-reward static">
			<?php do_action( 'atcf_shortcode_submit_field_rewards_before' ); ?>

			<p class="atcf-submit-campaign-reward-price">
				<label for="rewards[0][price]"><?php printf( __( 'Contribution Amount (%s)', 'atcf' ), edd_currency_filter( '' ) ); ?></label>
				<input class="name" type="text" name="rewards[0][price]" id="rewards[0][price]" placeholder="<?php _e( '$20', 'atcf' ); ?>">
			</p>

			<p class="atcf-submit-campaign-reward-description">
				<label for="rewards[0][description]"><?php _e( 'Description', 'atcf' ); ?></label>
				<input class="description" type="text" name="rewards[0][description]" id="rewards[0][description]" rows="3" placeholder="<?php _e( 'Description of reward for this level of contribution.', 'atcf' ); ?>" />
			</p>

			<p class="atcf-submit-campaign-reward-file">
				<label for="files[0]"><?php _e( 'File (optional)', 'atcf' ); ?></label>
				<input type="file" class="file" name="files[0]" id="files[0]" />
			</p>

			<?php do_action( 'atcf_shortcode_submit_field_rewards_after' ); ?>

			<p class="atcf-submit-campaign-reward-remove">
				<label>&nbsp;</label><br />
				<a href="#">&times;</a>
			</p>
		</div>

		<p class="atcf-submit-campaign-add-reward">
			<a href="#" class="atcf-submit-campaign-add-reward-button"><?php _e( '+ <em>Add Reward</em>', 'atcf' ); ?></a>
		</p>
	</div>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_rewards', 90 );

/**
 * Campaign PayPal Email
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_paypal_email() {
?>
	<h3 class="atcf-submit-section payment-information"><?php _e( 'Payment Information', 'atcf' ); ?></h3>

	<p class="atcf-submit-campaign-paypal-email">
		<label for="email"><?php _e( 'PayPal Email:', 'atcf' ); ?></label>
		<input type="text" name="email" id="email" placeholder="<?php _e( 'PayPal Email', 'atcf' ); ?>">
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_paypal_email', 100 );

/**
 * Success Message
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_before() {
	if ( ! isset ( $GET[ 'success' ] ) || $_GET[ 'success' ] != true )
		return;

	$message = apply_filters( 'atcf_shortcode_submit_success', __( 'Success! Your campaign has been received. It will be reviewed shortly.', 'atcf' ) );
?>
	<p class="edd_success"><?php echo esc_attr( $message ); ?></p>	
<?php
}
add_action( 'atcf_shortcode_submit_before', 'atcf_shortcode_submit_before' );