<?php
/**
 * Submit Shortcode.
 *
 * [appthemer_crowdfunding_submit] creates a submission form.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
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
function atcf_shortcode_submit( $editing = false ) {
	$crowdfunding = crowdfunding();
	$campaign     = null;

	if ( $editing ) {
		global $post;

		$campaign = atcf_get_campaign( $post );
	} else {
		wp_enqueue_script( 'jquery-validation', EDD_PLUGIN_URL . 'assets/js/jquery.validate.min.js');
		wp_enqueue_script( 'atcf-scripts', $crowdfunding->plugin_url . '/assets/js/crowdfunding.js', array( 'jquery', 'jquery-validation' ) );

		wp_localize_script( 'atcf-scripts', 'CrowdFundingL10n', array(
			'oneReward' => __( 'At least one reward is required.', 'atcf' )
		) );
	}

	ob_start();
?>
	<?php do_action( 'atcf_shortcode_submit_before', $editing, $campaign ); ?>
	<form action="" method="post" class="atcf-submit-campaign" enctype="multipart/form-data">
		<?php do_action( 'atcf_shortcode_submit_fields', $editing, $campaign ); ?>

		<p class="atcf-submit-campaign-submit">
			<input type="submit" value="<?php printf( '%s %s', $editing ? _x( 'Update', 'edit object', 'atcf' ) : _x( 'Submit', 'submit object', 'atcf' ), edd_get_label_singular() ); ?>">
			<input type="hidden" name="action" value="atcf-campaign-<?php echo $editing ? 'edit' : 'submit'; ?>" />
			<?php wp_nonce_field( 'atcf-campaign-' . ( $editing ? 'edit' : 'submit' ) ); ?>
		</p>
	</form>
	<?php do_action( 'atcf_shortcode_submit_after', $editing, $campaign ); ?>
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
function atcf_shortcode_submit_field_title( $editing, $campaign ) {
	if ( $editing )
		return;
?>
	<h3 class="atcf-submit-section campaign-information"><?php _e( 'Campaign Information', 'atcf' ); ?></h3>

	<p class="atcf-submit-title">
		<label for="title"><?php _e( 'Title', 'atcf' ); ?></label>
		<input type="text" name="title" id="title" placeholder="<?php esc_attr_e( 'Title', 'atcf' ); ?>">
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_title', 10, 2 );

/**
 * Campaign Goal
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_goal( $editing, $campaign ) {
	global $edd_options;

	if ( $editing )
		return;

	$currencies = edd_get_currencies();
?>
	<p class="atcf-submit-campaign-goal">
		<label for="goal"><?php printf( __( 'Goal (%s)', 'atcf' ), edd_currency_filter( '' ) ); ?></label>
		<input type="text" name="goal" id="goal" placeholder="800">
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_goal', 20, 2 );

/**
 * Campaign Length 
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_length( $editing, $campaign ) {
	global $edd_options;

	if ( $editing  )
		return;

	$min = isset ( $edd_options[ 'atcf_campaign_length_min' ] ) ? $edd_options[ 'atcf_campaign_length_min' ] : 14;
	$max = isset ( $edd_options[ 'atcf_campaign_length_max' ] ) ? $edd_options[ 'atcf_campaign_length_max' ] : 48;

	$start = round( ( $min + $max ) / 2 );
?>
	<p class="atcf-submit-campaign-length">
		<label for="length"><?php _e( 'Length (Days)', 'atcf' ); ?></label>
		<input type="number" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" step="1" name="length" id="length" value="<?php echo esc_attr( $start ); ?>">
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_length', 30, 2 );

/**
 * Campaign Type
 *
 * @since CrowdFunding 0.8
 *
 * @return void
 */
function atcf_shortcode_submit_field_type( $editing, $campaign ) {
	global $edd_options;

	if ( $editing  )
		return;

	$types = atcf_campaign_types();
?>
	<h4><?php _e( 'Funding Type', 'atcf' ); ?> <?php if ( $edd_options[ 'faq_page' ] ) : ?><small> &mdash; <a href="<?php echo esc_url( get_permalink( $edd_options[ 'faq_page' ] ) ); ?>"><?php echo apply_filters( 'atcf_submit_field_type_more_link', __( 'Learn More', 'atcf' ) ); ?></a></small><?php endif; ?></h4>

	<p class="atcf-submit-campaign-type">
		<?php foreach ( atcf_campaign_types_active() as $key => $desc ) : ?>
		<label for="campaign_type[<?php echo esc_attr( $key ); ?>]"><input type="radio" name="campaign_type" id="campaign_type[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, atcf_campaign_type_default() ); ?> /> <?php echo $types[ $key ][ 'title' ]; ?></label> &mdash; <small><?php echo $types[ $key ][ 'description' ]; ?></small><br />
		<?php endforeach; ?>
		<?php do_action( 'atcf_shortcode_submit_field_type' ); ?>
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_type', 35, 2 );

/**
 * Campaign Category
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_category( $editing, $campaign ) {
	if ( $editing ) {
		$categories = get_the_terms( $campaign->ID, 'download_category' );

		$selected = 0;

		if ( ! $categories )
			$categories = array();

		foreach( $categories as $category ) {
			$selected = $category->term_id;
			break;
		}
	}
?>
	<p class="atcf-submit-campaign-category">
		<label for="category"><?php _e( 'Category', 'atcf' ); ?></label>			
		<?php 
			wp_dropdown_categories( array( 
				'orderby'    => 'name', 
				'hide_empty' => 0,
				'taxonomy'   => 'download_category',
				'selected'   => $editing ? $selected : 0
			) );
		?>
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_category', 40, 2 );

/**
 * Campaign Description
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_description( $editing, $campaign ) {
?>
	<div class="atcf-submit-campaign-description">
		<label for="description"><?php _e( 'Description', 'atcf' ); ?></label>
		<?php 
			wp_editor( $editing ? wp_richedit_pre( $campaign->data->post_content ) : '', 'description', apply_filters( 'atcf_submit_field_description_editor_args', array( 
				'media_buttons' => false,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_description', 50, 2 );

/**
 * Campaign Updates
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_updates( $editing, $campaign ) {
	if ( ! $editing )
		return;
?>
	<div class="atcf-submit-campaign-updates">
		<label for="description"><?php _e( 'Updates', 'atcf' ); ?></label>
		<?php 
			wp_editor( $campaign->updates(), 'updates', apply_filters( 'atcf_submit_field_updates_editor_args', array( 
				'media_buttons' => false,
				'teeny'         => true,
				'quicktags'     => false,
				'editor_css'    => '<style>body { background: white; }</style>',
				'tinymce'       => array(
					'theme_advanced_path'     => false,
					'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
					'plugins'                 => 'paste',
					'paste_remove_styles'     => true
				),
			) ) ); 
		?>
	</div><br />
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_updates', 55, 2 );

/**
 * Campaign Export
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_excerpt( $editing, $campaign ) {
?>
	<p class="atcf-submit-campaign-excerpt">
		<label for="excerpt"><?php _e( 'Excerpt', 'atcf' ); ?></label>
		<textarea name="excerpt" id="excerpt" value="<?php echo $editing ? $campaign->data->post_excerpt : null; ?>"></textarea>
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_excerpt', 60, 2 );

/**
 * Campaign Images
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_images( $editing, $campaign ) {
	if ( $editing )
		return;
?>
	<p class="atcf-submit-campaign-images">
		<label for="excerpt"><?php _e( 'Preview Image', 'atcf' ); ?></label>
		<input type="file" name="image" id="image" />
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_images', 70, 2 );

/**
 * Campaign Video
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_video( $editing, $campaign ) {
	if ( $editing )
		return;
?>
	<p class="atcf-submit-campaign-video">
		<label for="length"><?php _e( 'Video URL', 'atcf' ); ?></label>
		<input type="text" name="video" id="video">
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_video', 80, 2 );

/**
 * Campaign Backer Rewards
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_rewards( $editing, $campaign ) {
	if ( $editing )
		return;
?>
	<h3 class="atcf-submit-section backer-rewards"><?php _e( 'Backer Rewards', 'atcf' ); ?></h3>

	<p class="atcf-submit-campaign-shipping">
		<label for="shipping"><input type="checkbox" id="shipping" name="shipping" value="1" checked="checked" /> <?php _e( 'Collect shipping information on checkout.', 'atcf' ); ?></label>
	</p>

	<div class="atcf-submit-campaign-rewards">
		<div class="atcf-submit-campaign-reward static">
			<?php do_action( 'atcf_shortcode_submit_field_rewards_before' ); ?>

			<p class="atcf-submit-campaign-reward-price">
				<label for="rewards[0][price]"><?php printf( __( 'Amount (%s)', 'atcf' ), edd_currency_filter( '' ) ); ?></label>
				<input class="name" type="text" name="rewards[0][price]" id="rewards[0][price]" placeholder="20">
			</p>

			<p class="atcf-submit-campaign-reward-description">
				<label for="rewards[0][description]"><?php _e( 'Reward', 'atcf' ); ?></label>
				<input class="description" type="text" name="rewards[0][description]" id="rewards[0][description]" rows="3" placeholder="<?php esc_attr_e( 'Description of reward for this level of contribution.', 'atcf' ); ?>" />
			</p>

			<p class="atcf-submit-campaign-reward-limit">
				<label for="rewards[0][limit]"><?php _e( 'Limit', 'atcf' ); ?></label>
				<input class="description" type="text" name="rewards[0][limit]" id="rewards[0][limit]" />
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
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_rewards', 90, 2 );

/**
 * Campaign PayPal Email
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_paypal_email( $editing, $campaign ) {
?>
	<h3 class="atcf-submit-section payment-information"><?php _e( 'Your Information', 'atcf' ); ?></h3>

	<?php if ( ! $editing ) : ?>
	<p class="atcf-submit-campaign-contact-email">
	<?php if ( ! is_user_logged_in() ) : ?>
		<label for="email"><?php _e( 'Contact Email', 'atcf' ); ?></label>
		<input type="text" name="contact-email" id="contact-email" value="<?php echo $editing ? $campaign->contact_email() : null; ?>" />
		<?php if ( ! $editing ) : ?><span class="description"><?php _e( 'An account will be created for you with this email address. It must be active.', 'atcf' ); ?></span><?php endif; ?>
	<?php else : ?>
		<?php $current_user = wp_get_current_user(); ?>
		<?php printf( __( '<strong>Note</strong>: You are currently logged in as %1$s. This %2$s will be associated with that account. Please <a href="%3$s">log out</a> if you would like to make a %2$s under a new account.', 'atcf' ), $current_user->user_email, strtolower( edd_get_label_singular() ), wp_logout_url( get_permalink() ) ); ?>
	<?php endif; ?>
	</p>
	<?php endif; ?>

	<p class="atcf-submit-campaign-paypal-email">
		<label for="email"><?php _e( 'PayPal Email', 'atcf' ); ?></label>
		<input type="text" name="email" id="email" value="<?php echo $editing ? $campaign->paypal_email() : null; ?>" />
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_paypal_email', 100, 2 );

/**
 * Campaign Author
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_author( $editing, $campaign ) {
?>
	<p class="atcf-submit-campaign-author">
		<label for="name"><?php _e( 'Name/Organization Name', 'atcf' ); ?></label>
		<input type="text" name="name" id="name" value="<?php echo $editing ? $campaign->author() : null; ?>" />
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_author', 110, 2 );

/**
 * Campaign Location
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_location( $editing, $campaign ) {
?>
	<p class="atcf-submit-campaign-location">
		<label for="length"><?php _e( 'Location', 'atcf' ); ?></label>
		<input type="text" name="location" id="location" value="<?php echo $editing ? $campaign->location() : null; ?>" />
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_location', 120, 2 );

function atcf_shortcode_submit_field_terms( $editing, $campaign ) {
	edd_agree_to_terms_js();
	edd_terms_agreement();
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_terms', 200, 2 );

/**
 * Success Message
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_before_success() {
	if ( ! isset ( $_GET[ 'success' ] ) )
		return;

	$message = apply_filters( 'atcf_shortcode_submit_success', __( 'Success! Your campaign has been received. It will be reviewed shortly.', 'atcf' ) );
?>
	<p class="edd_success"><?php echo esc_attr( $message ); ?></p>	
<?php
}
add_action( 'atcf_shortcode_submit_before', 'atcf_shortcode_submit_before_success', 1 );