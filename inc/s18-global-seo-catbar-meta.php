<?php
// Dari Code Snippets #18 "SEO: bar kategori utama, title, meta & OG, intro kategori (Yulika)" — scope: global
/* ===== Yulika SEO: bar kategori utama (sitewide), title + meta description + Open Graph, intro kategori, redirect /page/N/ homepage ===== */

/* Urutan = prioritas SEO & sales (volume pencarian x niat beli) */
function yk_seo_main_cats() {
	return array( 'bouquet', 'flower-boards', 'hampers', 'love-anniversary', 'graduation', 'budget-dibawah-500-ribu' );
}

/* Harga termurah di kategori (cache 6 jam) */
function yk_seo_term_min( $term_id ) {
	$k = 'yk_tmin_' . (int) $term_id;
	$v = get_transient( $k );
	if ( false !== $v ) {
		return (float) $v;
	}
	$ids = get_posts( array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => '_price',
		'orderby'        => 'meta_value_num',
		'order'          => 'ASC',
		'tax_query'      => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => (int) $term_id, 'include_children' => true ) ),
		'meta_query'     => array( array( 'key' => '_price', 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC' ) ),
	) );
	$v = $ids ? (float) get_post_meta( $ids[0], '_price', true ) : 0;
	set_transient( $k, $v, 6 * HOUR_IN_SECONDS );
	return $v;
}

function yk_seo_rp( $n ) {
	return 'Rp' . number_format( (float) $n, 0, '.', ',' );
}

/* Teks SEO per kategori: [title, meta description, intro]. {min} = harga termurah */
function yk_seo_copy( $slug ) {
	$c = array(
		'bouquet'                 => array(
			'Flower Bouquets Jakarta – Same-Day Delivery | Yulika Florist',
			'Hand-tied flower bouquets (buket bunga) from {min}: roses, tulips, lilies & imported blooms. Same-day delivery across Jakarta & Bekasi. Order online or WhatsApp.',
			'Hand-tied flower bouquets (<em>buket bunga</em>) designed by our florists in Kelapa Gading — roses, tulips, lilies and imported blooms for birthdays, anniversaries and graduations. Same-day delivery across Jakarta and Bekasi, from {min}.',
		),
		'flower-boards'           => array(
			'Flower Boards Jakarta (Papan Bunga) | Yulika Florist',
			'Custom flower boards (papan bunga) for grand openings, congratulations, weddings & condolences. Made with your message, delivered across Jakarta & Bekasi.',
			'Flower boards (<em>papan bunga</em>) for grand openings, congratulations, weddings and condolences — custom-made with your message and delivered across Jakarta and Bekasi.',
		),
		'hampers'                 => array(
			'Hampers & Gift Sets Jakarta – Eid, Christmas & Corporate | Yulika Florist',
			'Curated hampers & gift sets from {min}: tea sets, artisan food, cookies & flowers for Eid, Christmas, Chinese New Year and corporate gifts. Jakarta delivery.',
			'Curated hampers and gift sets — tea sets, artisan food, cookies and flowers — for Eid, Christmas, Chinese New Year and corporate gifting. Delivered across Jakarta and Bekasi, from {min}.',
		),
		'love-anniversary'        => array(
			'Anniversary & Romantic Flowers Jakarta | Yulika Florist',
			'Romantic flowers for anniversaries & Valentine’s from {min}: red roses, pink bouquets and flower boxes with teddy bears or chocolates. Same-day delivery in Jakarta.',
			'Romantic flowers for anniversaries, Valentine’s and “just because” — red roses, pink bouquets and flower boxes, some with a teddy bear or chocolates. Same-day delivery across Jakarta, from {min}.',
		),
		'graduation'              => array(
			'Graduation Bouquets Jakarta (Buket Wisuda) | Yulika Florist',
			'Graduation bouquets (buket wisuda) from {min}: sunflowers, roses & tulips in signature wrapping. Order ahead for graduation day — delivery across Jakarta.',
			'Graduation bouquets (<em>buket wisuda</em>) to celebrate the big day — sunflowers, roses and tulips in our signature wrapping. Order ahead for graduation day; delivery across Jakarta and Bekasi, from {min}.',
		),
		'budget-dibawah-500-ribu' => array(
			'Flowers Under Rp500,000 – Affordable Bouquets Jakarta | Yulika Florist',
			'Beautiful flowers under Rp500,000 — affordable bouquets that still look premium, made with fresh & imported blooms. Same-day delivery across Jakarta & Bekasi.',
			'Beautiful flowers under Rp500,000 — affordable bouquets that still look premium, made with fresh and imported blooms. Same-day delivery across Jakarta and Bekasi.',
		),
	);
	return isset( $c[ $slug ] ) ? $c[ $slug ] : null;
}

/* Data SEO halaman saat ini: title, description, image */
function yk_seo_current() {
	static $cur = null;
	if ( null !== $cur ) {
		return $cur;
	}
	$cur = array();
	if ( is_front_page() ) {
		$cur = array(
			'title' => 'Yulika Florist | Flower Delivery Jakarta – Bouquets, Flower Boards & Hampers',
			'desc'  => 'Florist in Kelapa Gading, Jakarta: flower bouquets, flower boards (papan bunga), hampers & gift sets. Same-day delivery across Jakarta & Bekasi. Order online or WhatsApp.',
			'img'   => '',
		);
	} elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$t = get_queried_object();
		if ( $t && ! is_wp_error( $t ) ) {
			$min  = yk_seo_term_min( $t->term_id );
			$name = html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' );
			$copy = yk_seo_copy( $t->slug );
			if ( $copy ) {
				$title = $copy[0];
				$desc  = $copy[1];
			} else {
				$title = $name . ' – Flower Delivery Jakarta | Yulika Florist';
				$desc  = $name . ' at Yulika Florist' . ( $min ? ' from {min}' : '' ) . ': fresh flower arrangements made in Kelapa Gading. Same-day delivery across Jakarta & Bekasi.';
			}
			$desc = str_replace( '{min}', yk_seo_rp( $min ), $desc );
			$img  = '';
			$p    = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => 'total_sales', 'orderby' => 'meta_value_num', 'order' => 'DESC', 'tax_query' => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => (int) $t->term_id ) ) ) );
			if ( $p && has_post_thumbnail( $p[0] ) ) {
				$img = get_the_post_thumbnail_url( $p[0], 'large' );
			}
			$cur = array( 'title' => $title, 'desc' => $desc, 'img' => $img );
		}
	}
	return $cur;
}

add_filter( 'pre_get_document_title', function ( $t ) {
	$c = yk_seo_current();
	return ! empty( $c['title'] ) ? $c['title'] : $t;
}, 99 );

add_action( 'wp_head', function () {
	$c = yk_seo_current();
	if ( empty( $c['desc'] ) ) {
		return;
	}
	$url = is_front_page() ? home_url( '/' ) : get_term_link( get_queried_object() );
	if ( is_wp_error( $url ) ) {
		$url = home_url( add_query_arg( array() ) );
	}
	$img = $c['img'];
	if ( ! $img ) {
		$sid = (int) get_option( 'site_icon' );
		$img = $sid ? wp_get_attachment_image_url( $sid, 'full' ) : '';
	}
	echo "\n" . '<meta name="description" content="' . esc_attr( $c['desc'] ) . '">' . "\n";
	echo '<meta property="og:type" content="website">' . "\n";
	echo '<meta property="og:site_name" content="Yulika Florist">' . "\n";
	echo '<meta property="og:locale" content="en_US">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $c['title'] ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $c['desc'] ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	if ( $img ) {
		echo '<meta property="og:image" content="' . esc_url( $img ) . '">' . "\n";
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
}, 2 );

/* Bar kategori utama di bawah header (HTML server-side → terbaca Google sebagai navigasi utama) */
function yk_seo_catbar() {
	static $done = false;
	if ( $done || is_admin() ) {
		return;
	}
	$done = true;
	if ( ( function_exists( 'is_checkout' ) && is_checkout() ) || ( function_exists( 'is_cart' ) && is_cart() ) || ( function_exists( 'is_product' ) && is_product() ) ) {
		return;
	}
	$cur = ( function_exists( 'is_product_category' ) && is_product_category() ) ? get_queried_object_id() : 0;
	$out = '';
	foreach ( yk_seo_main_cats() as $slug ) {
		$t = get_term_by( 'slug', $slug, 'product_cat' );
		if ( ! $t ) {
			continue;
		}
		$l = get_term_link( $t );
		if ( is_wp_error( $l ) ) {
			continue;
		}
		$out .= '<a href="' . esc_url( $l ) . '"' . ( (int) $t->term_id === (int) $cur ? ' class="on" aria-current="page"' : '' ) . '>' . esc_html( html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' ) ) . '</a>';
		if ( 'hampers' === $slug ) {
			$out .= '<a href="' . esc_url( home_url( '/custom-order/' ) ) . '"' . ( is_page( 'custom-order' ) ? ' class="on" aria-current="page"' : '' ) . '>✿ Custom Order</a>';
		}
	}
	if ( ! $out ) {
		return;
	}
	echo '<style id="yk-catbar-css">.yk-catbar{background:#fff;border-bottom:1px solid #eee5da}.yk-catbar .yk-cb{display:flex;gap:8px;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;padding:9px 12px;max-width:1240px;margin:0 auto}.yk-catbar .yk-cb::-webkit-scrollbar{display:none}.yk-catbar a{flex:0 0 auto;white-space:nowrap;padding:9px 15px;border-radius:22px;background:#f6efe6;color:#5a3d22!important;font:600 14px/1.2 Montserrat,Arial,sans-serif;text-decoration:none!important}.yk-catbar a:hover,.yk-catbar a.on{background:#a0784a;color:#fff!important}@media(min-width:1024px){.yk-catbar .yk-cb{justify-content:center;gap:10px}.yk-catbar a{font-size:15px}}</style>';
	echo '<nav class="yk-catbar" aria-label="Shop by category"><div class="yk-cb">' . $out . '</div></nav>'; // phpcs:ignore
}
add_action( 'elementor/theme/after_do_header', 'yk_seo_catbar' );

/* Intro singkat di atas grid produk (6 kategori utama) + panel custom order untuk kategori yang belum ada produknya */
add_action( 'elementor/frontend/widget/before_render', function ( $w ) {
	static $done = false;
	if ( $done || 'wc-archive-products' !== $w->get_name() || ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
		return;
	}
	$done = true;
	$t    = get_queried_object();
	$copy = $t ? yk_seo_copy( $t->slug ) : null;
	$cnt  = $t ? (int) $t->count : 0;
	if ( ! $copy && $cnt > 0 ) {
		return;
	}
	$name  = html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' );
	$intro = $copy ? str_replace( '{min}', yk_seo_rp( yk_seo_term_min( $t->term_id ) ), $copy[2] ) : '';
	echo '<style>.yk-cat-intro{max-width:820px;margin:0 auto 18px;padding:0 4px;text-align:center;font:15px/1.6 Montserrat,Arial,sans-serif;color:#444}.yk-cat-intro em{font-style:normal;font-weight:600}.yk-co-panel{max-width:620px;margin:6px auto 26px;padding:22px 18px;border-radius:12px;background:#f6efe6;text-align:center;font:15px/1.6 Montserrat,Arial,sans-serif;color:#333}.yk-co-panel b{display:block;font-size:18px;margin-bottom:6px;color:#222}.yk-co-panel a{display:inline-block;margin-top:12px;background:#25d366;color:#fff!important;padding:13px 22px;border-radius:26px;font-weight:700;text-decoration:none!important}.yk-co-empty .woocommerce-info,.yk-co-empty .woocommerce-no-products-found{display:none!important}</style>';
	if ( $intro ) {
		echo '<div class="yk-cat-intro"><p>' . wp_kses( $intro, array( 'em' => array() ) ) . '</p></div>';
	}
	if ( $cnt < 1 ) {
		$msg = 'Hi Yulika Florist, I would like to order: ' . $name . '. Occasion & message: ... Delivery date & area: ...';
		echo '<div class="yk-co-panel"><b>Made to order</b>Tell us the occasion, the message and the delivery time — our florist will send you design options and prices on WhatsApp during opening hours.<br><a href="' . esc_attr( 'https://wa.me/6281315881538?text=' . rawurlencode( $msg ) ) . '" target="_blank" rel="noopener">Order ' . esc_html( $name ) . ' via WhatsApp</a></div>';
		echo '<script>document.body.classList.add("yk-co-empty")</script>';
	}
} );

/* Homepage /page/2/, /page/3/ … (muncul di Google sebagai "Page 2 – Florist with Ideas") → 301 ke homepage */
add_action( 'template_redirect', function () {
	if ( is_front_page() && ( (int) get_query_var( 'paged' ) > 1 || (int) get_query_var( 'page' ) > 1 ) ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}, 0 );
