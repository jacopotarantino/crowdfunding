<?php

function cf_metabox_page_subtitle() {
	add_meta_box( 'cf_subtitle', __( 'Subtitle', 'cf' ), 'cf_metabox_subtitle_box', 'page', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'cf_metabox_page_subtitle' );

function cf_metabox_subtitle_box( $post ) {
	/** Verification Field */
	wp_nonce_field( 'cf', 'cf-save' );

	/** Get Previous Value */
	$subtitle = esc_attr( get_post_meta( $post->ID, 'subtitle', true ) );
?>
	<input type="text" name="subtitle" id="subtitle" value="<?php echo $subtitle; ?>" style="width:100%;" />
<?php
}

function cf_metabox_save( $post_id ) {
	if ( empty( $_POST ) )
		return $post_id;

	/** Check Nonce */
	if ( ! isset( $_POST[ 'cf-save' ] ) || ! wp_verify_nonce( $_POST[ 'cf-save' ], 'cf' ) )
		return $post_id;

	if ( ! in_array( $_POST[ 'post_type' ], array( 'page' ) ) )
		return $post_id;

	/** Don't save when autosaving */
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
		return $post_id;
	
	$keys = array( 'subtitle' );

	foreach ( $keys as $key ) {
		if ( ! isset( $_POST[ $key ] ) )
			continue;
		
		$value = esc_attr( $_POST[ $key ] );

		if ( '' == $key )
			delete_post_meta( $post_id, $key, $value );
		else
			update_post_meta( $post_id, $key, $value );
	}
}
add_action( 'save_post', 'cf_metabox_save' );