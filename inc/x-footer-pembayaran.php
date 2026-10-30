<?php
// Logo metode pembayaran di footer (di atas baris copyright). Dipasang oleh Claude 30 Okt 2026.
// Logo memakai file resmi bawaan plugin Midtrans & WooCommerce yang sudah ada di server (tanpa upload media).
// Daftar mengikuti metode yang aktif: Midtrans Snap (QRIS, GoPay, VA Mandiri/BNI/Permata/CIMB + bank lain via
// ATM Bersama/PRIMA/ALTO), transfer manual BCA, dan PayPal (USD). BRI VA sedang nonaktif di Snap → tidak ditampilkan.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Satu <img>; dilewati kalau file logonya tidak ada (mis. plugin dihapus). Tinggi tampilan 20px, lebar proporsional. */
function yk_pay_img( $rel, $alt, $nw, $nh, $h = 20 ) {
	if ( ! file_exists( WP_PLUGIN_DIR . '/' . $rel ) ) {
		return '';
	}
	$w = (int) round( $nw * $h / $nh );
	return sprintf(
		'<img src="%s" alt="%s" width="%d" height="%d" loading="lazy" decoding="async">',
		esc_url( plugins_url( $rel ) ),
		esc_attr( $alt ),
		$w,
		$h
	);
}

function yk_pay_footer_html() {
	$mt = 'midtrans-woocommerce/public/images/payment-methods/';
	$wc = 'woocommerce/assets/images/';

	$groups = array(
		'QRIS &amp; E-Wallet' => array(
			array( yk_pay_img( $mt . 'qris.png', 'QRIS', 160, 50 ) ),
			array( yk_pay_img( $mt . 'gopay.png', 'GoPay', 160, 50 ) ),
		),
		'Virtual Account'     => array(
			array( yk_pay_img( $mt . 'echannel.png', 'Bank Mandiri', 155, 50 ) ),
			array( yk_pay_img( $mt . 'bni_va.png', 'BNI', 155, 50 ) ),
			array( yk_pay_img( $mt . 'permata_va.png', 'PermataBank', 155, 50 ) ),
			array( yk_pay_img( $mt . 'cimb_clicks.png', 'CIMB Niaga', 155, 50 ) ),
			array(
				yk_pay_img( $mt . 'other_va_2.png', 'ATM Bersama', 80, 80, 18 ),
				yk_pay_img( $mt . 'other_va_1.png', 'PRIMA', 80, 80, 18 ),
				yk_pay_img( $mt . 'other_va_3.png', 'ALTO', 80, 80, 18 ),
			),
		),
		'Transfer Bank'       => array(
			array( yk_pay_img( $mt . 'bca_va.png', 'BCA', 155, 50 ) ),
		),
		'PayPal (USD)'        => array(
			array( yk_pay_img( $wc . 'paypal.png', 'PayPal', 1686, 409, 18 ) ),
		),
	);

	$html = '';
	foreach ( $groups as $label => $chips ) {
		$items = '';
		foreach ( $chips as $imgs ) {
			$imgs = array_filter( $imgs );
			if ( ! $imgs ) {
				continue;
			}
			$cls    = count( $imgs ) > 1 ? ' class="yk-pay-multi" title="Bank lain via ATM Bersama, PRIMA &amp; ALTO"' : '';
			$items .= '<li' . $cls . '>' . implode( '', $imgs ) . '</li>';
		}
		if ( '' === $items ) {
			continue;
		}
		$html .= '<div class="yk-pay-g"><span class="yk-pay-l">' . $label . '</span><ul class="yk-pay-row">' . $items . '</ul></div>';
	}
	if ( '' === $html ) {
		return '';
	}

	return '<div class="yk-pay" role="region" aria-label="Metode pembayaran">'
		. '<p class="yk-pay-h">Metode Pembayaran</p>'
		. '<div class="yk-pay-groups">' . $html . '</div>'
		. '<p class="yk-pay-note">Scan QRIS dari semua e-wallet &amp; m-banking (OVO, DANA, ShopeePay, LinkAja, dll.) &middot; QRIS, GoPay &amp; Virtual Account terkonfirmasi otomatis</p>'
		. '</div>';
}

/* Sisipkan tepat sebelum section copyright footer Elementor (ed295cd). */
add_action( 'elementor/frontend/section/before_render', function ( $element ) {
	if ( is_admin() || ! empty( $GLOBALS['yk_pay_done'] ) || 'ed295cd' !== $element->get_id() ) {
		return;
	}
	$GLOBALS['yk_pay_done'] = true;
	echo yk_pay_footer_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- HTML dibangun & di-escape di atas.
} );

/* Cadangan: kalau template footer berubah dan section copyright tidak ditemukan, tempel di akhir footer. */
add_action( 'wp_footer', function () {
	if ( is_admin() || ! empty( $GLOBALS['yk_pay_done'] ) || wp_doing_ajax() ) {
		return;
	}
	$GLOBALS['yk_pay_done'] = true;
	echo '<template id="yk-pay-tpl">' . yk_pay_footer_html() . '</template>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<script>(function(){var t=document.getElementById("yk-pay-tpl"),f=document.querySelector(".elementor-location-footer");if(t&&f){f.appendChild(t.content.cloneNode(true));}})();</script>';
}, 5 );

add_action( 'wp_head', function () {
	if ( is_admin() ) {
		return;
	}
	echo '<style id="yk-pay-css">'
		. '.yk-pay{max-width:1000px;margin:8px auto 0;padding:16px 16px 4px;border-top:1px solid #E9D3C6;text-align:center;font-family:Montserrat,sans-serif}'
		. '.yk-pay-h{margin:0 0 12px!important;font-size:13px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#30373E}'
		. '.yk-pay-groups{display:flex;flex-wrap:wrap;justify-content:center;align-items:flex-start;gap:14px 26px}'
		. '.yk-pay-g{display:flex;flex-direction:column;align-items:center;gap:7px}'
		. '.yk-pay-l{font-size:10.5px;font-weight:600;letter-spacing:.09em;text-transform:uppercase;color:#8A6A55}'
		. '.yk-pay-row{display:flex;flex-wrap:wrap;justify-content:center;gap:6px;margin:0!important;padding:0!important;list-style:none!important}'
		. '.yk-pay-row li{display:flex;align-items:center;justify-content:center;gap:6px;height:34px;margin:0!important;padding:0 10px;background:#fff;border:1px solid #E9D3C6;border-radius:8px;box-shadow:0 1px 2px rgba(90,60,40,.06)}'
		. '.yk-pay-row li::before,.yk-pay-row li::marker{content:none!important}'
		. '.yk-pay-row img{display:block;width:auto!important;height:20px!important;max-width:84px;object-fit:contain;margin:0!important;border:0!important;box-shadow:none!important}'
		. '.yk-pay-row li.yk-pay-multi img{height:18px!important}'
		. '.yk-pay-note{max-width:780px;margin:12px auto 0!important;font-size:11.5px;line-height:1.55;color:#6B5A4E}'
		. '@media (max-width:767px){'
		. '.yk-pay{padding:14px 12px 2px}'
		. '.yk-pay-groups{gap:12px 16px}'
		. '.yk-pay-row li{height:30px;padding:0 8px;gap:4px}'
		. '.yk-pay-row img{height:17px!important;max-width:70px}'
		. '.yk-pay-row li.yk-pay-multi img{height:16px!important}'
		. '}'
		. '</style>' . "\n";
}, 99 );
