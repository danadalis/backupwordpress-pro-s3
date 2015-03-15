<?php namespace HM\BackUpWordPressS3;

use HM\BackUpWordPress\Notices;

/**
 * Class Check_License
 * @package HM\BackUpWordPressS3
 */
class Check_License {

	/**
	 * @var Check_License Singleton instance.
	 */
	protected static $instance;

	/**
	 * @var string Name of this plugin.
	 */
	protected $plugin_name;

	/**
	 * @return Check_License
	 */
	public static function get_instance() {

		if ( ! ( self::$instance instanceof Check_License ) ) {
			self::$instance = new Check_License();
		}

		return self::$instance;
	}

	/**
	 * Instantiate a new object.
	 */
	private function __construct() {

		add_action( 'backupwordpress_loaded', array( $this, 'plugins_loaded' ) );

		$this->plugin_name = 'BackUpWordPress to S3';

	}

	/**
	 * Runs on the plugins_loaded hook and hooks into WordPress.
	 */
	public function plugins_loaded() {

		if ( false === $this->validate_key() ) {

			add_action( 'admin_notices', array( $this, 'display_license_form' ) );

		}

		add_action( 'admin_post_hmbkp_license_key_submit_action', array( $this, 'license_key_submit' ) );

	}

	/**
	 * Check whether the provided license key is valid.
	 *
	 * @return bool
	 */
	protected function validate_key() {

		$settings = $this->fetch_settings();

		if ( empty( $settings['license_key'] ) ) {
			return false;
		}

		$license_data = $this->fetch_license_data();

		$notices = array();

		if ( $this->is_license_expired( $license_data->expires ) ) {
			$notices[] = sprintf( __( 'Your %s license has expired, renew it now to continue to receive updates and support. Thanks!', 'backupwordpress' ), $this->plugin_name );
		}

		if ( ! $this->is_license_valid( $license_data->license ) ) {
			$notices[] = sprintf( __( 'Your %s license is invalid, please double check it now to continue to receive updates and support. Thanks!', 'backupwordpress' ), $this->plugin_name );
		}

		if ( ! $this->is_license_allowed_for_domain( $license_data->license ) ) {
			$notices[] = __( 'Please contact support to enable the license on this domain.', 'backupwordpress' );
		}

		if ( ! empty( $notices ) ) {
			Notices::get_instance()->set_notices( 'license_check', $notices, false );
			return false;
		}

		return true;

	}

	/**
	 * Checks whether the license key has expired.
	 *
	 * @param $expiry
	 *
	 * @return bool
	 */
	protected function is_license_expired( $expiry ) {

		$expiry_date = strtotime( $expiry );
		$now = strtotime( 'now' );

		return $expiry_date < $now;
	}

	/**
	 * Checks whether the license key is valid.
	 *
	 * @param $license_status
	 *
	 * @return bool
	 */
	protected function is_license_valid( $license_status ) {

		return ( 'valid' === $license_status );

	}

	protected function is_license_allowed_for_domain( $license_status ) {

		return ( 'site_inactive' === $license_status );
	}

	/**
	 * Fetches the plugin's license data either from the cache or from the EDD API.
	 *
	 * @return array|bool|mixed
	 */
	protected function fetch_license_data() {

		$settings = $this->fetch_settings();

		$license_data = get_transient( 'hmbkp_aws_license_data' );

		if ( false === $license_data ) {

			$api_params = array(
				'edd_action' => 'check_license',
				'license' => $settings['license_key'],
				'item_name' => urlencode( \HM\BackUpWordPressS3\Plugin::EDD_DOWNLOAD_FILE_NAME )
			);

			// Call the custom API.
			$response = wp_remote_get( add_query_arg( $api_params, \HM\BackUpWordPressS3\Plugin::EDD_STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( $this->is_license_valid( $license_data->license ) ) {
				set_transient( 'hmbkp_aws_license_data', $license_data, DAY_IN_SECONDS );
			}

		}

		return $license_data;

	}

	/**
	 * Fetch the settings from the database.
	 *
	 * @return mixed|void
	 */
	public function fetch_settings() {
		return get_option( 'hmbkpp_aws_settings', array( 'license_key' => '' ) );
	}

	/**
	 * Save the settings to the database.
	 *
	 * @param $data
	 *
	 * @return bool
	 */
	protected function update_settings( $data ) {
		return update_option( 'hmbkpp_aws_settings', $data );
	}

	/**
	 * Display a form in the dashboard so the user can provide their license key.
	 *
	 */
	public function display_license_form() {

		$current_screen = get_current_screen();

		if ( is_null( $current_screen ) ) {
			return;
		}

		if ( ! defined( 'HMBKP_ADMIN_PAGE' ) ) {
			return;
		}

		if ( $current_screen->id !== HMBKP_ADMIN_PAGE ) {
			return;
		}

		$notices = Notices::get_instance()->get_notices();

		if ( ! empty( $notices['license_check'] ) ) : ?>

		<div class="error">

			<?php foreach ( $notices['license_check'] as $msg ) : ?>
				<p><?php echo esc_html( $msg ); ?></p>
			<?php endforeach; ?>

		</div>

		<?php endif; ?>

		<div class="updated">

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

			<p>
				<label style="vertical-align: baseline;" for="license_key"><?php printf( __( '%1$s%2$s is almost ready.%3$s Enter your license key to get updates and support.', 'backupwordpress' ), '<strong>', $this->plugin_name, '</strong>' ); ?></label>
				<input id="license_key" class="code regular-text" name="license_key" type="text" value="" />

			</p>

			<input type="hidden" name="action" value="hmbkp_license_key_submit_action" />

			<?php wp_nonce_field( 'hmbkp_license_key_submit_action', 'hmbkp_license_key_submit_nonce' ); ?>

			<?php submit_button( __( 'Save license key', 'backupwordpress' ) ); ?>

			</form>

		</div>

	<?php }

	/**
	 * Handles the license key form submission. Saves the license key.
	 */
	public function license_key_submit() {

		check_admin_referer( 'hmbkp_license_key_submit_action', 'hmbkp_license_key_submit_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_safe_redirect( wp_get_referer() );
			die;
		}

		if ( empty( $_POST['license_key'] ) ) {
			wp_safe_redirect( wp_get_referer() );
			die;
		}
		$key = sanitize_text_field( $_POST['license_key'] );

		$data = $this->fetch_settings();
		$data['license_key'] = $key;
		$this->update_settings( $data );

		wp_safe_redirect( wp_get_referer() );
		die;
	}
}
