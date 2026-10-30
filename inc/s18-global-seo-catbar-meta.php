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
	return 'Rp' . number_format( (float) $n, 0, ',', '.' );
}

/* Teks SEO per kategori: [title, meta description, intro]. {min} = harga termurah */
function yk_seo_copy( $slug ) {
	/* 15 Okt 2026: judul & deskripsi Google versi Indonesia (pembeli retail mencari dalam bahasa Indonesia); istilah Inggris dipertahankan di intro */
	$c = array(
		'bouquet'                 => array(
			'Buket Bunga Jakarta – Kirim Hari Ini | Yulika Florist',
			'Buket bunga segar: mawar, tulip, lily & bunga impor, dirangkai florist di Kelapa Gading. Kirim hari yang sama ke Jakarta & Bekasi. Pesan online/WA.',
			'Buket bunga (<em>flower bouquet</em>) segar yang dirangkai florist kami di Kelapa Gading — mawar, tulip, lily, dan bunga impor untuk ulang tahun, anniversary, dan wisuda. Kirim hari yang sama ke seluruh Jakarta dan Bekasi.',
		),
		'flower-boards'           => array(
			'Papan Bunga Jakarta – Grand Opening, Wedding & Duka Cita | Yulika',
			'Papan bunga custom untuk grand opening, ucapan selamat, pernikahan & duka cita. Tulisan sesuai pesanan, kirim ke seluruh Jakarta & Bekasi.',
			'Papan bunga (<em>flower board</em>) untuk grand opening, ucapan selamat, pernikahan, dan duka cita — tulisan dibuat sesuai pesananmu dan dikirim ke seluruh Jakarta dan Bekasi.',
		),
		'hampers'                 => array(
			'Hampers & Parcel Jakarta – Lebaran, Natal, Imlek | Yulika Florist',
			'Hampers & parcel: tea set, kue, makanan artisan & bunga untuk Lebaran, Natal, Imlek dan hadiah korporat. Kirim ke Jakarta & Bekasi.',
			'Hampers dan parcel pilihan — tea set, kue, makanan artisan, dan bunga — untuk Lebaran, Natal, Imlek, dan hadiah korporat. Dikirim ke seluruh Jakarta dan Bekasi.',
		),
		'love-anniversary'        => array(
			'Bunga Anniversary & Bunga untuk Pacar Jakarta | Yulika Florist',
			'Bunga romantis: buket mawar merah, bunga pink & flower box dengan boneka atau cokelat. Untuk anniversary & pacar, kirim hari yang sama di Jakarta.',
			'Bunga romantis untuk anniversary, Valentine, atau sekadar kejutan untuk pacar — buket mawar merah, bunga pink, dan <em>flower box</em>, sebagian dengan boneka atau cokelat. Kirim hari yang sama ke seluruh Jakarta.',
		),
		'graduation'              => array(
			'Buket Wisuda Jakarta – Bunga Wisuda Segar | Yulika Florist',
			'Buket wisuda: bunga matahari, mawar, gerbera & tulip dengan wrapping cantik. Pesan lebih awal untuk hari wisuda, kirim ke seluruh Jakarta.',
			'Buket wisuda (<em>graduation bouquet</em>) untuk merayakan hari kelulusan — bunga matahari, mawar, gerbera, dan tulip dengan wrapping khas Yulika. Pesan lebih awal untuk hari H; kirim ke seluruh Jakarta dan Bekasi.',
		),
		'budget-dibawah-500-ribu' => array(
			'Buket Bunga Murah di Bawah 500 Ribu Jakarta | Yulika Florist',
			'Buket bunga di bawah Rp500.000 yang tetap terlihat premium, dari bunga segar & impor. Kirim hari yang sama ke seluruh Jakarta & Bekasi. Pesan online/WA.',
			'Buket bunga di bawah Rp500.000 yang tetap terlihat premium, dirangkai dari bunga segar dan impor. Kirim hari yang sama ke seluruh Jakarta dan Bekasi.',
		),
		'birthday'                => array(
			'Bunga Ulang Tahun Jakarta – Buket & Flower Box | Yulika Florist',
			'Bunga ulang tahun: buket, flower box & rangkaian bunga untuk pasangan, ibu, sahabat atau rekan kerja. Kirim hari yang sama ke Jakarta & Bekasi.',
			'Bunga ulang tahun untuk pasangan, ibu, sahabat, atau rekan kerja — buket, <em>flower box</em>, dan rangkaian meja. Kirim hari yang sama ke seluruh Jakarta dan Bekasi.',
		),
		'congratulations'         => array(
			'Bunga Ucapan Selamat & Grand Opening Jakarta | Yulika Florist',
			'Bunga ucapan selamat: standing flower, papan bunga & rangkaian meja untuk grand opening, promosi jabatan dan peresmian. Kirim ke Jakarta & Bekasi.',
			'Bunga ucapan selamat untuk grand opening, promosi jabatan, dan peresmian — <em>standing flower</em>, papan bunga, dan rangkaian meja. Kirim ke seluruh Jakarta dan Bekasi.',
		),
		'sympathy-condolences'    => array(
			'Bunga Duka Cita Jakarta – Kirim ke Rumah Duka | Yulika Florist',
			'Bunga duka cita: rangkaian & buket bunga putih untuk rumah duka atau keluarga. Kartu ucapan sesuai pesanan, kirim ke seluruh Jakarta & Bekasi.',
			'Bunga duka cita (<em>sympathy flowers</em>) dalam nuansa putih dan lembut, untuk dikirim ke rumah duka atau rumah keluarga. Kartu ucapan ditulis sesuai pesanan; kirim ke seluruh Jakarta dan Bekasi.',
		),
		'standing-flowers'        => array(
			'Standing Flower Jakarta – Grand Opening & Wedding | Yulika Florist',
			'Standing flower untuk grand opening, pernikahan & acara kantor: lily, mawar & anggrek segar dengan pita ucapan. Kirim ke Jakarta & Bekasi.',
			'<em>Standing flower</em> untuk grand opening, pernikahan, dan acara kantor — lily, mawar, dan anggrek segar dengan pita ucapan. Kirim ke seluruh Jakarta dan Bekasi.',
		),
		'table-arrangements'      => array(
			'Rangkaian Bunga Meja Jakarta – Table Arrangement | Yulika Florist',
			'Rangkaian bunga meja untuk rumah, kantor, resepsionis & hadiah. Bunga segar dalam vas atau box, kirim hari yang sama ke Jakarta & Bekasi.',
			'Rangkaian bunga meja (<em>table arrangement</em>) untuk rumah, lobi kantor, meja resepsionis, atau hadiah — bunga segar dalam vas atau box. Kirim ke seluruh Jakarta dan Bekasi.',
		),
		'flower-boxes-baskets'    => array(
			'Flower Box & Keranjang Bunga Jakarta | Yulika Florist',
			'Flower box & keranjang bunga: mawar, hydrangea & lily dalam box cantik, sebagian dengan boneka atau cokelat. Kirim ke seluruh Jakarta & Bekasi.',
			'<em>Flower box</em> dan keranjang bunga — mawar, hydrangea, dan lily dalam box cantik, sebagian dengan boneka atau cokelat. Kirim ke seluruh Jakarta dan Bekasi.',
		),
		'rose'                    => array(
			'Buket Mawar Jakarta – Mawar Merah, Pink & Putih | Yulika Florist',
			'Buket & rangkaian mawar: mawar merah, pink, putih, kuning & peach, lokal maupun impor. Kirim hari yang sama ke seluruh Jakarta & Bekasi.',
			'Buket dan rangkaian mawar — merah, pink, putih, kuning, dan peach, dari mawar lokal maupun impor. Kirim hari yang sama ke seluruh Jakarta dan Bekasi.',
		),
		'lily'                    => array(
			'Buket Bunga Lily Jakarta – Lily Putih, Pink & Kuning | Yulika',
			'Buket & rangkaian lily: lily putih, pink stargazer & kuning untuk ulang tahun, pernikahan dan duka cita. Kirim ke seluruh Jakarta & Bekasi.',
			'Buket dan rangkaian bunga lily — lily putih, pink stargazer, dan kuning — untuk ulang tahun, pernikahan, dan duka cita. Kirim ke seluruh Jakarta dan Bekasi.',
		),
		'new-baby-get-well'       => array(
			'Bunga Kelahiran Bayi & Get Well Soon Jakarta | Yulika Florist',
			'Bunga untuk kelahiran bayi & orang sakit: warna lembut, aroma ringan, kirim ke rumah atau rumah sakit di Jakarta & Bekasi.',
			'Bunga untuk menyambut bayi baru lahir atau mendoakan cepat sembuh — warna lembut dan aroma ringan, bisa dikirim ke rumah atau rumah sakit di Jakarta dan Bekasi.',
		),
		'wedding-engagement'      => array(
			'Bunga Pernikahan & Lamaran Jakarta | Yulika Florist',
			'Bunga untuk pernikahan, lamaran & sangjit: buket, standing flower & rangkaian. Dekorasi event juga tersedia. Kirim ke seluruh Jakarta.',
			'Bunga untuk pernikahan, lamaran, dan sangjit — buket, <em>standing flower</em>, dan rangkaian ucapan. Untuk dekorasi venue, lihat proyek event kami. Kirim ke seluruh Jakarta dan Bekasi.',
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
			'title' => 'Toko Bunga Jakarta & Florist Kelapa Gading | Yulika Florist',
			'desc'  => 'Florist di Kelapa Gading: buket bunga, papan bunga, standing flower & hampers. Kirim hari yang sama ke seluruh Jakarta & Bekasi. Pesan online atau WhatsApp.',
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
				$title = $name . ' – Toko Bunga Jakarta | Yulika Florist';
				$desc  = $name . ' di Yulika Florist' . ': rangkaian bunga segar dari florist Kelapa Gading. Kirim hari yang sama ke seluruh Jakarta & Bekasi.';
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
	echo '<meta property="og:locale" content="id_ID">' . "\n";
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
		$msg = ( function_exists( 'yk_wa_text' ) ? yk_wa_text( 'ingin memesan *' . $name . '*' ) : 'Halo Yulika Florist' ) . "\nAcara & ucapan: ...\nTanggal & area kirim: ...";
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
