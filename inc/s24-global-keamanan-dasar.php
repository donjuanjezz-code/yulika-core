<?php
// Dari Code Snippets #24 "Keamanan: XML-RPC off, editor off, sembunyikan username (Yulika)" — scope: global
/* ===== Yulika: pengamanan dasar — XML-RPC off, editor file off, cegah pencarian username ===== */

/* XML-RPC (sering dipakai untuk brute-force login) dimatikan */
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', function () {
	return array();
} );
add_filter( 'wp_headers', function ( $h ) {
	unset( $h['X-Pingback'] );
	return $h;
} );

/* Editor tema/plugin di wp-admin dimatikan (mencegah penyusup menanam kode lewat dashboard) */
add_filter( 'map_meta_cap', function ( $caps, $cap ) {
	if ( in_array( $cap, array( 'edit_themes', 'edit_plugins', 'edit_files' ), true ) ) {
		return array( 'do_not_allow' );
	}
	return $caps;
}, 10, 2 );

/* Username admin tidak bisa dilihat publik (REST /wp/v2/users dan ?author=1) */
add_filter( 'rest_endpoints', function ( $e ) {
	if ( ! is_user_logged_in() ) {
		foreach ( array_keys( $e ) as $k ) {
			if ( 0 === strpos( $k, '/wp/v2/users' ) ) {
				unset( $e[ $k ] );
			}
		}
	}
	return $e;
} );
add_action( 'template_redirect', function () {
	if ( ! is_user_logged_in() && ( isset( $_GET['author'] ) || is_author() ) ) { // phpcs:ignore
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}, 0 );
