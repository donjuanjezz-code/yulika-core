<?php
// Dari Code Snippets #23 "Kecepatan: prefetch + pemanas cache (Yulika)" — scope: global
/* ===== Yulika: kecepatan — prefetch halaman berikutnya + pemanas cache LSCache (crawler server tidak aktif) ===== */

/* 1) Prefetch: browser mengambil halaman produk/kategori sebelum diklik (hover di desktop, tap/scroll di HP) */
add_filter( 'wp_speculation_rules_configuration', function ( $config ) {
	if ( is_user_logged_in() ) {
		return $config;
	}
	return array( 'mode' => 'prefetch', 'eagerness' => 'moderate' );
} );
add_filter( 'wp_speculation_rules_href_exclude_paths', function ( $paths ) {
	return array_merge( (array) $paths, array( '/checkout/*', '/cart/*', '/my-account/*', '/welcome/*', '/favorit/*', '/cari/*' ) );
} );

/* 2) Pemanas cache: tiap 10 menit membuka halaman yang belum ter-cache, supaya pengunjung selalu dapat versi cache (±30 ms) */
function yk_warm_lists() {
	$prio  = array( home_url( '/' ), home_url( '/faq/' ), home_url( '/return-cancellations/' ), home_url( '/retail-store/' ), home_url( '/collections/' ) );
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $t ) {
			$l = get_term_link( $t );
			if ( ! is_wp_error( $l ) ) {
				$prio[] = $l;
			}
		}
	}
	$prods = array();
	$ids   = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'date', 'order' => 'DESC' ) );
	foreach ( $ids as $id ) {
		$prods[] = get_permalink( $id );
	}
	return array( array_values( array_unique( $prio ) ), $prods );
}
function yk_warm_urls() {
	list( $a, $b ) = yk_warm_lists();
	return array_merge( $a, $b );
}

/* Halaman utama & kategori SELALU dicek dulu tiap putaran; sisa waktu dipakai bergiliran untuk halaman produk */
function yk_warm_run( $budget = 20, $max = 10 ) { /* 13 Okt: dikurangi (50s/60 URL tiap 5 menit membebani server) */
	@set_time_limit( $budget + 30 );
	list( $prio, $prods ) = yk_warm_lists();
	$start = time();
	$done  = 0;
	$hit   = 0;
	$miss  = 0;
	$err   = 0;
	$get   = function ( $u ) use ( &$hit, &$miss, &$err ) {
		$r = wp_remote_get( $u, array( 'timeout' => 25, 'redirection' => 0, 'user-agent' => 'Mozilla/5.0 (compatible; YulikaCacheWarmer/1.1)' ) );
		if ( is_wp_error( $r ) ) {
			$err++;
		} elseif ( 'hit' === strtolower( (string) wp_remote_retrieve_header( $r, 'x-litespeed-cache' ) ) ) {
			$hit++;
		} else {
			$miss++;
		}
	};
	foreach ( $prio as $u ) {
		if ( $done >= $max || ( time() - $start ) >= $budget ) {
			break;
		}
		$get( $u );
		$done++;
	}
	$n   = count( $prods );
	$pos = $n ? (int) get_option( 'yk_warm_pos', 0 ) % $n : 0;
	$k   = 0;
	while ( $n && $done < $max && $k < $n && ( time() - $start ) < $budget ) {
		$get( $prods[ ( $pos + $k ) % $n ] );
		$k++;
		$done++;
	}
	if ( $n ) {
		update_option( 'yk_warm_pos', ( $pos + $k ) % $n, false );
	}
	$stat = array( 'time' => current_time( 'mysql' ), 'checked' => $done, 'hit' => $hit, 'warmed' => $miss, 'error' => $err, 'prio' => count( $prio ), 'products' => $n, 'seconds' => time() - $start );
	update_option( 'yk_warm_last', $stat, false );
	return $stat;
}

add_filter( 'cron_schedules', function ( $s ) {
	$s['yk_10min'] = array( 'interval' => 900, 'display' => 'Every 15 minutes (Yulika)' );
	return $s;
} );
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'yk_cache_warm' ) ) {
		wp_schedule_event( time() + 120, 'yk_10min', 'yk_cache_warm' );
	}
} );
add_action( 'yk_cache_warm', function () {
	yk_warm_run();
} );

/* Status / jalankan manual (admin) */
add_action( 'rest_api_init', function () {
	register_rest_route( 'yk/v1', '/warm', array(
		'methods'             => 'GET',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
		'callback'            => function ( WP_REST_Request $r ) {
			if ( $r->get_param( 'run' ) ) {
				return yk_warm_run( 40, (int) ( $r->get_param( 'max' ) ? $r->get_param( 'max' ) : 15 ) );
			}
			return array( 'last' => get_option( 'yk_warm_last' ), 'next' => wp_next_scheduled( 'yk_cache_warm' ), 'urls' => count( yk_warm_urls() ) );
		},
	) );
} );

/* 3) Google Fonts: hanya ketebalan yang dipakai (sebelumnya 54 varian font) */
add_filter( 'style_loader_src', function ( $src, $handle ) {
	if ( 0 === strpos( $handle, 'google-fonts-' ) && false !== strpos( $src, 'fonts.googleapis.com' ) ) {
		return 'https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&family=Sorts+Mill+Goudy:ital@0;1&family=Jomolhari&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap';
	}
	return $src;
}, 20, 2 );
add_action( 'wp_head', function () {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}, 1 );
