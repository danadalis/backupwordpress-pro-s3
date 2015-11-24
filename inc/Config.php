<?php

$container['addon_class'] = 'HM\\BackUpWordPress\\Addon';
$container['checklicense_class'] = 'HM\\BackUpWordPress\\CheckLicense';
$container['addon_version'] = '2.1.6';
$container['min_bwp_version'] = '3.1.4';
$container['edd_download_file_name'] = 'BackUpWordPress To Amazon S3';
$container['addon_settings_key'] = 'hmbkpp_aws_settings';
$container['addon_settings_defaults'] = array( 'license_key' => '', 'license_status' => '', 'license_expired' => '', 'expiry_date' => '' );
$container['service_class'] = 'S3BackUpService';
$container['updater_class'] = 'HM\\BackUpWordPress\\PluginUpdater';
$container['prefix'] = 'aws';
$container['plugin_name'] = 's3';
