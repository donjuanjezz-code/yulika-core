<?php
// Dari Code Snippets #21 "FAQ: tabel zona antar + schema (Yulika)" — scope: global
/* ===== Yulika: halaman FAQ — tabel zona antar otomatis [yk_delivery_zones] + schema FAQPage ===== */

function yk_faq_title( $s ) {
	$s = ucwords( $s );
	return str_replace( array( 'Pik', 'Dki' ), array( 'PIK', 'DKI' ), $s );
}

/* Tabel zona diambil langsung dari data zona checkout (snippet #6), jadi selalu sama dengan ongkir di checkout */
add_shortcode( 'yk_delivery_zones', function () {
	if ( ! function_exists( 'yk_zone_tables' ) || ! function_exists( 'yk_zone_fees' ) ) {
		return '';
	}
	list( $jkel, $jkec ) = yk_zone_tables();
	list( $bkel, $bkec ) = function_exists( 'yk_zone_bekasi' ) ? yk_zone_bekasi() : array( array(), array() );
	$fees = yk_zone_fees();
	$z    = array();
	$add  = function ( $list, $group ) use ( &$z ) {
		$seen = array( 'tanjungpriuk' => 1, 'grogol' => 1, 'mampang' => 1, 'makassar' => 1, 'sunter' => 1, 'medansatria' => ( 'Area Bekasi' === $group ? 1 : null ) );
		$seen = array_filter( $seen );
		foreach ( $list as $name => $zone ) {
			$key = str_replace( ' ', '', $name );
			if ( isset( $seen[ $key ] ) ) {
				continue; // ejaan alternatif (pulogadung / pulo gadung)
			}
			$seen[ $key ]               = 1;
			$z[ $zone ][ $group ][]     = yk_faq_title( $name );
		}
	};
	$add( $jkec, 'Jakarta' );
	$add( $jkel, 'Area Jakarta' );
	$add( $bkec, 'Bekasi' );
	$add( $bkel, 'Area Bekasi' );
	$z[3]['Tangerang'][] = 'Seluruh area PIK 2 (Pantai Indah Kapuk 2)';
	ksort( $z );
	$h = '<div class="yk-zones">';
	foreach ( $z as $zone => $groups ) {
		$served = isset( $fees[ $zone ] );
		$h     .= '<div class="yk-zone' . ( $served ? '' : ' off' ) . '"><div class="yk-zh"><span class="yk-zn">' . ( $served ? 'Zona ' . (int) $zone : 'Belum terjangkau' ) . '</span><span class="yk-zf">' . ( $served ? 'Rp' . number_format( $fees[ $zone ], 0, ',', '.' ) : 'Hubungi kami' ) . '</span></div>';
		foreach ( array( 'Jakarta', 'Area Jakarta', 'Bekasi', 'Area Bekasi', 'Tangerang' ) as $g ) {
			if ( empty( $groups[ $g ] ) ) {
				continue;
			}
			$h .= '<p><b>' . esc_html( 'Tangerang' === $g ? 'Tangerang' : ( 0 === strpos( $g, 'Area' ) ? $g . ' (kelurahan)' : $g . ' (kecamatan)' ) ) . ':</b> ' . esc_html( implode( ', ', array_unique( $groups[ $g ] ) ) ) . '</p>';
		}
		$h .= '</div>';
	}
	$h .= '<div class="yk-zone free"><div class="yk-zh"><span class="yk-zn">Store Pick Up</span><span class="yk-zf">Gratis</span></div><p>Ambil sendiri di toko kami di Kelapa Gading, setiap hari 08.00–19.00 WIB.</p></div>';
	$h .= '</div>';
	return $h;
} );

/* Schema FAQPage untuk Google */
add_action( 'wp_head', function () {
	if ( ! is_page( 'faq' ) ) {
		return;
	}
	$qa = array(
		array( 'Bagaimana cara memesan bunga di Yulika Florist?', 'Pilih rangkaian, cek foto dan harga lalu tekan Next. Pilih Delivery atau Store Pick Up, tentukan tanggal dan jam, isi data penerima, pengirim dan kartu ucapan, pilih pembayaran (Transfer Bank atau PayPal) lalu tekan Place Order. Terakhir tekan Send to WhatsApp di halaman terima kasih untuk konfirmasi pesanan.' ),
		array( 'Apakah detail pesanan bisa diubah?', 'Bisa, maksimal 24 jam sebelum tanggal pengiriman. Perubahan hanya untuk jam pengiriman, alamat pengiriman, atau isi kartu ucapan. Hubungi WhatsApp 0813-1588-1538 atau telepon (021) 4532630 dengan menyebutkan nomor pesanan.' ),
		array( 'Bagaimana saya tahu bunga sudah sampai?', 'Kami akan mengabari Anda melalui WhatsApp begitu pesanan sudah sampai di alamat tujuan.' ),
		array( 'Ke mana saja Yulika Florist mengantar dan berapa biayanya?', 'Kami mengantar ke Jakarta, sebagian Bekasi, dan seluruh area PIK 2. Zona 1 Rp50.000, Zona 2 Rp75.000, Zona 3 Rp100.000. Zona dihitung otomatis di checkout dari titik peta alamat penerima. Store Pick Up di Kelapa Gading gratis.' ),
	);
	$ld = array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array() );
	foreach ( $qa as $q ) {
		$ld['mainEntity'][] = array( '@type' => 'Question', 'name' => $q[0], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $q[1] ) );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	echo '<meta name="description" content="FAQ Yulika Florist: cara pesan bunga online, perubahan pesanan, konfirmasi pengiriman via WhatsApp, serta zona dan ongkir antar Jakarta &amp; Bekasi.">' . "\n";
}, 2 );
