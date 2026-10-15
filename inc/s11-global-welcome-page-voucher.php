<?php
// Dari Code Snippets #11 "Yulika: Welcome page + voucher 10% 24 jam" — scope: global
/* ===== Yulika: Welcome page — daftar + auto login + voucher 10% (1x, 24 jam) ===== */

function yk_wel_info( $uid ) {
	$code = get_user_meta( $uid, 'yk_welcome_coupon', true );
	if ( ! $code || ! function_exists( 'wc_get_coupon_id_by_code' ) ) {
		return null;
	}
	$cid = wc_get_coupon_id_by_code( $code );
	if ( ! $cid ) {
		return null;
	}
	$c   = new WC_Coupon( $cid );
	$exp = $c->get_date_expires();
	$ts  = $exp ? $exp->getTimestamp() : 0;
	if ( $c->get_usage_count() >= 1 ) {
		$st = 'used';
	} elseif ( $ts && $ts < time() ) {
		$st = 'expired';
	} else {
		$st = 'active';
	}
	return array( 'code' => $c->get_code(), 'exp' => $ts, 'status' => $st );
}

function yk_wel_time( $ts ) {
	return wp_date( 'j M Y, H:i', $ts ) . ' WIB';
}

function yk_wel_css() {
	static $done = false;
	if ( $done ) {
		return '';
	}
	$done = true;
	return '<style>
.yk-wel{max-width:460px;margin:24px auto 48px;padding:28px 22px;background:#fff;border:1px solid #ece5db;border-radius:14px;box-shadow:0 6px 24px rgba(60,40,20,.06);text-align:center}
.yk-wel-eyebrow{font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:#a0784a;margin:0 0 6px}
.yk-wel h2{font-size:28px;line-height:1.2;margin:0 0 8px}
.yk-wel-sub{color:#6b625a;font-size:15px;margin:0 0 20px}
.yk-wel form{text-align:left}
.yk-wel .yk-f{margin:0 0 14px}
.yk-wel label{display:block;font-size:13px;font-weight:600;color:#3d352e;margin:0 0 5px}
.yk-wel input[type=text],.yk-wel input[type=email],.yk-wel input[type=tel],.yk-wel input[type=password]{width:100%;box-sizing:border-box;height:46px;padding:0 14px;border:1px solid #d9d2c8;border-radius:8px;font-size:16px;background:#fff;color:#2b2621}
.yk-wel input:focus{border-color:#a0784a;outline:none;box-shadow:0 0 0 3px rgba(160,120,74,.15)}
.yk-wel-priv{font-size:12.5px;color:#7a7067;margin:2px 0 16px}
.yk-wel button[type=submit]{width:100%;height:50px;border:0;border-radius:8px;background:#a0784a;color:#fff;font-size:16px;font-weight:600;letter-spacing:.04em;cursor:pointer}
.yk-wel button[type=submit]:hover{background:#8a6538}
.yk-wel-fine{font-size:12px;color:#8a8077;margin:12px 0 0;text-align:center}
.yk-wel-terms{font-size:12px;color:#8a8077;margin:6px 0 0;text-align:center}
.yk-wel-login{font-size:14px;margin:18px 0 0;text-align:center}
.yk-wel-login a,.yk-wel-err a{color:#a0784a;font-weight:600}
.yk-wel-err{background:#fdf0ef;border:1px solid #f3c9c4;color:#9b2c22;border-radius:8px;padding:10px 14px;margin:0 0 16px;font-size:14px;text-align:left}
.yk-wel-err ul{margin:0;padding-left:18px}
.yk-hp{position:absolute!important;left:-9999px!important;width:1px;height:1px;overflow:hidden}
.yk-vch{border:2px dashed #a0784a;border-radius:12px;padding:16px;margin:0 0 18px;background:#fbf7f1;text-align:center}
.yk-vch-h{font-size:14px;color:#3d352e;margin-bottom:6px;font-weight:600}
.yk-vch-code{font-size:24px;font-weight:700;letter-spacing:.08em;color:#a0784a;word-break:break-all}
.yk-vch-meta{font-size:13px;color:#6b625a;margin-top:6px}
.yk-vch-note{font-size:12px;color:#8a8077;margin-top:4px}
.yk-wel-btn2{display:inline-block;margin:6px;padding:12px 22px;border-radius:8px;background:#a0784a;color:#fff!important;text-decoration:none!important;font-weight:600}
.yk-wel-btn2.alt{background:#fff;color:#a0784a!important;border:1px solid #a0784a}
.yk-wel-priv,.yk-wel-fine,.yk-wel-terms,.yk-vch-meta,.yk-vch-note{line-height:1.5}
.yk-wel-ok{background:#f1f8f1;border:1px solid #cfe6cf;color:#2d6a2d;border-radius:8px;padding:12px 14px;margin:0 0 16px;font-size:14px}
</style>';
}

function yk_wel_voucher_box( $i, $heading = '' ) {
	if ( ! $i || 'active' !== $i['status'] ) {
		return '';
	}
	$h = '<div class="yk-vch">';
	if ( $heading ) {
		$h .= '<div class="yk-vch-h">' . esc_html( $heading ) . '</div>';
	}
	$h .= '<div class="yk-vch-code">' . esc_html( strtoupper( $i['code'] ) ) . '</div>';
	$h .= '<div class="yk-vch-meta">10% off &middot; one-time use &middot; valid until ' . esc_html( yk_wel_time( $i['exp'] ) ) . '</div>';
	$h .= '<div class="yk-vch-note">Applied automatically at checkout.</div></div>';
	return $h;
}

/* ---------- Form ---------- */
add_shortcode( 'yk_welcome_form', function () {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	do_action( 'litespeed_control_set_nocache', 'yulika welcome form' );

	$out  = yk_wel_css();
	$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	$acct = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();

	if ( is_user_logged_in() ) {
		$i    = yk_wel_info( get_current_user_id() );
		$out .= '<div class="yk-wel"><p class="yk-wel-eyebrow">Yulika Florist</p><h2>You are signed in</h2>';
		if ( $i && 'active' === $i['status'] ) {
			$out .= '<p class="yk-wel-sub">Your welcome voucher is ready to use.</p>' . yk_wel_voucher_box( $i );
		} else {
			$out .= '<p class="yk-wel-sub">Thank you for being part of Yulika Florist.</p>';
		}
		$out .= '<a class="yk-wel-btn2" href="' . esc_url( $shop ) . '">Shop now</a><a class="yk-wel-btn2 alt" href="' . esc_url( $acct ) . '">My account</a></div>';
		return $out;
	}

	$err = isset( $GLOBALS['yk_wel_err'] ) ? (array) $GLOBALS['yk_wel_err'] : array();
	$v   = function ( $k ) use ( $err ) {
		return ( $err && isset( $_POST[ $k ] ) ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) ) : '';
	};
	$f   = function ( $id, $label, $type, $extra = '' ) use ( $v ) {
		$val = ( 'password' === $type ) ? '' : $v( $id );
		return '<p class="yk-f"><label for="' . $id . '">' . $label . '</label><input type="' . $type . '" id="' . $id . '" name="' . $id . '" value="' . $val . '" required ' . $extra . '></p>';
	};

	$out .= '<div class="yk-wel" id="yk-welcome">';
	$out .= '<p class="yk-wel-eyebrow">Welcome to Yulika Florist</p>';
	$out .= '<h2>Get 10% Off Your First Order</h2>';
	$out .= '<p class="yk-wel-sub">Sign up to get special offers, updates, gift ideas &amp; more.</p>';
	if ( $err ) {
		$out .= '<div class="yk-wel-err" role="alert"><ul><li>' . implode( '</li><li>', array_map( 'wp_kses_post', $err ) ) . '</li></ul></div>';
	}
	$out .= '<form method="post" action="' . esc_url( get_permalink() ) . '#yk-welcome">';
	$out .= $f( 'yk_w_email', 'Email', 'email', 'autocomplete="email" placeholder="name@email.com"' );
	$out .= $f( 'yk_w_name', 'Full Name', 'text', 'autocomplete="name"' );
	$out .= $f( 'yk_w_wa', 'WhatsApp', 'tel', 'autocomplete="tel" inputmode="tel" placeholder="08xxxxxxxxxx"' );
	$out .= $f( 'yk_w_pass', 'Password', 'password', 'autocomplete="new-password" minlength="8" placeholder="Minimum 8 characters"' );
	$out .= $f( 'yk_w_pass2', 'Confirm Password', 'password', 'autocomplete="new-password" minlength="8"' );
	$out .= '<p class="yk-hp" aria-hidden="true"><label>Website <input type="text" name="yk_w_site" value="" tabindex="-1" autocomplete="off"></label></p>';
	$out .= '<input type="hidden" name="yk_wel_go" value="1">' . wp_nonce_field( 'yk_wel', 'yk_wel_nonce', false, false );
	$out .= '<p class="yk-wel-priv">Your details will only be used to send you personalised offers and emails.</p>';
	$out .= '<button type="submit">Subscribe</button>';
	$out .= '<p class="yk-wel-fine">By clicking Subscribe, you agree to receive marketing emails from Yulika Florist.</p>';
	$out .= '<p class="yk-wel-terms">Voucher valid for 24 hours from sign-up, for one order only.</p>';
	$out .= '</form>';
	$out .= '<p class="yk-wel-login">Already have an account? <a href="' . esc_url( $acct ) . '">Log in</a></p>';
	$out .= '</div>';
	return $out;
} );

/* ---------- Proses daftar ---------- */
add_action( 'template_redirect', function () {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) || empty( $_POST['yk_wel_go'] ) ) {
		return;
	}
	if ( is_user_logged_in() || ! function_exists( 'wc_create_new_customer' ) ) {
		return;
	}
	if ( ! empty( $_POST['yk_w_site'] ) ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
	if ( empty( $_POST['yk_wel_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['yk_wel_nonce'] ) ), 'yk_wel' ) ) {
		$GLOBALS['yk_wel_err'] = array( 'Your session has expired. Please refresh the page and try again.' );
		return;
	}

	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? preg_replace( '/[^0-9a-fA-F:.]/', '', $_SERVER['REMOTE_ADDR'] ) : '';
	$rk = 'yk_wel_rl_' . md5( $ip );
	$n  = (int) get_transient( $rk );
	if ( $n >= 3 ) {
		$GLOBALS['yk_wel_err'] = array( 'Too many sign-ups from this connection. Please try again in an hour or contact us on WhatsApp.' );
		return;
	}

	$acct  = wc_get_page_permalink( 'myaccount' );
	$email = sanitize_email( wp_unslash( isset( $_POST['yk_w_email'] ) ? $_POST['yk_w_email'] : '' ) );
	$name  = trim( preg_replace( '/\s+/', ' ', sanitize_text_field( wp_unslash( isset( $_POST['yk_w_name'] ) ? $_POST['yk_w_name'] : '' ) ) ) );
	$wa    = yk_norm_wa( sanitize_text_field( wp_unslash( isset( $_POST['yk_w_wa'] ) ? $_POST['yk_w_wa'] : '' ) ) );
	$p1    = isset( $_POST['yk_w_pass'] ) ? (string) $_POST['yk_w_pass'] : '';
	$p2    = isset( $_POST['yk_w_pass2'] ) ? (string) $_POST['yk_w_pass2'] : '';
	$err   = array();

	if ( ! is_email( $email ) ) {
		$err[] = 'Please enter a valid email address.';
	} elseif ( email_exists( $email ) ) {
		$err[] = 'This email is already registered. Please <a href="' . esc_url( $acct ) . '">log in</a> instead.';
	}
	if ( strlen( $name ) < 2 ) {
		$err[] = 'Please enter your full name.';
	}
	if ( strlen( $wa ) < 10 || strlen( $wa ) > 15 ) {
		$err[] = 'Please enter a valid WhatsApp number (e.g. 0812xxxxxxxx).';
	} else {
		$dup = get_users( array( 'meta_key' => 'billing_phone', 'meta_value' => $wa, 'number' => 1, 'fields' => 'ID' ) );
		if ( $dup ) {
			$err[] = 'This WhatsApp number is already linked to an account. Please <a href="' . esc_url( $acct ) . '">log in</a> instead.';
		}
	}
	if ( strlen( $p1 ) < 8 ) {
		$err[] = 'Password must be at least 8 characters.';
	} elseif ( $p1 !== $p2 ) {
		$err[] = 'Passwords do not match.';
	}
	if ( $err ) {
		$GLOBALS['yk_wel_err'] = $err;
		return;
	}

	/* dipakai hook member (snippet #7) untuk simpan nama, WA, opt-in */
	$_POST['yk_full_name'] = $name;
	$_POST['yk_wa']        = $wa;
	$_POST['yk_optin']     = '1';

	$parts = explode( ' ', $name, 2 );
	$uid   = wc_create_new_customer( $email, '', $p1, array( 'first_name' => $parts[0], 'last_name' => isset( $parts[1] ) ? $parts[1] : '' ) );
	if ( is_wp_error( $uid ) ) {
		$GLOBALS['yk_wel_err'] = array( $uid->get_error_message() );
		return;
	}
	update_user_meta( $uid, 'yk_signup_source', 'welcome' );
	update_user_meta( $uid, 'yk_marketing_optin', 'yes' );
	update_user_meta( $uid, 'yk_optin_date', current_time( 'mysql' ) );

	/* Voucher kini diterbitkan oleh snippet #40 (hook user_register): 10% maks Rp100rb, 1 per IP/perangkat/WA */

	set_transient( $rk, $n + 1, HOUR_IN_SECONDS );
	wc_set_customer_auth_cookie( $uid );
	wp_safe_redirect( add_query_arg( 'welcome', '1', $acct ) );
	exit;
}, 5 );

/* ---------- Dasbor akun: sambutan + voucher ---------- */
add_action( 'woocommerce_account_dashboard', function () {
	$i = yk_wel_info( get_current_user_id() );
	echo yk_wel_css(); // phpcs:ignore
	if ( isset( $_GET['welcome'] ) ) {
		echo '<div class="yk-wel-ok"><strong>Welcome to Yulika Florist!</strong> Your account is ready and you are now signed in.</div>';
	}
	if ( $i && 'active' === $i['status'] ) {
		echo yk_wel_voucher_box( $i, 'Your 10% welcome voucher' ); // phpcs:ignore
		$shop = wc_get_page_permalink( 'shop' );
		echo '<p style="text-align:center;margin:-6px 0 20px"><a class="yk-wel-btn2" href="' . esc_url( $shop ) . '">Start shopping</a></p>';
	}
}, 5 );

/* ---------- Pasang voucher otomatis di keranjang/checkout ---------- */
function yk_wel_autoapply() {
	if ( ! is_user_logged_in() || ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}
	if ( 'yes' !== get_option( 'woocommerce_enable_coupons' ) ) {
		return;
	}
	$i = yk_wel_info( get_current_user_id() );
	if ( ! $i || 'active' !== $i['status'] ) {
		return;
	}
	if ( WC()->cart->get_applied_coupons() ) {
		return;
	}
	if ( WC()->session && WC()->session->get( 'yk_wel_skip' ) ) {
		return;
	}
	WC()->cart->apply_coupon( $i['code'] );
}
add_action( 'woocommerce_before_cart', 'yk_wel_autoapply', 5 );
add_action( 'woocommerce_before_checkout_form', 'yk_wel_autoapply', 5 );

add_action( 'woocommerce_removed_coupon', function ( $code ) {
	if ( ! is_user_logged_in() || ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}
	$mine = (string) get_user_meta( get_current_user_id(), 'yk_welcome_coupon', true );
	if ( $mine && strtolower( $code ) === strtolower( $mine ) ) {
		WC()->session->set( 'yk_wel_skip', 1 );
	}
} );

/* ---------- Alamat penerima wajib diisi ulang setiap checkout ---------- */
add_filter( 'woocommerce_checkout_get_value', function ( $value, $key ) {
	if ( 0 === strpos( $key, 'shipping_' ) && ! in_array( $key, array( 'shipping_country', 'shipping_state' ), true ) ) {
		return '';
	}
	return $value;
}, 20, 2 );
