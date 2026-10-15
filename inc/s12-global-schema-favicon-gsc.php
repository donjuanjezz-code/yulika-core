<?php
// Dari Code Snippets #12 "Yulika: Schema logo & toko (Google)" — scope: global
/* ===== Yulika: structured data (logo, nama situs, toko) untuk Google ===== */
add_action( 'wp_head', function () {
	if ( ! is_front_page() ) {
		return;
	}
	$home = home_url( '/' );
	$logo = wp_get_attachment_image_url( 987701, 'full' );
	$icon = get_site_icon_url( 512 );
	$data = array(
		'@context' => 'https://schema.org',
		'@graph'   => array(
			array(
				'@type'         => 'WebSite',
				'@id'           => $home . '#website',
				'url'           => $home,
				'name'          => 'Yulika Florist',
				'alternateName' => array( 'Yulika', 'yulikaflorist.com' ),
				'inLanguage'    => 'id-ID',
				'publisher'     => array( '@id' => $home . '#organization' ),
			),
			array(
				'@type'       => 'Florist',
				'@id'         => $home . '#organization',
				'name'        => 'Yulika Florist',
				'slogan'      => 'The Florist with Ideas',
				'url'         => $home,
				'logo'        => array(
					'@type'  => 'ImageObject',
					'url'    => $logo ? $logo : $icon,
					'width'  => 758,
					'height' => 442,
				),
				'image'       => array_values( array_filter( array( $logo, $icon ) ) ),
				'telephone'   => '+6281315881538',
				'priceRange'  => 'Rp',
				'address'     => array(
					'@type'           => 'PostalAddress',
					'streetAddress'   => 'Kelapa Gading Permai Blok WA 2 No. 12-14, Jl. Boulevard Raya',
					'addressLocality' => 'Kelapa Gading, Jakarta Utara',
					'addressRegion'   => 'DKI Jakarta',
					'postalCode'      => '14240',
					'addressCountry'  => 'ID',
				),
				'openingHoursSpecification' => array(
					array(
						'@type'     => 'OpeningHoursSpecification',
						'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' ),
						'opens'     => '08:00',
						'closes'    => '19:00',
					),
				),
				'sameAs'      => array( 'https://www.instagram.com/yulikaflorist/' ),
			),
		),
	);
	echo "\n" . '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}, 5 );

/* ---------- Favicon untuk Google: ukuran kelipatan 48px ---------- */
add_filter( 'site_icon_meta_tags', function ( $tags ) {
	$u = 'https://yulikaflorist.com/wp-content/uploads/2026/10/';
	return array(
		'<link rel="icon" href="' . $u . 'yulika-florist-round-icon-48.png" sizes="48x48" type="image/png" />',
		'<link rel="icon" href="' . $u . 'yulika-florist-round-icon-96.png" sizes="96x96" type="image/png" />',
		'<link rel="icon" href="' . $u . 'yulika-florist-round-icon-192.png" sizes="192x192" type="image/png" />',
		'<link rel="apple-touch-icon" href="' . $u . 'yulika-florist-round-icon-192.png" />',
		'<meta name="msapplication-TileImage" content="' . $u . 'yulika-florist-round-icon-192.png" />',
	);
} );

add_action( 'init', function () {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? strtok( $_SERVER['REQUEST_URI'], '?' ) : '';
	if ( '/favicon.ico' === $uri ) {
		wp_redirect( 'https://yulikaflorist.com/wp-content/uploads/2026/10/yulika-florist-round-icon-48.png', 301 );
		exit;
	}
}, 1 );

/* ---------- Verifikasi Google Search Console ---------- */
add_action( 'wp_head', function () {
	echo '<meta name="google-site-verification" content="mlorGwfS05sDlfhmw-rNnlDLmm7b9E-pJhfRbIZTmG8" />' . "\n";
}, 1 );

/* ---------- Sitemap: jangan tampilkan daftar user (keamanan) ---------- */
add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
	return ( 'users' === $name ) ? false : $provider;
}, 10, 2 );

/* ---------- LSCache: TTL publik 10 jam (nonce WordPress tetap valid di halaman cache) + purge sekali setelah pindah server ---------- */
add_action( 'template_redirect', function () {
	if ( ! is_user_logged_in() ) {
		do_action( 'litespeed_control_set_ttl', 36000 );
	}
}, 99 );
add_action( 'init', function () {
	if ( ! get_option( 'yk_ls_purged_20261010' ) && did_action( 'litespeed_init' ) !== null ) {
		update_option( 'yk_ls_purged_20261010', 1, false );
		do_action( 'litespeed_purge_all' );
	}
}, 99 );
