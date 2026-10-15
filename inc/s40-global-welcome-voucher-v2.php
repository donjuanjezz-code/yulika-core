<?php
// Dari Code Snippets #40 "#40 Welcome voucher v2: semua member, max 100rb, 1 per IP/perangkat/WA" — scope: global

// #40 Welcome voucher v2 (13 Okt 2026): semua member baru, 10% max Rp100.000 (cap hanya tampil di checkout),
// 1x pakai dalam 24 jam, 1 voucher per IP (30 hari) / perangkat (cookie, 1 thn) / nomor WA pemesan.
if (!defined('YK_WEL_CAP')) define('YK_WEL_CAP', 100000);

function yk_wel_ipkey() {
	$ip = class_exists('WC_Geolocation') ? WC_Geolocation::get_ip_address() : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
	$b  = $ip ? @inet_pton($ip) : false;
	if (!$b) return '';
	return strlen($b) === 16 ? 'v6:' . bin2hex(substr($b, 0, 8)) : 'v4:' . $ip; // IPv6: per /64 (1 HP/router)
}
function yk_wel_dev() {
	static $id = null;
	if ($id !== null) return $id;
	$id = (isset($_COOKIE['yk_dv']) && preg_match('/^[a-z0-9]{20}$/', $_COOKIE['yk_dv'])) ? $_COOKIE['yk_dv'] : '';
	if (!$id && !headers_sent()) {
		$id = strtolower(wp_generate_password(20, false));
		setcookie('yk_dv', $id, array('expires' => time() + 2 * YEAR_IN_SECONDS, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax'));
		$_COOKIE['yk_dv'] = $id;
	}
	return $id;
}
function yk_wel_phone_key($p) {
	$p = preg_replace('/\D/', '', (string) $p);
	$p = preg_replace('/^(62|0)/', '', $p);
	return strlen($p) >= 8 ? 'p:' . $p : '';
}
/* cookie perangkat hanya di halaman yang tidak di-cache */
add_action('template_redirect', function () {
	if ((function_exists('is_checkout') && (is_checkout() || is_account_page() || is_cart())) || is_page('welcome')) yk_wel_dev();
}, 1);

/* ---------- Terbitkan voucher untuk SEMUA pendaftaran member baru ---------- */
function yk_wel_issue($uid) {
	if (get_user_meta($uid, 'yk_welcome_coupon', true) || get_user_meta($uid, 'yk_welcome_blocked', true)) return;
	if (is_admin() && !wp_doing_ajax()) return; // dibuat admin di wp-admin
	$u = get_userdata($uid);
	if (!$u || !array_intersect((array) $u->roles, array('customer', 'subscriber'))) return;
	if (!class_exists('WC_Coupon')) return;
	$ipk = yk_wel_ipkey();
	$dev = yk_wel_dev();
	$hit = '';
	if ($ipk && get_transient('yk_wi_' . md5($ipk))) $hit = 'ip';
	elseif ($dev && get_transient('yk_wi_' . md5('d:' . $dev))) $hit = 'device';
	update_user_meta($uid, 'yk_welcome_ip', $ipk);
	if ($hit) { update_user_meta($uid, 'yk_welcome_blocked', $hit); return; }
	try {
		$exp  = time() + DAY_IN_SECONDS;
		$code = 'WELCOME-' . strtoupper(wp_generate_password(6, false, false));
		$c = new WC_Coupon();
		$c->set_code($code);
		$c->set_discount_type('percent');
		$c->set_amount(10);
		$c->set_usage_limit(1);
		$c->set_usage_limit_per_user(1);
		$c->set_individual_use(true);
		$c->set_email_restrictions(array($u->user_email));
		$c->set_date_expires($exp);
		$c->set_description('Welcome 10% (maks Rp100.000) member #' . $uid . ' (' . $u->user_email . '), berlaku 24 jam, 1 per IP/perangkat/WA.');
		$c->update_meta_data('_yk_wel_uid', $uid);
		$c->save();
		update_user_meta($uid, 'yk_welcome_coupon', $code);
		update_user_meta($uid, 'yk_welcome_coupon_exp', $exp);
		if ($ipk) set_transient('yk_wi_' . md5($ipk), $uid, 30 * DAY_IN_SECONDS);
		if ($dev) set_transient('yk_wi_' . md5('d:' . $dev), $uid, YEAR_IN_SECONDS);
	} catch (Exception $e) {
		error_log('yk_wel_issue: ' . $e->getMessage());
	}
}
add_action('user_register', 'yk_wel_issue', 50);

/* ---------- Validasi saat dipakai: 1 per IP / perangkat / WA pemesan ---------- */
function yk_wel_keys() {
	$k = array();
	if ($ip = yk_wel_ipkey()) $k[] = $ip;
	if ($d = yk_wel_dev()) $k[] = 'd:' . $d;
	if (!empty($_POST['billing_phone']) && ($p = yk_wel_phone_key(wp_unslash($_POST['billing_phone'])))) $k[] = $p;
	if (is_user_logged_in() && ($p = yk_wel_phone_key(get_user_meta(get_current_user_id(), 'billing_phone', true)))) $k[] = $p;
	return array_unique($k);
}
add_filter('woocommerce_coupon_is_valid', function ($valid, $coupon) {
	if (!$valid || stripos($coupon->get_code(), 'welcome-') !== 0) return $valid;
	foreach (yk_wel_keys() as $k) {
		$o = (int) get_transient('yk_wr_' . md5($k));
		if ($o && $o !== (int) $coupon->get_id()) {
			throw new Exception('The welcome voucher can only be used once per device / WhatsApp number.');
		}
	}
	return $valid;
}, 10, 2);
add_action('woocommerce_checkout_order_processed', function ($order_id, $posted = array(), $order = null) {
	$order = $order ? $order : wc_get_order($order_id);
	if (!$order) return;
	foreach ($order->get_coupon_codes() as $code) {
		if (stripos($code, 'welcome-') !== 0) continue;
		$cid = wc_get_coupon_id_by_code($code);
		foreach (yk_wel_keys() as $k) {
			$ttl = strpos($k, 'p:') === 0 ? 10 * YEAR_IN_SECONDS : (strpos($k, 'd:') === 0 ? YEAR_IN_SECONDS : 30 * DAY_IN_SECONDS);
			set_transient('yk_wr_' . md5($k), $cid, $ttl);
		}
		if ($p = yk_wel_phone_key($order->get_billing_phone())) set_transient('yk_wr_' . md5($p), $cid, 10 * YEAR_IN_SECONDS);
	}
}, 10, 3);

/* ---------- Maksimal potongan Rp100.000 ---------- */
add_filter('woocommerce_coupon_get_discount_amount', function ($discount, $amount, $item, $single, $coupon) {
	if (stripos($coupon->get_code(), 'welcome-') !== 0 || !function_exists('WC') || !WC()->cart) return $discount;
	$base = 0;
	foreach (WC()->cart->get_cart() as $ci) $base += (float) $ci['line_subtotal'];
	$full = $base * (float) $coupon->get_amount() / 100;
	if ($full <= YK_WEL_CAP || $full <= 0) return $discount;
	return $discount * YK_WEL_CAP / $full;
}, 20, 5);

/* Label: batas maksimal hanya ditampilkan di checkout */
add_filter('woocommerce_cart_totals_coupon_label', function ($label, $coupon) {
	if (stripos($coupon->get_code(), 'welcome-') !== 0) return $label;
	return (function_exists('is_checkout') && is_checkout()) ? 'Welcome voucher 10% (max. Rp 100.000)' : 'Welcome voucher 10%';
}, 20, 2);

/* Dasbor: info kalau voucher tidak diberikan karena IP/perangkat sudah pernah klaim */
add_action('woocommerce_account_dashboard', function () {
	$uid = get_current_user_id();
	if (!get_user_meta($uid, 'yk_welcome_blocked', true) || get_user_meta($uid, 'yk_welcome_coupon', true)) return;
	$reg = strtotime(get_userdata($uid)->user_registered . ' UTC');
	if (time() - $reg > 3 * DAY_IN_SECONDS) return;
	echo '<div style="background:#f8f4ee;border-left:3px solid #a0784a;border-radius:0 8px 8px 0;padding:10px 12px;margin:0 0 16px;font-size:14px;line-height:1.45;color:#5a3d22">The welcome voucher has already been claimed from this device or connection, so it isn\'t available for this account.</div>';
}, 6);
