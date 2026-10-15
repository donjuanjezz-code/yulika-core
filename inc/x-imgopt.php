<?php
// Kompres ulang ukuran-ukuran foto produk (bukan file asli) + WebP-nya (Converter for Media, folder uploads-webpc).
// Dijalankan manual oleh admin lewat REST: /wp-json/yk/v1/imgopt?offset=0&limit=5 (dry=1 untuk simulasi).
// File asli tidak disentuh, jadi kalau perlu bisa dibuat ulang lewat Regenerate Thumbnails. Selalu dimuat.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function yk_imgopt_ids() {
	$soon = get_term_by( 'slug', 'coming-soon', 'product_cat' );
	$args = array( 'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids' );
	if ( $soon ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => (int) $soon->term_id, 'operator' => 'NOT IN' ) );
	}
	$ids = array();
	foreach ( get_posts( $args ) as $pid ) {
		$ids[] = (int) get_post_thumbnail_id( $pid );
		foreach ( array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $pid, '_product_image_gallery', true ) ) ) ) as $g ) {
			$ids[] = $g;
		}
	}
	foreach ( get_posts( array( 'post_type' => 'post', 'post_status' => array( 'publish', 'future' ), 'posts_per_page' => -1, 'fields' => 'ids' ) ) as $pid ) {
		$ids[] = (int) get_post_thumbnail_id( $pid );
	}
	$ids = array_values( array_unique( array_filter( $ids ) ) );
	sort( $ids );
	return $ids;
}

/* Paksa kualitas saat alat ini berjalan (plugin lain bisa menimpa kualitas lewat filter) */
add_filter( 'wp_editor_set_quality', function ( $q ) {
	return ! empty( $GLOBALS['yk_imgopt_q'] ) ? (int) $GLOBALS['yk_imgopt_q'] : $q;
}, PHP_INT_MAX );
add_filter( 'jpeg_quality', function ( $q ) {
	return ! empty( $GLOBALS['yk_imgopt_q'] ) ? (int) $GLOBALS['yk_imgopt_q'] : $q;
}, PHP_INT_MAX );

function yk_imgopt_one( $att, $dry ) {
	$meta = wp_get_attachment_metadata( $att );
	if ( empty( $meta['file'] ) || empty( $meta['sizes'] ) ) {
		return array( 'files' => 0, 'before' => 0, 'after' => 0 );
	}
	$up   = wp_get_upload_dir();
	$dir  = trailingslashit( $up['basedir'] ) . trailingslashit( dirname( $meta['file'] ) );
	$wdir = trailingslashit( WP_CONTENT_DIR ) . 'uploads-webpc/uploads/' . trailingslashit( dirname( $meta['file'] ) );
	$done = (array) get_post_meta( $att, '_yk_imgopt_v1', true );
	$out  = array( 'files' => 0, 'before' => 0, 'after' => 0 );
	foreach ( $meta['sizes'] as $name => $s ) {
		$f = $dir . $s['file'];
		if ( in_array( $s['file'], $done, true ) || ! file_exists( $f ) ) {
			continue;
		}
		$mime = wp_check_filetype( $f );
		$mime = $mime['type'];
		if ( ! in_array( $mime, array( 'image/jpeg', 'image/webp' ), true ) ) {
			continue;
		}
		$q      = ( (int) $s['width'] <= 500 ) ? 70 : 76;
		$GLOBALS['yk_imgopt_q'] = $q;
		$before = filesize( $f );
		$after  = $before;
		$tmp    = $f . '.yktmp.' . ( 'image/webp' === $mime ? 'webp' : 'jpg' );
		$ed     = wp_get_image_editor( $f );
		if ( ! is_wp_error( $ed ) ) {
			$ed->set_quality( $q );
			$r = $ed->save( $tmp, $mime );
			if ( ! is_wp_error( $r ) && file_exists( $tmp ) ) {
				$n = filesize( $tmp );
				if ( $n < $before * 0.92 ) {
					$after = $n;
					if ( ! $dry ) {
						@rename( $tmp, $f );
					}
				}
				if ( file_exists( $tmp ) ) {
					@unlink( $tmp );
				}
			}
		}
		/* WebP pendamping untuk JPG (dipakai Converter for Media) */
		if ( 'image/jpeg' === $mime ) {
			$wf = $wdir . $s['file'] . '.webp';
			$wb = file_exists( $wf ) ? filesize( $wf ) : PHP_INT_MAX;
			$ed = wp_get_image_editor( $f );
			if ( ! is_wp_error( $ed ) && wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
				$ed->set_quality( $q );
				$wt = $wf . '.yktmp.webp';
				if ( ! is_dir( $wdir ) ) {
					wp_mkdir_p( $wdir );
				}
				$r = $ed->save( $wt, 'image/webp' );
				if ( ! is_wp_error( $r ) && file_exists( $wt ) ) {
					$n = filesize( $wt );
					if ( $n < $wb * 0.95 ) {
						$out['webp_before'] = ( isset( $out['webp_before'] ) ? $out['webp_before'] : 0 ) + ( PHP_INT_MAX === $wb ? 0 : $wb );
						$out['webp_after']  = ( isset( $out['webp_after'] ) ? $out['webp_after'] : 0 ) + $n;
						if ( ! $dry ) {
							@rename( $wt, $wf );
						}
					}
					if ( file_exists( $wt ) ) {
						@unlink( $wt );
					}
				}
			}
		}
		$out['files']++;
		$out['before'] += $before;
		$out['after']  += $after;
		if ( ! $dry ) {
			$done[] = $s['file'];
		}
	}
	$GLOBALS['yk_imgopt_q'] = 0;
	if ( ! $dry ) {
		update_post_meta( $att, '_yk_imgopt_v1', array_values( array_unique( array_filter( $done ) ) ) );
	}
	return $out;
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'yk/v1', '/imgopt', array(
		'methods'             => 'GET',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
		'callback'            => function ( WP_REST_Request $r ) {
			@set_time_limit( 120 );
			$ids   = $r->get_param( 'id' ) ? array( (int) $r->get_param( 'id' ) ) : yk_imgopt_ids();
			$off   = max( 0, (int) $r->get_param( 'offset' ) );
			$lim   = min( 20, max( 1, (int) ( $r->get_param( 'limit' ) ? $r->get_param( 'limit' ) : 5 ) ) );
			$dry   = (bool) $r->get_param( 'dry' );
			$tot   = array( 'files' => 0, 'before' => 0, 'after' => 0, 'webp_before' => 0, 'webp_after' => 0 );
			$start = time();
			$i     = $off;
			for ( ; $i < min( count( $ids ), $off + $lim ); $i++ ) {
				$o = yk_imgopt_one( $ids[ $i ], $dry );
				foreach ( $o as $k => $v ) {
					$tot[ $k ] += $v;
				}
				if ( time() - $start > 40 ) {
					$i++;
					break;
				}
			}
			$tot['next']  = $i;
			$tot['total'] = count( $ids );
			$tot['webp']  = wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) );
			$e = wp_get_image_editor( __FILE__ );
			$tot['editor'] = _wp_image_editor_choose( array( 'mime_type' => 'image/jpeg' ) );
			return $tot;
		},
	) );
} );
