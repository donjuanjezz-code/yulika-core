<?php
/**
 * Checkout CRO putaran 1 (14 Nov 2026, disetujui Jess — usulan 2 & 4 audit checkout).
 *
 * A. Ringkasan pesanan di atas form checkout + bar total melekat di bawah layar
 *    (masalah: produk & total baru terlihat di layar ke-4; tombol pesan di layar ke-6).
 * B. Pemulihan pesanan yang belum dibayar (Midtrans: order Pending dibatalkan otomatis oleh WooCommerce
 *    setelah batas waktu "hold stock"; sebelumnya pembeli & admin tidak diberi tahu apa-apa):
 *    - kotak "Pembayaran belum selesai" + batas waktu + bantuan WhatsApp di halaman bayar & terima kasih;
 *    - halaman bayar untuk order kedaluwarsa: tombol "Pesan lagi" + WhatsApp (bukan pesan error Inggris);
 *    - admin: tombol "Kirim pengingat bayar via WhatsApp" di halaman order;
 *    - admin: email pemberitahuan bila order Midtrans masih belum dibayar 15 menit setelah dibuat.
 * Tidak mengubah harga, kebijakan, maupun batas waktu pembatalan.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =====================================================================
 * A. Ringkasan di atas + bar total melekat
 * ===================================================================== */
function yk_cro_is_form() {
	return function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url( 'order-received' ) && ! is_wc_endpoint_url( 'order-pay' );
}

add_action( 'woocommerce_before_checkout_form', function () {
	if ( ! yk_cro_is_form() || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}
	echo '<div class="yk-cosum" id="yk-cosum">';
	foreach ( WC()->cart->get_cart() as $it ) {
		$p = isset( $it['data'] ) ? $it['data'] : null;
		if ( ! $p || ! is_object( $p ) ) {
			continue;
		}
		$img = $p->get_image_id() ? wp_get_attachment_image_url( $p->get_image_id(), 'thumbnail' ) : '';
		echo '<div class="yk-cosum-i">';
		if ( $img ) {
			echo '<img src="' . esc_url( $img ) . '" alt="" width="56" height="56" loading="eager">';
		}
		echo '<div class="yk-cosum-t"><b>' . esc_html( wp_strip_all_tags( $p->get_name() ) ) . '</b><span>' . (int) $it['quantity'] . ' &times; ' . wp_kses_post( wc_price( (float) $p->get_price() ) ) . '</span></div></div>';
	}
	echo '<div class="yk-cosum-f"><span>Total <small id="yk-cosum-n"></small></span><b id="yk-cosum-tot">' . wp_kses_post( WC()->cart->get_total() ) . '</b></div>';
	echo '</div>';
}, 4 );

add_action( 'wp_footer', function () {
	if ( ! yk_cro_is_form() || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}
	?>
<style id="yk-cro-css">
.yk-cosum{max-width:760px;margin:0 auto 16px;padding:12px 14px;border:1px solid #ece0cf;border-radius:12px;background:#fbf7f1;font-family:Montserrat,Arial,sans-serif}
.yk-cosum-i{display:flex;align-items:center;gap:12px;padding:2px 0 10px}
.yk-cosum-i img{width:56px;height:56px;object-fit:cover;border-radius:8px;flex:0 0 56px}
.yk-cosum-t{display:flex;flex-direction:column;min-width:0}
.yk-cosum-t b{font-size:15px;line-height:1.3;color:#2b2621;font-weight:700}
.yk-cosum-t span{font-size:13.5px;color:#6b625a}
.yk-cosum-f{display:flex;justify-content:space-between;align-items:baseline;gap:10px;border-top:1px dashed #dccdb8;padding-top:9px;font-size:15px;color:#3d352e}
.yk-cosum-f small{font-size:12px;color:#8a8077;font-weight:400}
.yk-cosum-f b{font-size:18px;color:#8a5a2b;white-space:nowrap}
.yk-cobar{position:fixed;left:0;right:0;bottom:0;z-index:99980;background:#fff;border-top:1px solid #e7ded2;box-shadow:0 -4px 16px rgba(60,40,20,.10);padding:10px 14px calc(10px + env(safe-area-inset-bottom,0px));display:flex;align-items:center;gap:12px;font-family:Montserrat,Arial,sans-serif;transition:transform .2s}
.yk-cobar.hide{transform:translateY(110%)}
.yk-cobar-t{flex:1;min-width:0;line-height:1.25}
.yk-cobar-t span{display:block;font-size:12px;color:#7a7067}
.yk-cobar-t b{display:block;font-size:18px;color:#2b2621;white-space:nowrap}
.yk-cobar button{flex:0 0 auto;border:0;border-radius:26px;background:#a0784a;color:#fff;font:700 15px/1 Montserrat,Arial,sans-serif;padding:14px 22px;cursor:pointer}
.yk-cobar button:active{background:#8a6338}
body.yk-has-cobar{padding-bottom:84px}
body.yk-has-cobar .yk-wa{bottom:92px!important}
.yk-cro-hl{outline:3px solid rgba(160,120,74,.45)!important;outline-offset:3px;border-radius:8px;transition:outline-color .3s}
@media(min-width:900px){.yk-cobar{left:50%;right:auto;width:760px;margin-left:-380px;border:1px solid #e7ded2;border-bottom:0;border-radius:14px 14px 0 0}}
</style>
<div class="yk-cobar hide" id="yk-cobar" role="region" aria-label="Total pesanan">
	<div class="yk-cobar-t"><span id="yk-cobar-l">Total</span><b id="yk-cobar-tot"></b></div>
	<button type="button" id="yk-cobar-b">Lanjut</button>
</div>
<script id="yk-cro-js">(function(){
var $=window.jQuery;if(!$)return;
var bar=document.getElementById('yk-cobar'),btn=document.getElementById('yk-cobar-b');if(!bar)return;
function vis(e){if(!e)return false;var r=e.getBoundingClientRect();if(!r.width&&!r.height)return false;var s=getComputedStyle(e);return s.visibility!=='hidden'&&s.display!=='none'}
function total(){var a=document.querySelectorAll('#order_review .order-total .amount');var t=a.length?a[a.length-1].textContent.trim():'';
var ship=!!document.querySelector('#order_review .woocommerce-shipping-totals .amount, #order_review .woocommerce-shipping-totals input[type=hidden], #order_review .woocommerce-shipping-totals label');
var pickup=!!document.querySelector('input[name=yk_method][value=pickup]:checked');
var note=(ship||pickup)?'':'belum termasuk ongkir';
if(t){var x=document.getElementById('yk-cobar-tot');if(x)x.textContent=t;x=document.getElementById('yk-cosum-tot');if(x)x.textContent=t}
var l=document.getElementById('yk-cobar-l');if(l)l.textContent=note?'Total ('+note+')':'Total';
var n=document.getElementById('yk-cosum-n');if(n)n.textContent=note?'('+note+')':''}
/* Langkah pertama yang belum diisi (urut dari atas layar) */
function pending(){var f=document.querySelector('form.checkout');if(!f)return null;var c=[];
var d=f.querySelector('#yk_date');if(d&&vis(d)&&!d.value)c.push(d);
var sl=f.querySelector('#yk-slots');if(sl&&vis(sl)&&!f.querySelector('input[name=yk_slot]:checked'))c.push(sl);
[].forEach.call(f.querySelectorAll('.validate-required input,.validate-required textarea,.validate-required select,#yk_greeting'),function(e){
 if(e.type==='hidden'||e.type==='checkbox'||e.type==='radio'||!vis(e))return;if(!String(e.value||'').trim())c.push(e)});
var src=f.querySelector('input[name=yk_src]');if(src&&vis(src.closest('label')||src)&&!f.querySelector('input[name=yk_src]:checked'))c.push(src.closest('div,fieldset')||src);
var tm=f.querySelector('#terms');if(tm&&vis(tm.closest('label')||tm)&&!tm.checked)c.push(tm.closest('p,label')||tm);
if(!c.length)return null;c.sort(function(a,b){return a.getBoundingClientRect().top-b.getBoundingClientRect().top});return c[0]}
function label(){btn.textContent=pending()?'Lanjut':'Pesan Sekarang'}
btn.addEventListener('click',function(){var p=pending();
if(!p){var po=document.getElementById('place_order');if(po){po.scrollIntoView({block:'center'});po.click()}return}
var y=p.getBoundingClientRect().top+window.pageYOffset-130;window.scrollTo({top:y<0?0:y,behavior:'smooth'});
var box=p.closest('.form-row,.yk-slotset,p,div')||p;box.classList.add('yk-cro-hl');setTimeout(function(){box.classList.remove('yk-cro-hl')},1800);
if(p.focus&&/INPUT|TEXTAREA|SELECT/.test(p.tagName))setTimeout(function(){try{p.focus({preventScroll:true})}catch(e){}},450)});
/* Bar disembunyikan saat tombol pesan asli sudah terlihat */
function show(v){bar.classList.toggle('hide',!v);document.body.classList.toggle('yk-has-cobar',v)}
function watch(){var po=document.getElementById('place_order');if(!po){show(true);return}
var r=po.getBoundingClientRect();show(!(r.top<window.innerHeight-10&&r.bottom>0))}
var tick=0;function onScroll(){if(tick)return;tick=1;requestAnimationFrame(function(){tick=0;watch()})}
window.addEventListener('scroll',onScroll,{passive:true});window.addEventListener('resize',onScroll);
$(document.body).on('updated_checkout',function(){total();label();watch()});
$(document).on('change input','form.checkout',function(){label();total()});
total();label();watch();
})();</script>
	<?php
}, 70 );

/* =====================================================================
 * B. Pemulihan pesanan yang belum dibayar
 * ===================================================================== */
function yk_cro_wa_help( $order ) {
	$txt = 'Halo Admin, saya butuh bantuan untuk pembayaran pesanan #' . $order->get_order_number() . ' di yulikaflorist.com.';
	return 'https://wa.me/6281315881538?text=' . rawurlencode( $txt );
}

/* Batas waktu sebelum WooCommerce membatalkan order yang belum dibayar (0 = tidak ada) */
function yk_cro_deadline( $order ) {
	$min = (int) get_option( 'woocommerce_hold_stock_minutes', 0 );
	if ( $min < 1 || 'yes' !== get_option( 'woocommerce_manage_stock' ) || ! $order->get_date_created() ) {
		return 0;
	}
	return $order->get_date_created()->getTimestamp() + $min * MINUTE_IN_SECONDS;
}

function yk_cro_first_pid( $order ) {
	foreach ( $order->get_items() as $it ) {
		return (int) $it->get_product_id();
	}
	return 0;
}

function yk_cro_box( $title, $body, $buttons ) {
	$h  = '<div class="yk-cro-pay" style="max-width:640px;margin:0 auto 22px;padding:18px;border:1px solid #f0d9a8;background:#fff8e8;border-radius:12px;text-align:center;font-family:Montserrat,Arial,sans-serif">';
	$h .= '<p style="margin:0 0 6px;font-weight:700;font-size:17px;color:#2b2621">' . esc_html( $title ) . '</p>';
	$h .= '<p style="margin:0 0 14px;font-size:14.5px;line-height:1.5;color:#4a4038">' . wp_kses( $body, array( 'b' => array(), 'br' => array() ) ) . '</p>';
	foreach ( $buttons as $b ) {
		$wa = ! empty( $b[2] );
		$h .= '<a href="' . esc_url( $b[1] ) . '"' . ( $wa ? ' target="_blank" rel="noopener" data-wa-keep="1"' : '' ) . ' style="display:inline-block;margin:4px;padding:13px 22px;border-radius:26px;font-weight:700;font-size:15px;text-decoration:none;'
			. ( $wa ? 'background:#25d366;color:#fff' : 'background:#a0784a;color:#fff' ) . '">' . esc_html( $b[0] ) . '</a>';
	}
	return $h . '</div>';
}

/* Halaman bayar (/checkout/order-pay/ID/?key=…) */
add_action( 'before_woocommerce_pay', function () {
	global $wp;
	$id    = isset( $wp->query_vars['order-pay'] ) ? absint( $wp->query_vars['order-pay'] ) : 0;
	$key   = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore
	$order = $id ? wc_get_order( $id ) : null;
	if ( ! $order || ! $key || ! hash_equals( $order->get_order_key(), $key ) ) {
		return;
	}
	if ( $order->has_status( array( 'cancelled', 'failed' ) ) && ! $order->is_paid() ) {
		$pid  = yk_cro_first_pid( $order );
		$btns = array();
		if ( $pid && 'publish' === get_post_status( $pid ) ) {
			$btns[] = array( 'Pesan lagi', add_query_arg( 'yk_buy', $pid, wc_get_checkout_url() ) );
		}
		$btns[] = array( 'Chat WhatsApp', yk_cro_wa_help( $order ), 1 );
		echo yk_cro_box( 'Pesanan #' . $order->get_order_number() . ' sudah kedaluwarsa', 'Pembayaran belum kami terima sampai batas waktu, jadi pesanan ini dibatalkan otomatis. Belum ada dana yang terpotong.<br>Masih ingin memesan? Pesan lagi atau chat kami, kami bantu.', $btns ); // phpcs:ignore
		echo '<style>.woocommerce-error,.woocommerce-notices-wrapper .woocommerce-error{display:none!important}</style>';
		return;
	}
	if ( $order->needs_payment() ) {
		$dl   = yk_cro_deadline( $order );
		$body = 'Pesanan #' . $order->get_order_number() . ' sudah kami simpan. Selesaikan pembayaran' . ( $dl ? ' sebelum <b>' . esc_html( wp_date( 'H:i', $dl ) ) . ' WIB</b>' : '' ) . ' supaya pesanan langsung diproses.<br>Jendela pembayaran tertutup atau ada kendala? Kami bantu lewat WhatsApp.';
		$btns = array();
		$mt   = (string) $order->get_meta( '_mt_payment_url' );
		if ( $mt && 0 === strpos( $mt, 'https://app.midtrans.com/' ) ) {
			$btns[] = array( 'Buka halaman pembayaran', $mt );
		}
		$btns[] = array( 'Butuh bantuan? Chat WhatsApp', yk_cro_wa_help( $order ), 1 );
		echo yk_cro_box( 'Tinggal satu langkah: pembayaran', $body, $btns ); // phpcs:ignore
	}
}, 5 );

/* Halaman terima kasih: order masih menunggu pembayaran online */
add_action( 'woocommerce_thankyou', function ( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || 'midtrans' !== $order->get_payment_method() || ! $order->needs_payment() ) {
		return;
	}
	$dl = yk_cro_deadline( $order );
	echo yk_cro_box( // phpcs:ignore
		'Pembayaran belum selesai',
		'Pesanan #' . $order->get_order_number() . ' sudah kami simpan, tetapi pembayarannya belum kami terima.' . ( $dl ? ' Selesaikan sebelum <b>' . esc_html( wp_date( 'H:i', $dl ) ) . ' WIB</b> supaya pesanan tidak dibatalkan otomatis.' : '' ),
		array(
			array( 'Bayar sekarang', $order->get_checkout_payment_url() ),
			array( 'Butuh bantuan? Chat WhatsApp', yk_cro_wa_help( $order ), 1 ),
		)
	);
}, 0 );

/* Pesan pengingat untuk dikirim admin ke pembeli (admin yang menekan kirim di WhatsApp) */
function yk_cro_remind_url( $order ) {
	$hp = function_exists( 'yk_wa_num' ) ? yk_wa_num( $order->get_billing_phone() ) : preg_replace( '/\D/', '', (string) $order->get_billing_phone() );
	if ( ! $hp ) {
		return '';
	}
	$name  = trim( (string) $order->get_billing_first_name() );
	$items = array();
	foreach ( $order->get_items() as $it ) {
		$items[] = wp_strip_all_tags( $it->get_name() );
	}
	$total = 'Rp' . number_format( (float) $order->get_total(), 0, ',', '.' );
	if ( $order->needs_payment() ) {
		$dl  = yk_cro_deadline( $order );
		$msg = 'Halo' . ( $name ? ' ' . $name : '' ) . ', kami dari Yulika Florist. Terima kasih sudah memesan *' . implode( ', ', $items ) . '* (pesanan #' . $order->get_order_number() . ', ' . $total . ").\n\n"
			. 'Pembayarannya belum kami terima. Bisa diselesaikan lewat link ini' . ( $dl ? ' sebelum ' . wp_date( 'H:i', $dl ) . ' WIB' : '' ) . ":\n" . $order->get_checkout_payment_url() . "\n\n"
			. 'Kalau ada kendala saat membayar, balas pesan ini ya, kami bantu.';
	} else {
		$pid = yk_cro_first_pid( $order );
		$msg = 'Halo' . ( $name ? ' ' . $name : '' ) . ', kami dari Yulika Florist. Pesanan #' . $order->get_order_number() . ' (*' . implode( ', ', $items ) . '*, ' . $total . ") belum sempat dibayar sehingga sudah kedaluwarsa.\n\n"
			. 'Kalau masih ingin memesan, bisa pesan lagi di sini:' . "\n" . ( $pid ? add_query_arg( 'yk_buy', $pid, wc_get_checkout_url() ) : home_url( '/' ) ) . "\n\n"
			. 'Atau balas pesan ini, kami bantu pesankan.';
	}
	return 'https://wa.me/' . $hp . '?text=' . rawurlencode( $msg );
}

add_action( 'woocommerce_admin_order_data_after_shipping_address', function ( $order ) {
	if ( ! $order || $order->is_paid() || ! $order->has_status( array( 'pending', 'failed', 'cancelled' ) ) ) {
		return;
	}
	$url = yk_cro_remind_url( $order );
	if ( ! $url ) {
		return;
	}
	$lab = $order->needs_payment() ? 'Kirim pengingat bayar via WhatsApp' : 'Tawarkan pesan ulang via WhatsApp';
	echo '<p style="margin:10px 0"><a href="' . esc_attr( $url ) . '" target="_blank" rel="noopener" style="display:inline-block;background:#a0784a;color:#fff;padding:8px 14px;border-radius:6px;text-decoration:none;font-weight:600">' . esc_html( $lab ) . '</a></p>';
}, 20 );

/* Email ke admin toko: order Midtrans masih belum dibayar 15 menit setelah dibuat */
add_action( 'woocommerce_checkout_order_processed', function ( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( $order && 'midtrans' === $order->get_payment_method() && ! wp_next_scheduled( 'yk_cro_unpaid_check', array( (int) $order_id ) ) ) {
		wp_schedule_single_event( time() + 15 * MINUTE_IN_SECONDS, 'yk_cro_unpaid_check', array( (int) $order_id ) );
	}
}, 50 );

add_action( 'yk_cro_unpaid_check', function ( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || ! $order->needs_payment() || $order->get_meta( '_yk_unpaid_mail' ) ) {
		return;
	}
	if ( false !== stripos( (string) $order->get_billing_first_name(), 'test' ) ) {
		return; // order tes
	}
	$to = get_option( 'admin_email' );
	if ( ! is_email( $to ) ) {
		return;
	}
	$dl    = yk_cro_deadline( $order );
	$items = array();
	foreach ( $order->get_items() as $it ) {
		$items[] = wp_strip_all_tags( $it->get_name() );
	}
	$wa   = yk_cro_remind_url( $order );
	$body = '<p>Order <b>#' . esc_html( $order->get_order_number() ) . '</b> dibuat 15 menit lalu dan <b>belum dibayar</b>.</p>'
		. '<p>Pembeli: ' . esc_html( $order->get_billing_first_name() ) . ' &middot; ' . esc_html( $order->get_billing_phone() ) . '<br>'
		. 'Produk: ' . esc_html( implode( ', ', $items ) ) . '<br>Total: Rp' . esc_html( number_format( (float) $order->get_total(), 0, ',', '.' ) ) . '</p>'
		. ( $dl ? '<p>Order akan dibatalkan otomatis pukul <b>' . esc_html( wp_date( 'H:i', $dl ) ) . ' WIB</b> bila belum dibayar.</p>' : '' )
		. ( $wa ? '<p style="margin:22px 0"><a href="' . esc_url( $wa ) . '" style="display:inline-block;background:#25d366;color:#fff;text-decoration:none;padding:12px 20px;border-radius:24px;font-weight:700">Kirim pengingat bayar via WhatsApp</a></p>' : '' )
		. '<p><a href="' . esc_url( $order->get_edit_order_url() ) . '">Buka order di wp-admin</a></p>';
	$mailer = WC()->mailer();
	if ( $mailer->send( $to, 'Order #' . $order->get_order_number() . ' belum dibayar (15 menit)', $mailer->wrap_message( 'Order belum dibayar', $body ), "Content-Type: text/html\r\n" ) ) {
		$order->update_meta_data( '_yk_unpaid_mail', current_time( 'mysql' ) );
		$order->add_order_note( 'Email pemberitahuan "belum dibayar 15 menit" terkirim ke admin toko.' );
		$order->save();
	}
} );
