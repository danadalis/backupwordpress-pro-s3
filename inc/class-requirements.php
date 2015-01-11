<?php
defined( 'WPINC' ) or die;

/**
 * Class HMBKP_Requirement_Define_HMBKPP_AWS_LICENSE_STATUS
 */
class HMBKP_Requirement_Define_HMBKPP_AWS_LICENSE_STATUS extends HMBKP_Requirement {

	var $name = 'License Status';

	/**
	 * @return string
	 */
	protected function test() {

		$plugin = BackUpWordPress_S3::get_instance();
		$settings = $plugin->fetch_settings();

		return ( isset( $settings['license_status'] ) && 'valid' === $settings['license_status'] ) ? 'License is valid' : 'License is invalid or not set';

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_Define_HMBKPP_AWS_LICENSE_STATUS', 'amazon' );
