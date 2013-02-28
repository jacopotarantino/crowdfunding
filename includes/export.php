<?php
/**
 * 
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

require_once EDD_PLUGIN_DIR . 'includes/admin/reporting/class-export.php';

class ATCF_Campaign_Export extends EDD_Export {
	/**
	 * Our export type. Used for export-type specific filters / actions
	 */
	public $export_type = 'payments';

	/**
	 * Set the CSV columns
	 *
	 * @return array $cols
	 */
	public function csv_cols() {
		$cols = array(
			'id'       => __( 'ID',   'atcf' ),
			'email'    => __( 'Email', 'atcf' ),
			'first'    => __( 'First Name', 'atcf' ),
			'last'     => __( 'Last Name', 'atcf' ),
			'shipping' => __( 'Shipping Address', 'atcf' ),
			'products' => __( 'Pledge', 'atcf' ),
			'amount'   => __( 'Amount', 'atcf' ),
			'tax'      => __( 'Tax', 'atcf' ),
			'gateway'  => __( 'Payment Method', 'atcf' ),
			'key'      => __( 'Purchase Key', 'atcf' ),
			'date'     => __( 'Date', 'atcf' ),
			'user'     => __( 'User', 'atcf' ),
			'status'   => __( 'Status', 'atcf' )
		);

		return $cols;
	}

	/**
	 * Get the data being exported
	 *
	 * @return array $data
	 */
	public function get_data() {
		global $wpdb;

		$campaign_id = absint( $_POST[ 'edd_export_campaign_id' ] );

		$data     = array();
		$campaign = atcf_get_campaign( $campaign_id );

		$backers  = $campaign->backers();

		foreach ( $backers as $log ) {
			$payment_id     = get_post_meta( $log->ID, '_edd_log_payment_id', true );
			$payment_meta 	= edd_get_payment_meta( $payment_id );
			$user_info 		= edd_get_payment_meta_user_info( $payment_id );
			$downloads      = edd_get_payment_meta_cart_details( $payment_id );
			$total          = isset( $payment_meta['amount'] ) ? $payment_meta['amount'] : 0.00;
			$user_id        = isset( $user_info['id'] ) && $user_info['id'] != -1 ? $user_info['id'] : $user_info['email'];
			$products       = '';

			if ( $downloads ) {
				foreach ( $downloads as $key => $download ) {
					// Download ID
					$id = isset( $payment_meta['cart_details'] ) ? $download['id'] : $download;

					// If the download has variable prices, override the default price
					$price_override = isset( $payment_meta['cart_details'] ) ? $download['price'] : null;

					$price = edd_get_download_final_price( $id, $user_info, $price_override );

					// Display the Downoad Name
					$products .= get_the_title( $id ) . ' - ';

					if ( isset( $downloads[ $key ]['item_number'] ) ) {
						$price_options = $downloads[ $key ]['item_number']['options'];

						if ( isset( $price_options['price_id'] ) ) {
							$products .= edd_get_price_option_name( $id, $price_options['price_id'] ) . ' - ';
						}
					}
					$products .= html_entity_decode( edd_currency_filter( $price ) );

					if ( $key != ( count( $downloads ) -1 ) ) {
						$products .= ' / ';
					}
				}
			}

			if ( is_numeric( $user_id ) ) {
				$user = get_userdata( $user_id );
			} else {
				$user = false;
			}

			$shipping = $payment_meta[ 'shipping' ];

			$data[] = array(
				'id'       => $payment->ID,
				'email'    => $payment_meta['email'],
				'first'    => $user_info['first_name'],
				'last'     => $user_info['last_name'],
				'shipping' => ! empty ( $shipping ) ? $shipping[ 'shipping_address' ] . "\n" . $shipping[ 'shipping_address_2' ] . "\n" . $shipping[ 'shipping_city' ] . ', ' . $shipping[ 'shipping_state' ] . $shipping[ 'shipping_zip' ] . "\n" . $shipping[ 'shipping_country' ] : '',
				'products' => $products,
				'amount'   => html_entity_decode( edd_currency_filter( edd_format_amount( $total ) ) ),
				'tax'      => html_entity_decode( edd_payment_tax( $payment->ID, $payment_meta ) ),
				'discount' => isset( $user_info['discount'] ) && $user_info['discount'] != 'none' ? $user_info['discount'] : __( 'none', 'edd' ),
				'gateway'  => edd_get_gateway_admin_label( get_post_meta( $payment_id, '_edd_payment_gateway', true ) ),
				'key'      => $payment_meta['key'],
				'date'     => date_i18n( get_option( 'date_format' ), strtotime( $payment->post_date ) ),
				'user'     => $user ? $user->display_name : __( 'guest', 'edd' ),
				'status'   => edd_get_payment_status( $payment, true )
			);

		}

		$data = apply_filters( 'edd_export_get_data', $data );
		$data = apply_filters( 'edd_export_get_data_' . $this->export_type, $data );

		return $data;
	}
}

/**
 * 
 */
function atcf_export_campaign() {
	$campaign_id = absint( $_POST[ 'edd_export_campaign_id' ] );

	if ( 0 != $campaign_id ) {
		$campaign_export = new ATCF_Campaign_Export();

		$campaign_export->export();
	}
}
add_action( 'edd_export_campaign', 'atcf_export_campaign' );

/**
 * 
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

										if ( ! $_campaign->is_funded() )
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