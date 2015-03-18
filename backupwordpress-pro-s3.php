<?php
/*
Plugin Name: BackUpWordPress To Amazon S3
Plugin URI: https://bwp.hmn.md/downloads/backupwordpress-to-amazon-s3/
Description: Send your backups to your Amazon S3 account
Author: Human Made Limited
Version: 2.0.4
Author URI: https://bwp.hmn.md/
License: GPLv2
Network: true
Text Domain: backupwordpress
Domain Path: /languages
*/

/*
Copyright 2013-2014 Human Made Limited  (email : support@hmn.md)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace HM\BackUpWordPressS3;

use HM\BackUpWordPress;

register_activation_hook( __FILE__, array( 'HM\BackUpWordPressS3\Plugin', 'on_activation' ) );

register_deactivation_hook( __FILE__, array( 'HM\BackUpWordPressS3\Plugin', 'on_deactivation' ) );

/**
 * Class Plugin
 * @package HM\BackUpWordPressS3
 */
class Plugin {

	/**
	 * The plugin version number.
	 */
	const PLUGIN_VERSION = '2.0.4';

	/**
	 * Minimum version of BackUpWordPress compatibility.
	 */
	const MIN_BWP_VERSION = '3.1.4';

	/**
	 * URL for the updater to ping for a new version.
	 */
	const EDD_STORE_URL = 'https://bwp.hmn.md';

	/**
	 * File name for EDD updates to check against for updates.
	 */
	const EDD_DOWNLOAD_FILE_NAME = 'BackUpWordPress To Amazon S3';

	/**
	 * Required by EDD licensing plugin API.
	 */
	const EDD_PLUGIN_AUTHOR = 'Human Made Limited';

	/**
	 * @var Plugin The instance of this class.
	 */
	private static $instance;

	protected $admin;

	/**
	 * Instantiates a new Plugin object
	 */
	private function __construct() {

		add_action( 'backupwordpress_loaded', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'maybe_self_deactivate' ) );

	}

	/**
	 * @return Plugin
	 */
	public static function get_instance() {

		if ( ! ( self::$instance instanceof Plugin ) ) {
			self::$instance = new Plugin();
		}

		return self::$instance;
	}

	/**
	 * Fires on plugin activation. Checks plugin requirements, and interrupts activation if not met.
	 */
	public static function on_activation() {}

	/**
	 * Performs a cleanup on deactivation.
	 */
	public static function on_deactivation() {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		delete_option( 'hmbkpp_aws_settings' );
		delete_transient( 'hmbkp_license_data_s3' );
	}

	/**
	 * PLugin setup routine.
	 */
	public function init() {

		if ( ! defined( 'HMBKP_S3_BASENAME' ) ) {
			define( 'HMBKP_S3_BASENAME', plugin_basename( __FILE__ ) );
		}

		$this->includes();

	}

	/**
	 * Self deactivate ourself if incompatibility found.
	 */
	public function maybe_self_deactivate() {

		if ( $this->meets_requirements() ) {
			return;
		}

		deactivate_plugins( plugin_basename( __FILE__ ) );
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

	}

	/**
	 * Include required scripts and classes.
	 */
	protected function includes() {

		if ( ! class_exists( '\HMBKPP_SL_Plugin_Updater' ) ) {
			include( plugin_dir_path( __FILE__ ) . 'assets/edd-plugin-updater/HMBKPP-SL-Plugin-Updater.php' );
		}

		require_once plugin_dir_path( __FILE__ ) . 'inc/class-transfer.php';

	}

	/**
	 * Displays a user friendly message in the WordPress admin.
	 */
	public function display_admin_notices() {

		echo '<div class="error"><p>' . esc_html( self::get_notice_message() ) . '</p></div>';

	}

	/**
	 * Returns a localized user friendly error message.
	 *
	 * @return string
	 */
	public function get_notice_message() {

		return sprintf(
			$this->notice,
			self::EDD_DOWNLOAD_FILE_NAME,
			self::MIN_BWP_VERSION
		);
	}

	/**
	 * Check if current WordPress install meets necessary requirements.
	 *
	 * @return bool True is passes checks, false otherwise.
	 */
	public function meets_requirements() {

		if ( ! class_exists( 'HM\BackUpWordPress\Plugin' ) ) {
			$this->notice = __( '%1$s requires BackUpWordPress version %2$s. Please install or update it first.', 'backupwordpress' );
			return false;
		}

		$bwp = BackUpWordPress\Plugin::get_instance();

		if ( version_compare( BackUpWordPress\Plugin::PLUGIN_VERSION, self::MIN_BWP_VERSION, '<' ) ) {
			$this->notice = __( '%1$s requires BackUpWordPress version %2$s. Please install or update it first.', 'backupwordpress' );
			return false;
		}

		return true;
	}

}
Plugin::get_instance();

if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'admin/admin.php';
	Check_License::get_instance();
}
