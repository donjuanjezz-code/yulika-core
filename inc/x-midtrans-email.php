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
		if ( '' === $email || ! is_email( $email ) ) {
			unset( $params['customer_details']['email'] );
		} else {
			$params['customer_details']['email'] = $email;
		}
		return $params;
	}
}
add_filter( 'midtrans_snap_params_main_before_charge', 'yk_midtrans_clean_params', 20 );
