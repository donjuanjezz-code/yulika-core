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

/* 2) Pemanas cache: tiap 15 menit membuka halaman yang belum ter-cache, supaya pengunjung selalu dapat versi cache.
   15 Okt: LiteSpeed menyimpan cache terpisah untuk HP dan desktop (Vary: User-Agent), jadi dipanaskan dua-duanya.
   Daftar dirotasi penuh (sebelumnya hanya 10 URL pertama yang pernah dipanaskan). Produk "coming-soon" dilewati. */
function yk_warm_lists() {
	$prio  = array( home_url( '/' ), home_url( '/custom-order/' ), home_url( '/our-story/' ), home_url( '/faq/' ), home_url( '/category/florist-journal/' ) );
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
	$skip  = 0;
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $t ) {
			if ( 'coming-soon' === $t->slug ) {
				$skip = (int) $t->term_id;
				continue;
			}
			$l = get_term_link( $t );
			if ( ! is_wp_error( $l ) ) {
				$prio[] = $l;
			}
		}
	}
	$args = array( 'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'date', 'order' => 'DESC' );
	if ( $skip ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $skip, 'operator' => 'NOT IN' ) );
	}
	$prods = array();
	foreach ( get_posts( $args ) as $id ) {
		$prods[] = get_permalink( $id );
	}
	foreach ( get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 60, 'fields' => 'ids' ) ) as $id ) {
		$prods[] = get_permalink( $id );
	}
	return array( array_values( array_unique( $prio ) ), $prods );
}
function yk_warm_urls() {
	list( $a, $b ) = yk_warm_lists();
	return array_merge( $a, $b );
}
function yk_warm_agents() {
	return array(
		'd' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Safari/537.36 YulikaCacheWarmer/2.0',
		'm' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1 YulikaCacheWarmer/2.0',
	);
}

/* Beranda (HP + desktop) dicek tiap putaran; sisanya bergiliran. Berhenti saat waktu habis supaya server tidak berat. */
function yk_warm_run( $budget = 40, $max = 80 ) {
	@set_time_limit( $budget + 30 );
	$all   = yk_warm_urls();
	$ua    = yk_warm_agents();
	$jobs  = array();
	foreach ( $all as $u ) {
		foreach ( $ua as $k => $a ) {
			$jobs[] = array( $u, $k );
		}
	}
	$start = time();
	$done  = 0;
	$hit   = 0;
	$miss  = 0;
	$err   = 0;
	$get   = function ( $u, $k ) use ( &$hit, &$miss, &$err, $ua ) {
		$r = wp_remote_get( $u, array( 'timeout' => 25, 'redirection' => 0, 'user-agent' => $ua[ $k ], 'headers' => array( 'Accept-Encoding' => 'gzip' ) ) );
		if ( is_wp_error( $r ) ) {
			$err++;
		} elseif ( 'hit' === strtolower( (string) wp_remote_retrieve_header( $r, 'x-litespeed-cache' ) ) ) {
			$hit++;
		} else {
			$miss++;
		}
	};
	foreach ( array_keys( $ua ) as $k ) {
		$get( home_url( '/' ), $k );
		$done++;
	}
	$n   = count( $jobs );
	$pos = $n ? (int) get_option( 'yk_warm_pos', 0 ) % $n : 0;
	$i   = 0;
	while ( $n && $done < $max && $i < $n && ( time() - $start ) < $budget ) {
		list( $u, $k ) = $jobs[ ( $pos + $i ) % $n ];
		$get( $u, $k );
		$i++;
		$done++;
	}
	if ( $n ) {
		update_option( 'yk_warm_pos', ( $pos + $i ) % $n, false );
	}
	$stat = array( 'time' => current_time( 'mysql' ), 'checked' => $done, 'hit' => $hit, 'warmed' => $miss, 'error' => $err, 'jobs' => $n, 'pos' => ( $pos + $i ) % max( 1, $n ), 'seconds' => time() - $start );
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
				return yk_warm_run( 40, (int) ( $r->get_param( 'max' ) ? $r->get_param( 'max' ) : 80 ) );
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
