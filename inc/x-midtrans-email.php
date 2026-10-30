<?php
// Midtrans: perbaikan error "customer_details.email format is invalid" (HTTP 400) — 30 Okt 2026.
// Penyebab: checkout v3 (s06) sengaja tidak meminta email (cukup nama + nomor HP), jadi order tamu
// tidak punya billing email. Plugin Midtrans tetap mengirim email kosong ke Snap API sehingga ditolak.
// Solusi: sebelum request ke Snap, hapus email yang kosong/tidak valid (email opsional di Snap API).
// Order WooCommerce tidak diubah; hanya data yang dikirim ke Midtrans.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'yk_midtrans_clean_params' ) ) {
	function yk_midtrans_clean_params( $params ) {
		if ( ! is_array( $params ) || empty( $params['customer_details'] ) || ! is_array( $params['customer_details'] ) ) {
			return $params;
		}
		$email = isset( $params['customer_details']['email'] ) ? trim( (string) $params['customer_details']['email'] ) : '';
		$had   = array_key_exists( 'email', $params['customer_details'] );
		if ( '' === $email || ! is_email( $email ) ) {
			unset( $params['customer_details']['email'] );
		} else {
			$params['customer_details']['email'] = $email;
		}
		// DEBUG sementara (30 Okt): catat apakah filter jalan. Hapus setelah terverifikasi.
		@file_put_contents( WP_CONTENT_DIR . '/uploads/yk-mt-debug.log', gmdate( 'c' ) . ' order=' . ( isset( $params['transaction_details']['order_id'] ) ? $params['transaction_details']['order_id'] : '?' ) . ' had_email=' . ( $had ? 'y' : 'n' ) . ' len=' . strlen( $email ) . ' now=' . ( array_key_exists( 'email', $params['customer_details'] ) ? 'y' : 'n' ) . "\n", FILE_APPEND );
		return $params;
	}
}
add_filter( 'midtrans_snap_params_main_before_charge', 'yk_midtrans_clean_params', 20 );

// DEBUG sementara (30 Okt): lihat params final tepat sebelum dikirim (prioritas paling akhir).
add_filter( 'midtrans_snap_params_main_before_charge', function ( $params ) {
	$cd = isset( $params['customer_details'] ) && is_array( $params['customer_details'] ) ? $params['customer_details'] : array();
	@file_put_contents( WP_CONTENT_DIR . '/uploads/yk-mt-debug.log', gmdate( 'c' ) . ' FINAL keys=' . implode( ',', array_keys( $cd ) ) . ' email=' . ( isset( $cd['email'] ) ? var_export( $cd['email'], true ) : '-' ) . "\n", FILE_APPEND );
	return $params;
}, 9999 );
