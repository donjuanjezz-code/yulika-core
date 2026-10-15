<?php
/**
 * Plugin Name: Yulika Core
 * Description: Fitur custom Yulika Florist (pengganti Code Snippets). Dikelola lewat GitHub repo donjuanjezz-code/yulika-core, auto-deploy via FTP.
 * Version: 0.2.0
 * Author: Yulika Florist
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'YK_CORE_VERSION', '0.2.0' );
define( 'YK_CORE_DIR', __DIR__ );
define( 'YK_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * ID snippet Code Snippets yang masih AKTIF.
 * Modul inc/sNN-*.php hanya dimuat kalau snippet #NN sudah dimatikan,
 * jadi kode lama & baru tidak pernah jalan bersamaan (tidak ada error "function sudah ada").
 */
function yk_core_active_snippets() {
	static $ids = null;
	if ( null !== $ids ) {
		return $ids;
	}
	$ids = array();
	if ( ! defined( 'CODE_SNIPPETS_VERSION' ) && ! function_exists( 'code_snippets' ) ) {
		return $ids; // plugin Code Snippets tidak aktif = tidak ada snippet yang jalan
	}
	global $wpdb;
	$table = $wpdb->prefix . 'snippets';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
		$ids = array_map( 'intval', (array) $wpdb->get_col( "SELECT id FROM {$table} WHERE active = 1" ) ); // phpcs:ignore
	}
	return $ids;
}

/**
 * Nama file modul: inc/sNN-SCOPE-nama.php
 *   NN    = ID snippet asal (2 digit, urutan muat = urutan ID)
 *   SCOPE = global (semua) | front (hanya front-end, sama seperti scope "front-end" Code Snippets)
 * Modul tanpa awalan sNN (inc/x-*.php) = fitur baru, selalu dimuat.
 */
add_action( 'plugins_loaded', function () {
	$active = yk_core_active_snippets();
	foreach ( (array) glob( YK_CORE_DIR . '/inc/*.php' ) as $file ) {
		$base = basename( $file );
		if ( preg_match( '/^s(\d+)-(global|front)-/', $base, $m ) ) {
			if ( in_array( (int) $m[1], $active, true ) ) {
				continue;
			}
			if ( 'front' === $m[2] && is_admin() ) {
				continue;
			}
		}
		require_once $file;
	}
}, 1 );
