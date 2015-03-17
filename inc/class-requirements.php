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

		$status = '';

		if ( $admin->is_license_invalid() || $admin->is_license_expired() ) {
			$status = __( 'License is invalid or expired', 'backupwordpress' );
		}

		return $status;
	}

}

BackUpWordPress\Requirements::register( 'HM\BackUpWordPressS3\Define_License_Status', 'amazon' );
