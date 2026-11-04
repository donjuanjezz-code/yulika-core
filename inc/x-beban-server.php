<?php
/* ===== Yulika: kurangi beban server (4 Nov 2026, OK Jess "ok 1-2-3") =====
 * Latar: akun cPanel yulikafl (Yulika + Allesscia) kena 474 CPU fault + 254 RAM fault pada 4 Nov.
 * Analisa lengkap: project doc claude/hosting-analisa-beban-2026-11-04.md
 *
 * 1) WP-Cron tidak lagi dipicu oleh pengunjung. Cron dijalankan oleh cron cPanel tiap 5 menit:
 *      wget -q -O /dev/null -t 1 -T 120 "https://yulikaflorist.com/wp-cron.php?doing_wp_cron"
 *    Konstanta didefinisikan di sini (bukan di wp-config.php) supaya bisa dibatalkan cukup lewat repo.
 *    Kalau cron cPanel dihapus, HAPUS juga bagian ini, kalau tidak jadwal WordPress berhenti.
 * 2) Jejak cron: option yk_cron_last = waktu terakhir wp-cron.php jalan (dicek lewat /wp-json/yk/v1/warm).
 * 3) Cart fragments WooCommerce (?wc-ajax=get_refreshed_fragments) hanya dimuat kalau keranjang pengunjung
 *    berisi (cookie woocommerce_items_in_cart=1). Pengunjung dengan keranjang kosong (mayoritas, karena
 *    tombol Order Now langsung ke checkout) tidak lagi mengirim request PHP tanpa cache di tiap halaman.
 *    Halaman cart & checkout tidak diubah.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* 1) WP-Cron lewat cron cPanel */
if ( ! defined( 'DISABLE_WP_CRON' ) ) {
	define( 'DISABLE_WP_CRON', true );
}

/* 2) Jejak cron */
add_action( 'init', function () {
	if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
		update_option( 'yk_cron_last', time(), false );
	}
}, 1 );

/* 3) Cart fragments hanya untuk keranjang berisi.
   Script tetap di-enqueue (WooCommerce tetap menulis wc_cart_fragments_params), hanya tag <script src>
   miliknya diganti pemuat kecil: file baru diunduh & dijalankan kalau cookie keranjang ada. */
add_filter( 'script_loader_tag', function ( $tag, $handle, $src ) {
	if ( 'wc-cart-fragments' !== $handle || is_admin() || ! $src ) {
		return $tag;
	}
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() ) ) {
		return $tag;
	}
	$lazy = '<script id="wc-cart-fragments-js" data-no-optimize="1" data-no-defer="1" data-cfasync="false">'
		. '(function(){if(!/(?:^|;\s*)woocommerce_items_in_cart=1/.test(document.cookie))return;'
		. 'var n=0,src=' . wp_json_encode( $src ) . ';'
		. '(function w(){if(window.jQuery&&(window.Cookies||n>50)){var s=document.createElement("script");s.src=src;document.body.appendChild(s);return;}'
		. 'if(++n<150)setTimeout(w,100);})();})();</script>' . "\n";
	$out = preg_replace( '#<script\b[^>]*\bsrc=(["\'])[^"\']*cart-fragments[^"\']*\1[^>]*>\s*</script>\s*#i', $lazy, $tag, 1, $count );
	return ( $count && $out ) ? $out : $tag;
}, 20, 3 );
