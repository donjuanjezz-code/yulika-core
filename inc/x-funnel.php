<?php
/**
 * Corong konversi (14 Nov 2026, disetujui Jess "ok corong").
 * Pencatat internal ringan pengganti Google Analytics: sesi manusia, halaman keluar, dan corong
 * lihat produk → checkout → klik Place Order → halaman terima kasih.
 *
 * - Hanya dicatat setelah ada interaksi manusia pertama (sentuh/scroll/klik/ketik) → bot tidak ikut.
 * - Staff tidak dicatat: browser yang pernah menampilkan admin bar ditandai (localStorage yk_fn_staff).
 * - Tidak menyimpan IP, nama, atau data pribadi. sid = angka acak per sesi (habis setelah 30 menit diam).
 * - 1 request kecil (sendBeacon → admin-ajax) per halaman, tidak menahan render.
 * Laporan: WooCommerce → Corong.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'YK_FN_DBV', '1' );

function yk_fn_table() {
	global $wpdb;
	return $wpdb->prefix . 'yk_funnel';
}

function yk_fn_install() {
	if ( YK_FN_DBV === get_option( 'yk_fn_dbv' ) ) {
		return;
	}
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( 'CREATE TABLE ' . yk_fn_table() . " (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		t datetime NOT NULL,
		sid varchar(16) NOT NULL DEFAULT '',
		ev varchar(8) NOT NULL DEFAULT '',
		pt varchar(10) NOT NULL DEFAULT '',
		page varchar(190) NOT NULL DEFAULT '',
		src varchar(60) NOT NULL DEFAULT '',
		lp varchar(190) NOT NULL DEFAULT '',
		dev varchar(10) NOT NULL DEFAULT '',
		PRIMARY KEY  (id),
		KEY t (t),
		KEY sid (sid)
	) " . $wpdb->get_charset_collate() . ';' );
	update_option( 'yk_fn_dbv', YK_FN_DBV, false );
}
add_action( 'admin_init', 'yk_fn_install' );

function yk_fn_ajax() {
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : ''; // phpcs:ignore
	$p  = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification -- endpoint publik (halaman di-cache); hanya menulis baris statistik anonim.
	$ev = isset( $p['ev'] ) ? (string) $p['ev'] : '';
	$sid = isset( $p['sid'] ) ? (string) $p['sid'] : '';
	if ( '' === $ua || preg_match( '/bot|crawl|spider|slurp|headless|lighthouse|facebookexternalhit|preview/i', $ua )
		|| ! in_array( $ev, array( 'pv', 'po', 'err' ), true ) || ! preg_match( '/^[a-z0-9]{8,16}$/', $sid )
		|| current_user_can( 'edit_posts' ) ) {
		status_header( 204 );
		exit;
	}
	yk_fn_install();
	global $wpdb;
	$s = function ( $k, $n ) use ( $p ) {
		return isset( $p[ $k ] ) ? mb_substr( sanitize_text_field( (string) $p[ $k ] ), 0, $n ) : '';
	};
	$pt = $s( 'pt', 10 );
	if ( ! in_array( $pt, array( 'home', 'product', 'list', 'cart', 'checkout', 'thanks', 'other' ), true ) ) {
		$pt = 'other';
	}
	$wpdb->insert( // phpcs:ignore
		yk_fn_table(),
		array(
			't'    => current_time( 'mysql' ),
			'sid'  => $sid,
			'ev'   => $ev,
			'pt'   => $pt,
			'page' => $s( 'page', 190 ),
			'src'  => $s( 'src', 60 ),
			'lp'   => $s( 'lp', 190 ),
			'dev'  => 'HP' === $s( 'dev', 10 ) ? 'HP' : 'Desktop',
		)
	);
	status_header( 204 );
	exit;
}
add_action( 'wp_ajax_yk_fn', 'yk_fn_ajax' );
add_action( 'wp_ajax_nopriv_yk_fn', 'yk_fn_ajax' );

/* ---------- Skrip depan ---------- */
add_action( 'wp_footer', function () {
	if ( is_admin() ) {
		return;
	}
	$pt = 'other';
	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) {
		$pt = 'thanks';
	} elseif ( function_exists( 'is_checkout' ) && is_checkout() ) {
		$pt = 'checkout';
	} elseif ( function_exists( 'is_cart' ) && is_cart() ) {
		$pt = 'cart';
	} elseif ( function_exists( 'is_product' ) && is_product() ) {
		$pt = 'product';
	} elseif ( is_front_page() ) {
		$pt = 'home';
	} elseif ( ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() ) ) || is_search() ) {
		$pt = 'list';
	}
	?>
<script id="yk-fn">(function(){try{
var L=localStorage,PT=<?php echo wp_json_encode( $pt ); ?>,AJ=<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,done=0;
function staff(){if(document.getElementById('wpadminbar')){try{L.setItem('yk_fn_staff','1')}catch(e){}return true}return L.getItem('yk_fn_staff')==='1'}
function sid(){var o=null,n=Date.now();try{o=JSON.parse(L.getItem('yk_fn')||'null')}catch(e){}
if(!o||!o.i||n-(o.t||0)>18e5)o={i:Math.random().toString(36).slice(2,12)+Math.random().toString(36).slice(2,4)};
o.t=n;try{L.setItem('yk_fn',JSON.stringify(o))}catch(e){}return o.i}
function send(ev,page){if(staff())return;var S=null;try{S=JSON.parse(sessionStorage.getItem('yk_src')||'null')}catch(e){}
var f=new FormData();f.append('action','yk_fn');f.append('ev',ev);f.append('sid',sid());f.append('pt',PT);
f.append('page',String(page||location.pathname).slice(0,190));f.append('src',S&&S.s?S.s:'');f.append('lp',S&&S.lp?S.lp:'');
f.append('dev',/Mobi|Android|iPhone|iPad/i.test(navigator.userAgent)?'HP':'Desktop');
try{if(navigator.sendBeacon&&navigator.sendBeacon(AJ,f))return}catch(e){}
try{fetch(AJ,{method:'POST',body:f,keepalive:true,credentials:'same-origin'})}catch(e){}}
var E=['pointerdown','touchstart','scroll','keydown','mousemove'];
function go(){if(done)return;done=1;E.forEach(function(e){removeEventListener(e,go,true)});send('pv')}
E.forEach(function(e){addEventListener(e,go,{capture:true,passive:true})});
if(PT==='thanks')setTimeout(go,1500);
if(PT==='checkout'){document.addEventListener('click',function(e){var b=e.target&&e.target.closest&&e.target.closest('#place_order');if(b)send('po')},true);
var w=function(){if(!window.jQuery)return;jQuery(document.body).on('checkout_error',function(ev,msg){var d=document.createElement('div');d.innerHTML=String(msg||'');
var t=(d.textContent||'').replace(/\s+/g,' ').trim();if(!t){var x=document.querySelector('.woocommerce-error,.woocommerce-NoticeGroup');t=x?x.textContent.replace(/\s+/g,' ').trim():'(tanpa pesan)'}send('err',t)})};
if(document.readyState==='complete')w();else addEventListener('load',w)}
}catch(e){}})();</script>
	<?php
}, 99 );

/* ---------- Laporan: WooCommerce → Corong ---------- */
add_action( 'admin_menu', function () {
	add_submenu_page( 'woocommerce', 'Corong Konversi', 'Corong', 'manage_woocommerce', 'yk-corong', 'yk_fn_page' );
}, 60 );

function yk_fn_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	yk_fn_install();
	global $wpdb;
	$T    = yk_fn_table();
	$d    = isset( $_GET['d'] ) ? max( 1, min( 120, (int) $_GET['d'] ) ) : 7; // phpcs:ignore
	$from = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - $d * DAY_IN_SECONDS ); // phpcs:ignore
	$wpdb->query( $wpdb->prepare( "DELETE FROM $T WHERE t < %s", gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 180 * DAY_IN_SECONDS ) ) ); // phpcs:ignore

	$n = function ( $where ) use ( $wpdb, $T, $from ) {
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT sid) FROM $T WHERE t >= %s AND $where", $from ) ); // phpcs:ignore
	};
	$dev = isset( $_GET['dev'] ) && in_array( $_GET['dev'], array( 'HP', 'Desktop' ), true ) ? sanitize_text_field( wp_unslash( $_GET['dev'] ) ) : ''; // phpcs:ignore
	$dw  = $dev ? $wpdb->prepare( ' AND dev = %s', $dev ) : '';
	$st  = array(
		'Sesi (pengunjung manusia)'  => $n( "ev='pv'" . $dw ),
		'Melihat halaman produk'     => $n( "ev='pv' AND pt='product'" . $dw ),
		'Masuk halaman checkout'     => $n( "ev='pv' AND pt='checkout'" . $dw ),
		'Klik Place Order'           => $n( "ev='po'" . $dw ),
		'Sampai halaman terima kasih' => $n( "ev='pv' AND pt='thanks'" . $dw ),
	);
	$base = max( 1, reset( $st ) );

	echo '<div class="wrap"><h1>Corong Konversi</h1><style>.ykfn-t{max-width:760px;margin:12px 0 24px}.ykfn-t td,.ykfn-t th{vertical-align:middle}.ykfn-bar{display:inline-block;height:10px;background:#a0784a;border-radius:5px;vertical-align:middle;margin-right:8px}.ykfn-f a{margin-right:6px}.ykfn-f a.cur{font-weight:700;color:#1d2327;text-decoration:none}</style>';
	echo '<p>Hanya pengunjung manusia (dicatat setelah sentuh/scroll pertama). Staff dan bot tidak dihitung. Data mulai 14 Nov 2026.</p><p class="ykfn-f">Periode: ';
	foreach ( array( 1 => 'Hari ini (24 jam)', 7 => '7 hari', 14 => '14 hari', 30 => '30 hari', 90 => '90 hari' ) as $k => $lab ) {
		echo '<a class="' . ( $k === $d ? 'cur' : '' ) . '" href="' . esc_url( add_query_arg( array( 'page' => 'yk-corong', 'd' => $k, 'dev' => $dev ), admin_url( 'admin.php' ) ) ) . '">' . esc_html( $lab ) . '</a>';
	}
	echo ' &nbsp;|&nbsp; Perangkat: ';
	foreach ( array( '' => 'Semua', 'HP' => 'HP', 'Desktop' => 'Desktop' ) as $k => $lab ) {
		echo '<a class="' . ( $k === $dev ? 'cur' : '' ) . '" href="' . esc_url( add_query_arg( array( 'page' => 'yk-corong', 'd' => $d, 'dev' => $k ), admin_url( 'admin.php' ) ) ) . '">' . esc_html( $lab ) . '</a>';
	}
	echo '</p><table class="widefat striped ykfn-t"><thead><tr><th>Tahap</th><th>Sesi</th><th>% dari semua sesi</th><th>% dari tahap sebelumnya</th></tr></thead><tbody>';
	$prev = 0;
	foreach ( $st as $lab => $v ) {
		$pc = round( 100 * $v / $base, 1 );
		echo '<tr><td>' . esc_html( $lab ) . '</td><td><b>' . (int) $v . '</b></td><td><span class="ykfn-bar" style="width:' . (int) max( 2, $pc * 1.6 ) . 'px"></span>' . esc_html( $pc ) . '%</td><td>' . ( $prev ? esc_html( round( 100 * $v / $prev, 1 ) ) . '%' : '–' ) . '</td></tr>';
		$prev = max( 1, $v );
	}
	echo '</tbody></table>';

	/* Order WooCommerce pada periode yang sama (termasuk order tes staff) */
	if ( function_exists( 'wc_get_orders' ) ) {
		$cnt = array();
		foreach ( array( 'processing', 'completed', 'on-hold', 'pending', 'cancelled', 'failed' ) as $s ) {
			$cnt[ $s ] = count( wc_get_orders( array( 'status' => $s, 'date_created' => '>' . ( time() - $d * DAY_IN_SECONDS ), 'limit' => -1, 'return' => 'ids' ) ) );
		}
		echo '<p><b>Order WooCommerce periode ini</b> (termasuk tes staff): lunas ' . (int) ( $cnt['processing'] + $cnt['completed'] ) . ' · menunggu bayar ' . (int) ( $cnt['on-hold'] + $cnt['pending'] ) . ' · batal/gagal ' . (int) ( $cnt['cancelled'] + $cnt['failed'] ) . '</p>';
	}

	$tbl = function ( $title, $rows, $h1, $h2 ) {
		echo '<h2>' . esc_html( $title ) . '</h2><table class="widefat striped ykfn-t"><thead><tr><th>' . esc_html( $h1 ) . '</th><th>' . esc_html( $h2 ) . '</th></tr></thead><tbody>';
		if ( ! $rows ) {
			echo '<tr><td colspan="2">Belum ada data.</td></tr>';
		}
		foreach ( (array) $rows as $r ) {
			echo '<tr><td>' . esc_html( '' === $r->k ? '(kosong)' : $r->k ) . '</td><td>' . (int) $r->n . '</td></tr>';
		}
		echo '</tbody></table>';
	};
	// phpcs:disable
	$tbl( 'Halaman keluar terbanyak (halaman terakhir tiap sesi)', $wpdb->get_results( $wpdb->prepare( "SELECT a.page k, COUNT(*) n FROM $T a JOIN (SELECT MAX(id) m FROM $T WHERE t >= %s AND ev='pv'$dw GROUP BY sid) b ON a.id=b.m WHERE a.pt<>'thanks' GROUP BY a.page ORDER BY n DESC LIMIT 15", $from ) ), 'Halaman', 'Sesi' );
	$tbl( 'Halaman masuk terbanyak', $wpdb->get_results( $wpdb->prepare( "SELECT a.page k, COUNT(*) n FROM $T a JOIN (SELECT MIN(id) m FROM $T WHERE t >= %s AND ev='pv'$dw GROUP BY sid) b ON a.id=b.m GROUP BY a.page ORDER BY n DESC LIMIT 15", $from ) ), 'Halaman', 'Sesi' );
	$tbl( 'Sumber trafik', $wpdb->get_results( $wpdb->prepare( "SELECT src k, COUNT(DISTINCT sid) n FROM $T WHERE t >= %s AND ev='pv'$dw GROUP BY src ORDER BY n DESC LIMIT 15", $from ) ), 'Sumber', 'Sesi' );
	$tbl( 'Sumber trafik yang sampai checkout', $wpdb->get_results( $wpdb->prepare( "SELECT src k, COUNT(DISTINCT sid) n FROM $T WHERE t >= %s AND ev='pv' AND pt='checkout'$dw GROUP BY src ORDER BY n DESC LIMIT 15", $from ) ), 'Sumber', 'Sesi' );
	$tbl( 'Pesan error saat klik Place Order', $wpdb->get_results( $wpdb->prepare( "SELECT page k, COUNT(*) n FROM $T WHERE t >= %s AND ev='err'$dw GROUP BY page ORDER BY n DESC LIMIT 15", $from ) ), 'Pesan', 'Kali' );
	// phpcs:enable
	echo '</div>';
}
