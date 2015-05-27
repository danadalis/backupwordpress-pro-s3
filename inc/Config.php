<?php

$container['addon_class'] = 'HM\\BackUpWordPress\\Addon';
$container['checklicense_class'] = 'HM\\BackUpWordPress\\CheckLicense';
$container['addon_version'] = '2.1.2';
$container['min_bwp_version'] = '3.1.4';
$container['edd_download_file_name'] = 'BackUpWordPress To Amazon S3';
$container['addon_settings'] = 'hmbkpp_aws_settings';
$container['service_class'] = 'S3BackUpService';
$container['updater_class'] = 'HM\\BackUpWordPress\\PluginUpdater';
$container['prefix'] = 'aws';
$container['plugin_name'] = 's3';