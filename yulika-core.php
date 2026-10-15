<?php
/**
 * Plugin Name: Yulika Core
 * Description: Fitur custom Yulika Florist (pengganti Code Snippets). Dikelola lewat GitHub repo donjuanjezz-code/yulika-core, auto-deploy via FTP.
 * Version: 0.1.0
 * Author: Yulika Florist
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'YK_CORE_VERSION', '0.1.0' );
define( 'YK_CORE_DIR', __DIR__ );
define( 'YK_CORE_URL', plugin_dir_url( __FILE__ ) );

/* Muat semua modul di folder inc/ (satu file per fitur) */
foreach ( (array) glob( YK_CORE_DIR . '/inc/*.php' ) as $yk_file ) {
	require_once $yk_file;
}
