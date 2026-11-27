<?php
/**
 * Gerbang WhatsApp wajib setelah tombol "Pesan Sekarang", untuk SEMUA kanal pembayaran.
 * Alur: checkout → halaman gerbang (1 tombol: kirim rekap pesanan ke WhatsApp admin) → pembayaran / halaman terima kasih.
 *
 * - Nomor tujuan rekap: YK_WAGATE_NUM.
 * - YK_WAGATE_LIVE = true sejak 27 Nov 2026 ("ok tayang"). false → pembeli tidak terpengaruh. Contoh tampilan: /checkout/?yk_wa_gate=demo
 * - Yang tercatat hanya "tombol ditekan" (meta _yk_wa_sent + catatan order). Terkirim atau tidaknya pesan tidak bisa
 *   dibaca dari website: itu terjadi di aplikasi WhatsApp pembeli.
 * - Cara mengembalikan: hapus file ini. Tombol di halaman terima kasih otomatis kembali ke nomor lama.
 */
if ( ! defined( 'YK_WAGATE_NUM' ) ) {
	define( 'YK_WAGATE_NUM', '6281315881538' );
}
if ( ! defined( 'YK_WAGATE_LIVE' ) ) {
	define( 'YK_WAGATE_LIVE', true );
}

function yk_wagate_num_label() {
	return preg_replace( '/^62(\d{3})(\d{4})(\d+)$/', '0$1-$2-$3', YK_WAGATE_NUM );
}

function yk_wagate_wa_url( $order ) {
	$txt = function_exists( 'yk_order_wa_text' ) ? yk_order_wa_text( $order, 'shop' ) : 'Hi Yulika Florist, I have just placed order #' . $order->get_order_number() . '.';
	return 'https://wa.me/' . YK_WAGATE_NUM . '?text=' . rawurlencode( $txt );
}

function yk_wagate_url( $order ) {
	return add_query_arg( array( 'yk_wa_gate' => $order->get_id(), 'gk' => $order->get_order_key() ), wc_get_checkout_url() );
}

/* 1. Setelah order dibuat: belokkan ke halaman gerbang, simpan tujuan aslinya (halaman bayar / terima kasih). */
function yk_wagate_divert( $redirect, $order ) {
	if ( ! YK_WAGATE_LIVE || ! $order || ! $redirect || $order->get_meta( '_yk_wa_sent' ) ) {
		return $redirect;
	}
	try {
		$order->update_meta_data( '_yk_gate_next', esc_url_raw( $redirect ) );
		$order->save();
		return yk_wagate_url( $order );
	} catch ( \Throwable $e ) {
		return $redirect; // apa pun yang gagal di sini tidak boleh menghalangi pembayaran
	}
}
add_filter( 'woocommerce_payment_successful_result', function ( $result, $order_id = 0 ) {
	if ( is_array( $result ) && ! empty( $result['redirect'] ) && ( ! isset( $result['result'] ) || 'success' === $result['result'] ) ) {
		$result['redirect'] = yk_wagate_divert( $result['redirect'], wc_get_order( $order_id ) );
	}
	return $result;
}, 999, 2 );
add_filter( 'woocommerce_checkout_no_payment_needed_redirect', function ( $url, $order = null ) {
	return yk_wagate_divert( $url, $order );
}, 999, 2 );

function yk_wagate_next( $order ) {
	$next = (string) $order->get_meta( '_yk_gate_next' );
	if ( '' === $next ) {
		$next = $order->needs_payment() ? $order->get_checkout_payment_url() : $order->get_checkout_order_received_url();
	}
	return $next;
}

/* 2. Halaman gerbang: /checkout/?yk_wa_gate=ID&gk=KUNCI (di bawah /checkout/ supaya tidak di-cache Cloudflare/LiteSpeed). */
add_action( 'template_redirect', function () {
	if ( empty( $_GET['yk_wa_gate'] ) ) { // phpcs:ignore
		return;
	}
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	nocache_headers();
	$raw = sanitize_text_field( wp_unslash( $_GET['yk_wa_gate'] ) ); // phpcs:ignore
	if ( 'demo' === $raw ) {
		$sample = "Hi Yulika Florist, I have just placed order #000000.\n\n- 1x Contoh Buket (CONTOH, mohon abaikan)\nTotal: Rp500.000 (QRIS)\n\nIni pesan contoh dari halaman uji. Mohon abaikan.";
		yk_wagate_render(
			array(
				'demo'  => true,
				'no'    => '000000',
				'items' => array( '1 × Contoh Buket' ),
				'total' => 'Rp500.000',
				'pay'   => 'QRIS, GoPay, ShopeePay & Virtual Account',
				'when'  => 'Delivery: Saturday, 28 November 2026, 12:00 – 17:00',
				'wa'    => 'https://wa.me/' . YK_WAGATE_NUM . '?text=' . rawurlencode( $sample ),
				'next'  => '#',
				'oid'   => 0,
				'key'   => '',
			)
		);
		exit;
	}
	$order = function_exists( 'wc_get_order' ) ? wc_get_order( absint( $raw ) ) : null;
	$key   = isset( $_GET['gk'] ) ? sanitize_text_field( wp_unslash( $_GET['gk'] ) ) : ''; // phpcs:ignore
	if ( ! $order || '' === $key || ! hash_equals( (string) $order->get_order_key(), $key ) ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
	$next = yk_wagate_next( $order );
	if ( $order->get_meta( '_yk_wa_sent' ) || $order->has_status( array( 'cancelled', 'failed', 'refunded', 'completed' ) ) ) {
		wp_redirect( $next ); // phpcs:ignore -- tujuan disimpan server dari hasil gateway, bukan dari URL
		exit;
	}
	$items = array();
	foreach ( $order->get_items() as $it ) {
		$items[] = $it->get_quantity() . ' × ' . wp_strip_all_tags( $it->get_name() );
	}
	$date = (string) $order->get_meta( '_yk_delivery_date' );
	$slot = (string) $order->get_meta( '_yk_delivery_slot' );
	$when = '';
	if ( $date ) {
		$when = ( 'pickup' === $order->get_meta( '_yk_method' ) ? 'Store pickup: ' : 'Delivery: ' ) . ( function_exists( 'yk_dlv_date_label' ) ? yk_dlv_date_label( $date ) : $date ) . ( $slot ? ', ' . $slot : '' );
	}
	yk_wagate_render(
		array(
			'demo'  => false,
			'no'    => $order->get_order_number(),
			'items' => $items,
			'total' => html_entity_decode( wp_strip_all_tags( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ), ENT_QUOTES, 'UTF-8' ),
			'pay'   => html_entity_decode( wp_strip_all_tags( (string) $order->get_payment_method_title() ), ENT_QUOTES, 'UTF-8' ),
			'when'  => $when,
			'wa'    => yk_wagate_wa_url( $order ),
			'next'  => $next,
			'oid'   => $order->get_id(),
			'key'   => $order->get_order_key(),
		)
	);
	exit;
}, 0 );

function yk_wagate_render( $d ) {
	$logo = content_url( '/uploads/2020/02/yulika-logo-bg-removed.png' );
	header( 'Content-Type: text/html; charset=utf-8' );
	?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Kirim rekap pesanan #<?php echo esc_html( $d['no'] ); ?> – Yulika Florist</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&family=Sorts+Mill+Goudy&display=swap">
<style>
*{box-sizing:border-box}
html,body{margin:0}
body{background:#fbf7f3;color:#30373e;font-family:"Plus Jakarta Sans",Montserrat,Arial,sans-serif;font-size:16px;line-height:1.55;-webkit-font-smoothing:antialiased}
.g{max-width:520px;margin:0 auto;padding:22px 18px 40px;text-align:center}
.g .lg{display:block;width:118px;height:auto;margin:0 auto 14px}
.steps{display:flex;justify-content:center;align-items:center;gap:8px;margin:0 0 18px;font-size:13px;font-weight:600;color:#6e6e73}
.steps b{display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:50%;background:#e9dfd2;color:#6b4a2b;font-size:13px;margin-right:6px}
.steps .on{color:#6b4a2b}
.steps .on b{background:#8a5a2b;color:#fff}
.steps i{display:block;width:28px;height:2px;background:#e3d9cc}
h1{font-family:"Sorts Mill Goudy",Georgia,serif;font-weight:400;font-size:28px;line-height:1.2;color:#222;margin:0 0 10px}
.lead{margin:0 0 18px;font-size:16px;color:#30373e}
.card{background:#fff;border:1px solid #e3d9cc;border-radius:14px;padding:16px 18px;margin:0 0 20px;text-align:left}
.card .no{font-size:12.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#8a5a2b;margin:0 0 8px}
.card p{margin:0 0 4px;font-size:15.5px}
.card .tt{margin:10px 0 0;padding-top:10px;border-top:1px solid #efe6da;font-weight:700;color:#222;font-size:17px}
.card .sm{font-size:14px;color:#555;font-weight:400}
.btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;min-height:58px;padding:14px 18px;border-radius:999px;background:#1b8a4b;color:#fff;font-weight:700;font-size:17.5px;line-height:1.2;text-decoration:none;box-shadow:0 6px 18px rgba(27,138,75,.28)}
.btn svg{flex:none}
.hint{margin:14px 0 0;font-size:14.5px;color:#555}
.en{margin:10px 0 0;font-size:13.5px;color:#6e6e73}
.after{display:none;margin:22px 0 0;padding:18px;border:1px solid #e3d9cc;border-radius:14px;background:#fff}
.after p{margin:0 0 12px;font-size:15.5px}
.btn2{display:flex;align-items:center;justify-content:center;width:100%;min-height:56px;padding:14px 18px;border-radius:999px;background:#8a5a2b;color:#fff;font-weight:700;font-size:17px;text-decoration:none}
.sent .after{display:block}
.sent .btn{background:#fff;color:#1b8a4b;border:1.5px solid #1b8a4b;box-shadow:none;min-height:50px;font-size:15.5px}
.sent .hint,.sent .en{display:none}
.demo{display:inline-block;margin:0 0 12px;padding:4px 12px;border-radius:999px;background:#fff3cd;color:#664d03;font-size:13px;font-weight:700}
@media(min-width:700px){.g{padding-top:44px}h1{font-size:34px}}
</style>
</head>
<body>
<main class="g">
<img class="lg" src="<?php echo esc_url( $logo ); ?>" alt="Yulika Florist" width="118" height="69">
	<?php if ( $d['demo'] ) : ?>
<p class="demo">CONTOH TAMPILAN, bukan pesanan asli</p>
	<?php endif; ?>
<div class="steps"><span class="on"><b>1</b>Kirim rekap</span><i></i><span><b>2</b>Pembayaran</span></div>
<h1>Pesanan #<?php echo esc_html( $d['no'] ); ?> sudah kami simpan</h1>
<p class="lead">Satu langkah wajib sebelum pembayaran: kirim rekap pesanan ini ke WhatsApp kami, supaya tim langsung memprosesnya.</p>
<div class="card">
<p class="no">Rekap pesanan</p>
	<?php foreach ( $d['items'] as $line ) : ?>
<p><?php echo esc_html( $line ); ?></p>
	<?php endforeach; ?>
	<?php if ( $d['when'] ) : ?>
<p class="sm"><?php echo esc_html( $d['when'] ); ?></p>
	<?php endif; ?>
<p class="tt">Total <?php echo esc_html( $d['total'] ); ?><?php if ( $d['pay'] ) : ?> <span class="sm">· <?php echo esc_html( $d['pay'] ); ?></span><?php endif; ?></p>
</div>
<a class="btn" id="yk-go" href="<?php echo esc_attr( $d['wa'] ); ?>" target="_blank" rel="noopener">
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3a9 9 0 0 0-7.7 13.6L3 21l4.5-1.2A9 9 0 1 0 12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M8.5 12.5 11 15l4.5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
<span id="yk-go-t">Kirim Rekap via WhatsApp</span></a>
<p class="hint">WhatsApp terbuka dengan pesan yang sudah terisi. Tekan <b>Kirim</b>, lalu kembali ke halaman ini untuk membayar.</p>
<p class="en">Required step: tap the button to send your order summary to our WhatsApp (<?php echo esc_html( yk_wagate_num_label() ); ?>), then come back here to pay.</p>
<div class="after">
<p><b>Sudah menekan Kirim di WhatsApp?</b><br>Lanjutkan ke pembayaran supaya pesanan diproses.</p>
<a class="btn2" id="yk-next" href="<?php echo esc_attr( $d['next'] ); ?>">Lanjut ke Pembayaran &rarr;</a>
</div>
<noscript><p class="hint"><a href="<?php echo esc_attr( $d['next'] ); ?>">Lanjut ke pembayaran</a></p></noscript>
</main>
<script>
(function(){
var C=<?php echo wp_json_encode( array( 'ajax' => admin_url( 'admin-ajax.php' ), 'o' => (int) $d['oid'], 'gk' => (string) $d['key'], 'next' => (string) $d['next'], 'demo' => (bool) $d['demo'] ) ); ?>;
var go=document.getElementById('yk-go'),sent=false,away=false;
function rec(){if(C.demo||!C.o)return;var f=new FormData();f.append('action','yk_wagate');f.append('o',C.o);f.append('gk',C.gk);
try{if(navigator.sendBeacon&&navigator.sendBeacon(C.ajax,f))return}catch(e){}
try{fetch(C.ajax,{method:'POST',body:f,keepalive:true,credentials:'same-origin'})}catch(e){}}
go.addEventListener('click',function(){if(!sent){sent=true;rec();}
setTimeout(function(){document.body.className+=' sent';document.getElementById('yk-go-t').textContent='Buka WhatsApp lagi';},400);});
document.addEventListener('visibilitychange',function(){if(!sent)return;if(document.hidden){away=true}else if(away&&!C.demo){location.href=C.next}});
if(C.demo){document.getElementById('yk-next').addEventListener('click',function(e){e.preventDefault();this.textContent='Contoh: di pesanan asli membuka pembayaran'});}
})();
</script>
</body>
</html>
	<?php
}

/* 3. Catat "tombol ditekan" (dari halaman gerbang atau dari kotak WhatsApp di halaman terima kasih). */
function yk_wagate_ajax() {
	$order = function_exists( 'wc_get_order' ) ? wc_get_order( isset( $_REQUEST['o'] ) ? absint( $_REQUEST['o'] ) : 0 ) : null; // phpcs:ignore
	$key   = isset( $_REQUEST['gk'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['gk'] ) ) : ''; // phpcs:ignore
	if ( ! $order || '' === $key || ! hash_equals( (string) $order->get_order_key(), $key ) ) {
		wp_send_json_error( null, 403 );
	}
	if ( ! empty( $_REQUEST['p'] ) ) { // phpcs:ignore -- konfirmasi SETELAH lunas (halaman terima kasih)
		if ( ! $order->get_meta( '_yk_wa_paid' ) ) {
			$order->update_meta_data( '_yk_wa_paid', current_time( 'mysql' ) );
			$order->save();
			$order->add_order_note( 'Pembeli menekan tombol "Send WhatsApp confirmation to admin" setelah lunas (tujuan ' . yk_wagate_num_label() . '). Pastikan pesannya masuk di WhatsApp.' );
		}
		wp_send_json_success();
	}
	if ( ! $order->get_meta( '_yk_wa_sent' ) ) {
		$order->update_meta_data( '_yk_wa_sent', current_time( 'mysql' ) );
		$order->save();
		$order->add_order_note( 'Pembeli menekan tombol "Kirim Rekap via WhatsApp" (tujuan ' . yk_wagate_num_label() . '). Pastikan pesannya masuk di WhatsApp.' );
	}
	wp_send_json_success();
}
add_action( 'wp_ajax_yk_wagate', 'yk_wagate_ajax' );
add_action( 'wp_ajax_nopriv_yk_wagate', 'yk_wagate_ajax' );

/* Halaman terima kasih: klik kotak "Confirm your order on WhatsApp" ikut tercatat. */
add_action( 'woocommerce_thankyou', function ( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || $order->get_meta( '_yk_wa_sent' ) ) {
		return;
	}
	$c = array( 'ajax' => admin_url( 'admin-ajax.php' ), 'o' => $order->get_id(), 'gk' => $order->get_order_key() );
	echo '<script>(function(){var C=' . wp_json_encode( $c ) . ',a=document.querySelector(".yk-wa-confirm a");if(!a)return;a.addEventListener("click",function(){var f=new FormData();f.append("action","yk_wagate");f.append("o",C.o);f.append("gk",C.gk);try{if(navigator.sendBeacon&&navigator.sendBeacon(C.ajax,f))return}catch(e){}try{fetch(C.ajax,{method:"POST",body:f,keepalive:true,credentials:"same-origin"})}catch(e){}})})();</script>';
}, 3 );

/* Halaman terima kasih SETELAH LUNAS: lapisan wajib, satu tombol "Send WhatsApp confirmation to admin". */
add_action( 'woocommerce_thankyou', function ( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! YK_WAGATE_LIVE || ! $order ) {
		return;
	}
	if ( ! $order->is_paid() ) {
		// Midtrans: pembeli bisa tiba sebelum notifikasi lunas masuk. Muat ulang sebentar sampai status berubah.
		$ts = isset( $_GET['transaction_status'] ) ? sanitize_key( wp_unslash( $_GET['transaction_status'] ) ) : ''; // phpcs:ignore
		if ( $order->has_status( 'pending' ) && in_array( $ts, array( 'settlement', 'capture' ), true ) ) {
			echo '<script>(function(){var k="ykpr' . (int) $order->get_id() . '",n=0;try{n=+sessionStorage.getItem(k)||0}catch(e){}if(n<4){try{sessionStorage.setItem(k,n+1)}catch(e){}setTimeout(function(){location.reload()},4000)}})();</script>';
		}
		return;
	}
	if ( $order->get_meta( '_yk_wa_paid' ) || $order->has_status( 'completed' ) ) {
		return;
	}
	$c = array( 'ajax' => admin_url( 'admin-ajax.php' ), 'o' => $order->get_id(), 'gk' => $order->get_order_key() );
	?>
<div id="yk-paid-gate" style="position:fixed;inset:0;z-index:999999;background:rgba(34,34,34,.72);display:flex;align-items:center;justify-content:center;padding:16px">
<div style="background:#fbfaf9;border-radius:14px;max-width:420px;width:100%;padding:26px 20px;text-align:center;font-family:'Plus Jakarta Sans',Montserrat,Arial,sans-serif;color:#30373e;box-shadow:0 10px 40px rgba(0,0,0,.3)">
<div style="width:54px;height:54px;border-radius:50%;background:#e7f5ec;color:#1b8a4b;font-size:28px;line-height:54px;margin:0 auto 12px">&#10003;</div>
<p style="font-family:'Sorts Mill Goudy',Georgia,serif;font-size:24px;line-height:1.25;margin:0 0 8px;color:#222">Pembayaran diterima</p>
<p style="font-size:15px;line-height:1.5;margin:0 0 6px">Pesanan #<?php echo esc_html( $order->get_order_number() ); ?> sudah lunas. Satu langkah terakhir yang wajib: kirim konfirmasi ke WhatsApp admin kami.</p>
<p style="font-size:13px;line-height:1.5;margin:0 0 16px;color:#666">Payment received. One last required step: send your confirmation to our WhatsApp (<?php echo esc_html( yk_wagate_num_label() ); ?>).</p>
<a id="yk-paid-go" data-wa-keep="1" target="_blank" rel="noopener" href="<?php echo esc_url( yk_wagate_wa_url( $order ) ); ?>" style="display:block;background:#1faa59;color:#fff;font-weight:700;font-size:16px;padding:15px 14px;border-radius:10px;text-decoration:none">Send WhatsApp confirmation to admin</a>
</div></div>
<script>(function(){var C=<?php echo wp_json_encode( $c ); ?>,g=document.getElementById('yk-paid-gate'),a=document.getElementById('yk-paid-go');if(!g||!a)return;document.body.appendChild(g);
a.addEventListener('click',function(){var f=new FormData();f.append('action','yk_wagate');f.append('o',C.o);f.append('gk',C.gk);f.append('p','1');var ok=false;try{ok=navigator.sendBeacon&&navigator.sendBeacon(C.ajax,f)}catch(e){}if(!ok){try{fetch(C.ajax,{method:'POST',body:f,keepalive:true,credentials:'same-origin'})}catch(e){}}setTimeout(function(){g.style.display='none'},600)});})();</script>
	<?php
}, 1 );

/* 4. Admin: status rekap WhatsApp di halaman order dan di daftar order. */
function yk_wagate_status_html( $order ) {
	$sent = (string) $order->get_meta( '_yk_wa_sent' );
	$paid = (string) $order->get_meta( '_yk_wa_paid' );
	$pp   = $paid ? '<br><span style="color:#1b8a4b;font-weight:600">lunas: ditekan ' . esc_html( mysql2date( 'j M H:i', $paid ) ) . '</span>' : ( $order->is_paid() ? '<br><span style="color:#b32d2e;font-weight:600">lunas: belum ditekan</span>' : '' );
	if ( $sent ) {
		return '<span style="color:#1b8a4b;font-weight:600">ditekan ' . esc_html( mysql2date( 'j M H:i', $sent ) ) . '</span>' . $pp;
	}
	if ( $pp ) {
		return ( $order->get_meta( '_yk_gate_next' ) ? '<span style="color:#b32d2e;font-weight:600">belum ditekan</span>' : '' ) . $pp;
	}
	if ( $order->get_meta( '_yk_gate_next' ) ) {
		return '<span style="color:#b32d2e;font-weight:600">belum ditekan</span>';
	}
	return '<span style="color:#888">–</span>';
}
add_action( 'woocommerce_admin_order_data_after_billing_address', function ( $order ) {
	echo '<p><strong>Rekap WhatsApp (' . esc_html( yk_wagate_num_label() ) . '):</strong> ' . yk_wagate_status_html( $order ) . '</p>'; // phpcs:ignore
}, 20 );
function yk_wagate_col( $cols ) {
	$out = array();
	foreach ( $cols as $k => $v ) {
		$out[ $k ] = $v;
		if ( 'order_status' === $k ) {
			$out['yk_wa'] = 'Rekap WA';
		}
	}
	if ( ! isset( $out['yk_wa'] ) ) {
		$out['yk_wa'] = 'Rekap WA';
	}
	return $out;
}
add_filter( 'woocommerce_shop_order_list_table_columns', 'yk_wagate_col', 20 );
add_filter( 'manage_edit-shop_order_columns', 'yk_wagate_col', 20 );
add_action( 'woocommerce_shop_order_list_table_custom_column', function ( $col, $order ) {
	if ( 'yk_wa' === $col && $order ) {
		echo yk_wagate_status_html( $order ); // phpcs:ignore
	}
}, 10, 2 );
add_action( 'manage_shop_order_posts_custom_column', function ( $col, $post_id ) {
	if ( 'yk_wa' === $col ) {
		$order = wc_get_order( $post_id );
		if ( $order ) {
			echo yk_wagate_status_html( $order ); // phpcs:ignore
		}
	}
}, 10, 2 );
