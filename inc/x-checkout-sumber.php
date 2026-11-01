<?php
// Survey "Tahu Yulika dari mana?" di checkout (1 Nov 2026, permintaan Jess).
// - Wajib pilih 1, tanpa default. Letak: tepat di atas checkbox Return & Cancellation + tombol Place Order.
// - Disimpan di order: _yk_src (kunci) + _yk_src_other (teks kalau "Other").
// - Tampil di: ringkasan WhatsApp ke Yulika (s06 yk_order_wa_text), email "New order" ke admin,
//   halaman order admin, kolom "Tahu dari" di daftar Orders, dan WooCommerce → Sumber Order (rekap).
// - Dibandingkan dengan data klik otomatis WooCommerce Order Attribution (_wc_order_attribution_*):
//   customer tidak bisa membedakan iklan Google vs hasil biasa, jadi "Google Ads" dibaca dari data klik.
// - Opsi "nama admin" sengaja tidak dipakai (keputusan Jess: memperlambat checkout).
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'yk_src_options' ) ) {
	/* ui = teks di checkout, en = ringkasan WhatsApp (bahasa Inggris), id = admin/email/rekap */
	function yk_src_options() {
		return array(
			'google_search' => array( 'icon' => '🔍', 'ui' => 'Google Search', 'en' => 'Google Search', 'id' => 'Google Search' ),
			'google_maps'   => array( 'icon' => '📍', 'ui' => 'Google Maps', 'en' => 'Google Maps', 'id' => 'Google Maps' ),
			'instagram'     => array( 'icon' => '📸', 'ui' => 'Instagram', 'en' => 'Instagram', 'id' => 'Instagram' ),
			'tiktok'        => array( 'icon' => '🎵', 'ui' => 'TikTok', 'en' => 'TikTok', 'id' => 'TikTok' ),
			'admin_wa'      => array( 'icon' => '💬', 'ui' => 'Yulika Admin (WhatsApp)', 'en' => 'Yulika Admin (WhatsApp)', 'id' => 'Admin Yulika (WhatsApp)' ),
			'friend'        => array( 'icon' => '👥', 'ui' => 'Friend / Family', 'en' => 'Friend / Family', 'id' => 'Teman / keluarga' ),
			'returning'     => array( 'icon' => '🔁', 'ui' => "I've ordered before", 'en' => 'Returning customer', 'id' => 'Pelanggan lama' ),
			'other'         => array( 'icon' => '✏️', 'ui' => 'Other', 'en' => 'Other', 'id' => 'Lainnya' ),
		);
	}
}

if ( ! function_exists( 'yk_src_key_label' ) ) {
	function yk_src_key_label( $key, $lang = 'id', $other = '' ) {
		$opts = yk_src_options();
		if ( ! isset( $opts[ $key ] ) ) {
			return '';
		}
		$label = $opts[ $key ][ 'en' === $lang ? 'en' : 'id' ];
		if ( 'other' === $key && '' !== (string) $other ) {
			$label .= ' – ' . $other;
		}
		return $label;
	}
}

if ( ! function_exists( 'yk_src_label' ) ) {
	/* Jawaban survey order ini. $lang: 'en' (WhatsApp) atau 'id' (admin). Kosong kalau tidak ada. */
	function yk_src_label( $order, $lang = 'id' ) {
		if ( ! is_object( $order ) || ! is_callable( array( $order, 'get_meta' ) ) ) {
			return '';
		}
		return yk_src_key_label( (string) $order->get_meta( '_yk_src' ), $lang, (string) $order->get_meta( '_yk_src_other' ) );
	}
}

if ( ! function_exists( 'yk_src_auto' ) ) {
	/* Data klik otomatis (WooCommerce Order Attribution) dalam bahasa singkat. */
	function yk_src_auto( $order ) {
		if ( ! is_object( $order ) || ! is_callable( array( $order, 'get_meta' ) ) ) {
			return '';
		}
		$type = strtolower( (string) $order->get_meta( '_wc_order_attribution_source_type' ) );
		$src  = (string) $order->get_meta( '_wc_order_attribution_utm_source' );
		$med  = strtolower( (string) $order->get_meta( '_wc_order_attribution_utm_medium' ) );
		if ( '' === $type ) {
			return '';
		}
		$name = ( '' !== $src && '(none)' !== $src ) ? ucfirst( $src ) : '?';
		switch ( $type ) {
			case 'typein':
				return 'Direct (ketik langsung / bookmark)';
			case 'organic':
				return 'Organic: ' . $name;
			case 'referral':
				return 'Referral: ' . ( '' !== $src ? $src : '?' );
			case 'admin':
				return 'Admin (order manual)';
			case 'mobile_app':
				return 'Aplikasi: ' . $name;
			case 'utm':
				if ( preg_match( '/^(cpc|ppc|paid|paidsearch|paid_search|paid-search)$/', $med ) ) {
					return false !== stripos( $src, 'google' ) ? 'Google Ads (klik iklan)' : 'Iklan: ' . $name;
				}
				return 'UTM: ' . $name . ( ( '' !== $med && '(none)' !== $med ) ? ' / ' . $med : '' );
		}
		return ucfirst( $type ) . ( '' !== $src ? ': ' . $src : '' );
	}
}

if ( ! function_exists( 'yk_src_current' ) ) {
	/* Pilihan yang sedang aktif saat bagian pembayaran di-refresh lewat AJAX (update_order_review). */
	function yk_src_current() {
		$data = array();
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['post_data'] ) && is_string( $_POST['post_data'] ) ) {
			parse_str( wp_unslash( $_POST['post_data'] ), $data );
		} elseif ( isset( $_POST['yk_src'] ) ) {
			$data = wp_unslash( $_POST );
		}
		// phpcs:enable
		$val   = ( isset( $data['yk_src'] ) && is_string( $data['yk_src'] ) ) ? sanitize_key( $data['yk_src'] ) : '';
		$other = ( isset( $data['yk_src_other'] ) && is_string( $data['yk_src_other'] ) ) ? sanitize_text_field( $data['yk_src_other'] ) : '';
		$opts  = yk_src_options();
		return array( isset( $opts[ $val ] ) ? $val : '', $other );
	}
}

/* =========================================================
 * CHECKOUT: tampilan survey
 * ======================================================= */
if ( ! function_exists( 'yk_src_render' ) ) {
	function yk_src_render() {
		static $done = false;
		if ( $done ) {
			return;
		}
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
			return; // halaman bayar ulang (Midtrans/PayPal): bukan checkout baru
		}
		$done = true;
		list( $cur, $other ) = yk_src_current();
		echo '<div class="yk-src" id="yk-src">';
		echo '<input type="hidden" name="yk_src_shown" value="1">';
		echo '<p class="yk-src-t" id="yk-src-t">How did you find us for this order? <abbr class="required" title="required">*</abbr></p>';
		echo '<p class="yk-src-h">Choose one. It helps us serve you better.</p>';
		echo '<div class="yk-src-o" role="radiogroup" aria-labelledby="yk-src-t">';
		foreach ( yk_src_options() as $key => $o ) {
			echo '<label><input type="radio" name="yk_src" value="' . esc_attr( $key ) . '"' . checked( $cur, $key, false ) . '><span><i aria-hidden="true">' . $o['icon'] . '</i>' . esc_html( $o['ui'] ) . '</span></label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- icon = emoji tetap
		}
		echo '</div>';
		echo '<div class="yk-src-other"' . ( 'other' === $cur ? '' : ' hidden' ) . '><input type="text" name="yk_src_other" maxlength="80" autocomplete="off" aria-label="Where did you find us?" placeholder="Please tell us where (e.g. Facebook, an event)" value="' . esc_attr( $other ) . '"></div>';
		echo '<p class="yk-src-msg" role="alert">Please choose one option.</p>';
		echo '</div>';
	}
}
// Di dalam blok pembayaran: sebelum checkbox Return & Cancellation (cadangan: tepat sebelum tombol).
add_action( 'woocommerce_checkout_before_terms_and_conditions', 'yk_src_render', 5 );
add_action( 'woocommerce_review_order_before_submit', 'yk_src_render', 5 );

/* =========================================================
 * VALIDASI & SIMPAN
 * ======================================================= */
add_action( 'woocommerce_after_checkout_validation', function ( $data, $errors ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce sudah dicek WooCommerce
	if ( empty( $_POST['yk_src_shown'] ) || ! is_object( $errors ) ) {
		return; // survey tidak tampil di form ini (mis. halaman lama sebelum update): jangan blokir order
	}
	$val  = isset( $_POST['yk_src'] ) ? sanitize_key( wp_unslash( $_POST['yk_src'] ) ) : '';
	$opts = yk_src_options();
	if ( ! isset( $opts[ $val ] ) ) {
		$errors->add( 'yk_src', 'Please tell us <strong>how you found us</strong>. It\'s just one tap above the Place Order button.' );
	} elseif ( 'other' === $val ) {
		$other = isset( $_POST['yk_src_other'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['yk_src_other'] ) ) ) : '';
		if ( '' === $other ) {
			$errors->add( 'yk_src', 'Please tell us <strong>where you found us</strong> in the "Other" box above the Place Order button.' );
		}
	}
	// phpcs:enable
}, 20, 2 );

add_action( 'woocommerce_checkout_create_order', function ( $order ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	$val  = isset( $_POST['yk_src'] ) ? sanitize_key( wp_unslash( $_POST['yk_src'] ) ) : '';
	$opts = yk_src_options();
	if ( ! isset( $opts[ $val ] ) ) {
		return;
	}
	$order->update_meta_data( '_yk_src', $val );
	if ( 'other' === $val ) {
		$other = isset( $_POST['yk_src_other'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['yk_src_other'] ) ) ) : '';
		if ( '' !== $other ) {
			$order->update_meta_data( '_yk_src_other', function_exists( 'mb_substr' ) ? mb_substr( $other, 0, 80 ) : substr( $other, 0, 80 ) );
		}
	}
	// phpcs:enable
} );

/* =========================================================
 * ADMIN: detail order, email admin, kolom daftar Orders
 * ======================================================= */
if ( ! function_exists( 'yk_src_box_html' ) ) {
	function yk_src_box_html( $order, $always = true ) {
		$label = yk_src_label( $order, 'id' );
		$auto  = yk_src_auto( $order );
		if ( '' === $label && ( ! $always || '' === $auto ) ) {
			return '';
		}
		return '<p style="margin:12px 0;padding:8px 10px;border-left:4px solid #a0784a;background:#faf7f2"><strong>📊 Tahu Yulika dari:</strong> '
			. esc_html( '' !== $label ? $label : '–' )
			. ( '' !== $auto ? '<br><span style="color:#666">Data klik otomatis: ' . esc_html( $auto ) . '</span>' : '' )
			. '</p>';
	}
}

add_action( 'woocommerce_admin_order_data_after_shipping_address', function ( $order ) {
	echo yk_src_box_html( $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 30 );

add_action( 'woocommerce_email_after_order_table', function ( $order, $sent_to_admin = false, $plain_text = false ) {
	if ( ! $sent_to_admin ) {
		return; // hanya email ke admin
	}
	if ( $plain_text ) {
		$label = yk_src_label( $order, 'id' );
		if ( '' !== $label ) {
			$auto = yk_src_auto( $order );
			echo "\nTahu Yulika dari: " . $label . ( '' !== $auto ? "\nData klik otomatis: " . $auto : '' ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		return;
	}
	echo yk_src_box_html( $order, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 25, 3 );

if ( ! function_exists( 'yk_src_columns' ) ) {
	function yk_src_columns( $cols ) {
		$out = array();
		foreach ( $cols as $key => $title ) {
			if ( 'origin' === $key ) {
				$out['yk_src'] = 'Tahu dari';
			}
			$out[ $key ] = $title;
		}
		if ( ! isset( $out['yk_src'] ) ) {
			$out['yk_src'] = 'Tahu dari';
		}
		return $out;
	}
}
add_filter( 'manage_woocommerce_page_wc-orders_columns', 'yk_src_columns', 30 );
add_filter( 'manage_edit-shop_order_columns', 'yk_src_columns', 30 );
add_action( 'manage_woocommerce_page_wc-orders_custom_column', function ( $col, $order ) {
	if ( 'yk_src' === $col ) {
		$label = yk_src_label( $order, 'id' );
		echo esc_html( '' !== $label ? $label : '–' );
	}
}, 10, 2 );
add_action( 'manage_shop_order_posts_custom_column', function ( $col, $post_id ) {
	if ( 'yk_src' !== $col ) {
		return;
	}
	$order = function_exists( 'wc_get_order' ) ? wc_get_order( $post_id ) : null;
	$label = $order ? yk_src_label( $order, 'id' ) : '';
	echo esc_html( '' !== $label ? $label : '–' );
}, 10, 2 );

/* =========================================================
 * ADMIN: WooCommerce → Sumber Order (rekap order lunas)
 * ======================================================= */
add_action( 'admin_menu', function () {
	add_submenu_page( 'woocommerce', 'Sumber Order', 'Sumber Order', 'manage_woocommerce', 'yk-sumber-order', 'yk_src_report_page' );
}, 99 );

if ( ! function_exists( 'yk_src_report_page' ) ) {
	function yk_src_report_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! function_exists( 'wc_get_orders' ) ) {
			return;
		}
		$days = isset( $_GET['days'] ) ? absint( $_GET['days'] ) : 30; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! in_array( $days, array( 7, 30, 90 ), true ) ) {
			$days = 30;
		}
		$from = new DateTime( 'now', wp_timezone() );
		$from->modify( '-' . ( $days - 1 ) . ' days' );
		$orders = wc_get_orders(
			array(
				'limit'        => -1,
				'type'         => 'shop_order',
				'status'       => array( 'processing', 'completed' ),
				'date_created' => '>=' . $from->format( 'Y-m-d' ),
			)
		);
		$survey = array();
		$auto   = array();
		$others = array();
		$count  = 0;
		$rev    = 0.0;
		foreach ( $orders as $o ) {
			$total = (float) $o->get_total();
			++$count;
			$rev  += $total;
			$key   = (string) $o->get_meta( '_yk_src' );
			$label = '' !== $key ? yk_src_key_label( $key, 'id' ) : '';
			if ( '' === $label ) {
				$label = '(kosong / sebelum survey)';
			}
			if ( ! isset( $survey[ $label ] ) ) {
				$survey[ $label ] = array( 0, 0.0 );
			}
			++$survey[ $label ][0];
			$survey[ $label ][1] += $total;
			if ( 'other' === $key ) {
				$txt = strtolower( trim( (string) $o->get_meta( '_yk_src_other' ) ) );
				if ( '' !== $txt ) {
					$others[ $txt ] = isset( $others[ $txt ] ) ? $others[ $txt ] + 1 : 1;
				}
			}
			$a = yk_src_auto( $o );
			if ( '' === $a ) {
				$a = '(tidak ada data)';
			}
			if ( ! isset( $auto[ $a ] ) ) {
				$auto[ $a ] = array( 0, 0.0 );
			}
			++$auto[ $a ][0];
			$auto[ $a ][1] += $total;
		}
		$by_rev = function ( $x, $y ) {
			return $y[1] <=> $x[1];
		};
		uasort( $survey, $by_rev );
		uasort( $auto, $by_rev );
		arsort( $others );
		$money = function ( $v ) {
			return 'Rp' . number_format( (float) $v, 0, ',', '.' );
		};
		$table = function ( $title, $rows ) use ( $count, $rev, $money ) {
			$h = '<h2 style="margin-top:28px">' . esc_html( $title ) . '</h2><table class="widefat striped" style="max-width:780px"><thead><tr><th>Sumber</th><th style="text-align:right">Order</th><th style="text-align:right">% order</th><th style="text-align:right">Omzet</th><th style="text-align:right">% omzet</th></tr></thead><tbody>';
			if ( ! $rows ) {
				$h .= '<tr><td colspan="5">Belum ada order lunas di periode ini.</td></tr>';
			}
			foreach ( $rows as $label => $r ) {
				$h .= '<tr><td>' . esc_html( $label ) . '</td><td style="text-align:right">' . (int) $r[0] . '</td><td style="text-align:right">' . ( $count ? (int) round( 100 * $r[0] / $count ) : 0 ) . '%</td><td style="text-align:right">' . esc_html( $money( $r[1] ) ) . '</td><td style="text-align:right">' . ( $rev > 0 ? (int) round( 100 * $r[1] / $rev ) : 0 ) . '%</td></tr>';
			}
			return $h . '</tbody></table>';
		};
		echo '<div class="wrap"><h1>Sumber Order</h1>';
		echo '<p>Order lunas (Processing + Completed), ' . (int) $days . ' hari terakhir: <strong>' . (int) $count . ' order</strong>, omzet <strong>' . esc_html( $money( $rev ) ) . '</strong> (termasuk ongkir).</p><p>';
		foreach ( array( 7, 30, 90 ) as $d ) {
			$url = add_query_arg( array( 'page' => 'yk-sumber-order', 'days' => $d ), admin_url( 'admin.php' ) );
			echo $d === $days ? '<strong style="margin-right:14px">' . (int) $d . ' hari</strong>' : '<a style="margin-right:14px" href="' . esc_url( $url ) . '">' . (int) $d . ' hari</a>';
		}
		echo '</p>';
		echo $table( 'Jawaban customer (survey checkout)', $survey ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( $others ) {
			$list = array();
			foreach ( array_slice( $others, 0, 15, true ) as $txt => $n ) {
				$list[] = esc_html( $txt ) . ' (' . (int) $n . ')';
			}
			echo '<p style="max-width:780px"><strong>Isi "Lainnya":</strong> ' . implode( ', ', $list ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo $table( 'Data klik otomatis (WooCommerce Order Attribution)', $auto ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<p style="color:#666;max-width:780px;margin-top:16px">Cara baca: <strong>survey</strong> = dari mana customer menemukan Yulika untuk order ini (kata customer). <strong>Data klik</strong> = asal kunjungan terakhir sebelum order (tercatat otomatis). Contoh: survey "Instagram" + data klik "Google Ads" berarti Instagram yang membuat customer kenal Yulika, iklan Google yang menutup penjualan.</p>';
		echo '</div>';
	}
}

/* =========================================================
 * CSS + JS (halaman checkout saja)
 * ======================================================= */
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) || is_wc_endpoint_url( 'order-pay' ) ) {
		return;
	}
	?>
<style id="yk-src-css">
#yk-src{margin:4px 0 16px;padding:14px 12px 12px;border:1.5px solid #e3d9cc;border-radius:14px;background:#fff;text-align:left}
#yk-src .yk-src-t{font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#1d1d1f;margin:0 0 2px;line-height:1.35}
#yk-src .yk-src-t abbr{color:#b3261e;text-decoration:none;border:0}
#yk-src .yk-src-h{font-size:13px;color:#6e6e73;margin:0 0 10px;line-height:1.4}
#yk-src .yk-src-o{display:grid;grid-template-columns:1fr 1fr;gap:8px}
#yk-src .yk-src-o label{position:relative;margin:0!important;padding:0!important;cursor:pointer;display:block;line-height:1.25!important}
#yk-src .yk-src-o input{position:absolute;opacity:0;width:1px;height:1px;pointer-events:none;margin:0}
#yk-src .yk-src-o span{display:flex;align-items:center;justify-content:center;gap:6px;text-align:center;min-height:46px;height:100%;box-sizing:border-box;padding:8px 6px;border:1.5px solid #e3d9cc;border-radius:12px;background:#fff;font-size:14px;line-height:1.25;color:#1d1d1f;transition:border-color .15s,background .15s}
#yk-src .yk-src-o i{font-style:normal;flex:0 0 auto}
#yk-src .yk-src-o input:checked+span{border-color:#a0784a;background:#f8f4ee;box-shadow:inset 0 0 0 1px #a0784a;color:#8a5a2b;font-weight:600}
#yk-src .yk-src-o input:focus-visible+span{outline:2px solid #a0784a;outline-offset:2px}
#yk-src .yk-src-other{margin-top:10px}
#yk-src .yk-src-other[hidden]{display:none!important}
#yk-src .yk-src-other input{width:100%;box-sizing:border-box;min-height:46px;padding:10px 12px;border:1.5px solid #e3d9cc;border-radius:12px;font-size:16px;background:#fff;color:#1d1d1f}
#yk-src .yk-src-other input:focus{outline:0;border-color:#a0784a}
#yk-src .yk-src-msg{display:none;margin:8px 0 0;font-size:13px;color:#b3261e;font-weight:600}
#yk-src.yk-src-err{border-color:#b3261e;background:#fff8f7}
#yk-src.yk-src-err .yk-src-msg{display:block}
</style>
<script data-no-optimize="1" data-no-defer="1" data-cfasync="false">(function(){
var st={v:'',o:''};
function box(){return document.getElementById('yk-src');}
function sync(){var b=box();if(!b)return;var c=b.querySelector('input[name="yk_src"]:checked'),w=b.querySelector('.yk-src-other');if(w)w.hidden=!(c&&c.value==='other');}
function apply(){var b=box();if(!b)return;
 if(!b.querySelector('input[name="yk_src"]:checked')&&st.v){var r=b.querySelector('input[name="yk_src"][value="'+st.v+'"]');if(r)r.checked=true;}
 var o=b.querySelector('input[name="yk_src_other"]');if(o&&!o.value&&st.o)o.value=st.o;
 sync();}
function bad(){var b=box();if(!b)return '';var c=b.querySelector('input[name="yk_src"]:checked');if(!c)return 'pick';if(c.value==='other'){var o=b.querySelector('input[name="yk_src_other"]');if(!o||!o.value.trim())return 'other';}return '';}
function flag(k){var b=box();if(!b)return;b.classList.add('yk-src-err');var m=b.querySelector('.yk-src-msg');if(m)m.textContent=k==='other'?'Please tell us where you found us.':'Please choose one option.';
 try{b.scrollIntoView({behavior:'smooth',block:'center'});}catch(e){b.scrollIntoView();}
 if(k==='other'){var o=b.querySelector('input[name="yk_src_other"]');if(o)setTimeout(function(){try{o.focus({preventScroll:true});}catch(e){o.focus();}},350);}}
function ok(){var b=box();if(b&&!bad())b.classList.remove('yk-src-err');}
document.addEventListener('change',function(e){var t=e.target;if(!t||t.name!=='yk_src')return;st.v=t.value;sync();ok();},true);
document.addEventListener('input',function(e){var t=e.target;if(!t||t.name!=='yk_src_other')return;st.o=t.value;ok();},true);
// Place Order tanpa jawaban: tahan di browser (tanpa kirim ke server), tandai kotaknya
document.addEventListener('submit',function(e){var f=e.target;if(!f||!f.matches||!f.matches('form.checkout')||!box())return;var k=bad();if(!k)return;e.preventDefault();e.stopPropagation();if(e.stopImmediatePropagation)e.stopImmediatePropagation();flag(k);},true);
if(window.jQuery){jQuery(document.body).on('updated_checkout',apply).on('checkout_error',function(){var k=bad();if(k)setTimeout(function(){flag(k);},1100);});}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',apply);else apply();
window.ykSrcCheck=bad;
})();</script>
	<?php
}, 40 );
