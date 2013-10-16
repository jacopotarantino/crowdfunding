<?php
/**
 * Installation
 *
 * @since Astoundify Crowdfunding 1.6
 */

class ATCF_Install {
	/**
	 * Called on activation. Calls necessary methods, and adds
	 * a flag in the database that ATCF has been installed.
	 *
	 * @since Astoundify Crowdfunding 1.6
	 *
	 * @return void
	 */
	public static function init() {
		flush_rewrite_rules();

		update_option( 'atcf_installed', true );
		
		self::roles();
		self::cron();
	}

	/**
	 * Set up roles. Done on activation to avoid database writes every time.
	 *
	 * @since Astoundify Crowdfunding 1.6
	 *
	 * @return ATCF_Roles
	 */
	public static function roles() {
		return new ATCF_Roles;
	}

	/**
	 * Set up crons. CLear any existing scheduled hooks, and add ours.
	 *
	 * @since Astoundify Crowdfunding 1.6
	 *
	 * @return void
	 */
	public static function cron() {
		wp_clear_scheduled_hook( 'atcf_check_for_completed_campaigns' );
		wp_schedule_event( time(), 'hourly', 'atcf_check_for_completed_campaigns' );

		wp_clear_scheduled_hook( 'atcf_process_payments' );
		wp_schedule_event( time(), 'hourly', 'atcf_process_payments' );
	}
}