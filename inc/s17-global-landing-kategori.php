<?php
// Dari Code Snippets #17 "Homepage: landing kategori 4 grup (Yulika)" — scope: global
/* ===== Yulika: Landing page = kategori (Occasion, Arrangement, Budget, Flower) — 2 kolom di HP, huruf besar ===== */

function yk_lp_groups() {
	return array(
		array( 'occasion', 'Shop by Occasion', 'Flowers for every moment that matters', 'yk-c5', array( 'birthday', 'love-anniversary', 'graduation', 'sympathy-condolences', 'congratulations', 'new-baby-get-well', 'wedding-engagement', 'lebaran', 'chinese-new-year', 'christmas' ) ),
		array( 'arrangement', 'Shop by Arrangement', 'Bouquets, centrepieces, boards and gift sets', 'yk-c6', array( 'bouquet', 'table-arrangements', 'flower-boards', 'standing-flowers', 'flower-boxes-baskets', 'hampers' ) ),
		array( 'budget', 'Shop by Budget', 'Beautiful choices for every budget', 'yk-c4', array( 'budget-dibawah-500-ribu', 'budget-500-ribu-1-juta', 'budget-1-2-juta', 'budget-2-juta-keatas' ) ),
		array( 'flower', 'Shop by Flower', 'Choose your favourite bloom', 'yk-c6', array( 'rose', 'lily', 'carnation', 'orchid', 'tulips', 'sunflower' ) ),
	);
}

/* Data tiap kategori: jumlah produk, harga termurah, foto (produk terlaris yang belum dipakai di grup yang sama). Cache 6 jam. */
function yk_lp_data() {
	$data = get_transient( 'yk_lp_data_v2' );
	if ( is_array( $data ) ) {
		return $data;
	}
	$data = array();
	foreach ( yk_lp_groups() as $g ) {
		$used = array();
		foreach ( $g[4] as $slug ) {
			$t = get_term_by( 'slug', $slug, 'product_cat' );
			if ( ! $t ) {
				continue;
			}
			$ids = get_posts( array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => 'total_sales',
				'orderby'        => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ),
				'tax_query'      => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => (int) $t->term_id, 'include_children' => true ) ),
				'meta_query'     => array( array( 'key' => '_price', 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC' ) ),
			) );
			$min = 0;
			$img = 0;
			foreach ( $ids as $pid ) {
				$pr = (float) get_post_meta( $pid, '_price', true );
				if ( $pr > 0 && ( ! $min || $pr < $min ) ) {
					$min = $pr;
				}
			}
			/* foto: utamakan produk tanpa dekorasi Lebaran (kecuali tile Eid & Hampers) */
			$eid_ok = in_array( $slug, array( 'lebaran', 'hampers' ), true );
			foreach ( array( 1, 2 ) as $pass ) {
				foreach ( $ids as $pid ) {
					$th = (int) get_post_thumbnail_id( $pid );
					if ( ! $th || in_array( $pid, $used, true ) ) {
						continue;
					}
					if ( 1 === $pass && ! $eid_ok && has_term( 'lebaran', 'product_cat', $pid ) ) {
						continue;
					}
					$img    = $th;
					$used[] = $pid;
					break 2;
				}
			}
			if ( ! $img && $ids ) {
				$img = (int) get_post_thumbnail_id( $ids[0] );
			}
			$data[ $slug ] = array(
				'name'  => $t->name,
				'link'  => get_term_link( $t ),
				'count' => count( $ids ),
				'min'   => $min,
				'img'   => $img,
			);
		}
	}
	set_transient( 'yk_lp_data_v2', $data, 6 * HOUR_IN_SECONDS );
	return $data;
}

function yk_lp_flush() {
	delete_transient( 'yk_lp_data_v2' );
}
add_action( 'woocommerce_update_product', 'yk_lp_flush' );
add_action( 'woocommerce_new_product', 'yk_lp_flush' );
add_action( 'edited_product_cat', 'yk_lp_flush' );
add_action( 'created_product_cat', 'yk_lp_flush' );
add_action( 'trashed_post', 'yk_lp_flush' );

function yk_lp_html() {
	$data = yk_lp_data();
	$wa   = 'https://wa.me/6281315881538?text=';
	ob_start();
	?>
<style id="yk-lp-css">
.yk-lp{max-width:1240px;margin:0 auto;padding:18px 12px 6px;font-family:Montserrat,Arial,sans-serif}
.yk-lp .yk-jump{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin:0 0 6px}
@media(min-width:768px){.yk-lp .yk-jump{display:flex;justify-content:center}}
.yk-lp .yk-jump a{display:block;text-align:center;padding:10px 16px;border:1.5px solid #a0784a;border-radius:24px;color:#7a5530!important;font:600 15px Montserrat,Arial,sans-serif;text-decoration:none!important;background:#fff}
.yk-lp .yk-jump a:hover{background:#a0784a;color:#fff!important}
.yk-lp .yk-jump a.yk-jump-pj{grid-column:1/-1}
.yk-lp .yk-grp{padding:22px 0 6px;scroll-margin-top:90px}
.yk-lp .yk-grp h2{font-family:'Sorts Mill Goudy',Georgia,serif!important;font-weight:400!important;font-size:28px!important;line-height:1.2!important;color:#222!important;text-align:center;margin:0 0 4px!important;text-transform:none!important;letter-spacing:0!important}
.yk-lp .yk-grp>p{text-align:center;font-size:15px;line-height:1.5;color:#555;margin:0 0 16px}
.yk-lp .yk-g{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.yk-lp a.yk-t{display:flex;flex-direction:column;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 1px 5px rgba(0,0,0,.10);text-decoration:none!important;color:#222!important;transition:box-shadow .15s}
.yk-lp a.yk-t:hover{box-shadow:0 4px 14px rgba(0,0,0,.16)}
.yk-lp .yk-ti{display:block;aspect-ratio:1/1;background:#f4ede4;overflow:hidden}
.yk-lp .yk-ti img{width:100%!important;height:100%!important;object-fit:cover;object-position:center 35%;display:block;margin:0!important;border-radius:0!important}
.yk-lp .yk-ti.yk-empty{display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#f6efe6,#ead9c6)}
.yk-lp .yk-ti.yk-empty svg{width:40%;height:auto;display:block}
.yk-lp .yk-tn{flex:1;display:flex;align-items:center;justify-content:center;padding:12px 8px 13px;text-align:center;font-weight:700;font-size:16px;line-height:1.3;color:#222}
.yk-lp .yk-tp{display:block;padding:0 8px 13px;text-align:center;font-weight:600;font-size:15px;line-height:1.35;color:#8a5a2b}
.yk-lp .yk-tp.yk-tpwa{color:#1e8e4e}
@media(min-width:768px){
 .yk-lp{padding-top:26px}
 .yk-lp .yk-grp h2{font-size:34px!important}
 .yk-lp .yk-g{grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
 .yk-lp .yk-g.yk-c4{grid-template-columns:repeat(4,minmax(0,1fr))}
 .yk-lp .yk-tn{font-size:17px}
}
@media(min-width:1100px){
 .yk-lp .yk-g.yk-c5{grid-template-columns:repeat(5,minmax(0,1fr))}
 .yk-lp .yk-g.yk-c6{grid-template-columns:repeat(6,minmax(0,1fr))}
}
</style>
<div class="yk-lp" id="shop-categories">
	<nav class="yk-jump" aria-label="Shop by">
		<a href="#occasion">Occasion</a><a href="#arrangement">Arrangement</a><a href="#budget">Budget</a><a href="#flower">Flower</a><a class="yk-jump-pj" href="#event-interior-projects">Event &amp; Interior Projects</a>
	</nav>
	<?php
	$n = 0;
	foreach ( yk_lp_groups() as $g ) {
		echo '<section class="yk-grp" id="' . esc_attr( $g[0] ) . '"><h2>' . esc_html( $g[1] ) . '</h2><p>' . esc_html( $g[2] ) . '</p><div class="yk-g ' . esc_attr( $g[3] ) . '">';
		foreach ( $g[4] as $slug ) {
			if ( empty( $data[ $slug ] ) ) {
				continue;
			}
			$d = $data[ $slug ];
			$n++;
			if ( $d['count'] > 0 && ! is_wp_error( $d['link'] ) ) {
				$img = $d['img'] ? wp_get_attachment_image( $d['img'], 'woocommerce_thumbnail', false, array( 'alt' => $d['name'], 'loading' => $n <= 10 ? 'eager' : 'lazy', 'fetchpriority' => $n <= 3 ? 'high' : 'auto', 'decoding' => $n <= 10 ? 'sync' : 'async', 'sizes' => '(max-width:767px) 50vw, 260px' ) ) : '';
				/* 16 Okt 2026: tile hanya nama kategori (tanpa harga / jumlah desain), atas permintaan pemilik */
				echo '<a class="yk-t" href="' . esc_url( $d['link'] ) . '"><span class="yk-ti' . ( $img ? '' : ' yk-empty' ) . '">' . ( $img ? $img : '<svg viewBox="0 0 64 64" aria-hidden="true"><g fill="#dcb994"><circle cx="32" cy="16" r="10"/><circle cx="47" cy="27" r="10"/><circle cx="41" cy="45" r="10"/><circle cx="23" cy="45" r="10"/><circle cx="17" cy="27" r="10"/></g><circle cx="32" cy="32" r="8" fill="#a0784a"/></svg>' ) . '</span><span class="yk-tn">' . esc_html( $d['name'] ) . '</span></a>'; // phpcs:ignore
			} else {
				$msg = function_exists( 'yk_wa_text' ) ? yk_wa_text( 'ingin memesan *' . html_entity_decode( $d['name'], ENT_QUOTES, 'UTF-8' ) . '*' ) : 'Halo Admin';
				echo '<a class="yk-t" href="' . esc_attr( $wa . rawurlencode( $msg ) ) . '" target="_blank" rel="noopener"><span class="yk-ti yk-empty"><svg viewBox="0 0 64 64" aria-hidden="true"><g fill="#dcb994"><circle cx="32" cy="16" r="10"/><circle cx="47" cy="27" r="10"/><circle cx="41" cy="45" r="10"/><circle cx="23" cy="45" r="10"/><circle cx="17" cy="27" r="10"/></g><circle cx="32" cy="32" r="8" fill="#a0784a"/></svg></span><span class="yk-tn">' . esc_html( $d['name'] ) . '</span><span class="yk-tp yk-tpwa">Custom order via WhatsApp</span></a>';
			}
		}
		echo '</div></section>';
	}
	?>
</div>
	<?php
	return ob_get_clean();
}

/* Section kategori & budget lama (widget HTML) disembunyikan */
function yk_lp_hide( $should, $el ) {
	if ( is_front_page() && in_array( $el->get_id(), array( '9fc92b8', '71e9486' ), true ) ) {
		return false;
	}
	return $should;
}
add_filter( 'elementor/frontend/section/should_render', 'yk_lp_hide', 10, 2 );
add_filter( 'elementor/frontend/container/should_render', 'yk_lp_hide', 10, 2 );

/* Kategori baru tampil paling atas (sebelum banner "Tell Your Stories with Flowers") */
function yk_lp_insert( $el ) {
	static $done = false;
	if ( ! $done && is_front_page() && 'b58e694' === $el->get_id() ) {
		$done = true;
		echo yk_lp_html(); // phpcs:ignore
	}
}
add_action( 'elementor/frontend/section/before_render', 'yk_lp_insert' );
add_action( 'elementor/frontend/container/before_render', 'yk_lp_insert' );

/* Nama produk di daftar produk lebih bersih: buang "• mostly imported flowers ✅", "( Imported Rose ✅ )", dll. (nama di order/cart tidak berubah) */
add_filter( 'the_title', function ( $title, $id = 0 ) {
	if ( is_admin() || ! $id || 'product' !== get_post_type( $id ) ) {
		return $title;
	}
	if ( ( function_exists( 'is_cart' ) && is_cart() ) || ( function_exists( 'is_checkout' ) && is_checkout() ) || ( function_exists( 'is_account_page' ) && is_account_page() ) ) {
		return $title;
	}
	$t = str_replace( array( '✅', '&#x2705;', '&#9989;' ), '', $title );
	$t = preg_replace( '/\s*(•|&#8226;|&bull;).*$/u', '', $t );
	$t = preg_replace( '/\s*\(([^)]*(import|vg)[^)]*)\)/iu', '', $t );
	$t = preg_replace( '/\s+(mix|mostly|all)\s+import(ed)?(\s+flowers)?\s*$/iu', '', $t );
	$t = trim( preg_replace( '/\s{2,}/u', ' ', $t ) );
	return '' === $t ? $title : $t;
}, 20, 2 );

/* Halaman kategori: nama & harga produk lebih besar dan kontras (mudah dibaca) */
add_action( 'wp_head', function () {
	if ( ! ( function_exists( 'is_product_taxonomy' ) && ( is_product_taxonomy() || is_shop() || is_search() ) ) ) {
		return;
	}
	echo '<style id="yk-arch-read">
body .woocommerce ul.products li.product .woocommerce-loop-product__title{font-size:17px!important;line-height:1.3!important;color:#222!important}
body .woocommerce ul.products li.product .price,body .woocommerce ul.products li.product .price .amount{font-size:16px!important;font-weight:700!important;color:#8a5a2b!important}
body .woocommerce ul.products li.product .woocommerce-loop-product__title img.emoji{display:none!important}
@media(min-width:768px){body .woocommerce ul.products li.product .woocommerce-loop-product__title{font-size:18px!important}}
</style>';
}, 99 );
