<?php
use HM\BackUpWordPress;

/**
 * Register the EDD license settings settings
 *
 * @return null
 */
function hmbkpp_aws_setup_admin() {
	register_setting( 'hmbkpp-aws-settings', 'hmbkpp_aws_settings' );
}
add_action( 'admin_menu', 'hmbkpp_aws_setup_admin' );

/**
 * Add License Key form
 * Only shown if no License Key
 *
 * @return null
 */
function hmbkpp_aws_display_license_form() { ?>

	<div id="hmbkpp-aws-message" class="updated">

		<form method="post" action="options.php">

			<p>

				<?php
				printf(
					__( '%1$sLooks like you\'ve just installed BackUpWordPress to S3 or your license has expired.%2$s, %3$senter a valid license key to continue%4$s', 'backupwordpress' ),
					'<strong>',
					'</strong>',
					'<label style="vertical-align: baseline;" for="hmbkpp_aws_license_key">',
					'</label>'
				);
				?>

				<input type="text" style="margin-left: 5px; margin-right: 5px; " class="code regular-text" id="hmbkpp_aws_license_key" name="hmbkpp_aws_settings[license_key]" />

				<input type="hidden" name="hmbkpp_aws_settings[hmbkpp-aws-settings-updated]" value="1" />

				<input class="button-primary" type="submit" value="<?php _e( 'Save License Key', 'backupwordpress' ); ?>" />

			</p>

			<p>

				<?php
				printf(
					__( '%1$sDon\'t have a BackUpWordPress to Amazon S3 License Key?%2$s %3$sPurchase one now%4$s and then report back here', 'backupwordpress' ),
					'<strong>',
					'</strong>',
					'<a href="' . esc_url( 'https://bwp.hmn.md' ) . '" target="_blank">',
					'</a>'
				);
				?>

			</p>

			<style>#message {
					display: none;
				}</style>

			<?php settings_fields( 'hmbkpp-aws-settings' );

			// Output any sections defined for page sl-settings
			do_settings_sections( 'hmbkpp-aws-settings' ); ?>

		</form>

	</div>

<?php }

function hmbkpp_aws_license_validity_notice( $license_status ) { ?>

	<div id="hmbkpp-aws-message" class="updated">
		<p>
			<?php
			if ( 'valid' == $license_status ) {
				printf(
					__( '%1$sBackUpWordPress to Amazon S3 License Key successfully added%2$s, go back to %3$sthe backups admin page%4$s' , 'backupwordpress' ),
					'<strong>',
					'</strong>',
					'<a href="' . esc_attr( HMBKP_ADMIN_URL ) . '">',
					'</a>'
				);
			} else {
				delete_option( 'hmbkpp_aws_settings' );
				deactivate_plugins( 'backupwordpress-pro-s3/backupwordpress-pro-s3.php' );
				printf(
					__( '%1$sBackUpWordPress to Amazon S3 License Key is invalid%2$s, plugin will be deactivated' , 'backupwordpress' ),
					'<strong>',
					'</strong>'
				);
			}
			?>
		</p>
	</div>

<?php }

function hmbkpp_aws_add_api_key_admin_notice() {

	$plugin = \HM\BackUpWordPressS3\Plugin::get_instance();
	$settings = $plugin->fetch_settings();

	if ( 'valid' == $settings['license_status'] )
		return;

	// new license key entered, form submitted
	if ( isset( $_GET['settings-updated'] ) && isset( $settings['hmbkpp-aws-settings-updated'] ) ) {

		// We got this far so first reset the form submission flag
		unset( $settings['hmbkpp-aws-settings-updated'] );
		update_option( 'hmbkpp_aws_settings', $settings );

		// then we can activate the license
		hmbkpp_aws_activate_license();

		// Settings have changed
		$plugin = \HM\BackUpWordPressS3\Plugin::get_instance();
		$settings = $plugin->fetch_settings();

		hmbkpp_aws_license_validity_notice( $settings['license_status'] );

	} else {
		hmbkpp_aws_display_license_form();
	}

}
add_action( 'admin_notices', 'hmbkpp_aws_add_api_key_admin_notice' );

/************************************
 * this illustrates how to activate
 * a license key
 *************************************/

function hmbkpp_aws_activate_license() {

	// retrieve the license from the database
	$plugin = \HM\BackUpWordPressS3\Plugin::get_instance();
	$settings = $plugin->fetch_settings();
	$license = $settings['license_key'];

	// data to send in our API request
	$api_params = array(
		'edd_action'=> 'activate_license',
		'license' 	=> $license,
		'item_name' => urlencode( \HM\BackUpWordPressS3\Plugin::EDD_DOWNLOAD_FILE_NAME ) // the name of our product in EDD
	);

	// Call the custom API.
	$response = wp_remote_get( add_query_arg( $api_params, \HM\BackUpWordPressS3\Plugin::EDD_STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

	// make sure the response came back okay
	if ( is_wp_error( $response ) || ( 200 !== wp_remote_retrieve_response_code( $response ) ) ) {
		return false;
	}


	// decode the license data
	$license_data = json_decode( wp_remote_retrieve_body( $response ) );

	// $license_data->license will be either "active" or "inactive"
	$settings['license_status'] = $license_data->license;
	update_option( 'hmbkpp_aws_settings', $settings );

}
//add_action('admin_init', 'hmbkpp_aws_activate_license');

/***********************************************
 * Illustrates how to deactivate a license key.
 * This will descrease the site count
 ***********************************************/

function hmbkpp_aws_deactivate_license() {

	// retrieve the license from the database
	$plugin = \HM\BackUpWordPressS3\Plugin::get_instance();
	$settings = $plugin->fetch_settings();
	$license = $settings['license_key'];

	// data to send in our API request
	$api_params = array(
		'edd_action'=> 'deactivate_license',
		'license' 	=> $license,
		'item_name' => urlencode( \HM\BackUpWordPressS3\Plugin::EDD_DOWNLOAD_FILE_NAME ) // the name of our product in EDD
	);

	// Call the custom API.
	$response = wp_remote_get( add_query_arg( $api_params, \HM\BackUpWordPressS3\Plugin::EDD_STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

	// make sure the response came back okay
	if ( is_wp_error( $response ) )
		return false;

	// decode the license data
	$license_data = json_decode( wp_remote_retrieve_body( $response ) );

	$plugin = \HM\BackUpWordPressS3\Plugin::get_instance();
	$settings = $plugin->fetch_settings();

	// $license_data->license will be either "deactivated" or "failed"
	if ( $license_data->license == 'deactivated' ) {
		unset( $settings['license_status'] );
		update_option( 'hmbkpp_aws_settings', $settings );
	}

}
//add_action('admin_init', 'hmbkpp_aws_deactivate_license');

/************************************
 * this illustrates how to check if
 * a license key is still valid
 * the updater does this for you,
 * so this is only needed if you
 * want to do something custom
 *************************************/

function hmbkpp_aws_check_license() {

	global $wp_version;

	$plugin = \HM\BackUpWordPressS3\Plugin::get_instance();
	$settings = $plugin->fetch_settings();
	$license = $settings['license_key'];

	if ( empty( $license ) ) {
		return;
	}

	$license_data = get_transient( 'hmbkp_license_data_s3' );

	if ( false === $license_data ) {

		$api_params = array(
			'edd_action' => 'check_license',
			'license' => $license,
			'item_name' => urlencode( \HM\BackUpWordPressS3\Plugin::EDD_DOWNLOAD_FILE_NAME )
		);

		// Call the custom API.
		$response = wp_remote_get( add_query_arg( $api_params, \HM\BackUpWordPressS3\Plugin::EDD_STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

		if ( is_wp_error( $response ) )
			return false;

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		set_transient( 'hmbkp_license_data_s3', $license_data, DAY_IN_SECONDS );
	}

	$valid = ( 'valid' === $license_data->license );
	$expired = hmbkp_has_license_expired( $license_data->expires );

	if ( $valid && ! $expired ) {
		return true;
		// this license is still valid
	} else {
		return false;
		// this license is no longer valid
	}
}

/**
 * Check the license expiry date against current date.
 *
 * @param $expiry
 *
 * @return bool True if expired, False otherwise.
 */
if ( ! function_exists( 'hmbkp_has_license_expired' ) ) {

	function hmbkp_has_license_expired( $expiry ) {

		$expiry_date = strtotime( $expiry );
		$now = strtotime( 'now' );

		return $expiry_date < $now;
	}
}
