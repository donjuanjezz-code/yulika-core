<?php
// Pop-up "Why Yulika" (15 Okt 2026): muncul sekali di kunjungan pertama pada halaman pintu masuk
// (beranda & kategori produk). Di HP tampil sebagai bottom sheet (tidak menutup seluruh layar).
// Popup member (#41) tidak tampil di sesi yang sama supaya pengunjung tidak dapat 2 popup.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_footer', function () {
	if ( is_admin() || is_user_logged_in() ) {
		return;
	}
	$entry = is_front_page() || ( function_exists( 'is_product_category' ) && is_product_category() );
	if ( ! $entry ) {
		return;
	}
	$wa  = 'https://wa.me/6281315881538?text=' . rawurlencode( 'Halo Yulika Florist, saya mau tanya ready stock hari ini.' );
	$ico = array(
		'store' => '<path d="M3 9.5 4.5 4h15L21 9.5M3 9.5h18M3 9.5c0 1.4 1.1 2.5 2.5 2.5S8 10.9 8 9.5c0 1.4 1.1 2.5 2.5 2.5S13 10.9 13 9.5c0 1.4 1.1 2.5 2.5 2.5S18 10.9 18 9.5c0 1.4 1.1 2.5 2.5 2.5M5 12v8h14v-8M10 20v-5h4v5"/>',
		'leaf'  => '<path d="M5 19c0-8 5-13 15-14-1 10-6 15-14 15M5 19l7-7"/>',
		'bag'   => '<path d="M5 8h14l-1 12H6L5 8Zm4 0V6a3 3 0 0 1 6 0v2"/>',
		'truck' => '<path d="M3 6h11v9H3zM14 9h4l3 3v3h-7M7 18.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm10 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"/>',
		'hand'  => '<path d="M12 3c1.5 1.6 1.5 3.4 0 5-1.5-1.6-1.5-3.4 0-5Zm-4.5 2.5c2 .2 3.2 1.5 3.4 3.5-2-.2-3.2-1.5-3.4-3.5Zm9 0c-2 .2-3.2 1.5-3.4 3.5 2-.2 3.2-1.5 3.4-3.5ZM12 8v6M4 15.5l3.2-.8c.9-.2 1.8 0 2.5.5l1.3.9h3.5a1.3 1.3 0 0 1 0 2.6H11m-7 2.8 3-1.2 6 .9 6.2-3.4a1.4 1.4 0 0 0-1.3-2.5L16 16.6"/>',
		'arch'  => '<path d="M5 21V11a7 7 0 0 1 14 0v10M9 21v-8a3 3 0 0 1 6 0v8M3 21h18"/>',
	);
	$rows = array(
		array( 'store', 'Toko fisik di Kelapa Gading', 'ready stock, bisa datang langsung' ),
		array( 'hand', 'Semua dirangkai di florist kami', 'we are not a reseller' ),
		array( 'leaf', 'Kebun sendiri di Puncak', '+ importir bunga langsung' ),
		array( 'truck', 'Same-day delivery', 'seluruh Jakarta & Bekasi' ),
		array( 'arch', 'Buket, papan bunga, hingga', 'dekorasi wedding & interior' ),
	);
	?>
<div class="ykw" id="ykw" hidden role="dialog" aria-modal="true" aria-labelledby="ykw-t">
 <div class="ykw-bg" data-x></div>
 <div class="ykw-box">
  <button class="ykw-x" type="button" aria-label="Tutup" data-x>×</button>
  <p class="ykw-e">Welcome to Yulika Florist</p>
  <h3 id="ykw-t">The First Florist in Kelapa&nbsp;Gading</h3>
  <p class="ykw-s">Kebun sendiri. Dirangkai sendiri. Dikirim hari&nbsp;ini.</p>
  <ul class="ykw-l">
	<?php foreach ( $rows as $r ) : ?>
   <li><svg viewBox="0 0 24 24" aria-hidden="true"><?php echo $ico[ $r[0] ]; // phpcs:ignore ?></svg><span><b><?php echo esc_html( $r[1] ); ?></b> <?php echo esc_html( $r[2] ); ?></span></li>
	<?php endforeach; ?>
  </ul>
  <a class="ykw-b" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener" data-go>Cek Ready Stock via WhatsApp</a>
  <button class="ykw-n" type="button" data-x>Lihat koleksi online</button>
  <a class="ykw-m" href="<?php echo esc_url( home_url( '/welcome/' ) ); ?>">Member baru? Diskon 10% pesanan pertama →</a>
 </div>
</div>
<style>
.ykw[hidden]{display:none!important}
.ykw{position:fixed;inset:0;z-index:100001;display:flex;align-items:center;justify-content:center;font-family:'Plus Jakarta Sans',Montserrat,-apple-system,Arial,sans-serif;color:#3b2a1a}
.ykw-bg{position:absolute;inset:0;background:rgba(40,28,16,.45);opacity:0;transition:opacity .3s}
.ykw-box{position:relative;width:min(400px,92vw);background:#fbf7f1;border-radius:18px;padding:26px 24px 18px;box-shadow:0 24px 60px rgba(40,28,16,.25);transform:translateY(16px);opacity:0;transition:transform .35s ease,opacity .35s ease;text-align:center}
.ykw.on .ykw-bg{opacity:1}.ykw.on .ykw-box{transform:none;opacity:1}
.ykw-x{position:absolute;top:8px;right:10px;width:36px;height:36px;border:0;background:transparent;font-size:24px;line-height:1;color:#8a6a4a;cursor:pointer}
.ykw-e{margin:0 0 6px;font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:#a0784a}
.ykw h3{margin:0;font-family:'Sorts Mill Goudy',Georgia,serif;font-weight:400;font-size:27px;line-height:1.15;color:#3b2a1a}
.ykw-s{margin:8px 0 16px;font-size:14px;color:#6d5640}
.ykw-l{list-style:none;margin:0 0 18px;padding:14px 0 0;border-top:1px solid #e8dccb;text-align:left;display:grid;gap:10px}
.ykw-l li{display:flex;align-items:center;gap:12px;font-size:13.5px;line-height:1.35;color:#5a4632}
.ykw-l b{color:#3b2a1a;font-weight:600}
.ykw-l svg{flex:0 0 22px;width:22px;height:22px;fill:none;stroke:#a0784a;stroke-width:1.4;stroke-linecap:round;stroke-linejoin:round}
.ykw-b{display:block;padding:13px 16px;border-radius:26px;background:#8a5a2b;color:#fff!important;text-decoration:none;font-weight:600;font-size:14.5px}
.ykw-b:hover{background:#74491f}
.ykw-n{display:block;width:100%;margin:8px 0 0;padding:11px;border:1px solid #cdb79c;border-radius:26px;background:transparent;color:#5a4632;font:inherit;font-size:14px;cursor:pointer}
.ykw-m{display:inline-block;margin-top:12px;font-size:12.5px;color:#a0784a!important;text-decoration:underline;text-underline-offset:3px}
@media(max-width:600px){
 .ykw{align-items:flex-end}
 .ykw-box{width:100%;border-radius:20px 20px 0 0;padding:22px 20px calc(14px + env(safe-area-inset-bottom));transform:translateY(100%)}
 .ykw h3{font-size:24px}
 .ykw-s{margin:6px 0 12px}
 .ykw-l{gap:8px;padding-top:12px;margin-bottom:14px}
 .ykw-l li{font-size:13px}
}
/* tema menimpa gaya <button>: kunci ulang */
.ykw .ykw-x{background:transparent!important;border:0!important;box-shadow:none!important;padding:0!important;font-size:26px!important;line-height:1!important;color:#8a6a4a!important;min-height:0!important;width:36px!important;height:36px!important}
.ykw .ykw-n{border:1px solid #cdb79c!important;border-radius:26px!important;background:transparent!important;color:#5a4632!important;text-transform:none!important;letter-spacing:0!important;font-weight:500!important;font-size:14px!important;font-family:inherit!important;padding:11px!important;box-shadow:none!important}
.ykw .ykw-n:hover{background:#f3ebe0!important}
@media(prefers-reduced-motion:reduce){.ykw-bg,.ykw-box{transition:none}}
</style>
<script>
(function(){var m=document.getElementById('ykw');if(!m)return;var K='yk_why',now=Date.now(),st={};
try{st=JSON.parse(localStorage.getItem(K)||'{}')||{};}catch(e){}
if(st.until&&st.until>now)return;
function save(days){st.until=Date.now()+days*864e5;try{localStorage.setItem(K,JSON.stringify(st));}catch(e){}}
function hide(){m.classList.remove('on');setTimeout(function(){m.hidden=true;},350);document.removeEventListener('keydown',esc);}
function esc(e){if(e.key==='Escape')hide();}
function show(){if(document.querySelector('.ykm.on'))return;m.hidden=false;requestAnimationFrame(function(){m.classList.add('on');});save(7);try{sessionStorage.setItem('yk_why_seen','1');}catch(e){}document.addEventListener('keydown',esc);}
m.addEventListener('click',function(e){if(e.target.closest('[data-x]'))hide();if(e.target.closest('[data-go]'))setTimeout(hide,300);});
setTimeout(show,1500);
})();
</script>
	<?php
}, 30 );
