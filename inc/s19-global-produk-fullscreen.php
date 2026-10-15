<?php
// Dari Code Snippets #19 "Produk: foto layar penuh + zoom + caption + Next (Yulika)" — scope: global
/* ===== Yulika: halaman produk = foto 1 layar penuh (bisa di-zoom) + caption maks 10 kata + tombol Next ke checkout ===== */

/* Caption: meta yk_caption (dibuat AI, bisa diedit di produk). Kosong = dibuat otomatis dari kategori (tema + bunga utama). */
function yk_pv_caption( $p ) {
	$c = trim( (string) get_post_meta( $p->get_id(), 'yk_caption', true ) );
	if ( '' !== $c ) {
		return $c;
	}
	$slugs = wp_get_post_terms( $p->get_id(), 'product_cat', array( 'fields' => 'slugs' ) );
	$slugs = is_wp_error( $slugs ) ? array() : $slugs;
	$theme = 'Beautiful';
	foreach ( array( 'love-anniversary' => 'Romantic', 'sympathy-condolences' => 'Serene', 'wedding-engagement' => 'Elegant', 'lebaran' => 'Festive Eid', 'chinese-new-year' => 'Lucky', 'christmas' => 'Festive', 'graduation' => 'Celebratory', 'new-baby-get-well' => 'Cheerful', 'birthday' => 'Cheerful' ) as $s => $w ) {
		if ( in_array( $s, $slugs, true ) ) {
			$theme = $w;
			break;
		}
	}
	$type = 'arrangement';
	foreach ( array( 'bouquet' => 'bouquet', 'flower-boxes-baskets' => 'flower box', 'hampers' => 'gift hamper', 'table-arrangements' => 'arrangement' ) as $s => $w ) {
		if ( in_array( $s, $slugs, true ) ) {
			$type = $w;
			break;
		}
	}
	$fl = array();
	foreach ( array( 'rose' => 'roses', 'lily' => 'lilies', 'tulips' => 'tulips', 'orchid' => 'orchids', 'carnation' => 'carnations', 'sunflower' => 'sunflowers', 'gerbera' => 'gerberas', 'aster' => 'asters' ) as $s => $w ) {
		if ( in_array( $s, $slugs, true ) && count( $fl ) < 2 ) {
			$fl[] = $w;
		}
	}
	return $theme . ' ' . $type . ( $fl ? ' of ' . implode( ' and ', $fl ) : '' );
}

/* Field caption di halaman edit produk (tab General) */
add_action( 'woocommerce_product_options_general_product_data', function () {
	woocommerce_wp_text_input( array(
		'id'          => 'yk_caption',
		'label'       => 'Caption (maks 10 kata)',
		'description' => 'Tampil di bawah foto produk. Isi tema rangkaian + bunga utama. Kosong = dibuat otomatis dari kategori.',
		'desc_tip'    => true,
	) );
} );
add_action( 'woocommerce_admin_process_product_object', function ( $p ) {
	if ( isset( $_POST['yk_caption'] ) ) { // phpcs:ignore
		$words = preg_split( '/\s+/', trim( sanitize_text_field( wp_unslash( $_POST['yk_caption'] ) ) ) ); // phpcs:ignore
		$p->update_meta_data( 'yk_caption', implode( ' ', array_slice( array_filter( $words ), 0, 10 ) ) );
	}
} );

if ( ! function_exists( 'yk_is_soon' ) ) {
	function yk_is_soon( $p ) {
		return $p && $p->is_type( 'simple' ) && '' === (string) $p->get_price() && has_term( 'coming-soon', 'product_cat', $p->get_id() );
	}
}
function yk_pv_can_show( $p ) {
	return $p && ( ( function_exists( 'yk_can_buy' ) && yk_can_buy( $p ) ) || yk_is_soon( $p ) );
}

/* Title & meta halaman produk */
add_filter( 'pre_get_document_title', function ( $t ) {
	if ( function_exists( 'is_product' ) && is_product() ) {
		$p = wc_get_product( get_queried_object_id() );
		if ( yk_pv_can_show( $p ) ) {
			return get_the_title( $p->get_id() ) . ' – ' . yk_pv_caption( $p ) . ' | Yulika Florist';
		}
	}
	return $t;
}, 100 );

/* Render halaman produk khusus (menggantikan template produk lama) */
add_action( 'template_redirect', function () {
	if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() || isset( $_GET['yk_buy'] ) ) { // phpcs:ignore
		return;
	}
	$pid = (int) get_queried_object_id();
	$p   = wc_get_product( $pid );
	if ( ! yk_pv_can_show( $p ) ) {
		return; // produk tanpa harga / habis / varian: template lama
	}
	global $post;
	$post = get_post( $pid ); // phpcs:ignore
	setup_postdata( $post );

	$name    = get_the_title( $pid );
	$cap     = yk_pv_caption( $p );
	$soon    = yk_is_soon( $p );
	$price   = $soon ? 'Price coming soon' : 'Rp' . number_format( (float) $p->get_price(), 0, '.', ',' );
	$reg     = (float) $p->get_regular_price();
	$was     = ( $p->is_on_sale() && $reg > (float) $p->get_price() ) ? 'Rp' . number_format( $reg, 0, '.', ',' ) : '';
	$buy     = yk_buy_url( $pid );
	$img_ids = array_values( array_filter( array_merge( array( (int) $p->get_image_id() ), array_map( 'intval', $p->get_gallery_image_ids() ) ) ) );

	add_action( 'wp_head', function () use ( $p, $name, $cap, $price, $img_ids ) {
		$full = $img_ids ? wp_get_attachment_image_url( $img_ids[0], 'full' ) : '';
		$desc = $cap . '. ' . $price . ' · Same-day delivery across Jakarta & Bekasi · Yulika Florist.';
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
		echo '<meta property="og:type" content="product"><meta property="og:site_name" content="Yulika Florist">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $name . ' – ' . $cap ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
		echo '<meta property="og:url" content="' . esc_url( get_permalink( $p->get_id() ) ) . '">' . "\n";
		if ( $full ) {
			echo '<meta property="og:image" content="' . esc_url( $full ) . '">' . "\n";
			echo '<link rel="preload" as="image" href="' . esc_url( wp_get_attachment_image_url( $img_ids[0], 'large' ) ) . '">' . "\n";
		}
		$ld = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'Product',
			'name'        => wp_strip_all_tags( $name ),
			'description' => $cap,
			'image'       => $full,
			'sku'         => (string) $p->get_id(),
			'brand'       => array( '@type' => 'Brand', 'name' => 'Yulika Florist' ),
			'offers'      => array(
				'@type'         => 'Offer',
				'url'           => get_permalink( $p->get_id() ),
				'priceCurrency' => 'IDR',
				'price'         => (string) (float) $p->get_price(),
				'availability'  => 'https://schema.org/InStock',
				'seller'        => array( '@type' => 'Organization', 'name' => 'Yulika Florist' ),
			),
		);
		if ( yk_is_soon( $p ) ) {
			unset( $ld['offers'] );
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
		echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/photoswipe@5.4.4/dist/photoswipe.css">' . "\n";
	}, 3 );

	get_header();
	?>
<style id="ykp-css">
body.ykp-page .yk-sbar,body.ykp-page .elementor-location-footer,body.ykp-page footer.main-footer,body.ykp-page #scroll-to-top{display:none!important}
.ykp{max-width:1180px;margin:0 auto;padding:0 0 14px;font-family:Montserrat,Arial,sans-serif;color:#222}
.ykp-gal{position:relative;background:#f4ede4}
.ykp-gal a.ykp-z{display:block;cursor:zoom-in}
.ykp-gal a.ykp-z+a.ykp-z{display:none}
.ykp-gal img{display:block;width:100%;height:auto;max-height:62svh;object-fit:contain;margin:0 auto!important;border-radius:0!important}
.ykp-hint{position:absolute;right:10px;top:10px;background:rgba(0,0,0,.62);color:#fff;font:600 13px/1 Montserrat,Arial,sans-serif;padding:9px 12px;border-radius:20px;pointer-events:none;display:flex;gap:6px;align-items:center}
.ykp-hint svg{width:15px;height:15px;stroke:#fff;fill:none;stroke-width:2.4}
.ykp-back{position:absolute;left:10px;top:10px;z-index:2;background:rgba(255,255,255,.92);color:#5a3d22!important;font:600 13px/1 Montserrat,Arial,sans-serif;padding:9px 12px;border-radius:20px;text-decoration:none!important}
.ykp-thumbs{display:flex;gap:8px;padding:8px 12px 0;overflow-x:auto}
.ykp-thumbs button{flex:0 0 52px;height:52px;padding:0;border:2px solid transparent;border-radius:8px;overflow:hidden;background:none;cursor:pointer}
.ykp-thumbs button.on{border-color:#a0784a}
.ykp-thumbs img{width:100%;height:100%;object-fit:cover;display:block}
.ykp-info{padding:12px 16px 0;text-align:center}
.ykp-info h1{font-family:'Sorts Mill Goudy',Georgia,serif!important;font-weight:400!important;font-size:25px!important;line-height:1.2!important;margin:0 0 4px!important;color:#222!important;text-transform:none!important;letter-spacing:0!important}
.ykp-cap{font-size:16px;line-height:1.45;color:#444;margin:0 0 6px}
.ykp-price{font-size:21px;font-weight:700;color:#8a5a2b;margin:0 0 8px}
.ykp-price s{font-size:15px;font-weight:500;color:#999;margin-right:8px}
.ykp-spec{font-size:13px;line-height:1.45;color:#666;margin:0 auto;max-width:520px;background:#f8f4ee;border-radius:8px;padding:8px 10px}
.ykp-spec b{color:#333}
.ykp-act{display:flex;gap:10px;align-items:stretch;padding:12px 16px 0}
.ykp-next{flex:1;display:flex;align-items:center;justify-content:center;gap:8px;background:#a0784a;color:#fff!important;font:700 19px/1 Montserrat,Arial,sans-serif;padding:17px 20px;border-radius:30px;text-decoration:none!important;box-shadow:0 3px 10px rgba(160,120,74,.35)}
.ykp-next:hover{background:#8a6338}
.ykp-wab{flex:0 0 56px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:#25d366;text-decoration:none!important}
.ykp-wab svg{width:28px;height:28px;fill:#fff}
@media(max-width:899px){body.ykp-page .yk-wa,body.ykp-page .yk-wa-tip{display:none!important}}
.pswp img,.pswp .pswp__img{max-width:none!important;max-height:none!important;border-radius:0!important;margin:0!important}
.pswp .pswp__button{background:none!important;border:0!important;box-shadow:none!important;padding:0!important;min-width:0!important}
.pswp .pswp__button:hover{background:none!important}
@media(min-width:900px){
 .ykp{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,.9fr);gap:40px;align-items:center;padding:20px 20px 24px}
 .ykp-gal{border-radius:12px;overflow:hidden}
 .ykp-info{text-align:left;padding:0}
 .ykp-info h1{font-size:40px!important}
 .ykp-cap{font-size:18px}
 .ykp-price{font-size:24px}
 .ykp-spec{margin:0;font-size:14px}
 .ykp-act{padding:22px 0 0;max-width:460px}
}
</style>
<main class="ykp" id="main">
	<div>
		<div class="ykp-gal" id="ykp-gal">
			<a class="ykp-back" href="<?php echo esc_url( home_url( '/' ) ); ?>" onclick="if(document.referrer.indexOf(location.host)>-1&&history.length>1){history.back();return false;}">&larr; Back</a>
			<?php
			foreach ( $img_ids as $i => $iid ) {
				$f = wp_get_attachment_image_src( $iid, 'full' );
				if ( ! $f ) {
					continue;
				}
				echo '<a class="ykp-z" href="' . esc_url( $f[0] ) . '" data-pswp-width="' . (int) $f[1] . '" data-pswp-height="' . (int) $f[2] . '" target="_blank" rel="noopener">';
				echo wp_get_attachment_image( $iid, 'large', false, array( 'alt' => wp_strip_all_tags( $name . ' – ' . $cap ), 'sizes' => '(max-width:899px) 100vw, 640px', 'loading' => 0 === $i ? 'eager' : 'lazy', 'fetchpriority' => 0 === $i ? 'high' : 'auto', 'decoding' => 'async' ) );
				echo '</a>';
			}
			?>
			<span class="ykp-hint"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4M11 8v6M8 11h6"/></svg>Tap to zoom</span>
		</div>
		<?php if ( count( $img_ids ) > 1 ) : ?>
		<div class="ykp-thumbs">
			<?php foreach ( $img_ids as $i => $iid ) : ?>
				<button type="button" data-i="<?php echo (int) $i; ?>"<?php echo 0 === $i ? ' class="on"' : ''; ?> aria-label="Photo <?php echo (int) $i + 1; ?>"><?php echo wp_get_attachment_image( $iid, 'thumbnail', false, array( 'alt' => '' ) ); ?></button>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>
	<div id="ykp-side">
		<div class="ykp-info">
			<h1><?php echo esc_html( $name ); ?></h1>
			<p class="ykp-cap"><?php echo esc_html( $cap ); ?></p>
			<p class="ykp-price"><?php echo $was ? '<s>' . esc_html( $was ) . '</s>' : ''; ?><?php echo esc_html( $price ); ?></p>
			<p class="ykp-spec"><b>Specifications:</b> paper/box type &amp; colour and filler flowers may vary with availability; main flowers and style stay the same.</p>
		</div>
		<div class="ykp-act">
			<?php if ( $soon ) : ?><a class="ykp-next" href="<?php echo esc_attr( 'https://wa.me/6281315881538?text=' . rawurlencode( 'Hi Yulika Florist, I would like to order / ask the price of ' . wp_strip_all_tags( $name ) . ': ' . get_permalink( $pid ) ) ); ?>" target="_blank" rel="noopener nofollow">Ask Price <span aria-hidden="true">&rarr;</span></a><?php else : ?><a class="ykp-next" href="<?php echo esc_url( $buy ); ?>" rel="nofollow">Next <span aria-hidden="true">&rarr;</span></a><?php endif; ?>
			<a class="ykp-wab" href="<?php echo esc_attr( 'https://wa.me/6281315881538?text=' . rawurlencode( 'Hi Yulika Florist, I would like to ask about ' . wp_strip_all_tags( $name ) . ': ' . get_permalink( $pid ) ) ); ?>" target="_blank" rel="noopener" aria-label="Ask on WhatsApp"><svg viewBox="0 0 32 32"><path d="M16 3a13 13 0 0 0-11.2 19.6L3 29l6.6-1.7A13 13 0 1 0 16 3zm0 23.7a10.7 10.7 0 0 1-5.5-1.5l-.4-.2-3.9 1 1-3.8-.3-.4A10.7 10.7 0 1 1 16 26.7zm5.9-8c-.3-.2-1.9-.9-2.2-1s-.5-.2-.7.2-.8 1-1 1.2-.4.2-.7.1a8.8 8.8 0 0 1-4.4-3.8c-.3-.6.3-.5 1-1.7.1-.2 0-.4 0-.6l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6a1.2 1.2 0 0 0-.9.4 3.6 3.6 0 0 0-1.1 2.7 6.3 6.3 0 0 0 1.3 3.3 14.4 14.4 0 0 0 5.5 4.9c2 .9 2.8.9 3.8.8a3.3 3.3 0 0 0 2.2-1.5 2.7 2.7 0 0 0 .2-1.5c-.1-.1-.3-.2-.7-.4z"/></svg></a>
		</div>
	</div>
</main>
<script>
document.body.classList.add('ykp-page');
(function(){
 var g=document.getElementById('ykp-gal');if(!g)return;
 var links=[].slice.call(g.querySelectorAll('a.ykp-z'));
 function show(i){links.forEach(function(a,k){a.style.display=k===i?'block':'none'});
  [].forEach.call(document.querySelectorAll('.ykp-thumbs button'),function(b,k){b.classList.toggle('on',k===i)});fit()}
 [].forEach.call(document.querySelectorAll('.ykp-thumbs button'),function(b){b.addEventListener('click',function(){show(+b.getAttribute('data-i'))})});
 /* Foto mengisi sisa layar: foto + nama + caption + harga + spesifikasi + tombol Next terlihat tanpa scroll */
 function fit(){
  var vh=window.innerHeight, top=g.getBoundingClientRect().top+window.pageYOffset, imgs=g.querySelectorAll('img'), side=document.getElementById('ykp-side'), th=document.querySelector('.ykp-thumbs');
  var avail;
  if(window.innerWidth<900){avail=vh-top-side.offsetHeight-(th?th.offsetHeight:0)-12;}
  else{avail=vh-top-24;}
  avail=Math.max(260,Math.round(avail));
  [].forEach.call(imgs,function(im){im.style.maxHeight=avail+'px'});
 }
 fit();window.addEventListener('resize',fit);window.addEventListener('load',fit);setTimeout(fit,600);
})();
</script>
<script type="module">
import PhotoSwipeLightbox from 'https://cdn.jsdelivr.net/npm/photoswipe@5.4.4/dist/photoswipe-lightbox.esm.min.js';
const lb = new PhotoSwipeLightbox({
	gallery: '#ykp-gal',
	children: 'a.ykp-z',
	pswpModule: () => import('https://cdn.jsdelivr.net/npm/photoswipe@5.4.4/dist/photoswipe.esm.min.js'),
	initialZoomLevel: 'fit',
	secondaryZoomLevel: 1,
	maxZoomLevel: 2.5,
	bgOpacity: 1,
	wheelToZoom: true,
	imageClickAction: 'zoom',
	doubleTapAction: 'zoom'
});
lb.init();
</script>
	<?php
	get_footer();
	wp_reset_postdata();
	exit;
}, 20 );
