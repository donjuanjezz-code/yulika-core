<?php
// Dari Code Snippets #6 "Yulika: Checkout v3 (Delivery/Pickup, Maps, zona Jakarta+Bekasi, WA)" — scope: global
/* ===== Yulika Checkout v3: Delivery/Store Pickup, Sender/Recipient, Google Maps, ongkir per zona (Jakarta + Bekasi), jadwal, konfirmasi WhatsApp (English) ===== */
if ( ! defined( 'YK_TZ' ) ) {
	define( 'YK_TZ', 'Asia/Jakarta' );
}

function yk_dlv_slots() {
	return apply_filters( 'yk_dlv_slots', array(
		'08-12' => array( '08:00', '12:00', '08:00 – 12:00' ),
		'12-17' => array( '12:00', '17:00', '12:00 – 17:00' ),
		'17-20' => array( '17:00', '20:00', '17:00 – 20:00' ),
	) );
}

/* Slot ambil di toko: per jam, sesuai jam buka 08:00-19:00 */
function yk_pick_slots() {
	$o = array();
	for ( $h = 8; $h < 19; $h++ ) {
		$a           = sprintf( '%02d:00', $h );
		$b           = sprintf( '%02d:00', $h + 1 );
		$o[ 'p' . $h ] = array( $a, $b, $a . ' – ' . $b );
	}
	return $o;
}

function yk_slots_for( $method ) {
	return 'pickup' === $method ? yk_pick_slots() : yk_dlv_slots();
}

function yk_store_info() {
	return array(
		'name'  => 'Yulika Florist',
		'addr'  => 'Kelapa Gading Permai Blok WA 2 No. 12-14, Jl. Boulevard Raya, Kelapa Gading, Jakarta Utara 14240',
		'hours' => 'Open daily 08:00–19:00',
		'maps'  => 'https://www.google.com/maps/search/?api=1&query=Kelapa%20Gading%20Permai%20Blok%20WA%202%20No%2012-14%2C%20Jl.%20Boulevard%20Raya%2C%20Kelapa%20Gading%20Timur%2C%20Jakarta%20Utara%2014240',
	);
}

/* Produk siap = sekarang + 4 jam kerja (florist 08:00-19:00, setiap hari). */
function yk_dlv_ready_time() {
	$tz  = new DateTimeZone( YK_TZ );
	$t   = new DateTime( 'now', $tz );
	$rem = 240;
	for ( $i = 0; $i < 40; $i++ ) {
		$open  = clone $t;
		$open->setTime( 8, 0 );
		$close = clone $t;
		$close->setTime( 19, 0 );
		if ( $t < $open ) {
			$t = $open;
		}
		if ( $t >= $close ) {
			$open->modify( '+1 day' );
			$t = $open;
			continue;
		}
		$avail = ( $close->getTimestamp() - $t->getTimestamp() ) / 60;
		if ( $avail >= $rem ) {
			$t->modify( '+' . (int) ceil( $rem ) . ' minutes' );
			return $t;
		}
		$rem -= $avail;
		$open->modify( '+1 day' );
		$t = $open;
	}
	return $t;
}

function yk_dlv_date_label( $ymd ) {
	$d = DateTime::createFromFormat( 'Y-m-d', $ymd, new DateTimeZone( YK_TZ ) );
	return $d ? $d->format( 'l, j F Y' ) : $ymd;
}

function yk_wa_link( $msg ) {
	return 'https://wa.me/6281315881538?text=' . rawurlencode( $msg );
}

/* =========================================================
 * ZONA PENGIRIMAN
 * ======================================================= */
function yk_zone_fees() {
	return array( 1 => 50000, 2 => 75000, 3 => 100000 );
}

function yk_zone_tables() {
	/* Kelurahan / area khusus (dicek lebih dulu) */
	$kel = array(
		'pegangsaan dua'     => 1,
		'sunter agung'       => 1,
		'sunter jaya'        => 1,
		'sunter'             => 1,
		'rawamangun'         => 1,
		'kayu putih'         => 1,
		'pulomas'            => 1,
		'pulo mas'           => 1,
		'ancol'              => 2,
		'pluit'              => 3,
		'kamal muara'        => 3,
		'kapuk muara'        => 3,
		'pantai indah kapuk' => 3,
		'kembangan utara'    => 3,
		'kembangan selatan'  => 3,
		'meruya utara'       => 3,
		'meruya selatan'     => 3,
		'srengseng'          => 4,
		'joglo'              => 4,
	);
	/* Kecamatan DKI Jakarta */
	$kec = array(
		// Zona 1
		'kelapa gading'     => 1,
		'pulo gadung'       => 1,
		'pulogadung'        => 1,
		'cempaka putih'     => 1,
		'tanjung priok'     => 1,
		'tanjung priuk'     => 1,
		// Zona 2
		'pademangan'        => 2,
		'koja'              => 2,
		'kemayoran'         => 2,
		'senen'             => 2,
		'sawah besar'       => 2,
		'matraman'          => 2,
		'jatinegara'        => 2,
		'cakung'            => 2,
		'duren sawit'       => 2,
		'gambir'            => 2,
		'menteng'           => 2,
		'johar baru'        => 2,
		'cilincing'         => 2,
		// Zona 3
		'grogol petamburan' => 3,
		'grogol'            => 3,
		'taman sari'        => 3,
		'tamansari'         => 3,
		'tambora'           => 3,
		'kebon jeruk'       => 3,
		'palmerah'          => 3,
		'pal merah'         => 3,
		'tanah abang'       => 3,
		'setiabudi'         => 3,
		'setia budi'        => 3,
		'tebet'             => 3,
		'pancoran'          => 3,
		'kebayoran baru'    => 3,
		'kebayoran lama'    => 3,
		'kembangan'         => 3,
		'cipayung'          => 3,
		'ciracas'           => 3,
		'pasar rebo'        => 3,
		'penjaringan'       => 3,
		'mampang prapatan'  => 3,
		'mampang'           => 3,
		'pasar minggu'      => 3,
		'kramat jati'       => 3,
		'kramatjati'        => 3,
		'makasar'           => 3,
		'makassar'          => 3,
		'cilandak'          => 3,
		'pesanggrahan'      => 3,
		'cengkareng'        => 3,
		// Zona 4 (di luar jangkauan)
		'kalideres'         => 4,
		'kali deres'        => 4,
		'jagakarsa'         => 4,
	);
	return array( $kel, $kec );
}

/* Bekasi yang masih terjangkau < 90 menit dari Kelapa Gading */
function yk_zone_bekasi() {
	$kel = array(
		// Bekasi Barat
		'bintara' => 2, 'bintara jaya' => 2, 'kranji' => 2, 'kota baru' => 2, 'jakasampurna' => 2, 'jaka sampurna' => 2,
		// Bekasi Utara
		'harapan jaya' => 2, 'kaliabang tengah' => 2, 'perwira' => 2, 'harapan baru' => 2, 'teluk pucung' => 2, 'marga mulya' => 2,
		// Medan Satria
		'harapan mulya' => 2, 'pusaka rakyat' => 2, 'kalibaru' => 2, 'medan satria' => 2, 'pejuang' => 2, 'harapan indah' => 2,
		'summarecon bekasi' => 2,
	);
	$kec = array(
		'medan satria'   => 2,
		'bekasi barat'   => 2,
		'bekasi utara'   => 2,
		'bekasi timur'   => 3,
		'bekasi selatan' => 3,
		'rawalumbu'      => 3,
		'rawa lumbu'     => 3,
		'pondok gede'    => 3,
		'pondokgede'     => 3,
		'jatiasih'       => 3,
		'jati asih'      => 3,
		'pondok melati'  => 3,
		'mustika jaya'   => 3,
		'mustikajaya'    => 3,
		'tarumajaya'     => 3,
		'taruma jaya'    => 3,
		'babelan'        => 3,
		'tambun selatan' => 3,
		'jatisampurna'   => 4,
		'jati sampurna'  => 4,
		'bantar gebang'  => 4,
		'bantargebang'   => 4,
	);
	return array( $kel, $kec );
}

function yk_scan( $txt, $table ) {
	$keys = array_keys( $table );
	usort(
		$keys,
		function ( $x, $y ) {
			return strlen( $y ) - strlen( $x );
		}
	);
	foreach ( $keys as $k ) {
		if ( false !== strpos( $txt, ' ' . $k . ' ' ) ) {
			return $table[ $k ];
		}
	}
	return 0;
}

function yk_zn( $s ) {
	$s = strtolower( (string) $s );
	$s = preg_replace_callback(
		'/\b(klp|tj|kby|kb|ps|cemp|pd|kec|kel|kab|adm|tim|bar|sel|ut)\./',
		function ( $m ) {
			$map = array( 'klp' => 'kelapa', 'tj' => 'tanjung', 'kby' => 'kebayoran', 'kb' => 'kebon', 'ps' => 'pasar', 'cemp' => 'cempaka', 'pd' => 'pondok', 'kec' => ' ', 'kel' => ' ', 'kab' => ' ', 'adm' => ' ', 'tim' => 'timur', 'bar' => 'barat', 'sel' => 'selatan', 'ut' => 'utara' );
			return ' ' . $map[ $m[1] ] . ' ';
		},
		$s
	);
	$s = preg_replace( '/\bjkt\b/', 'jakarta', $s );
	$s = preg_replace( '/\b(daerah khusus ibukota|daerah khusus|kecamatan|kelurahan|kota administrasi|kota|kabupaten|administrasi|provinsi|dki)\b/', ' ', $s );
	$s = preg_replace( '/[^a-z0-9]+/', ' ', $s );
	return trim( preg_replace( '/\s+/', ' ', $s ) );
}

function yk_km( $lat1, $lng1, $lat2, $lng2 ) {
	$r  = 6371;
	$dl = deg2rad( $lat2 - $lat1 );
	$dg = deg2rad( $lng2 - $lng1 );
	$a  = sin( $dl / 2 ) * sin( $dl / 2 ) + cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * sin( $dg / 2 ) * sin( $dg / 2 );
	return $r * 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
}

function yk_zone_resolve( $area, $addr, $lat, $lng ) {
	$a     = is_array( $area ) ? $area : array();
	$prov  = yk_zn( isset( $a['prov'] ) ? $a['prov'] : '' );
	$city  = yk_zn( isset( $a['city'] ) ? $a['city'] : '' );
	$kec   = yk_zn( isset( $a['kec'] ) ? $a['kec'] : '' );
	$kel   = yk_zn( isset( $a['kel'] ) ? $a['kel'] : '' );
	$l1    = trim( preg_replace( '/^(kecamatan|kec\.)\s*/i', '', isset( $a['kec'] ) ? (string) $a['kec'] : '' ) );
	if ( '' === $l1 && ! empty( $a['kel'] ) ) {
		$l1 = trim( (string) $a['kel'] );
	}
	$l2    = trim( preg_replace( '/^(kota administrasi|kota adm\.|kota|kabupaten)\s*/i', '', isset( $a['city'] ) ? (string) $a['city'] : '' ) );
	$label = implode( ', ', array_filter( array( $l1, $l2 ) ) );
	$label = preg_replace( array( '/\bTj\.\s*/', '/\bKlp\.\s*/', '/\bKby\.\s*/', '/\bKb\.\s*/', '/\bPs\.\s*/', '/\bJkt\b/' ), array( 'Tanjung ', 'Kelapa ', 'Kebayoran ', 'Kebon ', 'Pasar ', 'Jakarta' ), $label );
	$out   = function ( $z ) use ( &$label ) {
		return array( 'zone' => (int) $z, 'label' => $label );
	};

	if ( false !== strpos( $city . ' ' . $kec, 'seribu' ) ) {
		return $out( 4 );
	}
	/* PIK 2 (Kab. Tangerang: Kosambi/Teluknaga/Pakuhaji) = Zona 3 — 12 Okt 2026 */
	$t_pik = ' ' . yk_zn( $addr ) . ' ' . $kel . ' ' . $kec . ' ';
	$in_pik2_box = ( $lat && $lng && (float) $lat >= -6.10 && (float) $lat <= -6.00 && (float) $lng >= 106.60 && (float) $lng <= 106.738 );
	if ( preg_match( '/\bpik ?2\b|\bpik ?ii\b|pantai indah kapuk (2|dua)\b/', $t_pik ) || $in_pik2_box ) {
		if ( '' === $label || preg_match( '/tangerang/i', $label ) ) {
			$label = 'PIK 2' . ( $label ? ' (' . $label . ')' : '' );
		}
		return $out( 3 );
	}
	$t       = yk_zn( $addr );
	$outside = ( $prov && false === strpos( $prov, 'jakarta' ) )
		|| ( ! $prov && ! $city && $t && preg_match( '/\b(bekasi|tangerang|depok|bogor|jawa barat|banten)\b/', $t ) && ! preg_match( '/\bjakarta\b/', $t ) );
	if ( $outside ) {
		$bks = ( false !== strpos( ' ' . $city . ' ', ' bekasi ' ) ) || ( ! $city && preg_match( '/\bbekasi\b/', $t ) );
		if ( ! $bks ) {
			return $out( 4 );
		}
		list( $bkel, $bkec ) = yk_zone_bekasi();
		if ( $kel && isset( $bkel[ $kel ] ) ) {
			return $out( $bkel[ $kel ] );
		}
		if ( $kec && isset( $bkec[ $kec ] ) ) {
			return $out( $bkec[ $kec ] );
		}
		$txt = ' ' . trim( $kel . ' ' . $kec . ' ' . $t ) . ' ';
		$z   = yk_scan( $txt, $bkel );
		if ( ! $z ) {
			$z = yk_scan( $txt, $bkec );
		}
		if ( $z ) {
			return $out( $z );
		}
		if ( $lat && $lng ) {
			$d = yk_km( -6.1580, 106.9050, $lat, $lng );
			return $out( $d <= 13 ? 2 : ( $d <= 22 ? 3 : 4 ) );
		}
		return $out( 4 );
	}

	list( $kel_t, $kec_t ) = yk_zone_tables();
	if ( $kel && isset( $kel_t[ $kel ] ) ) {
		return $out( $kel_t[ $kel ] );
	}
	if ( $kec && isset( $kec_t[ $kec ] ) ) {
		return $out( $kec_t[ $kec ] );
	}
	$txt = ' ' . trim( $kel . ' ' . $kec . ' ' . $city . ' ' . $t ) . ' ';
	foreach ( $kel_t as $k => $z ) {
		if ( false !== strpos( $txt, ' ' . $k . ' ' ) ) {
			return $out( $z );
		}
	}
	$keys = array_keys( $kec_t );
	usort(
		$keys,
		function ( $x, $y ) {
			return strlen( $y ) - strlen( $x );
		}
	);
	foreach ( $keys as $k ) {
		if ( false !== strpos( $txt, ' ' . $k . ' ' ) ) {
			return $out( $kec_t[ $k ] );
		}
	}

	/* Cadangan: jarak garis lurus dari toko */
	if ( $lat && $lng ) {
		$d = yk_km( -6.1580, 106.9050, $lat, $lng );
		if ( $lng > 106.98 && $lat < -6.08 && $lat > -6.40 ) {
			if ( '' === $label ) {
				$label = 'Bekasi, approx. ' . number_format( $d, 1 ) . ' km from our store';
			}
			return $out( $d <= 13 ? 2 : ( $d <= 22 ? 3 : 4 ) );
		}
		if ( $lat > -6.08 || $lat < -6.38 || $lng < 106.68 || $lng > 106.98 ) {
			return $out( 4 );
		}
		if ( '' === $label ) {
			$label = 'approx. ' . number_format( $d, 1 ) . ' km from our store';
		}
		return $out( $d <= 6 ? 1 : ( $d <= 12 ? 2 : ( $d <= 22 ? 3 : 4 ) ) );
	}
	return $out( 0 );
}

/* $d = data form (sudah unslashed) */
function yk_zone_from_data( $d ) {
	$area = array();
	if ( ! empty( $d['yk_area'] ) ) {
		$j = json_decode( (string) $d['yk_area'], true );
		if ( is_array( $j ) ) {
			foreach ( array( 'kel', 'kec', 'city', 'prov' ) as $k ) {
				$area[ $k ] = isset( $j[ $k ] ) ? sanitize_text_field( (string) $j[ $k ] ) : '';
			}
		}
	}
	$addr = isset( $d['shipping_address_1'] ) ? sanitize_text_field( (string) $d['shipping_address_1'] ) : '';
	$lat  = isset( $d['yk_lat'] ) ? (float) $d['yk_lat'] : 0;
	$lng  = isset( $d['yk_lng'] ) ? (float) $d['yk_lng'] : 0;
	$r    = yk_zone_resolve( $area, $addr, $lat, $lng );
	$r['pin'] = ( $lat && $lng ) ? 1 : 0;
	return $r;
}

function yk_zone_set( $d ) {
	$r = yk_zone_from_data( $d );
	if ( function_exists( 'WC' ) && WC()->session ) {
		WC()->session->set( 'yk_zone', $r );
	}
	return $r;
}

function yk_method_from( $d ) {
	return ( isset( $d['yk_method'] ) && 'pickup' === $d['yk_method'] ) ? 'pickup' : 'delivery';
}

function yk_method_get() {
	$m = ( function_exists( 'WC' ) && WC()->session ) ? WC()->session->get( 'yk_method' ) : '';
	return 'pickup' === $m ? 'pickup' : 'delivery';
}

function yk_method_set( $m ) {
	if ( function_exists( 'WC' ) && WC()->session ) {
		WC()->session->set( 'yk_method', $m );
	}
}

function yk_zone_get() {
	return ( function_exists( 'WC' ) && WC()->session ) ? WC()->session->get( 'yk_zone' ) : null;
}

add_action( 'woocommerce_checkout_update_order_review', function ( $post_data ) {
	$d = array();
	parse_str( (string) $post_data, $d );
	yk_method_set( yk_method_from( $d ) );
	yk_zone_set( $d );
} );

add_filter( 'woocommerce_cart_shipping_packages', function ( $packages ) {
	$z = yk_zone_get();
	foreach ( $packages as $i => $p ) {
		$packages[ $i ]['yk_zone']       = $z ? (int) $z['zone'] : 0;
		$packages[ $i ]['yk_zone_label'] = $z ? (string) $z['label'] : '';
		$packages[ $i ]['yk_method']     = yk_method_get();
	}
	return $packages;
} );

add_filter( 'woocommerce_package_rates', function ( $rates, $package ) {
	if ( isset( $package['yk_method'] ) && 'pickup' === $package['yk_method'] ) {
		$rate = new WC_Shipping_Rate( 'yk_pickup', 'Store Pickup – Kelapa Gading (free)', 0, array(), 'yk_pickup', 0 );
		return array( 'yk_pickup' => $rate );
	}
	$z    = isset( $package['yk_zone'] ) ? (int) $package['yk_zone'] : 0;
	$fees = yk_zone_fees();
	if ( ! isset( $fees[ $z ] ) ) {
		return array();
	}
	$label = 'Zone ' . $z . ( ! empty( $package['yk_zone_label'] ) ? ' – ' . $package['yk_zone_label'] : '' );
	$rate  = new WC_Shipping_Rate( 'yk_delivery', $label, $fees[ $z ], array(), 'yk_delivery', $z );
	return array( 'yk_delivery' => $rate );
}, 100, 2 );

add_filter( 'woocommerce_shipping_package_name', function () {
	return 'pickup' === yk_method_get() ? 'Store Pickup' : 'Delivery';
} );

add_filter( 'woocommerce_cart_needs_shipping_address', function ( $needs ) {
	if ( ! empty( $GLOBALS['yk_render_ship_form'] ) ) {
		return $needs; // saat merender form checkout: field penerima selalu dibuat (JS menyembunyikannya saat Pickup)
	}
	return 'pickup' === yk_method_get() ? false : $needs;
} );

/*
 * Fix 30 Okt 2026: kalau sesi terakhir = Pickup, form penerima (nama, WA, alamat, peta) tidak dirender,
 * sehingga pembeli yang pindah ke Delivery tanpa reload tidak bisa mengisi alamat.
 * Form sekarang selalu dirender; validasi tetap mengikuti metode (Pickup = tanpa alamat).
 */
add_action( 'woocommerce_checkout_shipping', function () {
	$GLOBALS['yk_render_ship_form'] = true;
}, 1 );
add_action( 'woocommerce_checkout_shipping', function () {
	$GLOBALS['yk_render_ship_form'] = false;
}, 99 );

add_filter( 'woocommerce_no_shipping_available_html', 'yk_no_ship_html' );
add_filter( 'woocommerce_cart_no_shipping_available_html', 'yk_no_ship_html' );
function yk_no_ship_html() {
	$z = yk_zone_get();
	if ( $z && 4 === (int) $z['zone'] ) {
		return 'Outside our delivery area';
	}
	return 'Select the recipient address to see the delivery fee.';
}

/* Keranjang: ongkir dihitung di checkout */
add_filter( 'woocommerce_cart_ready_to_calc_shipping', function ( $show ) {
	return ( function_exists( 'is_cart' ) && is_cart() ) ? false : $show;
} );

function yk_zone_info_html() {
	$z    = yk_zone_get();
	$zone = $z ? (int) $z['zone'] : 0;
	$fees = yk_zone_fees();
	$in   = '';
	if ( 'pickup' === yk_method_get() ) {
		return '<div id="yk-zone-info"></div>';
	}
	if ( isset( $fees[ $zone ] ) ) {
		$in = '<span class="ok">&#10003; Delivery area: <strong>' . esc_html( $z['label'] ? $z['label'] : 'Jakarta' ) . '</strong> &middot; Zone ' . $zone . ' &middot; ' . wc_price( $fees[ $zone ] ) . '</span>';
	} elseif ( 4 === $zone ) {
		$in = '<span class="no">Sorry, ' . esc_html( $z['label'] ? $z['label'] : 'this address' ) . ' is outside our delivery area (estimated travel time over 90 minutes). <a href="' . esc_attr( yk_wa_link( function_exists( 'yk_wa_text' ) ? yk_wa_text( 'ingin mengirim bunga ke alamat di luar area pengiriman' ) : 'Halo Admin' ) ) . '" target="_blank" rel="noopener">Chat with us on WhatsApp</a> for a special arrangement.</span>';
	}
	return '<div id="yk-zone-info">' . $in . '</div>';
}

add_filter( 'woocommerce_update_order_review_fragments', function ( $f ) {
	$f['#yk-zone-info'] = yk_zone_info_html();
	return $f;
} );

/* =========================================================
 * FIELD CHECKOUT
 * ======================================================= */
add_filter( 'woocommerce_checkout_fields', function ( $f ) {
	foreach ( array( 'billing_last_name', 'billing_company', 'billing_country', 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_state', 'billing_postcode', 'billing_email' ) as $k ) {
		unset( $f['billing'][ $k ] );
	}
	if ( isset( $f['billing']['billing_first_name'] ) ) {
		$f['billing']['billing_first_name'] = array_merge( $f['billing']['billing_first_name'], array( 'label' => 'Full Name', 'placeholder' => '', 'class' => array( 'form-row-wide' ), 'priority' => 10, 'required' => true, 'autocomplete' => 'name' ) );
	}
	if ( isset( $f['billing']['billing_phone'] ) ) {
		$f['billing']['billing_phone'] = array_merge( $f['billing']['billing_phone'], array( 'label' => 'WhatsApp Number', 'placeholder' => '08xxxxxxxxxx', 'class' => array( 'form-row-wide' ), 'priority' => 20, 'required' => true ) );
	}

	foreach ( array( 'shipping_last_name', 'shipping_company', 'shipping_country', 'shipping_city', 'shipping_state', 'shipping_postcode' ) as $k ) {
		unset( $f['shipping'][ $k ] );
	}
	if ( isset( $f['shipping']['shipping_first_name'] ) ) {
		$f['shipping']['shipping_first_name'] = array_merge( $f['shipping']['shipping_first_name'], array( 'label' => 'Full Name', 'placeholder' => '', 'class' => array( 'form-row-wide' ), 'priority' => 10, 'required' => true, 'autocomplete' => 'off' ) );
	}
	$f['shipping']['shipping_phone'] = array(
		'label'        => 'WhatsApp Number',
		'placeholder'  => '08xxxxxxxxxx',
		'required'     => true,
		'type'         => 'tel',
		'class'        => array( 'form-row-wide' ),
		'validate'     => array( 'phone' ),
		'autocomplete' => 'off',
		'priority'     => 20,
	);
	if ( isset( $f['shipping']['shipping_address_1'] ) ) {
		$f['shipping']['shipping_address_1'] = array_merge( $f['shipping']['shipping_address_1'], array( 'label' => 'Recipient Address', 'placeholder' => '', 'class' => array( 'form-row-wide', 'yk-addr1' ), 'priority' => 30, 'required' => true, 'autocomplete' => 'off' ) );
	}
	if ( isset( $f['shipping']['shipping_address_2'] ) ) {
		$f['shipping']['shipping_address_2'] = array_merge( $f['shipping']['shipping_address_2'], array( 'label' => 'House No. / Building, Floor / Landmark', 'label_class' => array(), 'placeholder' => 'e.g. Block A3 No. 12, Tower B Fl. 15, next to the mosque', 'class' => array( 'form-row-wide' ), 'priority' => 40, 'required' => false, 'autocomplete' => 'off' ) );
	}
	unset( $f['order']['order_comments'] );
	return $f;
}, 50 );

add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );
add_filter( 'woocommerce_checkout_registration_enabled', '__return_false' );

/* Nama lengkap (first + last lama) saat prefill pengirim */
add_filter( 'woocommerce_checkout_get_value', function ( $value, $key ) {
	if ( 'billing_first_name' === $key && is_user_logged_in() && function_exists( 'WC' ) && WC()->customer ) {
		$n = trim( WC()->customer->get_billing_first_name() . ' ' . WC()->customer->get_billing_last_name() );
		return '' !== $n ? $n : null;
	}
	return $value;
}, 30, 2 );

add_filter( 'woocommerce_checkout_posted_data', function ( $d ) {
	$d['billing_country']           = 'ID';
	$d['shipping_country']          = 'ID';
	$d['billing_last_name']         = '';
	$d['shipping_last_name']        = '';
	$d['ship_to_different_address'] = 1;
	if ( empty( $d['shipping_state'] ) ) {
		$d['shipping_state'] = 'JK';
	}
	$z = yk_zone_get();
	if ( $z && ! empty( $z['label'] ) ) {
		$d['shipping_city'] = $z['label'];
	}
	if ( empty( $d['billing_email'] ) && is_user_logged_in() ) {
		$d['billing_email'] = wp_get_current_user()->user_email;
	}
	return $d;
}, 50 );

/* Judul bagian */
add_filter( 'gettext', function ( $t, $text, $domain ) {
	if ( 'woocommerce' === $domain && 'Billing details' === $text ) {
		return 'Sender Details';
	}
	return $t;
}, 20, 3 );

add_action( 'woocommerce_before_checkout_shipping_form', function () {
	echo '<h3 class="yk-rcp-h">Recipient Details</h3>';
} );

/* Ucapan kartu (pengirim) */
add_action( 'woocommerce_after_checkout_billing_form', function () {
	echo '<p class="form-row form-row-wide" id="yk_greeting_field"><label for="yk_greeting">Greeting Card Message&nbsp;<abbr class="required" title="required">*</abbr></label><span class="woocommerce-input-wrapper"><textarea name="yk_greeting" id="yk_greeting" class="input-text" rows="4" maxlength="300" placeholder="e.g. Happy birthday, Mom! With love, Sarah"></textarea></span><span class="yk-hint">This message will be written on the greeting card (max. 300 characters).</span></p>';
} );

/* Blok peta sebelum field alamat penerima */
add_filter( 'woocommerce_form_field_text', function ( $field, $key ) {
	if ( 'shipping_address_1' !== $key ) {
		return $field;
	}
	$h  = '<div id="yk-addr" class="form-row form-row-wide">';
	$h .= '<label for="yk-map-q">Recipient Address&nbsp;<abbr class="required" title="required">*</abbr></label>';
	$h .= '<div class="yk-map-tools"><input type="text" id="yk-map-q" placeholder="Search street, building or housing complex…" autocomplete="off"><button type="button" id="yk-map-find" class="button">Search</button></div>';
	$h .= '<div id="yk-map"></div>';
	$h .= '<button type="button" id="yk-map-me" class="yk-linkbtn">&#128205; Use my current location</button>';
	$h .= '<p id="yk-map-info" class="yk-hint">Choose the address from the suggestions, then drag the pin to the exact spot if needed.</p>';
	$h .= '<div id="yk-zone-info"></div>';
	$h .= '<input type="hidden" name="yk_lat" id="yk_lat" value=""><input type="hidden" name="yk_lng" id="yk_lng" value=""><input type="hidden" name="yk_area" id="yk_area" value=""><input type="hidden" name="yk_map_ok" id="yk_map_ok" value="0">';
	$h .= '</div>';
	return $h . $field;
}, 10, 2 );

/* Pilihan Delivery / Store Pickup */
add_action( 'woocommerce_checkout_before_customer_details', function () {
	$m = yk_method_get();
	echo '<div id="yk-method"><h3>How would you like to send your order?</h3><p class="yk-msub">Delivery or Store Pick Up</p><div class="yk-mopts">';
	echo '<label class="yk-mopt"><input type="radio" name="yk_method" value="delivery"' . checked( 'pickup' !== $m, true, false ) . '><span><strong>Delivery</strong><small>Jakarta &amp; nearby Bekasi &middot; from Rp50.000</small></span></label>';
	echo '<label class="yk-mopt"><input type="radio" name="yk_method" value="pickup"' . checked( 'pickup' === $m, true, false ) . '><span><strong>Store Pickup</strong><small>Free &middot; Kelapa Gading &middot; daily 08:00–19:00</small></span></label>';
	echo '</div></div>';
} );

/* =========================================================
 * JADWAL KIRIM
 * ======================================================= */
add_action( 'woocommerce_checkout_after_customer_details', function () {
	$ready  = yk_dlv_ready_time();
	$slots  = yk_dlv_slots();
	$pslots = yk_pick_slots();
	$tz     = new DateTimeZone( YK_TZ );
	$start  = DateTime::createFromFormat( 'Y-m-d H:i', $ready->format( 'Y-m-d' ) . ' 00:00', $tz );
	$wa     = yk_wa_link( function_exists( 'yk_wa_text' ) ? yk_wa_text( 'ingin memesan bunga untuk dikirim hari ini (urgent)' ) : 'Halo Admin' );
	$st     = yk_store_info();
	echo '<div id="yk-dlv" data-ready="' . esc_attr( $ready->format( 'Y-m-d H:i' ) ) . '" data-slots="' . esc_attr( wp_json_encode( $slots ) ) . '" data-pslots="' . esc_attr( wp_json_encode( $pslots ) ) . '">';
	echo '<h3 id="yk-dlv-h">Delivery Schedule</h3>';
	echo '<div id="yk-pinfo" class="yk-pinfo" style="display:none"><strong>Pickup location:</strong> ' . esc_html( $st['name'] . ', ' . $st['addr'] ) . '<br>' . esc_html( $st['hours'] ) . ' &middot; <a href="' . esc_url( $st['maps'] ) . '" target="_blank" rel="noopener">Open in Google Maps</a></div>';
	echo '<p class="form-row form-row-wide"><label for="yk_date"><span class="yk-lbl-d">Delivery</span> Date&nbsp;<abbr class="required" title="required">*</abbr></label><select name="yk_date" id="yk_date">';
	for ( $i = 0; $i < 30; $i++ ) {
		$d = clone $start;
		$d->modify( '+' . $i . ' days' );
		$v = $d->format( 'Y-m-d' );
		echo '<option value="' . esc_attr( $v ) . '">' . esc_html( yk_dlv_date_label( $v ) ) . '</option>';
	}
	echo '</select></p>';
	echo '<div class="form-row form-row-wide"><label><span class="yk-lbl-d">Delivery</span> Time&nbsp;<abbr class="required" title="required">*</abbr></label><div id="yk-slots" class="yk-slotset">';
	foreach ( $slots as $k => $s ) {
		echo '<label class="yk-slot"><input type="radio" name="yk_slot" value="' . esc_attr( $k ) . '"><span>' . esc_html( $s[2] ) . '</span></label>';
	}
	echo '</div><div id="yk-pslots" class="yk-slotset" style="display:none">';
	foreach ( $pslots as $k => $s ) {
		echo '<label class="yk-slot yk-pslot"><input type="radio" name="yk_slot" value="' . esc_attr( $k ) . '" disabled><span>' . esc_html( $s[2] ) . '</span></label>';
	}
	echo '</div></div>';
	echo '<p class="yk-hint">Every arrangement is made fresh and needs about 4 working hours (our florists work 08:00–19:00 daily). Orders placed outside working hours are prepared the next day. Earliest available: <strong>' . esc_html( yk_dlv_date_label( $ready->format( 'Y-m-d' ) ) . ', ' . $ready->format( 'H:i' ) ) . '</strong>. Need it sooner? <a href="' . esc_attr( $wa ) . '" target="_blank" rel="noopener">Chat with us on WhatsApp</a>.</p>';
	echo '</div>';
} );

/* =========================================================
 * VALIDASI & SIMPAN
 * ======================================================= */
add_action( 'woocommerce_checkout_process', function () {
	$post   = wp_unslash( $_POST );
	$method = yk_method_from( $post );
	yk_method_set( $method );
	$z = yk_zone_set( $post );

	if ( 'pickup' === $method ) {
		// ambil di toko: alamat & zona tidak diperlukan
	} elseif ( empty( $z['pin'] ) && 0 === (int) $z['zone'] ) {
		wc_add_notice( 'Please choose the <strong>recipient address</strong> from the suggestions or pin it on the map.', 'error' );
	} elseif ( 4 === (int) $z['zone'] ) {
		wc_add_notice( 'Sorry, the recipient address is <strong>outside our delivery area</strong>. Please <a href="' . esc_attr( yk_wa_link( function_exists( 'yk_wa_text' ) ? yk_wa_text( 'ingin mengirim bunga ke alamat di luar area pengiriman' ) : 'Halo Admin' ) ) . '" target="_blank" rel="noopener">chat with us on WhatsApp</a> for a special arrangement.', 'error' );
	} elseif ( 0 === (int) $z['zone'] ) {
		wc_add_notice( 'We could not determine the delivery zone for this address. Please move the pin to the exact location.', 'error' );
	}

	$slots = yk_slots_for( $method );
	$word  = 'pickup' === $method ? 'pickup' : 'delivery';
	$date  = isset( $post['yk_date'] ) ? sanitize_text_field( $post['yk_date'] ) : '';
	$slot  = isset( $post['yk_slot'] ) ? sanitize_text_field( $post['yk_slot'] ) : '';
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		wc_add_notice( 'Please choose a <strong>' . $word . ' date</strong>.', 'error' );
		return;
	}
	if ( ! isset( $slots[ $slot ] ) ) {
		wc_add_notice( 'Please choose a <strong>' . $word . ' time</strong>.', 'error' );
		return;
	}
	$tz    = new DateTimeZone( YK_TZ );
	$st    = DateTime::createFromFormat( 'Y-m-d H:i', $date . ' ' . $slots[ $slot ][0], $tz );
	$ready = yk_dlv_ready_time();
	if ( ! $st || $st < $ready ) {
		wc_add_notice( 'The selected ' . $word . ' time is no longer available. The earliest available time is <strong>' . esc_html( yk_dlv_date_label( $ready->format( 'Y-m-d' ) ) . ', ' . $ready->format( 'H:i' ) ) . '</strong>. Please choose another time.', 'error' );
	}
	$max = clone $ready;
	$max->modify( '+31 days' );
	if ( $st && $st > $max ) {
		wc_add_notice( 'Orders can be scheduled up to 30 days in advance.', 'error' );
	}
}, 1 );

add_action( 'woocommerce_checkout_create_order', function ( $order ) {
	$post   = wp_unslash( $_POST );
	$method = yk_method_from( $post );
	$slots  = yk_slots_for( $method );
	$order->update_meta_data( '_yk_method', $method );
	$date  = isset( $post['yk_date'] ) ? sanitize_text_field( $post['yk_date'] ) : '';
	$slot  = isset( $post['yk_slot'] ) ? sanitize_text_field( $post['yk_slot'] ) : '';
	$order->update_meta_data( '_yk_delivery_date', $date );
	$order->update_meta_data( '_yk_delivery_slot', isset( $slots[ $slot ] ) ? $slots[ $slot ][2] : '' );
	$lat = isset( $post['yk_lat'] ) ? (float) $post['yk_lat'] : 0;
	$lng = isset( $post['yk_lng'] ) ? (float) $post['yk_lng'] : 0;
	if ( 'pickup' !== $method && $lat && $lng && abs( $lat ) <= 90 && abs( $lng ) <= 180 ) {
		$order->update_meta_data( '_yk_lat', $lat );
		$order->update_meta_data( '_yk_lng', $lng );
		$order->update_meta_data( '_yk_maps_url', 'https://www.google.com/maps?q=' . $lat . ',' . $lng );
	}
	$z = yk_zone_get();
	if ( $z && 'pickup' !== $method ) {
		$order->update_meta_data( '_yk_zone', (int) $z['zone'] );
		$order->update_meta_data( '_yk_zone_label', (string) $z['label'] );
	}
	$g = isset( $post['yk_greeting'] ) ? sanitize_textarea_field( $post['yk_greeting'] ) : '';
	if ( '' !== $g ) {
		$order->update_meta_data( '_yk_greeting', function_exists( 'mb_substr' ) ? mb_substr( $g, 0, 300 ) : substr( $g, 0, 300 ) );
	}
} );

function yk_dlv_lines( $order ) {
	$out    = array();
	$pickup = 'pickup' === $order->get_meta( '_yk_method' );
	$w      = $pickup ? 'Pickup' : 'Delivery';
	if ( $pickup ) {
		$st                 = yk_store_info();
		$out['Fulfilment']  = 'Store pickup – ' . esc_html( $st['name'] . ', ' . $st['addr'] );
	}
	$date = $order->get_meta( '_yk_delivery_date' );
	if ( $date ) {
		$out[ $w . ' date' ] = esc_html( yk_dlv_date_label( $date ) );
	}
	$slot = $order->get_meta( '_yk_delivery_slot' );
	if ( $slot ) {
		$out[ $w . ' time' ] = esc_html( $slot );
	}
	$zone = (int) $order->get_meta( '_yk_zone' );
	if ( $zone ) {
		$zl                   = $order->get_meta( '_yk_zone_label' );
		$out['Delivery zone'] = esc_html( 'Zone ' . $zone . ( $zl ? ' – ' . $zl : '' ) );
	}
	$url = $order->get_meta( '_yk_maps_url' );
	if ( $url ) {
		$out['Location'] = '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">Open in Google Maps</a>';
	}
	$g = $order->get_meta( '_yk_greeting' );
	if ( $g ) {
		$out['Card message'] = nl2br( esc_html( $g ) );
	}
	return $out;
}

function yk_dlv_print( $order ) {
	$lines = yk_dlv_lines( $order );
	if ( ! $lines ) {
		return;
	}
	echo '<div class="yk-dlv-info"><h3>' . ( 'pickup' === $order->get_meta( '_yk_method' ) ? 'Pickup Details' : 'Delivery Details' ) . '</h3>';
	foreach ( $lines as $k => $v ) {
		echo '<p style="margin:0 0 6px"><strong>' . esc_html( $k ) . ':</strong> ' . wp_kses_post( $v ) . '</p>';
	}
	echo '</div>';
}

add_action( 'woocommerce_admin_order_data_after_shipping_address', function ( $order ) {
	yk_dlv_print( $order );
	yk_wa_admin_buttons( $order );
} );
add_action( 'woocommerce_order_details_after_order_table', function ( $order ) {
	yk_dlv_print( $order );
} );
add_action( 'woocommerce_email_after_order_table', function ( $order, $sent_to_admin = false ) {
	yk_dlv_print( $order );
	if ( $sent_to_admin ) {
		yk_wa_admin_buttons( $order );
	}
}, 20, 2 );

/* =========================================================
 * KONFIRMASI WHATSAPP
 * ======================================================= */
function yk_wa_num( $p ) {
	$d = function_exists( 'yk_norm_wa' ) ? yk_norm_wa( $p ) : preg_replace( '/\D+/', '', (string) $p );
	return strlen( $d ) >= 10 ? $d : '';
}

function yk_money( $n ) {
	return 'Rp' . number_format( (float) $n, 0, ',', '.' );
}

/* Ringkasan order (teks WhatsApp). $to = 'shop' (pembeli → Yulika) atau 'customer' (Yulika → pembeli) */
function yk_order_wa_text( $order, $to = 'shop' ) {
	$pickup = 'pickup' === $order->get_meta( '_yk_method' );
	$name   = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
	$L      = array();
	if ( 'shop' === $to ) {
		$L[] = 'Hi Yulika Florist, I have just placed order #' . $order->get_order_number() . '.';
	} else {
		$L[] = 'Hi ' . ( $name ? $name : 'there' ) . ', thank you for ordering at Yulika Florist! Your order #' . $order->get_order_number() . ' has been received.';
	}
	$L[] = '';
	foreach ( $order->get_items() as $it ) {
		$L[] = '- ' . $it->get_quantity() . 'x ' . $it->get_name();
	}
	if ( (float) $order->get_shipping_total() > 0 ) {
		$L[] = ( $pickup ? 'Store pickup' : 'Delivery fee' ) . ': ' . yk_money( $order->get_shipping_total() );
	}
	if ( (float) $order->get_discount_total() > 0 ) {
		$L[] = 'Discount: -' . yk_money( $order->get_discount_total() );
	}
	$L[] = 'Total: ' . yk_money( $order->get_total() ) . ( $order->get_payment_method_title() ? ' (' . $order->get_payment_method_title() . ')' : '' );
	if ( (float) $order->get_meta( '_yk_usd_amount' ) > 0 ) {
		$L[] = 'PayPal amount: US$' . number_format( (float) $order->get_meta( '_yk_usd_amount' ), 2 );
	}
	$L[] = '';
	$date = $order->get_meta( '_yk_delivery_date' );
	$slot = $order->get_meta( '_yk_delivery_slot' );
	if ( $pickup ) {
		$st  = yk_store_info();
		$L[] = 'Store pickup: ' . ( $date ? yk_dlv_date_label( $date ) : '' ) . ( $slot ? ', ' . $slot : '' );
		$L[] = 'Pickup location: ' . $st['name'] . ', ' . $st['addr'];
	} else {
		if ( $date ) {
			$L[] = 'Delivery: ' . yk_dlv_date_label( $date ) . ( $slot ? ', ' . $slot : '' );
		}
		$rn = trim( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() );
		if ( $rn ) {
			$L[] = 'Recipient: ' . $rn . ( $order->get_shipping_phone() ? ' (' . $order->get_shipping_phone() . ')' : '' );
		}
		$ad = trim( $order->get_shipping_address_1() . ( $order->get_shipping_address_2() ? ', ' . $order->get_shipping_address_2() : '' ) );
		if ( $ad ) {
			$L[] = 'Address: ' . $ad;
		}
		if ( $order->get_meta( '_yk_maps_url' ) ) {
			$L[] = 'Map: ' . $order->get_meta( '_yk_maps_url' );
		}
	}
	$bdl = function_exists( 'yk_bd_wa_lines' ) ? yk_bd_wa_lines( $order ) : array();
	if ( $bdl ) {
		$L = array_merge( $L, $bdl );
	} elseif ( $order->get_meta( '_yk_greeting' ) ) {
		$L[] = 'Card message: "' . $order->get_meta( '_yk_greeting' ) . '"';
	}
	if ( 'shop' === $to ) {
		$L[] = '';
		$L[] = 'Sender: ' . $name . ( $order->get_billing_phone() ? ' (' . $order->get_billing_phone() . ')' : '' );
		$L[] = 'Please confirm my order. Thank you!';
	} else {
		$L[] = '';
		if ( $order->has_status( array( 'on-hold', 'pending' ) ) && 'yk_paypal_usd' === $order->get_payment_method() ) {
			$pl  = function_exists( 'yk_pp_link' ) ? yk_pp_link( $order ) : '';
			$L[] = 'Please complete the PayPal payment of US$' . number_format( (float) $order->get_meta( '_yk_usd_amount' ), 2 ) . ( $pl ? ' here: ' . $pl : ' (we will send you the PayPal link shortly)' ) . ' so we can start preparing your order.';
		}
		if ( $order->has_status( array( 'on-hold', 'pending' ) ) && 'bacs' === $order->get_payment_method() ) {
			$L[] = 'Please complete the bank transfer and send us the payment receipt here so we can start preparing your order.';
		}
		$L[] = 'We will keep you updated. Thank you!';
	}
	return implode( "\n", $L );
}

/* Tombol di admin order & email admin: konfirmasi ke pengirim / penerima lewat WhatsApp */
function yk_wa_admin_buttons( $order ) {
	$s = yk_wa_num( $order->get_billing_phone() );
	if ( ! $s ) {
		return;
	}
	$url = 'https://wa.me/' . $s . '?text=' . rawurlencode( yk_order_wa_text( $order, 'customer' ) );
	echo '<p style="margin:10px 0"><a href="' . esc_attr( $url ) . '" target="_blank" rel="noopener" style="display:inline-block;background:#25d366;color:#fff;padding:8px 14px;border-radius:6px;text-decoration:none;font-weight:600">Send WhatsApp confirmation to customer</a></p>';
}

/* Halaman terima kasih: kirim detail order ke WhatsApp Yulika */
add_action( 'woocommerce_thankyou', function ( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	$url = 'https://wa.me/6281315881538?text=' . rawurlencode( yk_order_wa_text( $order, 'shop' ) );
	echo '<div class="yk-wa-confirm" style="margin:0 0 24px;padding:18px;border:1px solid #cfe6cf;background:#f1f8f1;border-radius:10px;text-align:center">';
	echo '<p style="margin:0 0 6px;font-weight:600;font-size:16px">Confirm your order on WhatsApp</p>';
	echo '<p style="margin:0 0 14px;font-size:14px;color:#4a4a4a">Tap the button to send your order details to our team. We will reply on WhatsApp to confirm your order and payment.</p>';
	echo '<a href="' . esc_attr( $url ) . '" target="_blank" rel="noopener" style="display:inline-block;background:#25d366;color:#fff;padding:13px 22px;border-radius:8px;font-weight:700;text-decoration:none">&#128172; Send to WhatsApp</a>';
	echo '</div>';
}, 1 );

/* Setting: Google Maps API key (Settings → General) */
add_action( 'admin_init', function () {
	register_setting( 'general', 'yk_gmaps_key', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
	add_settings_field( 'yk_gmaps_key', 'Google Maps API Key (checkout)', function () {
		echo '<input type="text" class="regular-text code" name="yk_gmaps_key" id="yk_gmaps_key" value="' . esc_attr( get_option( 'yk_gmaps_key', '' ) ) . '" placeholder="AIza...">';
		echo '<p class="description">Isi untuk memakai Google Maps + saran alamat otomatis di checkout. Kosongkan untuk memakai OpenStreetMap.</p>';
	}, 'general' );
} );

/* =========================================================
 * CSS + JS
 * ======================================================= */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) || is_wc_endpoint_url( 'order-pay' ) ) {
		return; // order-pay (popup Midtrans) tidak punya peta: jangan muat Google Maps (error "ykGInit is not a function")
	}
	$gkey       = trim( (string) get_option( 'yk_gmaps_key', '' ) );
	$use_google = '' !== $gkey;
	wp_register_style( 'yk-dlv', false, array(), null );
	wp_enqueue_style( 'yk-dlv' );
	$deps = array( 'jquery' );
	if ( ! $use_google ) {
		wp_enqueue_style( 'yk-leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), null );
		wp_enqueue_script( 'yk-leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), null, true );
		$deps[] = 'yk-leaflet';
	}
	wp_register_script( 'yk-dlv', false, $deps, null, true );
	wp_enqueue_script( 'yk-dlv' );
	if ( $use_google ) {
		$src = add_query_arg( array( 'key' => $gkey, 'libraries' => 'places', 'loading' => 'async', 'callback' => 'ykGInit', 'language' => 'id', 'region' => 'ID', 'v' => 'weekly' ), 'https://maps.googleapis.com/maps/api/js' );
		wp_enqueue_script( 'yk-gmaps', $src, array( 'yk-dlv' ), null, array( 'in_footer' => true, 'strategy' => 'async' ) );
	}
	$css = <<<'CSS'
#ship-to-different-address{display:none!important}
#shipping_address_1_field{display:none!important}
.woocommerce-checkout .yk-rcp-h{margin:0 0 16px}
#yk-addr label{display:block}
#yk-addr .yk-map-tools{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px;align-items:stretch}
#yk-addr .yk-map-tools input,#yk-addr .yk-map-tools gmp-place-autocomplete{flex:1 1 200px;min-width:0}
#yk-addr .yk-map-tools .button{white-space:nowrap}
#yk-map{height:240px;border-radius:8px;overflow:hidden;z-index:1;border:1px solid #e6dfd5}
gmp-place-autocomplete{color-scheme:light;background:#fff;border:1px solid #d9d2c8;border-radius:6px}
.yk-linkbtn{background:none!important;border:0!important;padding:0!important;margin:8px 0 0;color:#a0784a!important;font-weight:600;font-size:14px;cursor:pointer;box-shadow:none!important;text-transform:none;letter-spacing:0}
.woocommerce-checkout .yk-hint{display:block;font-size:13px;color:#6b6b6b;margin:6px 0 10px;line-height:1.5}
#yk-zone-info .ok,#yk-zone-info .no{display:block;border-radius:8px;padding:10px 12px;font-size:14px;line-height:1.5;margin:0 0 6px}
#yk-zone-info .ok{background:#f1f8f1;border:1px solid #cfe6cf;color:#2d6a2d}
#yk-zone-info .no{background:#fdf0ef;border:1px solid #f3c9c4;color:#9b2c22}
#yk-zone-info .no a{color:#9b2c22;font-weight:600;text-decoration:underline}
#yk_greeting{min-height:96px}
#yk-method{margin:0 0 24px}
#yk-method h3{margin:0 0 12px}
.yk-mopts{display:flex;gap:10px;flex-wrap:wrap}
.yk-mopt{flex:1 1 200px;margin:0;cursor:pointer;position:relative}
.yk-mopt input{position:absolute;opacity:0;pointer-events:none}
.yk-mopt span{display:block;padding:12px 14px;border:1.5px solid #d9d2c8;border-radius:10px;background:#faf8f5;line-height:1.35}
.yk-mopt span strong{display:block;font-size:15px}
.yk-mopt span small{display:block;font-size:12.5px;color:#6b6b6b;margin-top:2px}
.yk-mopt input:checked+span{border-color:#a0784a;background:#fbf4ec;box-shadow:0 0 0 1px #a0784a}
.yk-pinfo{background:#fbf7f1;border:1px solid #ece5db;border-radius:8px;padding:10px 12px;font-size:14px;line-height:1.5;margin:0 0 14px}
.yk-pinfo a{color:#a0784a;font-weight:600}
#yk-pslots .yk-slot{flex:1 1 22%;min-width:92px}
#yk-dlv{clear:both;margin:24px 0;padding:16px;border:1px solid #e6dfd5;border-radius:8px;background:#fff}
#yk-dlv h3{margin:0 0 12px}
#yk-slots,#yk-pslots{display:flex;gap:8px;flex-wrap:wrap}
#yk-dlv .yk-slot{flex:1 1 30%;min-width:96px;margin:0;cursor:pointer;position:relative}
#yk-dlv .yk-slot input{position:absolute;opacity:0;pointer-events:none}
#yk-dlv .yk-slot span{display:block;text-align:center;padding:10px 6px;border:1.5px solid #d9d2c8;border-radius:8px;font-size:13px;font-weight:600;background:#faf8f5}
#yk-dlv .yk-slot input:checked+span{border-color:#a0784a;background:#a0784a;color:#fff}
#yk-dlv .yk-slot.dis{cursor:not-allowed}
#yk-dlv .yk-slot.dis span{opacity:.4;text-decoration:line-through;background:#f2f2f2}
CSS;
	wp_add_inline_style( 'yk-dlv', $css );
	$js = <<<'JS'
(function($){
var box=document.getElementById('yk-dlv');
function esc(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML}
if(box){
 var ready=box.getAttribute('data-ready');var DS=JSON.parse(box.getAttribute('data-slots')),PS=JSON.parse(box.getAttribute('data-pslots'));
 var isP=function(){return $('input[name="yk_method"]:checked').val()==='pickup'};
 var upd=function(){
  var p=isP(),slots=p?PS:DS,$set=p?$('#yk-pslots'):$('#yk-slots'),$off=p?$('#yk-slots'):$('#yk-pslots');
  $off.hide().find('input').prop('checked',false).prop('disabled',true);$set.show();
  var d=$('#yk_date').val();
  $('#yk_date option').each(function(){var day=this.value,ok=false;for(var k in slots){if((day+' '+slots[k][0])>=ready)ok=true}this.disabled=!ok});
  if($('#yk_date option:selected').prop('disabled')){var f=$('#yk_date option:not(:disabled)').first().val();$('#yk_date').val(f);d=f}
  $set.find('input').each(function(){var s=slots[this.value];var ok=(d+' '+s[0])>=ready;this.disabled=!ok;$(this).closest('label').toggleClass('dis',!ok);if(!ok&&this.checked)this.checked=false});
  if(!$set.find('input:checked').length){$set.find('input:not(:disabled)').first().prop('checked',true)}
 };
 var mode=function(first){var p=isP();
  $('.woocommerce-shipping-fields').toggle(!p);$('#yk-pinfo').toggle(p);
  $('#yk-dlv-h').text(p?'Pickup Schedule':'Delivery Schedule');$('.yk-lbl-d').text(p?'Pickup':'Delivery');
  upd();if(!first)$(document.body).trigger('update_checkout');
  if(!p&&window.google&&window.google.maps&&window.google.maps.event&&window.ykMap){google.maps.event.trigger(window.ykMap,'resize')}
 };
 $(document).on('change','#yk_date',upd);
 $(document).on('change','input[name="yk_method"]',function(){mode(false)});
 mode(true);
}
if(!document.getElementById('yk-map')){window.ykGInit=function(){};return;}
var STORE={lat:-6.158,lng:106.905};
function km(a,b){var R=6371,dl=(b.lat-a.lat)*Math.PI/180,dg=(b.lng-a.lng)*Math.PI/180;var x=Math.sin(dl/2)*Math.sin(dl/2)+Math.cos(a.lat*Math.PI/180)*Math.cos(b.lat*Math.PI/180)*Math.sin(dg/2)*Math.sin(dg/2);return R*2*Math.atan2(Math.sqrt(x),Math.sqrt(1-x))}
function comps(list){var o={kel:'',kec:'',city:'',prov:''};(list||[]).forEach(function(c){var t=c.types||[],n=c.longText||c.long_name||'';if(!n)return;
 if(t.indexOf('administrative_area_level_4')>-1){if(!o.kel)o.kel=n}else if(t.indexOf('administrative_area_level_3')>-1){if(!o.kec)o.kec=n}else if(t.indexOf('administrative_area_level_2')>-1){if(!o.city)o.city=n}else if(t.indexOf('administrative_area_level_1')>-1){if(!o.prov)o.prov=n}else if(t.indexOf('sublocality_level_1')>-1||t.indexOf('sublocality')>-1){if(!o.kel)o.kel=n}else if(t.indexOf('locality')>-1){if(!o.city)o.city=n}});return o}
var last=null,gcNo=false;
function nomi(p,needAddr){fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=16&accept-language=id&lat='+p.lat+'&lon='+p.lng).then(function(r){return r.json()}).then(function(j){var a=(j&&j.address)||{};var jk=a['ISO3166-2-lvl4']==='ID-JK';
 var o={kel:a.village||'',kec:jk?(a.suburb||''):(a.city_district||a.municipality||''),city:jk?(a.city_district||''):(a.city||a.county||''),prov:jk?'DKI Jakarta':(a.state||'')};
 if(needAddr)last=p;apply(p.lat,p.lng,needAddr?(j.display_name||null):null,o)}).catch(function(){apply(p.lat,p.lng,needAddr?'Pinned location '+(+p.lat).toFixed(6)+', '+(+p.lng).toFixed(6):null,null)})}
function apply(lat,lng,addr,area){
 $('#yk_lat').val((+lat).toFixed(6));$('#yk_lng').val((+lng).toFixed(6));
 if(addr){$('#shipping_address_1').val(addr)}
 if(!$('#shipping_address_1').val()){$('#shipping_address_1').val('Pinned location '+(+lat).toFixed(6)+', '+(+lng).toFixed(6))}
 $('#yk_area').val(area?JSON.stringify(area):'');
 var a=$('#shipping_address_1').val();
 $('#yk-map-info').html('&#128205; '+esc(a)+' &middot; <a target="_blank" rel="noopener" href="https://www.google.com/maps?q='+(+lat).toFixed(6)+','+(+lng).toFixed(6)+'">View on Google Maps</a>');
 $(document.body).trigger('update_checkout');
}
function geoBtn(cb){$('#yk-map-me').on('click',function(){if(!navigator.geolocation){$('#yk-map-info').text('Your browser does not support location.');return}
 $('#yk-map-info').text('Getting your location…');
 navigator.geolocation.getCurrentPosition(function(p){cb(p.coords.latitude,p.coords.longitude)},function(){$('#yk-map-info').text('Location permission denied. Please search the address or drag the pin.')},{enableHighAccuracy:true,timeout:10000})})}

/* ---- Google Maps ---- */
window.ykGInit=function(){
 var el=document.getElementById('yk-map');if(!el||!window.google||!google.maps||el.getAttribute('data-ok'))return;el.setAttribute('data-ok','1');
 var map=new google.maps.Map(el,{center:STORE,zoom:11,mapTypeControl:false,streetViewControl:false,fullscreenControl:false,gestureHandling:'cooperative',clickableIcons:false});window.ykMap=map;
 var mk=new google.maps.Marker({position:STORE,map:map,draggable:true,visible:false});
 $('#yk_map_ok').val('1');
 var gc=google.maps.Geocoder?new google.maps.Geocoder():null;
 function ll2(o){return{lat:typeof o.lat==='function'?o.lat():o.lat,lng:typeof o.lng==='function'?o.lng():o.lng}}
 function show(p){mk.setPosition(p);mk.setVisible(true)}
 function rev(p,forceAddr){show(p);
  var needAddr=forceAddr||!last||km(last,p)>0.3||!$('#shipping_address_1').val();
  if(!gc||gcNo){nomi(p,needAddr);return}
  gc.geocode({location:p},function(r,st){
   if(st==='OK'&&r&&r.length){var all=[];for(var i=0;i<Math.min(r.length,5);i++){all=all.concat(r[i].address_components||[])}
    if(needAddr)last=p;apply(p.lat,p.lng,needAddr?r[0].formatted_address:null,comps(all))}
   else{if(st==='REQUEST_DENIED')gcNo=true;nomi(p,needAddr)}
  });
 }
 mk.addListener('dragend',function(e){rev(ll2(e.latLng),false)});
 map.addListener('click',function(e){rev(ll2(e.latLng),false)});
 geoBtn(function(lat,lng){var p={lat:lat,lng:lng};map.setCenter(p);map.setZoom(17);rev(p,true)});
 var old=document.getElementById('yk-map-q');
 if(google.maps.places&&google.maps.places.PlaceAutocompleteElement&&old){
  var pac=new google.maps.places.PlaceAutocompleteElement({includedRegionCodes:['id'],locationBias:{radius:30000,center:STORE}});
  pac.id='yk-map-pac';pac.setAttribute('placeholder','Search street, building or housing complex…');
  old.replaceWith(pac);$('#yk-map-find').hide();
  var go=function(place){if(!place)return;place.fetchFields({fields:['displayName','formattedAddress','location','addressComponents']}).then(function(){if(!place.location)return;
    var p=ll2(place.location);var fa=place.formattedAddress||'';var nm=place.displayName||'';
    var addr=(nm&&fa.indexOf(nm)!==0?nm+', ':'')+fa;last=p;show(p);map.setCenter(p);map.setZoom(17);
    apply(p.lat,p.lng,addr,comps(place.addressComponents))})
   .catch(function(){$('#yk-map-info').text('Could not load the address details. Please drag the pin to the exact spot.')})};
  pac.addEventListener('gmp-select',function(e){if(e.placePrediction)go(e.placePrediction.toPlace())});
  pac.addEventListener('gmp-placeselect',function(e){if(e.place)go(e.place)});
 }else if(old&&gc){
  var find=function(){var q=old.value.trim();if(!q)return;gc.geocode({address:q,region:'id'},function(r,st){if(st==='OK'&&r[0]){var p=ll2(r[0].geometry.location);last=p;show(p);map.setCenter(p);map.setZoom(17);apply(p.lat,p.lng,r[0].formatted_address,comps(r[0].address_components))}else{$('#yk-map-info').text('Address not found. Please drag the pin to the exact spot.')}})};
  $('#yk-map-find').on('click',find);$(old).on('keydown',function(e){if(e.key==='Enter'){e.preventDefault();find()}});
 }
};
if(window.google&&window.google.maps&&window.google.maps.Map){window.ykGInit();return}
if(!window.L){return}

/* ---- OpenStreetMap (tanpa API key) ---- */
var map=L.map('yk-map',{scrollWheelZoom:false}).setView([STORE.lat,STORE.lng],11);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; OpenStreetMap'}).addTo(map);
var mk=L.marker([STORE.lat,STORE.lng],{draggable:true}).addTo(map);
$('#yk_map_ok').val('1');
function setp(ll,addr){mk.setLatLng(ll);if(addr){apply(ll.lat,ll.lng,addr,null)}else{nomi({lat:ll.lat,lng:ll.lng},true)}}
mk.on('dragend',function(){setp(mk.getLatLng())});
map.on('click',function(e){setp(e.latlng)});
function find(q){q=(q||'').trim();if(!q)return;$('#yk-map-info').text('Searching…');
 fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=id&q='+encodeURIComponent(q)).then(function(r){return r.json()}).then(function(a){
  if(a&&a[0]){var ll=L.latLng(+a[0].lat,+a[0].lon);map.setView(ll,17);setp(ll,a[0].display_name)}else{$('#yk-map-info').text('Address not found. Please drag the pin to the exact spot.')}
 }).catch(function(){$('#yk-map-info').text('Search failed. Please drag the pin to the exact spot.')})}
$('#yk-map-find').on('click',function(){find($('#yk-map-q').val())});
$('#yk-map-q').on('keydown',function(e){if(e.key==='Enter'){e.preventDefault();find(this.value)}});
geoBtn(function(lat,lng){var ll=L.latLng(lat,lng);map.setView(ll,17);setp(ll)});
setTimeout(function(){map.invalidateSize()},400);
})(jQuery);
JS;
	wp_add_inline_script( 'yk-dlv', $js );
} );

/* Pesan error: Sender/Recipient, tanpa duplikat "No shipping method" */
add_filter( 'gettext_with_context', function ( $t, $text, $context, $domain ) {
	if ( 'woocommerce' === $domain && 'checkout-validation' === $context ) {
		if ( 'Billing %s' === $text ) {
			return 'Sender %s';
		}
		if ( 'Shipping %s' === $text ) {
			return 'Recipient %s';
		}
	}
	return $t;
}, 20, 4 );

add_action( 'woocommerce_after_checkout_validation', function ( $data, $errors ) {
	if ( 'pickup' === yk_method_get() ) {
		return;
	}
	$z = yk_zone_get();
	if ( ( ! $z || ! isset( yk_zone_fees()[ (int) $z['zone'] ] ) ) && $errors->get_error_message( 'shipping' ) ) {
		$errors->remove( 'shipping' );
	}
}, 20, 2 );
