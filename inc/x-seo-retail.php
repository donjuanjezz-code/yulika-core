<?php
// SEO retail (15 Okt 2026): produk kategori "coming-soon" tidak diindeks Google & tidak masuk sitemap.
// Begitu produk dikeluarkan dari kategori coming-soon, otomatis bisa diindeks lagi. Selalu dimuat.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function yk_seo_soon_term_id() {
	static $id = null;
	if ( null === $id ) {
		$t  = get_term_by( 'slug', 'coming-soon', 'product_cat' );
		$id = $t ? (int) $t->term_id : 0;
	}
	return $id;
}

add_filter( 'wp_robots', function ( $robots ) {
	if ( function_exists( 'is_product' ) && is_product() && yk_seo_soon_term_id() && has_term( yk_seo_soon_term_id(), 'product_cat', get_queried_object_id() ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['max-image-preview'] );
	}
	if ( is_tax( 'product_cat', 'coming-soon' ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
}, 20 );

add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $post_type ) {
	if ( 'product' === $post_type && yk_seo_soon_term_id() ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => yk_seo_soon_term_id(), 'operator' => 'NOT IN' ) );
	}
	return $args;
}, 10, 2 );

add_filter( 'wp_sitemaps_taxonomies_query_args', function ( $args, $taxonomy ) {
	if ( 'product_cat' === $taxonomy && yk_seo_soon_term_id() ) {
		$args['exclude'] = array( yk_seo_soon_term_id() );
	}
	return $args;
}, 10, 2 );
