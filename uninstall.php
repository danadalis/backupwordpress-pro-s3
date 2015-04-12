<?php

if ( ! current_user_can( 'activate_plugins' ) ) {
	exit;
}
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// delete plugin option
delete_option( 'hmbkpp_aws_settings' );
delete_transient( 'hmbkp_license_data_s3' );
delete_transient( 'hmbkp_aws_license_data' );
