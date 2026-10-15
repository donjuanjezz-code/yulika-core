<?php
// Dari Code Snippets #5 "Auto kategori budget (Yulika)" — scope: global
function yk_budget_cat_sync( $product_id ) {
	static $running = false;
	if ( $running ) return;
	$product = wc_get_product( $product_id );
	if ( ! $product ) return;
	$ranges = array(
		'budget-dibawah-500-ribu' => array( 1, 499999 ),
		'budget-500-ribu-1-juta'  => array( 500000, 1000000 ),
		'budget-1-2-juta'         => array( 1000001, 2000000 ),
		'budget-2-juta-keatas'    => array( 2000001, PHP_INT_MAX ),
		/* kategori lama (tidak dipakai lagi): produk dilepas otomatis */
		'budget-3-4-juta'         => array( 1, 0 ),
		'budget-diatas-4-juta'    => array( 1, 0 ),
	);
	$price = $product->is_type( 'variable' ) ? (float) $product->get_variation_price( 'min', true ) : (float) $product->get_price();
	$budget_ids = array(); $target = 0;
	foreach ( $ranges as $slug => $r ) {
		$t = get_term_by( 'slug', $slug, 'product_cat' );
		if ( ! $t ) continue;
		$budget_ids[] = (int) $t->term_id;
		if ( $price >= $r[0] && $price <= $r[1] ) $target = (int) $t->term_id;
	}
	$current = wp_get_object_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
	if ( is_wp_error( $current ) ) return;
	$current = array_map( 'intval', $current );
	$skip = has_term( 'flower-boards', 'product_cat', $product_id ); /* flower board: tanpa kategori budget */
	/* kategori tambahan (overlap) Rp2–3 juta */
	$t23 = get_term_by( 'slug', 'budget-2-3-juta', 'product_cat' );
	if ( $t23 ) $budget_ids[] = (int) $t23->term_id;
	$new = array_values( array_diff( $current, $budget_ids ) );
	if ( $target && ! $skip ) $new[] = $target;
	if ( ! $skip && $t23 && $price >= 2000000 && $price <= 3000000 ) $new[] = (int) $t23->term_id;
	sort( $new ); sort( $current );
	if ( $new === $current ) return;
	$running = true;
	wp_set_object_terms( $product_id, $new, 'product_cat' );
	$running = false;
}
add_action( 'woocommerce_new_product', 'yk_budget_cat_sync', 20 );
add_action( 'woocommerce_update_product', 'yk_budget_cat_sync', 20 );

add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'yk_budget_daily_sync' ) ) {
		wp_schedule_event( time() + 300, 'daily', 'yk_budget_daily_sync' );
	}
} );
add_action( 'yk_budget_daily_sync', function () {
	foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => -1, 'return' => 'ids' ) ) as $id ) {
		yk_budget_cat_sync( $id );
	}
} );