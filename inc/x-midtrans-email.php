<?php
// Midtrans: perbaikan error "customer_details.email format is invalid" (HTTP 400) — 30 Okt 2026.
// Penyebab: checkout v3 (s06) sengaja tidak meminta email (cukup nama + nomor HP), jadi order tamu
// tidak punya billing email. Plugin Midtrans 2.32.3 memakai Sanitizer lama yang SELALU mengisi
// customer_details.email (kosong) dan memotongnya maks. 45 karakter, sehingga Snap API menolak.
// Solusi: kalau email pembeli kosong / tidak valid / > 45 karakter, kirim email toko ke Midtrans
// (yulikaflorist+order{ID}@gmail.com). Order WooCommerce tidak diubah; hanya data ke Midtrans.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'yk_midtrans_fallback_email' ) ) {
	function yk_midtrans_fallback_email( $order_id = '' ) {
		$admin = (string) get_option( 'admin_email' );
		if ( ! is_email( $admin ) ) {
			return '';
		}
		list( $local, $domain ) = explode( '@', $admin, 2 );
		$tag = preg_replace( '/[^0-9A-Za-z]/', '', (string) $order_id );
		if ( '' !== $tag && in_array( strtolower( $domain ), array( 'gmail.com', 'googlemail.com' ), true ) ) {
			$plus = $local . '+order' . $tag . '@' . $domain; // Gmail: tetap masuk inbox toko, tertandai per order
			if ( strlen( $plus ) <= 45 ) {
				return $plus;
			}
		}
		return strlen( $admin ) <= 45 ? $admin : '';
	}
}

if ( ! function_exists( 'yk_midtrans_clean_params' ) ) {
	function yk_midtrans_clean_params( $params ) {
		if ( ! is_array( $params ) || empty( $params['customer_details'] ) || ! is_array( $params['customer_details'] ) ) {
			return $params;
		}
		$email = isset( $params['customer_details']['email'] ) ? trim( (string) $params['customer_details']['email'] ) : '';
		if ( '' !== $email && is_email( $email ) && strlen( $email ) <= 45 ) {
			$params['customer_details']['email'] = $email;
			return $params;
		}
		$order_id = isset( $params['transaction_details']['order_id'] ) ? $params['transaction_details']['order_id'] : '';
		$fallback = yk_midtrans_fallback_email( $order_id );
		if ( '' !== $fallback ) {
			$params['customer_details']['email'] = $fallback;
		}
		return $params;
	}
}
add_filter( 'midtrans_snap_params_main_before_charge', 'yk_midtrans_clean_params', 20 );
