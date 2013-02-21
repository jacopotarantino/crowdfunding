<?php
/**
 * Plugin Name: Crowd Funding by AppThemer
 * Plugin URI:  http://appthemer.com/crowdfunding
 * Description: A crowd funding platform in the likes of Kickstarter and Indigogo
 * Author:      AppThemer
 * Author URI:  http://appthemer.com
 * Version:     0.1-alpha
 * Text Domain: atcf
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** Check if Easy Digital Downloads is active */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

/**
 * Main Crowd Funding Class
 *
 * @since v1.4
 */
final class AT_CrowdFunding {

	/**
	 *
	 */
	private static $instance;

	/**
	 * Main Crowd Funding Instance
	 *
	 * Ensures that only one instance of Crowd Funding exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since CrowdFunding 0.1-alpha
	 *
	 * @return The one true Crowd Funding
	 */
	public static function instance() {
		if ( ! isset ( self::$instance ) ) {
			self::$instance = new AT_CrowdFunding;
			self::$instance->setup_globals();
			self::$instance->includes();
			self::$instance->setup_actions();
		}

		return self::$instance;
	}

	/** Private Methods *******************************************************/

	/**
	 * Set some smart defaults to class variables. Allow some of them to be
	 * filtered to allow for early overriding.
	 *
	 * @since CrowdFunding 0.1-alpha
	 */
	private function setup_globals() {
		/** Versions **********************************************************/

		$this->version    = '0.1-alpha';
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

		$this->domain       = 'crowdfunding'; 
	}

	/**
	 * Include required files
	 *
	 * @since CrowdFunding 0.1-alpha
	 */
	private function includes() {
		require( $this->includes_dir . 'campaign.php' );
		require( $this->includes_dir . 'theme-stuff.php' );
		require( $this->includes_dir . 'shortcode-submit.php' );

		do_action( 'atcf_include_files' );

		if ( ! is_admin() )
			return;

		do_action( 'atcf_include_admin_files' );
	}

	/**
	 * Setup the default hooks and actions
	 *
	 * @since CrowdFunding 0.1-alpha
	 */
	private function setup_actions() {
		add_filter( 'template_include', array( $this, 'template_loader' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		do_action( 'atcf_setup_actions' );

		$this->load_textdomain();
	}

	/**
	 * Load a template.
	 *
	 * Handles template usage so that we can use our own templates instead of the themes.
	 *
	 * Templates are in the 'templates' folder. CrowdFunding looks for theme
	 * overides in /theme/crowdfunding/ by default
	 *
	 * @see https://github.com/woothemes/woocommerce/blob/master/woocommerce.php
	 *
	 * @access public
	 * @param mixed $template
	 * @return string
	 */
	public function template_loader( $template ) {
		global $wp_query;
		
		$find = array();
		$file = '';

		if ( isset( $wp_query->query[ 'backers' ] ) && is_singular( 'download' ) ) {
			$file   = 'single-campaign-backers.php';
		} else if ( is_single() && get_post_type() == 'download' ) {
			$file 	= 'single-campaign.php';
		} else if ( is_post_type_archive( 'download' ) ) {
			$file   = 'archive-campaigns.php';
		}

		$find[] = $file;
		$find[] = $this->template_url . $file;

		if ( $file ) {
			$template = locate_template( $find );

			if ( ! $template ) 
				$template = $this->plugin_dir . '/templates/' . $file;
		}

		return $template;
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'atcf-scripts', $this->plugin_url . '/assets/js/crowdfunding.js', array( 'jquery' ) );

		wp_localize_script( 'atcf-scripts', 'CrowdFundingL10n', array(
			'oneReward' => __( 'At least one reward is required.', 'atcf' )
		) );
	}

	/**
	 * Loads the plugin language files
	 *
	 * @since CrowdFunding 0.1-alpha
	 */
	public function load_textdomain() {
		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale',  get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/' . $this->domain . '/' . $mofile;

		// Look in global /wp-content/languages/crowdfunding folder
		if ( file_exists( $mofile_global ) ) {
			return load_textdomain( $this->domain, $mofile_global );

		// Look in local /wp-content/plugins/crowdfunding/languages/ folder
		} elseif ( file_exists( $mofile_local ) ) {
			return load_textdomain( $this->domain, $mofile_local );
		}

		// Nothing found
		return false;
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
 * @since v1.4
 *
 * @return The one true Crowd Funding Instance
 */

function crowdfunding() {
	return AT_CrowdFunding::instance();
}

crowdfunding();