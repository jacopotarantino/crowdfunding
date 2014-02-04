<?php
/**
 * Plugin Name: Crowdfunding by Astoundify
 * Plugin URI:  https://github.com/astoundify/crowdfunding/
 * Description: A crowd funding platform in the likes of Kickstarter and Indigogo
 * Author:      Astoundify
 * Author URI:  http://astoundify.com
 * Version:     1.8.1
 * Text Domain: atcf
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Check if Easy Digital Downloads is active */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

// EDD Slug above all.
define( 'EDD_SLUG', apply_filters( 'atcf_edd_slug', 'campaigns' ) );

/**
 * Main Crowd Funding Class
 *
 * @since Astoundify Crowdfunding 0.1-alpha
 */
final class ATCF_CrowdFunding {

	/**
	 * @var crowdfunding The one true AT_CrowdFunding
	 */
	public static $instance;

	/**
	 * Main Crowd Funding Instance
	 *
	 * Ensures that only one instance of Crowd Funding exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since Astoundify Crowdfunding 0.1-alpha
	 *
	 * @return The one true Crowd Funding
	 */
	public static function instance() {
		if ( ! isset ( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Start your engines.
	 *
	 * @since Astoundify Crowdfunding 1.5
	 *
	 * @return void
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	/** Private Methods *******************************************************/

	/**
	 * Set some smart defaults to class variables. Allow some of them to be
	 * filtered to allow for early overriding.
	 *
	 * @since Astoundify Crowdfunding 0.1-alpha
	 *
	 * @return void
	 */
	private function setup_globals() {
		/** Versions **********************************************************/

		$this->version    = '1.8';
		$this->version_db = get_option( 'atcf_version' );
		$this->db_version = '1';

		/** Paths *************************************************************/

		$this->file         = __FILE__;
		$this->basename     = apply_filters( 'atcf_plugin_basenname', plugin_basename( $this->file ) );
		$this->plugin_dir   = apply_filters( 'atcf_plugin_dir_path',  plugin_dir_path( $this->file ) );
		$this->plugin_url   = apply_filters( 'atcf_plugin_dir_url',   plugin_dir_url ( $this->file ) );

		$this->template_url = apply_filters( 'atcf_plugin_template_url', 'crowdfunding/' );

		// Includes
		$this->includes_dir = apply_filters( 'atcf_includes_dir', trailingslashit( $this->plugin_dir . 'includes'  ) );
		$this->includes_url = apply_filters( 'atcf_includes_url', trailingslashit( $this->plugin_url . 'includes'  ) );

		// Languages
		$this->lang_dir     = apply_filters( 'atcf_lang_dir',     trailingslashit( $this->plugin_dir . 'languages' ) );

		/** Misc **************************************************************/

		$this->domain       = 'atcf';
	}

	/**
	 * Include required files.
	 *
	 * @since Astoundify Crowdfunding 0.1-alpha
	 *
	 * @return void
	 */
	private function includes() {
		if ( ! class_exists( 'Easy_Digital_Downloads' ) )
			return;

		require_once( $this->includes_dir . 'class-campaigns.php' );
		require_once( $this->includes_dir . 'class-campaign.php' );
		require_once( $this->includes_dir . 'class-processing.php' );
		require_once( $this->includes_dir . 'class-roles.php' );
		require_once( $this->includes_dir . 'settings.php' );
		require_once( $this->includes_dir . 'gateways.php' );
		require_once( $this->includes_dir . 'theme-stuff.php' );
		require_once( $this->includes_dir . 'shipping.php' );
		require_once( $this->includes_dir . 'logs.php' );
		require_once( $this->includes_dir . 'export.php' );
		require_once( $this->includes_dir . 'permalinks.php' );
		require_once( $this->includes_dir . 'checkout.php' );
		require_once( $this->includes_dir . 'shortcode-submit.php' );
		require_once( $this->includes_dir . 'shortcode-profile.php' );
		require_once( $this->includes_dir . 'shortcode-login.php' );
		require_once( $this->includes_dir . 'shortcode-register.php' );

		do_action( 'atcf_include_files' );

		if ( ! is_admin() )
			return;

		do_action( 'atcf_include_admin_files' );
	}

	/**
	 * Setup the default hooks and actions
	 *
	 * @since Astoundify Crowdfunding 0.1-alpha
	 *
	 * @return void
	 */
	private function setup_actions() {
		add_action( 'init', array( $this, 'is_edd_activated' ), 1 );

		if ( ! class_exists( 'Easy_Digital_Downloads' ) )
			return;

		// Scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );

		// Template Files
		add_filter( 'template_include', array( $this, 'template_loader' ) );

		// Upgrade Routine
		add_action( 'admin_init', array( $this, 'check_upgrade' ) );

		// Textdomain
		add_action( 'init', array( $this, 'load_textdomain' ) );

		do_action( 'atcf_setup_actions' );
	}

	/**
	 *
	 */
	function check_upgrade() {
		if ( false === $this->version_db || version_compare( $this->version, $this->version_db, '<' ) ) {
			$this->upgrade_routine();
		}
	}

	function upgrade_routine() {
		flush_rewrite_rules();

		// If we are on 1.8, but their version is older.
		if ( $this->version === '1.8' || ! $this->version_db ) {
			ATCF_Install::init(); // Just run the installer again

			add_option( 'atcf_version', $this->version );
		}
	}

	/**
	 * Easy Digital Downloads
	 *
	 * @since Astoundify Crowdfunding 0.2-alpha
	 *
	 * @return void
	 */
	function is_edd_activated() {
		if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
			if ( is_plugin_active( $this->basename ) ) {
				deactivate_plugins( $this->basename );
				unset( $_GET[ 'activate' ] ); // Ghetto

				add_action( 'admin_notices', array( $this, 'edd_notice' ) );
			}
		}
	}

	/**
	 * Admin notice.
	 *
	 * @since Astoundify Crowdfunding 0.2-alpha
	 *
	 * @return void
	 */
	function edd_notice() {
?>
		<div class="updated">
			<p><?php printf(
						__( '<strong>Notice:</strong> Crowdfunding by Astoundify requires <a href="%s">Easy Digital Downloads</a> in order to function properly.', 'atcf' ),
						wp_nonce_url( network_admin_url( 'update.php?action=install-plugin&plugin=easy-digital-downloads' ), 'install-plugin_easy-digital-downloads' )
				); ?></p>
		</div>
<?php
	}

	/**
	 * Load a template.
	 *
	 * Handles template usage so that we can use our own templates instead of the themes.
	 *
	 * Templates are in the 'templates' folder. AT_CrowdFunding looks for theme
	 * overides in /theme_directory/crowdfunding/ by default
	 *
	 * @see https://github.com/woothemes/woocommerce/blob/master/woocommerce.php
	 *
	 * @access public
	 * @param mixed $template
	 * @return string $template The path of the file to include
	 */
	public function template_loader( $template ) {
		global $wp_query;

		$find    = array();
		$files   = array();

		/** Check if we are editing */
		if ( isset ( $wp_query->query_vars[ 'edit' ] ) &&
			 is_singular( 'download' ) &&
			 ( $wp_query->queried_object->post_author == get_current_user_id() || current_user_can( 'manage_options' ) ) &&
			 atcf_theme_supports( 'campaign-edit' )
		) {
			do_action( 'atcf_found_edit' );

			$files = apply_filters( 'atcf_crowdfunding_templates_edit', array( 'single-campaign-edit.php' ) );
		}

		/** Check if viewing a widget */
		else if ( isset ( $wp_query->query_vars[ 'widget' ] ) &&
			 is_singular( 'download' ) &&
			 atcf_theme_supports( 'campaign-widget' )
		) {
			do_action( 'atcf_found_widget' );

			$files = apply_filters( 'atcf_crowdfunding_templates_widget', array( 'campaign-widget.php' ) );
		}

		/** Check if viewing standard campaign */
		else if ( is_singular( 'download' ) ) {
			do_action( 'atcf_found_single' );

			$files = apply_filters( 'atcf_crowdfunding_templates_campaign', array( 'single-campaign.php', 'single-download.php', 'single.php' ) );
		}

		/** Check if viewing archives */
		else if ( is_post_type_archive( 'download' ) || is_tax( array( 'download_category', 'download_tag' ) ) ) {
			do_action( 'atcf_found_archive' );

			$files = apply_filters( 'atcf_crowdfunding_templates_archive', array( 'archive-campaigns.php', 'archive-download.php', 'archive.php' ) );
		}

		$files = apply_filters( 'atcf_template_loader', $files );

		foreach ( $files as $file ) {
			$find[] = $file;
			$find[] = $this->template_url . $file;
		}

		if ( ! empty( $files ) ) {
			$template = locate_template( $find );

			if ( ! $template )
				$template = $this->plugin_dir . 'templates/' . $file;
		}

		return $template;
	}

	/**
	 * Load scripts.
	 *
	 * @since Astoundify 1.6
	 *
	 * @param mixed $template
	 * @return string $template The path of the file to include
	 */
	public function frontend_scripts() {
		global $edd_options;

		$is_submission = is_page( $edd_options[ 'submit_page' ] ) || did_action( 'atcf_found_edit' );
		$is_campaign   = is_singular( 'download' ) || did_action( 'atcf_found_single' ) || apply_filters( 'atcf_is_campaign_page', false );

		if ( ! ( $is_submission || $is_campaign ) )
			return;

		if ( $is_campaign ) {
			wp_enqueue_script( 'formatCurrency', $this->plugin_url . 'assets/js/jquery.formatCurrency-1.4.0.pack.js', array( 'jquery' ) );
		}

		wp_enqueue_script( 'atcf-scripts', $this->plugin_url . 'assets/js/crowdfunding.js', array( 'jquery' ) );

		$settings = array(
			'pages' => array(
				'is_submission' => $is_submission,
				'is_campaign'   => $is_campaign
			)
		);

		if ( $is_submission ) {
			$settings[ 'submit' ] = array(
				array(
					'i18n' => array(
						'oneReward' => __( 'At least one reward is required.', 'atcf' )
					)
				)
			);
		}

		if ( $is_campaign ) {
			global $post;

			$campaign = atcf_get_campaign( $post );

			$settings[ 'campaign' ] = array(
				'i18n'        => array(),
				'isDonations' => $campaign->is_donations_only(),
				'currency'    => array(
					'thousands' => $edd_options[ 'thousands_separator' ],
					'decimal'   => $edd_options[ 'decimal_separator' ],
					'symbol'    => edd_currency_filter( '' )
				)
			);
		}

		wp_localize_script( 'atcf-scripts', 'atcfSettings', $settings );
	}

	/**
	 * Loads the plugin language files
	 *
	 * @since Astoundify Crowdfunding 0.1-alpha
	 */
	public function load_textdomain() {
		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/' . $this->domain . '/' . $mofile;

		// Look in global /wp-content/languages/atcf folder
		load_textdomain( $this->domain, $mofile_global );

		// Look in local /wp-content/plugins/appthemer-crowdfunding/languages/ folder
		load_textdomain( $this->domain, $mofile_local );
	}

	public function __destruct() {

	}
}

/**
 * The main function responsible for returning the one true Crowd Funding Instance
 * to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $crowdfunding = crowdfunding(); ?>
 *
 * @since Astoundify Crowdfunding 0.1-alpha
 *
 * @return The one true Crowd Funding Instance
 */
function crowdfunding() {
	return ATCF_CrowdFunding::instance();
}
add_action( 'plugins_loaded', 'crowdfunding' );

/**
 * Activation
 *
 * A bit ghetto, but it's a way to get around a few quirks.
 * We need to wait for other plugins to be loaded for the majority of things
 * but the activation hook can't run then. So we need to fire this off
 * right away.
 *
 * @since Astoundify Crowdfunding 1.7.3.1
 */
function atcf_install() {
	$file         = __FILE__;
	$plugin_dir   = apply_filters( 'atcf_plugin_dir_path',  plugin_dir_path( $file ) );
	$includes_dir = apply_filters( 'atcf_includes_dir', trailingslashit( $plugin_dir . 'includes'  ) );

	require_once( $includes_dir . 'class-roles.php' );
	require_once( $includes_dir . 'class-install.php' );
	register_activation_hook( $file, array( 'ATCF_Install', 'init' ), 10 );
}

atcf_install();