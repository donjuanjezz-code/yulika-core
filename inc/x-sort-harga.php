<?php
// Halaman kategori / shop / tag (16 Okt 2026): urutan bawaan = harga termurah di atas → termahal di bawah.
// Produk tanpa harga ("coming soon") selalu di paling bawah. Pilihan urutan manual pelanggan (?orderby=) tetap berlaku.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'woocommerce_default_catalog_orderby', function () {
	return 'price';
}, 99 );

add_filter( 'posts_clauses', function ( $c, $q ) {
	if ( is_admin() || ! $q->is_main_query() || false === strpos( (string) $c['orderby'], 'wc_product_meta_lookup.min_price ASC' ) ) {
		return $c;
	}
	$c['orderby'] = '( COALESCE( wc_product_meta_lookup.min_price, 0 ) <= 0 ) ASC, ' . $c['orderby'];
	return $c;
}, 99, 2 );
