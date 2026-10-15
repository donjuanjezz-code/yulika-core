<?php
// Dari Code Snippets #16 "Yulika: Landing lebih rapat + header cache HTML" — scope: front-end
/* ===== Yulika: landing page lebih rapat + HTML tidak disimpan 7 hari di browser ===== */

add_action( 'wp_head', function () {
	if ( ! is_front_page() ) {
		return;
	}
	echo '<style id="yk-home-tight">
.elementor-11 .elementor-element-d49f0b8,.elementor-11 .elementor-element-0a310aa,.elementor-11 .elementor-element-b08721f{display:none!important}
.elementor-11 .elementor-element-b58e694>.elementor-container{min-height:540px!important}
.elementor-11 .elementor-element-b58e694{padding-bottom:16px!important}
.elementor-11 .elementor-element-7bd38f3{margin-top:8px!important}
@media (max-width:1024px){.elementor-11 .elementor-element-b58e694>.elementor-container{min-height:440px!important}}
@media (max-width:767px){.elementor-11 .elementor-element-b58e694{padding-top:40px!important;padding-bottom:8px!important;margin-top:0!important}.elementor-11 .elementor-element-b58e694>.elementor-container{min-height:0!important}}
</style>';
}, 99 );

/* Halaman HTML: browser wajib cek ulang ke server (server tetap cepat karena LSCache) */
add_action( 'send_headers', function () {
	if ( is_admin() || headers_sent() ) {
		return;
	}
	header( 'Cache-Control: no-cache, max-age=0, must-revalidate', true );
}, 99 );

/* REST: jangan cache respons untuk user login, keranjang/checkout Store API & favorit */
add_filter( 'rest_pre_dispatch', function ( $result, $server, $request ) {
	$route = (string) $request->get_route();
	if ( is_user_logged_in() || 0 === strpos( $route, '/wc/store/v1/cart' ) || 0 === strpos( $route, '/wc/store/v1/checkout' ) || 0 === strpos( $route, '/wc/store/cart' ) || 0 === strpos( $route, '/yk/v1/' ) ) {
		do_action( 'litespeed_control_set_nocache', 'yulika rest nocache' );
	}
	return $result;
}, 10, 3 );
