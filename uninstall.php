<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

// delete plugin option
delete_option( 'hmbkpp_aws_settings' );
