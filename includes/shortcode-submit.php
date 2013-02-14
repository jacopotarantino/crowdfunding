<?php

/** Build Shortcode *******************************************************/

/**
 * 
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function cf_shortcode_submit() {
	$crowdfunding = crowdfunding();

	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_style( 'jquery-ui-style', $crowdfunding->plugin_url . 'assets/css/jquery-ui-fresh.css');
?>
	<form action="" method="post" class="atcf-submit-campaign" enctype="multipart/form-data">
		<?php do_action( 'cf_shortcode_submit_fields' ); ?>

		<p class="atcf-submit-campaign-submit">
			<input type="submit" value="<?php _e( 'Submit Project', 'atcf' ); ?>">
			<input type="hidden" name="action" value="cf-campaign-submit" />
			<?php wp_nonce_field( 'cf-campaign-submit' ) ?>
		</p>
	</form>
<?php
}
add_shortcode( 'appthemer_crowdfunding_submit', 'cf_shortcode_submit' );

/**
 * 
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
add_action( 'cf_shortcode_submit_fields', 'atcf_shortcode_submit_field_title', 10 );

/**
 * Goal Field
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
add_action( 'cf_shortcode_submit_fields', 'atcf_shortcode_submit_field_goal', 20 );

/**
 * 
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
add_action( 'cf_shortcode_submit_fields', 'atcf_shortcode_submit_field_length', 30 );

/**
 * Category Field
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
add_action( 'cf_shortcode_submit_fields', 'atcf_shortcode_submit_field_category', 35 );

/**
 * 
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
add_action( 'cf_shortcode_submit_fields', 'atcf_shortcode_submit_field_description', 40 );

/**
 * 
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
add_action( 'cf_shortcode_submit_fields', 'atcf_shortcode_submit_field_excerpt', 50 );

/**
 * 
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
add_action( 'cf_shortcode_submit_fields', 'atcf_shortcode_submit_field_images', 60 );

/**
 * Backer Rewards
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
		</div>

		<p class="atcf-submit-campaign-add-reward">
			<a href="#" class="atcf-submit-campaign-add-reward-button">+ <em>Add Reward</em></a>
		</p>
	</div>
<?php
}
add_action( 'cf_shortcode_submit_fields', 'atcf_shortcode_submit_field_rewards', 90 );

/**
 * Goal Field
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
add_action( 'cf_shortcode_submit_fields', 'atcf_shortcode_submit_field_paypal_email', 100 );