<?php
// Dari Code Snippets #14 "Yulika: PayPal (USD) manual link" — scope: global
/* ===== Yulika: PayPal (USD) manual — total dikonversi ke USD + link PayPal.me (PayPal tidak menerima IDR) ===== */

function yk_pp_opt( $k, $def = '' ) {
	$s = get_option( 'woocommerce_yk_paypal_usd_settings', array() );
	return ( is_array( $s ) && isset( $s[ $k ] ) && '' !== $s[ $k ] ) ? $s[ $k ] : $def;
}

/* Kurs IDR per 1 USD: live (open.er-api.com), cache 12 jam; cadangan = kurs terakhir / setting */
function yk_usd_rate() {
	$r = (float) get_transient( 'yk_usd_idr' );
	if ( $r > 0 ) {
		return $r;
	}
	$res = wp_remote_get( 'https://open.er-api.com/v6/latest/USD', array( 'timeout' => 6 ) );
	if ( ! is_wp_error( $res ) && 200 === (int) wp_remote_retrieve_response_code( $res ) ) {
		$j = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( ! empty( $j['rates']['IDR'] ) && (float) $j['rates']['IDR'] > 1000 ) {
			$r = (float) $j['rates']['IDR'];
			update_option( 'yk_usd_idr_last', $r, false );
			set_transient( 'yk_usd_idr', $r, 12 * HOUR_IN_SECONDS );
			return $r;
		}
	}
	$r = (float) get_option( 'yk_usd_idr_last', 0 );
	if ( $r <= 0 ) {
		$r = (float) yk_pp_opt( 'fallback_rate', 16500 );
	}
	set_transient( 'yk_usd_idr', $r, HOUR_IN_SECONDS );
	return $r;
}

function yk_to_usd( $idr ) {
	$m    = (float) yk_pp_opt( 'markup', 7 );
	$rate = yk_usd_rate();
	if ( $rate <= 0 ) {
		return 0;
	}
	return ceil( (float) $idr / $rate * ( 1 + $m / 100 ) * 100 ) / 100;
}

/* Akun PayPal: Merchant ID (contoh ABCD1234EFGH5), email PayPal, atau username PayPal.me */
function yk_pp_link( $order ) {
	$u = trim( (string) yk_pp_opt( 'paypalme', '' ) );
	$a = (float) $order->get_meta( '_yk_usd_amount' );
	if ( '' === $u || $a <= 0 ) {
		return '';
	}
	$amt = number_format( $a, 2, '.', '' );
	if ( is_email( $u ) || ( preg_match( '/^[A-Z0-9]{10,16}$/', $u ) && preg_match( '/\d/', $u ) ) ) {
		return add_query_arg(
			array(
				'cmd'           => '_xclick',
				'business'      => rawurlencode( $u ),
				'item_name'     => rawurlencode( 'Yulika Florist Order #' . $order->get_order_number() ),
				'invoice'       => rawurlencode( 'YK-' . $order->get_order_number() ),
				'amount'        => $amt,
				'currency_code' => 'USD',
				'no_shipping'   => '1',
				'return'        => rawurlencode( $order->get_checkout_order_received_url() ),
			),
			'https://www.paypal.com/cgi-bin/webscr'
		);
	}
	$u = trim( preg_replace( '~^(https?://)?(www\.)?paypal\.me/~i', '', $u ), '/ ' );
	return 'https://paypal.me/' . rawurlencode( $u ) . '/' . $amt . 'USD';
}

add_filter( 'woocommerce_payment_gateways', function ( $gateways ) {
	if ( ! class_exists( 'YK_Gateway_PayPal_USD' ) && class_exists( 'WC_Payment_Gateway' ) ) {
		class YK_Gateway_PayPal_USD extends WC_Payment_Gateway {
			public function __construct() {
				$this->id                 = 'yk_paypal_usd';
				$this->method_title       = 'PayPal (USD) – manual link';
				$this->method_description = 'Order dibuat dalam Rupiah. Pembeli melihat total dalam USD (kurs live + markup) dan link PayPal.me untuk membayar. Cek pembayaran di akun PayPal, lalu ubah status order ke Processing.';
				$this->has_fields         = false;
				$this->init_form_fields();
				$this->init_settings();
				$this->title       = $this->get_option( 'title' );
				$this->description = $this->get_option( 'description' );
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
				add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou' ) );
			}

			public function init_form_fields() {
				$this->form_fields = array(
					'enabled'       => array( 'title' => 'Enable', 'type' => 'checkbox', 'label' => 'Enable PayPal (USD)', 'default' => 'yes' ),
					'title'         => array( 'title' => 'Title', 'type' => 'text', 'default' => 'PayPal (USD)' ),
					'description'   => array( 'title' => 'Description', 'type' => 'textarea', 'default' => 'Pay with your PayPal account or card in US dollars. After placing your order you will receive a secure PayPal payment link.' ),
					'paypalme'      => array( 'title' => 'PayPal Merchant ID / email / PayPal.me', 'type' => 'text', 'default' => '', 'description' => 'Isi salah satu: Merchant ID (PayPal → Account Settings → Business information, 13 karakter), email akun PayPal, atau username PayPal.me. Kosong = link PayPal dikirim manual lewat WhatsApp.' ),
					'markup'        => array( 'title' => 'Markup (%)', 'type' => 'number', 'default' => '7', 'description' => 'Tambahan untuk biaya PayPal & selisih kurs.' ),
					'fallback_rate' => array( 'title' => 'Kurs cadangan (IDR per 1 USD)', 'type' => 'number', 'default' => '16500', 'description' => 'Dipakai hanya jika kurs live gagal diambil. Kurs live saat ini: Rp' . number_format( (float) get_option( 'yk_usd_idr_last', 0 ), 0, ',', '.' ) . ' per USD.' ),
				);
			}

			public function get_description() {
				$d = parent::get_description();
				if ( ! is_admin() && function_exists( 'WC' ) && WC()->cart ) {
					$t = (float) WC()->cart->get_total( 'edit' );
					if ( $t > 0 ) {
						$d .= '<br><strong>Estimated PayPal total: US$' . number_format( yk_to_usd( $t ), 2 ) . '</strong> <small>(includes ' . esc_html( yk_pp_opt( 'markup', 7 ) ) . '% PayPal fee &amp; currency conversion)</small>';
					}
				}
				return $d;
			}

			public function process_payment( $order_id ) {
				$order = wc_get_order( $order_id );
				$usd   = yk_to_usd( $order->get_total() );
				$order->update_meta_data( '_yk_usd_amount', $usd );
				$order->update_meta_data( '_yk_usd_rate', yk_usd_rate() );
				$order->update_meta_data( '_yk_usd_markup', (float) yk_pp_opt( 'markup', 7 ) );
				$order->update_status( 'on-hold', 'Awaiting PayPal payment of US$' . number_format( $usd, 2 ) . '.' );
				wc_reduce_stock_levels( $order_id );
				WC()->cart->empty_cart();
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			}

			public function thankyou( $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order || ! $order->has_status( array( 'on-hold', 'pending' ) ) ) {
					return;
				}
				echo yk_pp_box( $order ); // phpcs:ignore
			}
		}
	}
	if ( class_exists( 'YK_Gateway_PayPal_USD' ) ) {
		$gateways[] = 'YK_Gateway_PayPal_USD';
	}
	return $gateways;
} );

function yk_pp_box( $order, $email = false ) {
	$usd  = (float) $order->get_meta( '_yk_usd_amount' );
	$rate = (float) $order->get_meta( '_yk_usd_rate' );
	$mk   = (float) $order->get_meta( '_yk_usd_markup' );
	$link = yk_pp_link( $order );
	$h    = '<div class="yk-pp" style="margin:0 0 20px;padding:18px;border:1px solid #c9daf2;background:#f3f8ff;border-radius:10px;text-align:center">';
	$h   .= '<p style="margin:0 0 4px;font-size:15px;font-weight:600">Amount to pay with PayPal</p>';
	$h   .= '<p style="margin:0 0 6px;font-size:26px;font-weight:700;color:#003087">US$' . number_format( $usd, 2 ) . '</p>';
	$h   .= '<p style="margin:0 0 14px;font-size:12.5px;color:#555">Order total Rp' . number_format( (float) $order->get_total(), 0, ',', '.' ) . ' &middot; rate Rp' . number_format( $rate, 0, ',', '.' ) . '/USD &middot; includes ' . esc_html( $mk ) . '% PayPal fee &amp; conversion</p>';
	if ( $link ) {
		$h .= '<a href="' . esc_url( $link ) . '" target="_blank" rel="noopener" style="display:inline-block;background:#ffc439;color:#111;padding:13px 24px;border-radius:24px;font-weight:700;text-decoration:none">Pay US$' . number_format( $usd, 2 ) . ' with PayPal</a>';
		$h .= '<p style="margin:12px 0 0;font-size:13px;color:#555">Please pay within 2 hours. Your order is confirmed once we receive the payment.</p>';
	} else {
		$h .= '<p style="margin:0;font-size:14px">We will send you a secure PayPal payment link via WhatsApp shortly.</p>';
	}
	$h .= '</div>';
	return $h;
}

/* Email ke pembeli (jika ada email) */
add_action( 'woocommerce_email_before_order_table', function ( $order, $sent_to_admin = false ) {
	if ( $sent_to_admin || 'yk_paypal_usd' !== $order->get_payment_method() || ! $order->has_status( array( 'on-hold', 'pending' ) ) ) {
		return;
	}
	echo yk_pp_box( $order, true ); // phpcs:ignore
}, 10, 2 );

/* Admin order: info USD */
add_action( 'woocommerce_admin_order_data_after_billing_address', function ( $order ) {
	if ( 'yk_paypal_usd' !== $order->get_payment_method() ) {
		return;
	}
	$usd  = (float) $order->get_meta( '_yk_usd_amount' );
	$link = yk_pp_link( $order );
	echo '<p><strong>PayPal amount:</strong> US$' . esc_html( number_format( $usd, 2 ) ) . ' (rate Rp' . esc_html( number_format( (float) $order->get_meta( '_yk_usd_rate' ), 0, ',', '.' ) ) . '/USD)' . ( $link ? '<br><a href="' . esc_url( $link ) . '" target="_blank" rel="noopener">' . esc_html( $link ) . '</a>' : '' ) . '</p>';
} );
