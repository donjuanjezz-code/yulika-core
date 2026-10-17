<?php
// Komentar & pingback dimatikan di seluruh situs (17 Okt 2026): mencegah spam komentar berisi link.
// Pertanyaan pelanggan diarahkan ke WhatsApp. Hapus file ini untuk mengaktifkan komentar lagi.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'comments_open', '__return_false', 99 );
add_filter( 'pings_open', '__return_false', 99 );
add_filter( 'comments_array', '__return_empty_array', 99 );
add_filter( 'get_comments_number', '__return_zero', 99 );
add_filter( 'pre_option_default_comment_status', function () { return 'closed'; } );
add_filter( 'pre_option_default_ping_status', function () { return 'closed'; } );

/* Tolak kiriman komentar langsung ke wp-comments-post.php. */
add_action( 'pre_comment_on_post', function () {
	wp_die( 'Comments are closed.', '', array( 'response' => 403 ) );
} );

/* Matikan pingback XML-RPC + header X-Pingback. */
add_filter( 'xmlrpc_methods', function ( $m ) {
	unset( $m['pingback.ping'], $m['pingback.extensions.getPingbacks'] );
	return $m;
} );
add_filter( 'wp_headers', function ( $h ) {
	unset( $h['X-Pingback'] );
	return $h;
} );

/* Komentar REST API ditutup untuk publik. */
add_filter( 'rest_endpoints', function ( $e ) {
	if ( ! current_user_can( 'moderate_comments' ) ) {
		unset( $e['/wp/v2/comments'], $e['/wp/v2/comments/(?P<id>[\d]+)'] );
	}
	return $e;
} );

/* Admin: sembunyikan menu Comments & ikon di admin bar. */
add_action( 'admin_menu', function () {
	remove_menu_page( 'edit-comments.php' );
}, 99 );
add_action( 'admin_bar_menu', function ( $bar ) {
	$bar->remove_node( 'comments' );
}, 99 );
add_action( 'init', function () {
	foreach ( get_post_types() as $pt ) {
		if ( post_type_supports( $pt, 'comments' ) ) {
			remove_post_type_support( $pt, 'comments' );
			remove_post_type_support( $pt, 'trackbacks' );
		}
	}
}, 99 );
