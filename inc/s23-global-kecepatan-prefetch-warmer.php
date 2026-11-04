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

/* 2) Pemanas cache: membuka halaman yang belum ter-cache, supaya pengunjung selalu dapat versi cache.
   Daftar dirotasi penuh. Produk "coming-soon" dilewati.
   4 Nov 2026 (analisa beban server, OK Jess): diperkecil karena warmer = ±33% request Yulika dan ikut memicu
   CPU/RAM fault di akun cPanel bersama Allesscia.
   - tiap 30 menit (sebelumnya 10 menit), maks 25 URL & 25 detik per putaran (sebelumnya 80 URL & 40 detik);
   - hanya UA desktop: setting LiteSpeed "Cache Mobile" = OFF, jadi HP & desktop memakai cache yang sama;
   - berhenti sendiri kalau satu halaman butuh > 4 detik (tanda server sedang berat). */
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
		'd' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Safari/537.36 YulikaCacheWarmer/2.1',
	);
}

/* Beranda dicek tiap putaran; sisanya bergiliran. Berhenti saat waktu habis, jumlah maksimal tercapai,
   atau satu halaman lebih lambat dari $slow detik (server sedang berat → jangan ditambah beban). */
function yk_warm_run( $budget = 25, $max = 25, $slow = 4 ) {
	@set_time_limit( $budget + 30 );
	$all   = yk_warm_urls();
	$ua    = yk_warm_agents();
	$jobs  = array();
	foreach ( $all as $u ) {
		foreach ( $ua as $k => $a ) {
			$jobs[] = array( $u, $k );
		}
	}
	$start   = time();
	$done    = 0;
	$hit     = 0;
	$miss    = 0;
	$err     = 0;
	$stopped = '';
	$get     = function ( $u, $k ) use ( &$hit, &$miss, &$err, $ua ) {
		$t = microtime( true );
		$r = wp_remote_get( $u, array( 'timeout' => 10, 'redirection' => 0, 'user-agent' => $ua[ $k ], 'headers' => array( 'Accept-Encoding' => 'gzip' ) ) );
		if ( is_wp_error( $r ) ) {
			$err++;
		} elseif ( 'hit' === strtolower( (string) wp_remote_retrieve_header( $r, 'x-litespeed-cache' ) ) ) {
			$hit++;
		} else {
			$miss++;
		}
		return microtime( true ) - $t;
	};
	foreach ( array_keys( $ua ) as $k ) {
		$dt = $get( home_url( '/' ), $k );
		$done++;
		if ( $dt > $slow ) {
			$stopped = 'lambat';
		}
	}
	$n   = count( $jobs );
	$pos = $n ? (int) get_option( 'yk_warm_pos', 0 ) % $n : 0;
	$i   = 0;
	while ( ! $stopped && $n && $done < $max && $i < $n && ( time() - $start ) < $budget ) {
		list( $u, $k ) = $jobs[ ( $pos + $i ) % $n ];
		$dt = $get( $u, $k );
		$i++;
		$done++;
		if ( $dt > $slow ) {
			$stopped = 'lambat';
		}
	}
	if ( $n ) {
		update_option( 'yk_warm_pos', ( $pos + $i ) % $n, false );
	}
	$stat = array( 'time' => current_time( 'mysql' ), 'checked' => $done, 'hit' => $hit, 'warmed' => $miss, 'error' => $err, 'jobs' => $n, 'pos' => ( $pos + $i ) % max( 1, $n ), 'seconds' => time() - $start, 'stopped' => $stopped );
	update_option( 'yk_warm_last', $stat, false );
	return $stat;
}

add_filter( 'cron_schedules', function ( $s ) {
	$s['yk_10min'] = array( 'interval' => 600, 'display' => 'Every 10 minutes (Yulika)' ); // lama, dibiarkan agar event lama tidak yatim
	$s['yk_30min'] = array( 'interval' => 1800, 'display' => 'Every 30 minutes (Yulika)' );
	return $s;
} );
add_action( 'init', function () {
	$next = wp_next_scheduled( 'yk_cache_warm' );
	if ( $next && 'yk_30min' !== wp_get_schedule( 'yk_cache_warm' ) ) {
		wp_clear_scheduled_hook( 'yk_cache_warm' ); // pindah dari jadwal 10 menit ke 30 menit
		$next = false;
	}
	if ( ! $next ) {
		wp_schedule_event( time() + 300, 'yk_30min', 'yk_cache_warm' );
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
				return yk_warm_run( 25, min( 80, (int) ( $r->get_param( 'max' ) ? $r->get_param( 'max' ) : 25 ) ) );
			}
			return array(
				'last'        => get_option( 'yk_warm_last' ),
				'next'        => wp_next_scheduled( 'yk_cache_warm' ),
				'schedule'    => wp_get_schedule( 'yk_cache_warm' ),
				'urls'        => count( yk_warm_urls() ),
				'cron_last'   => (int) get_option( 'yk_cron_last', 0 ),
				'now'         => time(),
				'wp_cron_off' => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
			);
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
/* 15 Okt 2026: CSS Google Fonts dimuat non-blocking (PageSpeed: render-blocking ±1,9 dtk). Teks tampil dulu dengan font cadangan (display=swap). */
add_filter( 'style_loader_tag', function ( $tag, $handle, $href ) {
	if ( is_admin() || 0 !== strpos( $handle, 'google-fonts-' ) || false === strpos( $href, 'fonts.googleapis.com' ) ) {
		return $tag;
	}
	$h = esc_url( $href );
	return '<link rel="preload" as="style" href="' . $h . '" onload="this.onload=null;this.rel=\'stylesheet\'">'
		. '<noscript><link rel="stylesheet" href="' . $h . '"></noscript>' . "\n";
}, 20, 3 );
