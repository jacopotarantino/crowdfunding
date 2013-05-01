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
	global $edd_options;

	$crowdfunding = crowdfunding();
	$campaign     = null;

	ob_start();

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

	$start = apply_filters( 'atcf_shortcode_submit_field_length_start', round( ( $min + $max ) / 2 ) );
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
		<textarea name="excerpt" id="excerpt" value="<?php echo $editing ? apply_filters( 'get_the_excerpt', $campaign->data->post_excerpt ) : null; ?>"></textarea>
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
 * Campaign Contact Email
 *
 * @since CrowdFunding 0.1-alpha
 *
 * @return void
 */
function atcf_shortcode_submit_field_contact_email( $editing, $campaign ) {
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

/**
 * Terms
 *
 * @since CrowdFunding 1.0
 *
 * @return void
 */
function atcf_shortcode_submit_field_terms( $editing, $campaign ) {
	if ( $editing )
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

	$errors           = new WP_Error();
	$prices           = array();
	$edd_files        = array();
	$upload_overrides = array( 'test_form' => false );

	$terms     = $_POST[ 'edd_agree_to_terms' ];
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
	
	if ( isset ( $_POST[ 'contact-email' ] ) )
		$c_email = $_POST[ 'contact-email' ];
	else {
		$current_user = wp_get_current_user();
		$c_email = $current_user->user_email;
	}

	if ( isset( $edd_options[ 'show_agree_to_terms' ] ) && ! $terms )
		$errors->add( 'terms', __( 'Please agree to the Terms and Conditions', 'atcf' ) );

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

	if ( email_exists( $c_email ) && ! isset ( $current_user ) )
		$errors->add( 'invalid-c-email', __( 'That contact email address already exists.', 'atcf' ) );		

	do_action( 'atcf_campaign_submit_validate', $_POST, $errors );

	if ( ! empty ( $errors->errors ) ) // Not sure how to avoid empty instantiated WP_Error
		wp_die( $errors );

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
 * Redirect submit page if needed.
 *
 * @since Appthemer CrowdFunding 1.1
 *
 * @return void
 */
function atcf_shortcode_submit_redirect() {
	global $edd_options, $post;

	if ( ! is_user_logged_in() && ( $post->ID == $edd_options[ 'submit_page' ] ) && $edd_options[ 'atcf_settings_require_account' ] ) {
		$redirect = apply_filters( 'atcf_require_account_redirect', isset ( $edd_options[ 'login_page' ] ) ? get_permalink( $edd_options[ 'login_page' ] ) : home_url() );

		wp_safe_redirect( $redirect );
		exit();
	}
}
add_action( 'template_redirect', 'atcf_shortcode_submit_redirect', 1 );