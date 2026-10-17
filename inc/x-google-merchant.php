<?php
// Google Search Console (17 Okt 2026):
// 1) File verifikasi kepemilikan properti https://yulikaflorist.com/ (jangan dihapus, Google cek ulang berkala).
// 2) Merchant listings: tambah hasMerchantReturnPolicy + shippingDetails di "offers" schema produk WooCommerce.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* 1) Verifikasi Search Console: /googlec8dcffb1208932f1.html */
add_action( 'init', function () {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? strtok( (string) $_SERVER['REQUEST_URI'], '?' ) : '';
	$files = array( 'googlec8dcffb1208932f1.html', 'googlec2372ad2536278ed.html' ); // 1 file per akun Google pemilik
	$file  = ltrim( $path, '/' );
	if ( in_array( $file, $files, true ) ) {
		status_header( 200 );
		header( 'Content-Type: text/html; charset=UTF-8' );
		echo 'google-site-verification: ' . $file;
		exit;
	}
}, 0 );

/* 2) Merchant listings */
function yk_merchant_offer_extras( $offer ) {
	$offer['hasMerchantReturnPolicy'] = array(
		'@type'                => 'MerchantReturnPolicy',
		'applicableCountry'    => 'ID',
		// Bunga segar: tidak bisa retur/batal; klaim kerusakan maks. 3 jam via WhatsApp (lihat halaman kebijakan).
		'returnPolicyCategory' => 'https://schema.org/MerchantReturnNotPermitted',
		'merchantReturnLink'   => home_url( '/return-cancellations/' ),
	);
	$fees = function_exists( 'yk_zone_fees' ) ? yk_zone_fees() : array( 1 => 50000 );
	$offer['shippingDetails'] = array(
		'@type'               => 'OfferShippingDetails',
		'shippingRate'        => array(
			'@type'    => 'MonetaryAmount',
			'value'    => (int) min( $fees ), // ongkir mulai (Zona 1); final sesuai zona di checkout
			'currency' => 'IDR',
		),
		'shippingDestination' => array(
			'@type'          => 'DefinedRegion',
			'addressCountry' => 'ID',
			'addressRegion'  => array( 'JK', 'JB' ), // DKI Jakarta + Bekasi (Jawa Barat)
		),
		'deliveryTime'        => array(
			'@type'        => 'ShippingDeliveryTime',
			'handlingTime' => array( '@type' => 'QuantitativeValue', 'minValue' => 0, 'maxValue' => 0, 'unitCode' => 'DAY' ),
			'transitTime'  => array( '@type' => 'QuantitativeValue', 'minValue' => 0, 'maxValue' => 1, 'unitCode' => 'DAY' ),
		),
	);
	return $offer;
}
add_filter( 'yk_product_schema_offer', 'yk_merchant_offer_extras', 20 );            // schema produk custom (inc/s19)
add_filter( 'woocommerce_structured_data_product_offer', 'yk_merchant_offer_extras', 20 ); // cadangan: schema bawaan WooCommerce
