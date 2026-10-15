<?php
// Dari Code Snippets #15 "Yulika: Klik produk langsung checkout + T&C + ucapan wajib" — scope: global
/* ===== Yulika: klik produk = langsung checkout (Order Now), syarat Return & Cancellation, kartu ucapan wajib ===== */

function yk_can_buy( $p ) {
	return $p && $p->is_type( 'simple' ) && $p->is_purchasable() && $p->is_in_stock() && (float) $p->get_price() > 0;
}

function yk_buy_url( $pid ) {
	return add_query_arg( 'yk_buy', (int) $pid, wc_get_checkout_url() );
}

/* Bot pratinjau link (WhatsApp/Facebook/Telegram, dll.) tetap melihat halaman produk agar foto & judul muncul saat link dibagikan */
function yk_is_preview_bot() {
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
	return (bool) preg_match( '/facebookexternalhit|facebot|whatsapp|telegrambot|twitterbot|linkedinbot|slackbot|discordbot|pinterest|skypeuripreview|line-poker|embedly/i', $ua );
}

function yk_is_crawler() {
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
	return (bool) preg_match( '/bot|crawl|spider|slurp|lighthouse|pagespeed|gtmetrix|headless/i', $ua );
}

add_action( 'template_redirect', function () {
	if ( is_admin() || ! function_exists( 'WC' ) || wp_doing_ajax() ) {
		return;
	}
	$from_param = isset( $_GET['yk_buy'] );
	$pid        = $from_param ? absint( $_GET['yk_buy'] ) : 0; // halaman produk sekarang = halaman foto + tombol Next (snippet #19)
	if ( ! $pid ) {
		return;
	}
	$p = wc_get_product( $pid );
	if ( ! yk_can_buy( $p ) ) {
		if ( $from_param ) {
			wp_safe_redirect( get_permalink( $pid ) );
			exit;
		}
		return; // produk tanpa harga / habis / varian: tampilkan halaman produk
	}
	if ( ! $from_param && yk_is_preview_bot() ) {
		return;
	}
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	do_action( 'litespeed_control_set_nocache', 'yulika buy now' );
	nocache_headers();

	if ( ! yk_is_crawler() && WC()->cart ) {
		$only_this = false;
		$items     = WC()->cart->get_cart();
		if ( 1 === count( $items ) ) {
			$first     = reset( $items );
			$only_this = ( (int) $first['product_id'] === (int) $pid );
		}
		if ( ! $only_this ) {
			WC()->cart->empty_cart();
			WC()->cart->add_to_cart( $pid, 1 );
		}
		if ( WC()->session && ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}
	}
	wp_safe_redirect( wc_get_checkout_url(), 302 );
	exit;
}, 1 );

add_filter( 'woocommerce_loop_add_to_cart_link', function ( $html, $product ) {
	if ( ! yk_can_buy( $product ) ) {
		return $html;
	}
	return '<a href="' . esc_url( get_permalink( $product->get_id() ) ) . '" class="button yk-buy">Order Now</a>';
}, 20, 2 );

/* Checkout: foto produk di ringkasan order + link ganti bunga */
add_filter( 'woocommerce_cart_item_name', function ( $name, $item ) {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || empty( $item['data'] ) ) {
		return $name;
	}
	$img = $item['data']->get_image( array( 64, 64 ), array( 'class' => 'yk-co-thumb' ) );
	return '<span class="yk-co-item">' . $img . '<span>' . $name . '</span></span>';
}, 20, 2 );

add_action( 'woocommerce_review_order_before_cart_contents', function () {
	echo '<tr class="yk-co-change"><td colspan="2"><a href="' . esc_url( home_url( '/' ) ) . '">&larr; Choose a different arrangement</a></td></tr>';
} );

/* Syarat & ketentuan = Return & Cancellation Policy (wajib dicentang) */
add_filter( 'woocommerce_get_terms_and_conditions_checkbox_text', function () {
	return 'I have read and agree to the <a href="' . esc_url( home_url( '/return-cancellations/' ) ) . '" target="_blank" rel="noopener">Return &amp; Cancellation Policy</a>, and I agree to be contacted via WhatsApp about this order.';
} );
add_filter( 'woocommerce_checkout_show_terms', '__return_true' );

/* Kartu ucapan wajib */
add_action( 'woocommerce_checkout_process', function () {
	$g = isset( $_POST['yk_greeting'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['yk_greeting'] ) ) ) : '';
	if ( '' === $g ) {
		wc_add_notice( 'Please write the <strong>Greeting Card Message</strong>.', 'error' );
	}
}, 5 );

add_action( 'wp_head', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}
	echo '<style>.yk-co-item{display:inline-flex;align-items:center;gap:10px}.yk-co-item img.yk-co-thumb{width:56px;height:56px;object-fit:cover;border-radius:8px;flex:0 0 56px}.yk-co-change td{padding:4px 0 10px!important;border:0!important}.yk-co-change a{font-size:13px;color:#a0784a;font-weight:600}</style>';
} );
