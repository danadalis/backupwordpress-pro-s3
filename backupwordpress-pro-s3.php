<?php
/*
Plugin Name: BackUpWordPress To Amazon S3
Plugin URI: https://bwp.hmn.md/downloads/backupwordpress-to-amazon-s3/
Description: Send your backups to your Amazon S3 account
Author: Human Made Limited
Version: 1.0.9.1
Author URI: https://bwp.hmn.md/
license: GPLv2
*/

/*
Copyright 2013 Human Made Limited  (email : support@hmn.md)

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

defined( 'WPINC' ) or die;


if ( ! defined( 'HMBKP_S3_REQUIRED_PHP_VERSION' ) ) {
	define( 'HMBKP_S3_REQUIRED_PHP_VERSION', '5.3.1' );
}

// Don't activate on anything less than PHP required version
if ( version_compare( phpversion(), HMBKP_S3_REQUIRED_PHP_VERSION, '<' ) ) {
	deactivate_plugins( trailingslashit( basename( dirname( __FILE__ ) ) ) . basename( __FILE__ ) );
	wp_die( sprintf( __( 'BackUpWordPress to Amazon S3 requires PHP version %s or greater.', 'backupwordpress-pro-s3' ), HMBKP_S3_REQUIRED_PHP_VERSION ), __( 'BackUpWordPress to Amazon S3', 'backupwordpress-pro-s3' ), array( 'back_link' => true ) );
}


function hmbkp_aws_activate() {

	if ( ! defined( 'HMBKP_S3_REQUIRED_WP_VERSION' ) ) {
		define( 'HMBKP_S3_REQUIRED_WP_VERSION', '3.8.4' );
	}


	if ( ! class_exists( 'HMBKP_Scheduled_Backup' ) ) {

		deactivate_plugins( plugin_basename( __FILE__ ) );

		wp_die( __( 'BackUpWordPress To Amazon S3 requires BackUpWordPress to be activated. It has been deactivated.', 'backupwordpress-pro-gdrive' ), 'BackUpWordPress to Google Drive', array( 'back_link' => true ) );

	}

		// Don't activate on old versions of WordPress
	global $wp_version;

	if ( version_compare( $wp_version, HMBKP_S3_REQUIRED_WP_VERSION, '<' ) ) {
		deactivate_plugins( trailingslashit( basename( dirname( __FILE__ ) ) ) . basename( __FILE__ ) );
		wp_die( sprintf( __( 'BackUpWordPress to Amazon S3 requires WordPress version %s or greater.', 'backupwordpress-pro-s3' ), HMBKP_S3_REQUIRED_WP_VERSION ), __( 'BackUpWordPress to Amazon S3', 'backupwordpress-pro-s3' ), array( 'back_link' => true ) );

	}

}

register_activation_hook( __FILE__, 'hmbkp_aws_activate' );


function hmbkpp_aws_init() {


	if ( ! class_exists( 'HMBKP_Scheduled_Backup' ) ) {

		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( __( 'BackUpWordPress To Amazon S3 requires BackUpWordPress to be activated. It has been deactivated.', 'backupwordpress-pro-gdrive' ), 'BackUpWordPress to Google Drive', array( 'back_link' => true ) );

	}

	if ( ! defined( 'HMBKP_S3_REQUIRED_WP_VERSION' ) ) {
		define( 'HMBKP_S3_REQUIRED_WP_VERSION', '3.8.4' );
	}

	// Don't activate on old versions of WordPress
	global $wp_version;

	if ( version_compare( $wp_version, HMBKP_S3_REQUIRED_WP_VERSION, '<' ) ) {
		deactivate_plugins( trailingslashit( basename( dirname( __FILE__ ) ) ) . basename( __FILE__ ) );
		wp_die( sprintf( __( 'BackUpWordPress to Amazon S3 requires WordPress version %s or greater.', 'backupwordpress-pro-s3' ), HMBKP_S3_REQUIRED_WP_VERSION ), __( 'BackUpWordPress to Amazon S3', 'backupwordpress-pro-s3' ), array( 'back_link' => true ) );

	}

}
add_action( 'admin_init', 'hmbkpp_aws_init' );


/**
 * Set up plugin, load dependencies, modules and add hooks
 */
function hmbkp_aws_plugin_setup() {

	if ( ! defined( 'HMBKP_S3_PLUGIN_SLUG' ) ) {
		define( 'HMBKP_S3_PLUGIN_SLUG', plugin_basename( dirname( __FILE__ ) ) );
	}

	if ( ! defined( 'HMBKP_S3_PLUGIN_PATH' ) ) {
		define( 'HMBKP_S3_PLUGIN_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
	}

	if ( ! defined( 'HMBKP_S3_PLUGIN_URL' ) ) {
		define( 'HMBKP_S3_PLUGIN_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
	}

	// Set filter for plugin's languages directory
	if ( ! defined( 'HMBKP_S3_PLUGIN_LANG_DIR' ) ) {
		define( 'HMBKP_S3_PLUGIN_LANG_DIR', apply_filters( 'hmbkp_aws_filter_lang_dir',
			HMBKP_S3_PLUGIN_PATH . '/languages/' ) );
	}

	// this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
	define( 'HMBKPP_AWS_STORE_URL', 'https://bwp.hmn.md' ); // you should use your own CONSTANT name, and be sure to replace it throughout this file

	// the name of your product. This should match the download name in EDD exactly
	define( 'HMBKPP_AWS_ADDON_NAME', 'BackUpWordPress To Amazon S3' ); // you should use your own CONSTANT name, and be sure to replace it throughout this file

	if ( ! defined( 'HMBKP_S3_PLUGIN_VERSION' ) ) {
		define( 'HMBKP_S3_PLUGIN_VERSION', '1.0.9.1' );
	}

	if ( ! class_exists( 'HMBKPP_SL_Plugin_Updater' ) ) {
		include( trailingslashit( dirname( __FILE__ ) ) . 'assets/edd-plugin-updater/HMBKPP-SL-Plugin-Updater.php' );
	}

	// retrieve our license key from the DB
	$settings = hmbkpp_aws_fetch_settings();

	$license_key = $settings['license_key'];

	// setup the updater
	$edd_updater = new HMBKPP_SL_Plugin_Updater( HMBKPP_AWS_STORE_URL, __FILE__, array(
			'version'   => HMBKP_S3_PLUGIN_VERSION,                // current version number
			'license'   => $license_key,        // license key (used get_option above to retrieve from DB)
			'item_name' => HMBKPP_AWS_ADDON_NAME,    // name of this plugin
			'author'    => 'Human Made Limited',  // author of this plugin
		)
	);

	// loads the translation files
	hmbkp_aws_plugin_textdomain();

	if ( is_admin() ) {
		hmbkpp_aws_admin();
	}

	if ( class_exists( 'HMBKP_Service' ) ) {
		require_once HMBKP_S3_PLUGIN_PATH . 's3/s3.php';
	}
}
add_action( 'plugins_loaded', 'hmbkp_aws_plugin_setup' );


/**
 * Sets default plugin cap level
 * @return mixed|void
 */
function hmbkp_aws_get_manage_cap() {

	static $manage_cap;

	if ( ! empty( $manage_cap ) ) {
		return $manage_cap;
	}

	$manage_cap = apply_filters( 'hmbkp_aws_manage_cap', 'manage_options' );

	return $manage_cap;

}

/**
 * Loads the plugin text domain for translation
 * This setup allows a user to just drop his custom translation files into the WordPress language directory
 * Files will need to be in a subdirectory with the name of the textdomain 'backupwordpress-pro-s3'
 */
function hmbkp_aws_plugin_textdomain() {

	/** Set unique textdomain string */
	$textdomain = 'backupwordpress-pro-s3';

	/** The 'plugin_locale' filter is also used by default in load_plugin_textdomain() */
	$locale = apply_filters( 'plugin_locale', get_locale(), $textdomain );

	/** Set filter for WordPress languages directory */
	$hmbkp_aws_wp_lang_dir = apply_filters(
		'hmbkp_aws_filter_wp_lang_dir',
		trailingslashit( WP_LANG_DIR ) . trailingslashit( $textdomain ) . $textdomain . '-' . $locale . '.mo'
	);

	/** Translations: First, look in WordPress' "languages" folder = custom & update-secure! */
	load_textdomain( $textdomain, $hmbkp_aws_wp_lang_dir );

	/** Translations: Secondly, look in plugin's "languages" folder = default */
	load_plugin_textdomain( $textdomain, false, HMBKP_S3_PLUGIN_LANG_DIR );

}

/**
 * Include the plugin settings form
 */
function hmbkpp_aws_admin() {

	require_once HMBKP_S3_PLUGIN_PATH . 'admin/admin.php';

}

/**
 * Delete the License key on activate and deactivate
 *
 * @return null
 */
function hmbkpp_aws_deactivate() {

	delete_option( 'hmbkpp_aws_license_status' );
	delete_option( 'hmbkpp_aws_license_key' );

}
register_deactivation_hook( __FILE__, 'hmbkpp_aws_deactivate' );

/**
 * Define default settings
 *
 * @return array
 */
function hmbkpp_aws_default_settings() {

	$defaults = array(
		'license_key'    => '',
		'license_status' => '',
	);

	return $defaults;
}

/**
 * Fetch the plugin settings
 *
 * @return array
 */
function hmbkpp_aws_fetch_settings() {
	return array_merge( hmbkpp_aws_default_settings(), get_option( 'hmbkpp_aws_settings', array() ) );
}
