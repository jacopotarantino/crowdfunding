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
function atcf_shortcode_submit( $atts ) {
	global $edd_options;

	$atts = shortcode_atts( array(
		'editing'    => false,
		'previewing' => false
    ), $atts );

	$crowdfunding = crowdfunding();
	$campaign     = null;

	ob_start();

	if ( $atts[ 'editing' ] || $atts[ 'previewing' ] ) {
		global $post;
		
		$campaign = atcf_get_campaign( $post );
	}

	wp_enqueue_script( 'jquery-validation', EDD_PLUGIN_URL . 'assets/js/jquery.validate.min.js');
	wp_enqueue_script( 'atcf-scripts', $crowdfunding->plugin_url . '/assets/js/crowdfunding.js', array( 'jquery', 'jquery-validation' ) );

	wp_localize_script( 'atcf-scripts', 'CrowdFundingL10n', array(
		'oneReward' => __( 'At least one reward is required.', 'atcf' )
	) );

	if ( apply_filters( 'atcf_shortcode_submit_hide', false ) )
		return do_action( 'atcf_shortcode_submit_hidden' );
?>
	<?php do_action( 'atcf_shortcode_submit_before', $atts, $campaign ); ?>
	<form action="" method="post" class="atcf-submit-campaign" enctype="multipart/form-data">
		<?php do_action( 'atcf_shortcode_submit_fields', $atts, $campaign ); ?>

		<p class="atcf-submit-campaign-submit">
			<button type="submit" name="submit" value="submit" class="button">
				<?php echo $atts[ 'editing' ] && ! $atts[ 'previewing' ] ? sprintf( _x( 'Update %s', 'edit "campaign"', 'atcf' ), edd_get_label_singular() ) : sprintf( _x( 'Submit %s', 'submit "campaign"', 'atcf' ), edd_get_label_singular() ); ?>
			</button>

			<?php if ( is_user_logged_in() && ! $atts[ 'editing' ] ) : ?>
			<button type="submit" name="submit" value="preview" class="button button-secondary">
				<?php _e( 'Save and Preview', 'atcf' ); ?>
			</button>
			<?php endif; ?>

			<input type="hidden" name="action" value="atcf-campaign-submit" />
			<?php wp_nonce_field( 'atcf-campaign-submit' ); ?>

			<?php if ( $atts[ 'previewing' ] || $atts[ 'editing' ] ) : ?>
				<input type="hidden" name="campaign_id" value="<?php echo $campaign->ID; ?>" />
			<?php endif; ?>
		</p>
	</form>
	<?php do_action( 'atcf_shortcode_submit_after', $atts, $campaign ); ?>
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
function atcf_shortcode_submit_field_title( $atts, $campaign ) {
	if ( apply_filters( 'atcf_shortcode_submit_edit_title', $atts[ 'editing' ] ) )
		return;

	$title = $atts[ 'previewing' ] ? $campaign->data->post_title : null;
?>
	<h3 class="atcf-submit-section campaign-information"><?php _e( 'Campaign Information', 'atcf' ); ?></h3>

	<p class="atcf-submit-title">
		<label for="title"><?php _e( 'Title', 'atcf' ); ?></label>
		<input type="text" name="title" id="title" placeholder="<?php esc_attr_e( 'Title', 'atcf' ); ?>" value="<?php echo esc_attr( $title ); ?>">
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
function atcf_shortcode_submit_field_goal( $atts, $campaign ) {
	global $edd_options;

	if ( $atts[ 'editing' ] )
		return;

	$currencies = edd_get_currencies();
	$goal       = $atts[ 'previewing' ] ? $campaign->goal(false) : null;
?>
	<p class="atcf-submit-campaign-goal">
		<label for="goal"><?php printf( __( 'Goal (%s)', 'atcf' ), edd_currency_filter( '' ) ); ?></label>
		<input type="text" name="goal" id="goal" placeholder="<?php echo edd_format_amount( 800 ); ?>" value="<?php echo esc_attr( $goal ); ?>">
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
function atcf_shortcode_submit_field_length( $atts, $campaign ) {
	global $edd_options;

	if ( $atts[ 'editing' ] )
		return;

	$min = isset ( $edd_options[ 'atcf_campaign_length_min' ] ) ? $edd_options[ 'atcf_campaign_length_min' ] : 14;
	$max = isset ( $edd_options[ 'atcf_campaign_length_max' ] ) ? $edd_options[ 'atcf_campaign_length_max' ] : 48;

	$start = apply_filters( 'atcf_shortcode_submit_field_length_start', round( ( $min + $max ) / 2 ) );

	$length = $atts[ 'previewing' ] ? $campaign->days_remaining() : null;
?>
	<p class="atcf-submit-campaign-length">
		<label for="length"><?php _e( 'Length (Days)', 'atcf' ); ?></label>
		<input type="number" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" step="1" name="length" id="length" value="<?php echo esc_attr( $start ); ?>" value="<?php echo esc_attr( $length ); ?>">
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
function atcf_shortcode_submit_field_type( $atts, $campaign ) {
	global $edd_options;

	if ( $atts[ 'editing' ]  )
		return;

	$types = atcf_campaign_types();
	$type  = $atts[ 'previewing' ] ? $campaign->type() : atcf_campaign_type_default();
?>
	<h4><?php _e( 'Funding Type', 'atcf' ); ?> <?php if ( $edd_options[ 'faq_page' ] ) : ?><small> &mdash; <a href="<?php echo esc_url( get_permalink( $edd_options[ 'faq_page' ] ) ); ?>"><?php echo apply_filters( 'atcf_submit_field_type_more_link', __( 'Learn More', 'atcf' ) ); ?></a></small><?php endif; ?></h4>

	<p class="atcf-submit-campaign-type">
		<?php foreach ( atcf_campaign_types_active() as $key => $desc ) : ?>
		<label for="campaign_type[<?php echo esc_attr( $key ); ?>]"><input type="radio" name="campaign_type" id="campaign_type[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $type ); ?> /> <?php echo $types[ $key ][ 'title' ]; ?></label> &mdash; <small><?php echo $types[ $key ][ 'description' ]; ?></small><br />
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
function atcf_shortcode_submit_field_category( $atts, $campaign ) {
	if ( $atts[ 'editing' ] || $atts[ 'previewing' ] ) {
		$categories = get_the_terms( $campaign->ID, 'download_category' );

		$selected = 0;

		if ( ! $categories )
			$categories = array();

		foreach( $categories as $category ) {
			$selected = $category->term_id;
			break;
		}
	} else {
		$selected = 0;
	}
?>
	<p class="atcf-submit-campaign-category">
		<label for="category"><?php _e( 'Category', 'atcf' ); ?></label>			
		<?php 
			wp_dropdown_categories( array( 
				'orderby'    => 'name', 
				'hide_empty' => 0,
				'taxonomy'   => 'download_category',
				'selected'   => $selected
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
function atcf_shortcode_submit_field_description( $atts, $campaign ) {
?>
	<div class="atcf-submit-campaign-description">
		<label for="description"><?php _e( 'Description', 'atcf' ); ?></label>
		<?php 
			wp_editor( $atts[ 'editing' ] || $atts[ 'previewing' ] ? wp_richedit_pre( $campaign->data->post_content ) : '', 'description', apply_filters( 'atcf_submit_field_description_editor_args', array( 
				'media_buttons' => true,
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
function atcf_shortcode_submit_field_updates( $atts, $campaign ) {
	if ( ! $atts[ 'editing' ] || $atts[ 'previewing' ] )
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
function atcf_shortcode_submit_field_excerpt( $atts, $campaign ) {
?>
	<p class="atcf-submit-campaign-excerpt">
		<label for="excerpt"><?php _e( 'Excerpt', 'atcf' ); ?></label>
		<textarea name="excerpt" id="excerpt"><?php echo $atts[ 'editing' ] || $atts[ 'previewing' ] ? apply_filters( 'get_the_excerpt', $campaign->data->post_excerpt ) : null; ?></textarea>
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
function atcf_shortcode_submit_field_images( $atts, $campaign ) {
	if ( ! atcf_theme_supports( 'campaign-featured-image' ) )
		return;
?>
	<p class="atcf-submit-campaign-images">
		<label for="excerpt"><?php _e( 'Featuerd Image', 'atcf' ); ?></label>
		<input type="file" name="image" id="image" />

		<?php if ( $atts[ 'editing' ] || $atts[ 'previewing' ] ) : ?>
			<br /><?php the_post_thumbnail( array( 50, 50 ) ); ?>
		<?php endif; ?>
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
function atcf_shortcode_submit_field_video( $atts, $campaign ) {
	if ( ! atcf_theme_supports( 'campaign-video' ) )
		return;

	$video = $atts[ 'editing' ] || $atts[ 'previewing' ] ? $campaign->video() : null;
?>
	<p class="atcf-submit-campaign-video">
		<label for="length"><?php _e( 'Featued Video URL', 'atcf' ); ?></label>
		<input type="text" name="video" id="video" value="<?php echo esc_attr( $video ); ?>">
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
function atcf_shortcode_submit_field_rewards( $atts, $campaign ) {
	$rewards  = $atts[ 'previewing' ] || $atts[ 'editing' ] ? edd_get_variable_prices( $campaign->ID ) : array();
	$shipping = $atts[ 'previewing' ] || $atts[ 'editing' ] ? $campaign->needs_shipping() : 0;
?>
	<h3 class="atcf-submit-section backer-rewards"><?php _e( 'Backer Rewards', 'atcf' ); ?></h3>

	<p class="atcf-submit-campaign-shipping">
		<label for="shipping"><input type="checkbox" id="shipping" name="shipping" value="1" <?php checked(1, $shipping); ?> /> <?php _e( 'Collect shipping information on checkout.', 'atcf' ); ?></label>
	</p>

	<?php do_action( 'atcf_shortcode_submit_field_rewards_list_before' ); ?>

	<div class="atcf-submit-campaign-rewards">
		<?php foreach ( $rewards as $key => $reward ) : $disabled = isset ( $reward[ 'bought' ] ) && $reward[ 'bought' ] > 0 ? true : false; ?>
		<div class="atcf-submit-campaign-reward">
			<?php do_action( 'atcf_shortcode_submit_field_rewards_before' ); ?>

			<p class="atcf-submit-campaign-reward-price">
				<label for="rewards[<?php echo esc_attr( $key ); ?>][price]"><?php printf( __( 'Amount (%s)', 'atcf' ), edd_currency_filter( '' ) ); ?></label>
				<input class="name" type="text" name="rewards[<?php echo esc_attr( $key ); ?>][price]" id="rewards[<?php echo esc_attr( $key ); ?>][price]" value="<?php echo esc_attr( $reward[ 'amount' ] ); ?>" <?php disabled(true, $disabled); ?> />
			</p>

			<p class="atcf-submit-campaign-reward-description">
				<label for="rewards[<?php echo esc_attr( $key ); ?>][description]"><?php _e( 'Reward', 'atcf' ); ?></label>
				<input class="description" type="text" name="rewards[<?php echo esc_attr( $key ); ?>][description]" id="rewards[<?php echo esc_attr( $key ); ?>][description]" rows="3" value="<?php echo esc_attr( $reward[ 'name' ] ); ?>" <?php disabled(true, $disabled); ?> />
			</p>

			<p class="atcf-submit-campaign-reward-limit">
				<label for="rewards[<?php echo esc_attr( $key ); ?>][limit]"><?php _e( 'Limit', 'atcf' ); ?></label>
				<input class="description" type="text" name="rewards[<?php echo esc_attr( $key ); ?>][limit]" id="rewards[<?php echo esc_attr( $key ); ?>][limit]" value="<?php echo isset ( $reward[ 'limit' ] ) ? esc_attr( $reward[ 'limit' ] ) : null; ?>" <?php disabled(true, $disabled); ?> />
			</p>

			<?php do_action( 'atcf_shortcode_submit_field_rewards_after' ); ?>

			<p class="atcf-submit-campaign-reward-remove">
				<label>&nbsp;</label><br />
				<a href="#">&times;</a>
			</p>
		</div>
		<?php endforeach; ?>

		<?php if ( ! $atts[ 'previewing' ] && ! $atts[ 'editing' ] ) : ?>
		<div class="atcf-submit-campaign-reward">
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

			<?php do_action( 'atcf_shortcode_submit_field_rewards_after' ); ?>

			<p class="atcf-submit-campaign-reward-remove">
				<label>&nbsp;</label><br />
				<a href="#">&times;</a>
			</p>
		</div>
		<?php endif; ?>

		<p class="atcf-submit-campaign-add-reward">
			<a href="#" class="atcf-submit-campaign-add-reward-button"><?php _e( '+ <em>Add Reward</em>', 'atcf' ); ?></a>
		</p>
	</div>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_rewards', 90, 2 );

/**
 * Campaign Contact Email
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_contact_email( $atts, $campaign ) {
?>
	<h3 class="atcf-submit-section payment-information"><?php _e( 'Your Information', 'atcf' ); ?></h3>

	<?php if ( ! $atts[ 'editing' ] ) : ?>
		<p class="atcf-submit-campaign-contact-email">
		<?php if ( ! is_user_logged_in() ) : ?>
			<label for="email"><?php _e( 'Contact Email', 'atcf' ); ?></label>
			<input type="text" name="contact-email" id="contact-email" value="<?php echo $editing ? $campaign->contact_email() : null; ?>" />
			<?php if ( ! $atts[ 'editing' ] ) : ?><span class="description"><?php _e( 'An account will be created for you with this email address. It must be active.', 'atcf' ); ?></span><?php endif; ?>
		<?php else : ?>
			<?php $current_user = wp_get_current_user(); ?>
			<?php printf( __( '<strong>Note</strong>: You are currently logged in as %1$s. This %2$s will be associated with that account. Please <a href="%3$s">log out</a> if you would like to make a %2$s under a new account.', 'atcf' ), $current_user->user_email, strtolower( edd_get_label_singular() ), wp_logout_url( get_permalink() ) ); ?>
		<?php endif; ?>
		</p>
	<?php endif; ?>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_contact_email', 100, 2 );

/**
 * Campaign Author
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_author( $atts, $campaign ) { 
?>
	<p class="atcf-submit-campaign-author">
		<label for="name"><?php _e( 'Name/Organization Name', 'atcf' ); ?></label>
		<input type="text" name="name" id="name" value="<?php echo $atts[ 'editing' ] || $atts[ 'previewing' ] ? $campaign->author() : null; ?>" />
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
function atcf_shortcode_submit_field_location( $atts, $campaign ) {
?>
	<p class="atcf-submit-campaign-location">
		<label for="length"><?php _e( 'Location', 'atcf' ); ?></label>
		<input type="text" name="location" id="location" value="<?php echo $atts[ 'editing' ] || $atts[ 'previewing' ] ? $campaign->location() : null; ?>" />
	</p>
<?php
}
add_action( 'atcf_shortcode_submit_fields', 'atcf_shortcode_submit_field_location', 120, 2 );

/**
 * Terms
 *
 * @since CrowdFunding 1.0
 *
 * @return void
 */
function atcf_shortcode_submit_field_terms( $atts, $campaign ) {
	if ( $atts[ 'editing' ] || $atts[ 'previewing' ] )
		return;
	
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

	$action            = esc_attr( $_POST[ 'submit' ] );
	$existing_campaign = isset ( $_POST[ 'campaign_id' ] ) ? esc_attr( $_POST[ 'campaign_id' ] ) : null;

	$errors           = new WP_Error();
	$prices           = array();
	$upload_overrides = array( 'test_form' => false );

	$terms     = isset ( $_POST[ 'edd_agree_to_terms' ] ) ? $_POST[ 'edd_agree_to_terms' ] : 0;
	
	$title     = isset ( $_POST[ 'title' ] ) ? $_POST[ 'title' ] : null;
	$goal      = isset ( $_POST[ 'goal' ] ) ? $_POST[ 'goal' ] : null;
	$length    = isset ( $_POST[ 'length' ] ) ? $_POST[ 'length' ] : null;
	$type      = isset ( $_POST[ 'campaign_type' ] ) ? $_POST[ 'campaign_type' ] : null;
	$location  = isset ( $_POST[ 'location' ] ) ? $_POST[ 'location' ] : null;
	$category  = isset ( $_POST[ 'cat' ] ) ? $_POST[ 'cat' ] : 0;
	$content   = isset ( $_POST[ 'description' ] ) ? $_POST[ 'description' ] : null;
	$updates   = isset ( $_POST[ 'updates' ] ) ? $_POST[ 'updates' ] : null;
	$excerpt   = isset ( $_POST[ 'excerpt' ] ) ? $_POST[ 'excerpt' ] : null;
	$author    = isset ( $_POST[ 'name' ] ) ? $_POST[ 'name' ] : null;
	$shipping  = isset ( $_POST[ 'shipping' ] ) ? $_POST[ 'shipping' ] : null;

	$image     = isset ( $_FILES[ 'image' ] ) ? $_FILES[ 'image' ] : null;
	$video     = isset ( $_POST[ 'video' ] ) ? $_POST[ 'video' ] : null;

	$rewards   = isset ( $_POST[ 'rewards' ] ) ? $_POST[ 'rewards' ] : null;

	if ( isset ( $_POST[ 'contact-email' ] ) )
		$c_email = $_POST[ 'contact-email' ];
	else {
		$current_user = wp_get_current_user();
		$c_email = $current_user->user_email;
	}

	if ( isset( $edd_options[ 'show_agree_to_terms' ] ) && ! $terms )
		$errors->add( 'terms', __( 'Please agree to the Terms and Conditions', 'atcf' ) );

	/** Check Title */
	if ( $title && '' == $title )
		$errors->add( 'invalid-title', __( 'Please add a title to this campaign.', 'atcf' ) );

	/** Check Goal */
	$goal = edd_sanitize_amount( $goal );

	if ( $goal && ! is_numeric( $goal ) )
		$errors->add( 'invalid-goal', sprintf( __( 'Please enter a valid goal amount. All goals are set in the %s currency.', 'atcf' ), $edd_options[ 'currency' ] ) );

	/** Check Length */
	if ( $length ) {
		$length = absint( $length );

		$min = isset ( $edd_options[ 'atcf_campaign_length_min' ] ) ? $edd_options[ 'atcf_campaign_length_min' ] : 14;
		$max = isset ( $edd_options[ 'atcf_campaign_length_max' ] ) ? $edd_options[ 'atcf_campaign_length_max' ] : 42;

		if ( $length < $min )
			$length = $min;
		else if ( $length > $max )
			$length = $max;
	
		$end_date = strtotime( sprintf( '+%d day', $length ) );
		$end_date = get_gmt_from_date( date( 'Y-m-d H:i:s', $end_date ) );
	}

	/** Check Category */
	$category = absint( $category );

	/** Check Content */
	if ( $content && '' == $content )
		$errors->add( 'invalid-content', __( 'Please add content to this campaign.', 'atcf' ) );

	/** Check Excerpt */
	if ( $excerpt && '' == $excerpt )
		$excerpt = null;

	/** Check Image */
	if ( $image && empty( $image ) && atcf_theme_supports( 'campaign-featured-image' ) )
		$errors->add( 'invalid-previews', __( 'Please add a campaign image.', 'atcf' ) );

	/** Check Rewards */
	if ( $rewards && empty( $rewards ) )
		$errors->add( 'invalid-rewards', __( 'Please add at least one reward to the campaign.', 'atcf' ) );

	if ( email_exists( $c_email ) && ! isset ( $current_user ) )
		$errors->add( 'invalid-c-email', __( 'That contact email address already exists.', 'atcf' ) );		

	do_action( 'atcf_campaign_submit_validate', $_POST, $errors );

	if ( ! empty ( $errors->errors ) ) // Not sure how to avoid empty instantiated WP_Error
		wp_die( $errors );

	//die( print_r( $_POST ) );

	if ( ! $type )
		$type = atcf_campaign_type_default();

	if ( ! isset ( $current_user ) ) {
		$user_id = atcf_register_user( array(
			'user_login'           => $c_email, 
			'user_pass'            => $password, 
			'user_email'           => $c_email,
			'display_name'         => $author,
		) );
	} else {
		$user_id = $current_user->ID;
	}

	$status = 'submit' == $action ? 'pending' : 'draft';

	/** If we are submitting, but this is a live campaign, keep published */
	if ( $existing_campaign && ( 'pending' == $status && get_post( $existing_campaign )->post_status == 'publish' ) )
		$status = 'publish';

	$args = apply_filters( 'atcf_campaign_submit_data', array(
		'post_type'    => 'download',
		'post_status'  => $status,
		'post_content' => $content,
		'post_author'  => $user_id
	), $_POST );

	if ( $title )
		$args[ 'post_title' ] = $title;

	if ( $excerpt )
		$args[ 'post_excerpt' ] = $excerpt;

	if ( ! $existing_campaign ) {
		$campaign = wp_insert_post( $args, true );
	} else {
		$args[ 'ID' ] = $existing_campaign;

		$campaign = wp_update_post( $args );
	}

	wp_set_object_terms( $campaign, array( $category ), 'download_category' );

	/** Extra Campaign Information */
	if ( $goal )
		update_post_meta( $campaign, 'campaign_goal', apply_filters( 'edd_metabox_save_edd_price', $goal ) );
	
	if ( $type )
		update_post_meta( $campaign, 'campaign_type', sanitize_text_field( $type ) );
	
	if ( $c_email )
		update_post_meta( $campaign, 'campaign_contact_email', sanitize_text_field( $c_email ) );
	
	if ( $length )
		update_post_meta( $campaign, 'campaign_end_date', sanitize_text_field( $end_date ) );
	
	if ( $location )
		update_post_meta( $campaign, 'campaign_location', sanitize_text_field( $location ) );
	
	if ( $author )
		update_post_meta( $campaign, 'campaign_author', sanitize_text_field( $author ) );

	if ( $video && atcf_theme_supports( 'campaign-video' ) )
		update_post_meta( $campaign, 'campaign_video', esc_url( $video ) );

	if ( $updates )
		update_post_meta( $campaign, 'campaign_updates', wp_kses_post( $updates ) );
	
	update_post_meta( $campaign, '_campaign_physical', sanitize_text_field( $shipping ) );
	
	foreach ( $rewards as $key => $reward ) {
		if ( '' == $reward[ 'price' ] )
			continue;

		$prices[] = array(
			'name'   => sanitize_text_field( $reward[ 'description' ] ),
			'amount' => apply_filters( 'edd_metabox_save_edd_price', $reward[ 'price' ] ),
			'limit'  => sanitize_text_field( $reward[ 'limit' ] )
		);
	}

	if ( '' != $image[ 'name' ] ) {
		$upload = wp_handle_upload( $image, $upload_overrides );
		$attachment = array(
			'guid'           => $upload[ 'url' ], 
			'post_mime_type' => $upload[ 'type' ],
			'post_title'     => $upload[ 'file' ],
			'post_content'   => '',
			'post_status'    => 'inherit',
			'post_parent'    => $campaign
		);

		$attach_id = wp_insert_attachment( $attachment, $upload[ 'file' ], $campaign );		
		
		wp_update_attachment_metadata( 
			$attach_id, 
			wp_generate_attachment_metadata( $attach_id, $upload[ 'file' ] ) 
		);

		update_post_meta( $campaign, '_thumbnail_id', absint( $attach_id ) );
	}

	/** EDD Stuff */
	update_post_meta( $campaign, '_variable_pricing', 1 );
	update_post_meta( $campaign, '_edd_price_options_mode', 1 );
	update_post_meta( $campaign, '_edd_hide_purchase_link', 'on' );
	
	update_post_meta( $campaign, 'edd_variable_prices', $prices );

	do_action( 'atcf_submit_process_after', $campaign, $_POST, $status );

	if ( 'publish' == $status ) {
		wp_safe_redirect( add_query_arg( 'updated', 'true', get_permalink( $campaign ) ) );
		exit();
	} elseif ( 'submit' == $action ) {
		$url = isset ( $edd_options[ 'submit_page' ] ) ? get_permalink( $edd_options[ 'submit_page' ] ) : get_permalink();

		$redirect = apply_filters( 'atcf_submit_campaign_success_redirect', add_query_arg( array( 'success' => 'true' ), $url ) );
		wp_safe_redirect( $redirect );
		exit();
	} else {
		wp_safe_redirect( add_query_arg( 'preview', 'true', get_permalink( $campaign ) ) );
		exit();
	}
}
add_action( 'template_redirect', 'atcf_shortcode_submit_process' );

/**
 * Redirect submit page if needed.
 *
 * @since Appthemer CrowdFunding 1.1
 *
 * @return void
 */
function atcf_shortcode_submit_redirect() {
	global $edd_options, $post;

	if ( ! is_a( $post, 'WP_Post' ) )
		return;

	if ( ! is_user_logged_in() && ( $post->ID == $edd_options[ 'submit_page' ] ) && isset ( $edd_options[ 'atcf_settings_require_account' ] ) ) {
		$redirect = apply_filters( 'atcf_require_account_redirect', isset ( $edd_options[ 'login_page' ] ) ? get_permalink( $edd_options[ 'login_page' ] ) : home_url() );

		wp_safe_redirect( $redirect );
		exit();
	}
}
add_action( 'template_redirect', 'atcf_shortcode_submit_redirect', 1 );