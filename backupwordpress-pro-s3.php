<?php
/*
Plugin Name: BackUpWordPress To Amazon S3
Plugin URI: https://bwp.hmn.md/downloads/backupwordpress-to-amazon-s3/
Description: Send your backups to your Amazon S3 account
Author: Human Made Limited
Version: 2.1.3
Author URI: https://bwp.hmn.md/
License: GPLv2
Network: true
Text Domain: backupwordpress
Domain Path: /languages
*/

/*
Copyright 2013-2014 Human Made Limited  (email : support@hmn.md)

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

require_once __DIR__ . '/vendor/autoload.php';

use Pimple\Container;

$container = new Container();

require __DIR__ . '/inc/Config.php';
require __DIR__ . '/inc/Services.php';

$addon = $container['addon'];

register_activation_hook( __FILE__, array( $addon, 'maybe_self_deactivate' ) );
register_deactivation_hook( __FILE__, array( $addon, 'deactivate' ) );

$admin = $container['admin'];

add_action( 'backupwordpress_loaded', function() {

	require_once __DIR__ . '/inc/S3BackUpService.php';
});
