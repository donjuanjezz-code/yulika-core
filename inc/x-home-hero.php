<?php
/**
 * Homepage hero: slide foto terbaik per kategori (ganti tiap 1,8 detik) + pesan utama + deret angka + paragraf perkenalan.
 * Tayang untuk semua pengunjung bila YK_HERO_LIVE = true. Selama false, hanya tampil di /?yk_hero=1 (pratinjau).
 * Cara mengembalikan: hapus file ini.
 */
if ( ! defined( 'YK_HERO_LIVE' ) ) {
	define( 'YK_HERO_LIVE', true );
}

function yk_hero_on() {
	if ( is_admin() || ! is_front_page() ) {
		return false;
	}
	return YK_HERO_LIVE || isset( $_GET['yk_hero'] ); // phpcs:ignore
}

/* Daftar slide: [ sumber gambar, label kategori, tautan, kelas tambahan ]. Sumber = slug lampiran, atau "cat:slug-kategori" (foto produk terbaru). */
function yk_hero_slides() {
	$bq = '/product-category/arrangements/bouquet/';
	$pj = '/#event-interior-projects';
	return array(
		array( 'anniversary-luxury-white-rose-idr4225', 'Bouquets', $bq, '' ),
		array( 'birthday-elegant-pink-rose-idr2350', 'Flower Boxes', $bq, '' ),
		array( 'congratulation-elegant-peach-rose-idr2775', 'Table Flowers', '/collections/', '' ),
		array( 'grand-opening-festive-red-rose-idr1995', 'Standing Flowers', '/collections/', '' ),
		array( 'papan-bunga-congratulations-grand-opening-140x200-idr1000', 'Flower Boards', '/product-category/arrangements/flower-boards/', 'fit' ),
		array( 'cat:hampers', 'Hampers & Gift Sets', '/product-category/arrangements/hampers/', '' ),
		array( 'dekorasi-pelaminan-flower-wall-pink', 'Wedding Décor', $pj, '' ),
		array( 'dekorasi-resepsi-ballroom-bunga-pink', 'Event Décor', $pj, '' ),
		array( 'vendor-dekor-instalasi-bunga-mall-grand-indonesia-2', 'Mall & Exhibition', $pj, '' ),
		array( 'dekorasi-plafon-bunga-wisteria-artificial-pan-n-co', 'Interior Greenery', $pj, '' ),
	);
}

function yk_hero_image_id( $src ) {
	if ( 0 === strpos( $src, 'cat:' ) ) {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return 0;
		}
		$p = wc_get_products( array( 'status' => 'publish', 'limit' => 6, 'category' => array( substr( $src, 4 ) ), 'orderby' => 'date', 'order' => 'DESC' ) );
		foreach ( (array) $p as $prod ) {
			if ( $prod->get_image_id() && (float) $prod->get_price() > 0 ) {
				return (int) $prod->get_image_id();
			}
		}
		return 0;
	}
	$a = get_page_by_path( $src, OBJECT, 'attachment' );
	return $a ? (int) $a->ID : 0;
}

function yk_hero_data() {
	$d = get_transient( 'yk_hero_v1' );
	if ( is_array( $d ) ) {
		return $d;
	}
	$d = array();
	foreach ( yk_hero_slides() as $s ) {
		$id  = yk_hero_image_id( $s[0] );
		$img = $id ? wp_get_attachment_image_src( $id, 'large' ) : false;
		if ( ! $img ) {
			continue;
		}
		$d[] = array(
			'src'    => $img[0],
			'w'      => (int) $img[1],
			'h'      => (int) $img[2],
			'srcset' => (string) wp_get_attachment_image_srcset( $id, 'large' ),
			'alt'    => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
			'label'  => $s[1],
			'url'    => home_url( $s[2] ),
			'cls'    => $s[3],
		);
	}
	set_transient( 'yk_hero_v1', $d, 6 * HOUR_IN_SECONDS );
	return $d;
}

function yk_hero_html() {
	$slides = yk_hero_data();
	if ( count( $slides ) < 2 ) {
		return '';
	}
	$n     = count( $slides );
	$sizes = '(min-width:900px) 54vw, 100vw';
	ob_start();
	?>
<style id="yk-hero-css">
.ykh{position:relative;clear:both;background:#fbf7f3;color:#2f3337;font-family:"Plus Jakarta Sans",Montserrat,Arial,sans-serif;overflow:hidden;margin:0}
.ykh *{box-sizing:border-box}
.ykh-in{position:relative}
.ykh-media{position:relative;height:112vw;max-height:74vh;min-height:400px;background:#efe6da;overflow:hidden}
.ykh-s{position:absolute;left:0;top:0;width:100%;height:100%;opacity:0;transition:opacity .9s ease}
.ykh-s.on{opacity:1}
.ykh-s img{width:100%!important;height:100%!important;max-width:none!important;object-fit:cover;object-position:center 32%;display:block;transform:scale(1.05);transition:transform 2.7s ease-out}
.ykh-s.on img{transform:scale(1)}
.ykh-s.fit{background:#fff}
.ykh-s.fit img{object-fit:contain;object-position:center 20%;padding:56px 12px 0}
.ykh-shade{position:absolute;left:0;top:0;width:100%;height:100%;background:linear-gradient(180deg,rgba(24,16,10,.18) 0%,rgba(24,16,10,0) 26%,rgba(24,16,10,.5) 58%,rgba(24,16,10,.9) 100%);pointer-events:none}
.ykh-cap{position:absolute;left:12px;top:12px;display:inline-flex;align-items:center;gap:8px;background:rgba(29,29,31,.78);color:#fff!important;border-radius:999px;padding:8px 14px;font-size:13px;font-weight:600;line-height:1.2;text-decoration:none!important}
.ykh-cn{position:absolute;right:12px;top:12px;background:rgba(29,29,31,.78);color:#fff;border-radius:999px;padding:8px 12px;font-size:12px;font-weight:600;line-height:1.2;letter-spacing:.06em}
.ykh-bar{position:absolute;left:0;bottom:0;height:3px;width:100%;background:#d9b987;transform-origin:left center;animation:ykhbar 1.8s linear infinite}
@keyframes ykhbar{from{transform:scaleX(0)}to{transform:scaleX(1)}}
.ykh-copy{position:absolute;left:0;right:0;bottom:0;padding:18px 16px 20px;color:#fff}
.ykh-eye{font-size:11.5px!important;font-weight:700;letter-spacing:.16em;text-transform:uppercase;line-height:1.3;margin:0 0 8px!important;color:#f3e2d8}
.ykh-h{font-family:"Sorts Mill Goudy",Georgia,serif!important;font-weight:400!important;font-size:30px!important;line-height:1.12!important;letter-spacing:0!important;text-transform:none!important;margin:0 0 8px!important;color:#fff!important}
.ykh-sub{font-size:15px!important;line-height:1.5!important;margin:0 0 14px!important;color:#fff;max-width:540px}
.ykh-btns{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
.ykh-b{display:inline-block;padding:13px 22px;border-radius:999px;font-weight:700;font-size:14px;letter-spacing:.03em;line-height:1.2;text-decoration:none!important}
.ykh-b.p{background:#8a5a2b;color:#fff!important}
.ykh-b.t{padding:6px 0;border-radius:0;font-weight:600;color:#fff!important;border-bottom:1.5px solid rgba(255,255,255,.85);margin-left:6px}
.ykh-st{display:grid;grid-template-columns:1fr 1fr;background:#fff;border-bottom:1px solid #efe3d8}
.ykh-st div{padding:18px 12px 17px;text-align:center}
.ykh-st div:nth-child(odd){border-right:1px solid #efe3d8}
.ykh-st div:nth-child(-n+2){border-bottom:1px solid #efe3d8}
.ykh-st b{display:block;font-weight:500;font-size:30px;line-height:1.1;letter-spacing:-.01em;color:#8a5a2b}
.ykh-st span{display:block;font-size:13.5px;line-height:1.4;color:#555;margin-top:5px}
.ykh-ab{background:#fbf7f3;padding:36px 22px 38px;text-align:center}
.ykh-ab .q{font-family:"Sorts Mill Goudy",Georgia,serif!important;font-size:22px!important;line-height:1.42!important;color:#222;max-width:900px;margin:0 auto!important}
.ykh-ab .r{display:block;width:44px;height:2px;background:#a0784a;margin:20px auto 16px}
.ykh-ab .v{font-size:14px!important;line-height:1.65!important;color:#555;margin:0!important}
.ykh-ab .v a{color:#8a5a2b!important;font-weight:600;text-decoration:none!important;border-bottom:1px solid #a0784a;white-space:nowrap}
@media(min-width:900px){
.ykh-in{display:grid;grid-template-columns:46% 54%;align-items:stretch}
.ykh-media{grid-column:2;grid-row:1;height:72vh;max-height:660px;min-height:480px}
.ykh-shade{display:none}
.ykh-cap{left:18px;top:18px;font-size:14px;padding:10px 16px}
.ykh-cn{right:18px;top:18px;font-size:13px;padding:10px 14px}
.ykh-copy{position:static;grid-column:1;grid-row:1;display:flex;flex-direction:column;justify-content:center;color:#2f3337;padding:40px 56px 40px 40px}
.ykh-eye{color:#8a5a2b;font-size:13px!important;margin:0 0 14px!important}
.ykh-h{color:#222!important;font-size:50px!important;margin:0 0 16px!important}
.ykh-sub{color:#444;font-size:17px!important;margin:0 0 24px!important}
.ykh-b{font-size:15px;padding:15px 26px}
.ykh-b.t{color:#8a5a2b!important;border-bottom-color:#a0784a;margin-left:10px}
.ykh-st{grid-template-columns:repeat(4,1fr)}
.ykh-st div{padding:30px 20px 28px;border-bottom:0!important;border-right:1px solid #efe3d8}
.ykh-st div:last-child{border-right:0}
.ykh-st b{font-size:44px}
.ykh-st span{font-size:15px;margin-top:8px}
.ykh-ab{padding:68px 40px 70px}
.ykh-ab .q{font-size:32px!important;line-height:1.38!important}
.ykh-ab .r{margin:28px auto 22px}
.ykh-ab .v{font-size:15.5px!important}
}
@media(min-width:1320px){.ykh-copy{padding-left:calc((100vw - 1240px)/2)}.ykh-h{font-size:56px!important}}
@media(prefers-reduced-motion:reduce){.ykh-s,.ykh-s img{transition:none}.ykh-s img{transform:none}.ykh-bar{display:none}}
</style>
<section class="ykh" id="yk-hero" aria-label="Yulika Florist">
<div class="ykh-in">
<div class="ykh-media">
	<?php foreach ( $slides as $i => $s ) : ?>
<div class="ykh-s<?php echo 0 === $i ? ' on' : ''; ?><?php echo $s['cls'] ? ' ' . esc_attr( $s['cls'] ) : ''; ?>" data-l="<?php echo esc_attr( $s['label'] ); ?>" data-u="<?php echo esc_url( $s['url'] ); ?>">
		<?php if ( 0 === $i ) : ?>
<img src="<?php echo esc_url( $s['src'] ); ?>" srcset="<?php echo esc_attr( $s['srcset'] ); ?>" sizes="<?php echo esc_attr( $sizes ); ?>" width="<?php echo (int) $s['w']; ?>" height="<?php echo (int) $s['h']; ?>" alt="<?php echo esc_attr( $s['alt'] ? $s['alt'] : $s['label'] . ' – Yulika Florist' ); ?>" fetchpriority="high" decoding="async" data-no-lazy="1" class="skip-lazy">
		<?php else : ?>
<img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="<?php echo esc_url( $s['src'] ); ?>" data-srcset="<?php echo esc_attr( $s['srcset'] ); ?>" sizes="<?php echo esc_attr( $sizes ); ?>" width="<?php echo (int) $s['w']; ?>" height="<?php echo (int) $s['h']; ?>" alt="<?php echo esc_attr( $s['alt'] ? $s['alt'] : $s['label'] . ' – Yulika Florist' ); ?>" decoding="async" data-no-lazy="1" class="skip-lazy">
		<?php endif; ?>
</div>
	<?php endforeach; ?>
<div class="ykh-shade"></div>
<a class="ykh-cap" href="<?php echo esc_url( $slides[0]['url'] ); ?>"><span class="ykh-cl"><?php echo esc_html( $slides[0]['label'] ); ?></span><span aria-hidden="true">&rarr;</span></a>
<span class="ykh-cn" aria-hidden="true">01 / <?php echo esc_html( sprintf( '%02d', $n ) ); ?></span>
<span class="ykh-bar" aria-hidden="true"></span>
</div>
<div class="ykh-copy">
<p class="ykh-eye">Yulika Florist &middot; Kelapa Gading, Jakarta</p>
<h2 class="ykh-h">The first florist in Kelapa Gading</h2>
<p class="ykh-sub">Flowers, hampers and event décor, made in-house and delivered the same day in Jakarta and parts of Bekasi.</p>
<div class="ykh-btns"><a class="ykh-b p" href="#latest_products">Shop Flowers</a><a class="ykh-b t" href="<?php echo esc_url( home_url( '/#event-interior-projects' ) ); ?>">Event &amp; Interior Projects &rarr;</a></div>
</div>
</div>
<div class="ykh-st">
<div><b>43</b><span>Event &amp; interior projects</span></div>
<div><b>100%</b><span>Made in-house</span></div>
<div><b>Same day</b><span>Delivery in Jakarta &amp; parts of Bekasi</span></div>
<div><b>7 days</b><span>Open every day, 08:00&ndash;19:00</span></div>
</div>
<div class="ykh-ab">
<p class="q">Yulika is the first florist in Kelapa Gading. Every bouquet, flower board, hamper and event d&eacute;cor is made by our own florists, with flowers from our garden in Puncak and direct importers.</p>
<span class="r" aria-hidden="true"></span>
<p class="v">Visit our store at Jl. Boulevard Raya, Kelapa Gading, Jakarta Utara. <a href="<?php echo esc_url( home_url( '/our-story/' ) ); ?>">Our story &rarr;</a></p>
</div>
</section>
<script id="yk-hero-js">
(function(){var r=document.getElementById('yk-hero');if(!r)return;var s=[].slice.call(r.querySelectorAll('.ykh-s')),cap=r.querySelector('.ykh-cap'),cl=r.querySelector('.ykh-cl'),cn=r.querySelector('.ykh-cn'),bar=r.querySelector('.ykh-bar'),i=0,t=null,vis=true,red=window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches;
function pad(x){return('0'+x).slice(-2)}
function ready(k){var im=s[k].querySelector('img');if(im.getAttribute('data-src')){if(im.getAttribute('data-srcset'))im.srcset=im.getAttribute('data-srcset');im.src=im.getAttribute('data-src');im.removeAttribute('data-src');im.removeAttribute('data-srcset')}return im.complete&&im.naturalWidth>1}
function show(k){s[i].classList.remove('on');i=k;s[i].classList.add('on');cl.textContent=s[i].getAttribute('data-l');cap.href=s[i].getAttribute('data-u');cn.textContent=pad(i+1)+' / '+pad(s.length);if(bar){bar.style.animation='none';void bar.offsetWidth;bar.style.animation=''}}
function tick(){if(!vis||document.hidden)return;var n=(i+1)%s.length;if(ready(n)){show(n);ready((n+1)%s.length)}}
function start(){if(t||s.length<2)return;if(red){if(bar)bar.style.display='none';return}ready(1);t=setInterval(tick,1800)}
if('IntersectionObserver'in window){new IntersectionObserver(function(e){vis=e[0].isIntersecting},{threshold:.15}).observe(r)}
if(document.readyState==='complete')start();else window.addEventListener('load',start);
})();
</script>
	<?php
	return ob_get_clean();
}

/* Sisipkan di awal konten halaman utama (di bawah header, pencarian dan pil kategori). */
add_filter( 'the_content', function ( $content ) {
	static $done = false;
	if ( $done || ! yk_hero_on() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$done = true;
	return yk_hero_html() . $content;
}, 25 );

/* Foto slide berubah bila lampiran atau produk hampers diganti: buang cache daftar slide. */
add_action( 'save_post_product', function () {
	delete_transient( 'yk_hero_v1' );
} );
