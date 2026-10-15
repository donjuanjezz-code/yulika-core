<?php
// Perbaikan kecil hasil audit 15 Okt 2026 (bukan dari Code Snippets, selalu dimuat).
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* 1) Matikan script emoji WordPress: emoji tampil native (tidak lagi gambar dari s.w.org yang bisa gagal dimuat), 1 script + 1 domain lebih sedikit. */
add_action( 'init', function () {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	remove_action( 'admin_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'emoji_svg_url', '__return_false' );
} );
add_filter( 'wp_resource_hints', function ( $urls, $type ) {
	if ( 'dns-prefetch' === $type ) {
		$urls = array_values( array_filter( (array) $urls, function ( $u ) {
			return false === strpos( is_array( $u ) ? (string) ( $u['href'] ?? '' ) : (string) $u, 's.w.org' );
		} ) );
	}
	return $urls;
}, 10, 2 );

/* 2) Tampilan */
add_action( 'wp_head', function () {
	if ( is_admin() ) {
		return;
	}
	echo '<style id="yk-tweaks">'
		/* HP kecil: tombol Akun jadi ikon saja supaya tidak menabrak logo */
		. '@media(max-width:430px){.yk-acct span{display:none}.yk-acct{padding:7px}}'
		/* Checkout: bar Favorit tidak menutupi jadwal & tombol Place Order */
		. 'body.woocommerce-checkout .yk-favbar,body.woocommerce-cart .yk-favbar{display:none!important}'
		/* Tombol Place Order warna brand (sebelumnya tertimpa hitam oleh tema) */
		. 'html body.woocommerce-checkout #payment #place_order,html body.woocommerce-checkout button#place_order{background:#8a5a2b!important;border-color:#8a5a2b!important;color:#fff!important}'
		. 'html body.woocommerce-checkout #payment #place_order:hover{background:#74491f!important;border-color:#74491f!important}'
		. '</style>' . "\n";
}, 120 );
