<?php
namespace HM\BackUpWordPressS3;

use HM\BackUpWordPress;

/**
 * Class Define_License_Status
 */
class Define_License_Status extends BackUpWordPress\Requirement {

	var $name = 'License Status';

	/**
	 * @return string
	 */
	protected function test() {

		$admin = Check_License::get_instance();
		$settings = $admin->fetch_settings();

		return ( isset( $settings['license_status'] ) && 'valid' === $settings['license_status'] ) ? 'License is valid' : 'License is invalid or not set';

	}

}

BackUpWordPress\Requirements::register( 'HM\BackUpWordPressS3\Define_License_Status', 'amazon' );
