<?php
// Dari Code Snippets #7 "Member: data akun, favorit tersinkron & export marketing (Yulika)" — scope: global
/* ===== Yulika: data member, favorit tersinkron, export marketing ===== */

function yk_norm_wa( $p ) {
	$d = preg_replace( '/\D+/', '', (string) $p );
	if ( '' === $d ) {
		return '';
	}
	if ( 0 === strpos( $d, '0' ) ) {
		$d = '62' . substr( $d, 1 );
	} elseif ( 0 === strpos( $d, '8' ) ) {
		$d = '62' . $d;
	}
	return $d;
}

function yk_fmt_wa( $d ) {
	$d = yk_norm_wa( $d );
	if ( strlen( $d ) < 8 ) {
		return $d;
	}
	return '+' . substr( $d, 0, 2 ) . ' ' . substr( $d, 2, 3 ) . ' ' . substr( $d, 5, 4 ) . ' ' . substr( $d, 9 );
}

/* ---------- Form daftar ---------- */
add_action( 'woocommerce_register_form_start', function () {
	$v = function ( $k ) {
		return isset( $_POST[ $k ] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) ) : '';
	};
	echo '<p class="woocommerce-form-row form-row form-row-wide"><label for="yk_full_name">Nama lengkap <span class="required">*</span></label><input type="text" class="input-text" name="yk_full_name" id="yk_full_name" autocomplete="name" value="' . $v( 'yk_full_name' ) . '"></p>';
	echo '<p class="woocommerce-form-row form-row form-row-wide"><label for="yk_wa">Nomor WhatsApp <span class="required">*</span></label><input type="tel" class="input-text" name="yk_wa" id="yk_wa" autocomplete="tel" placeholder="08xxxxxxxxxx" value="' . $v( 'yk_wa' ) . '"></p>';
} );

add_action( 'woocommerce_register_form', function () {
	echo '<p class="form-row"><label class="woocommerce-form__label woocommerce-form__label-for-checkbox"><input type="checkbox" class="woocommerce-form__input-checkbox" name="yk_optin" value="1"> <span>Saya bersedia menerima info promo &amp; koleksi terbaru dari Yulika Florist via WhatsApp/email.</span></label></p>';
} );

add_action( 'woocommerce_register_post', function ( $username, $email, $errors ) {
	if ( empty( $_POST['yk_full_name'] ) || ! trim( wp_unslash( $_POST['yk_full_name'] ) ) ) {
		$errors->add( 'yk_full_name', 'Silakan isi <strong>nama lengkap</strong>.' );
	}
	$wa = isset( $_POST['yk_wa'] ) ? yk_norm_wa( wp_unslash( $_POST['yk_wa'] ) ) : '';
	if ( strlen( $wa ) < 10 || strlen( $wa ) > 15 ) {
		$errors->add( 'yk_wa', 'Silakan isi <strong>nomor WhatsApp</strong> yang valid.' );
	}
}, 10, 3 );

function yk_save_member_basics( $uid, $name, $wa ) {
	$name  = trim( preg_replace( '/\s+/', ' ', $name ) );
	$parts = explode( ' ', $name, 2 );
	$first = $parts[0];
	$last  = isset( $parts[1] ) ? $parts[1] : '';
	if ( $first ) {
		wp_update_user( array( 'ID' => $uid, 'first_name' => $first, 'last_name' => $last, 'display_name' => $name ) );
		update_user_meta( $uid, 'billing_first_name', $first );
		update_user_meta( $uid, 'billing_last_name', $last );
	}
	if ( $wa ) {
		update_user_meta( $uid, 'billing_phone', $wa );
	}
}

add_action( 'woocommerce_created_customer', function ( $uid ) {
	if ( isset( $_POST['yk_full_name'] ) || isset( $_POST['yk_wa'] ) ) {
		yk_save_member_basics(
			$uid,
			isset( $_POST['yk_full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['yk_full_name'] ) ) : '',
			isset( $_POST['yk_wa'] ) ? yk_norm_wa( wp_unslash( $_POST['yk_wa'] ) ) : ''
		);
	}
	$u = get_userdata( $uid );
	if ( $u ) {
		update_user_meta( $uid, 'billing_email', $u->user_email );
	}
	$opt = ! empty( $_POST['yk_optin'] ) ? 'yes' : 'no';
	update_user_meta( $uid, 'yk_marketing_optin', $opt );
	if ( 'yes' === $opt ) {
		update_user_meta( $uid, 'yk_optin_date', current_time( 'mysql' ) );
	}
	if ( ! get_user_meta( $uid, 'yk_signup_source', true ) ) {
		update_user_meta( $uid, 'yk_signup_source', isset( $_POST['woocommerce-register-nonce'] ) ? 'email' : 'checkout' );
	}
} );

/* Daftar via Google (Nextend Social Login) */
add_action( 'nsl_register_new_user', function ( $uid ) {
	update_user_meta( $uid, 'yk_signup_source', 'google' );
	if ( ! get_user_meta( $uid, 'yk_marketing_optin', true ) ) {
		update_user_meta( $uid, 'yk_marketing_optin', 'no' );
	}
	$u = get_userdata( $uid );
	if ( $u ) {
		update_user_meta( $uid, 'billing_email', $u->user_email );
		if ( $u->first_name ) {
			update_user_meta( $uid, 'billing_first_name', $u->first_name );
			update_user_meta( $uid, 'billing_last_name', $u->last_name );
		}
	}
} );

/* ---------- Label checkout & alamat: pemesan vs penerima ---------- */
add_filter( 'woocommerce_billing_fields', function ( $f ) {
	if ( isset( $f['billing_phone'] ) ) {
		$f['billing_phone']['label']       = 'Nomor WhatsApp pemesan';
		$f['billing_phone']['required']    = true;
		$f['billing_phone']['placeholder'] = '08xxxxxxxxxx';
	}
	return $f;
}, 20 );

add_filter( 'woocommerce_shipping_fields', function ( $f ) {
	if ( isset( $f['shipping_first_name'] ) ) {
		$f['shipping_first_name']['label'] = 'Nama penerima';
	}
	if ( isset( $f['shipping_last_name'] ) ) {
		$f['shipping_last_name']['label']    = 'Nama belakang penerima';
		$f['shipping_last_name']['required'] = false;
	}
	$f['shipping_phone'] = array(
		'label'        => 'WhatsApp penerima',
		'required'     => true,
		'type'         => 'tel',
		'class'        => array( 'form-row-wide' ),
		'validate'     => array( 'phone' ),
		'autocomplete' => 'tel',
		'placeholder'  => '08xxxxxxxxxx',
		'priority'     => 25,
	);
	return $f;
}, 20 );

add_filter( 'woocommerce_ship_to_different_address_checked', '__return_true' );

add_filter( 'woocommerce_checkout_posted_data', function ( $d ) {
	foreach ( array( 'billing_phone', 'shipping_phone' ) as $k ) {
		if ( ! empty( $d[ $k ] ) ) {
			$d[ $k ] = yk_norm_wa( $d[ $k ] );
		}
	}
	return $d;
} );

/* ---------- Data akun: WhatsApp & opt-in bisa diubah ---------- */
add_action( 'woocommerce_edit_account_form', function () {
	$uid = get_current_user_id();
	$wa  = get_user_meta( $uid, 'billing_phone', true );
	$opt = get_user_meta( $uid, 'yk_marketing_optin', true );
	echo '<p class="woocommerce-form-row form-row form-row-wide"><label for="yk_wa">Nomor WhatsApp <span class="required">*</span></label><input type="tel" class="input-text" name="yk_wa" id="yk_wa" value="' . esc_attr( $wa ) . '"></p>';
	echo '<p class="form-row"><label class="woocommerce-form__label-for-checkbox"><input type="checkbox" name="yk_optin" value="1" ' . checked( 'yes', $opt, false ) . '> <span>Saya bersedia menerima info promo &amp; koleksi terbaru dari Yulika Florist via WhatsApp/email.</span></label></p>';
} );

add_action( 'woocommerce_save_account_details_errors', function ( $errors ) {
	$wa = isset( $_POST['yk_wa'] ) ? yk_norm_wa( wp_unslash( $_POST['yk_wa'] ) ) : '';
	if ( strlen( $wa ) < 10 || strlen( $wa ) > 15 ) {
		$errors->add( 'yk_wa', 'Silakan isi <strong>nomor WhatsApp</strong> yang valid.' );
	}
} );

add_action( 'woocommerce_save_account_details', function ( $uid ) {
	if ( isset( $_POST['yk_wa'] ) ) {
		update_user_meta( $uid, 'billing_phone', yk_norm_wa( wp_unslash( $_POST['yk_wa'] ) ) );
	}
	$new = ! empty( $_POST['yk_optin'] ) ? 'yes' : 'no';
	if ( 'yes' === $new && 'yes' !== get_user_meta( $uid, 'yk_marketing_optin', true ) ) {
		update_user_meta( $uid, 'yk_optin_date', current_time( 'mysql' ) );
	}
	update_user_meta( $uid, 'yk_marketing_optin', $new );
} );

/* ---------- Menu akun (Bahasa Indonesia) ---------- */
add_filter( 'woocommerce_account_menu_items', function ( $items ) {
	$out = array();
	$map = array(
		'dashboard'       => 'Dasbor',
		'orders'          => 'Riwayat Pesanan',
		'yk-favorit'      => 'Favorit Saya',
		'edit-address'    => 'Alamat Kirim',
		'edit-account'    => 'Data Akun',
		'customer-logout' => 'Keluar',
	);
	foreach ( $map as $k => $label ) {
		if ( 'yk-favorit' === $k || isset( $items[ $k ] ) ) {
			$out[ $k ] = $label;
		}
	}
	return $out;
}, 99 );

add_filter( 'woocommerce_get_endpoint_url', function ( $url, $endpoint ) {
	return 'yk-favorit' === $endpoint ? home_url( '/favorit/' ) : $url;
}, 10, 2 );

/* ---------- Favorit tersimpan di akun ---------- */
add_action( 'rest_api_init', function () {
	register_rest_route( 'yk/v1', '/favs', array(
		array(
			'methods'             => 'GET',
			'permission_callback' => 'is_user_logged_in',
			'callback'            => function () {
				$f = get_user_meta( get_current_user_id(), 'yk_favs', true );
				return array_values( array_map( 'intval', is_array( $f ) ? $f : array() ) );
			},
		),
		array(
			'methods'             => 'POST',
			'permission_callback' => 'is_user_logged_in',
			'callback'            => function ( $req ) {
				$ids = $req->get_param( 'ids' );
				$ids = is_array( $ids ) ? array_slice( array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) ), 0, 50 ) : array();
				update_user_meta( get_current_user_id(), 'yk_favs', $ids );
				return $ids;
			},
		),
	) );
} );

add_action( 'wp_footer', function () {
	if ( ! is_user_logged_in() ) {
		return;
	}
	$nonce = wp_create_nonce( 'wp_rest' );
	?>
<script>
(function(){
var K='yk_favs',N=<?php echo wp_json_encode( $nonce ); ?>,busy=false,t;
function loc(){try{return JSON.parse(localStorage.getItem(K))||[]}catch(e){return[]}}
function push(a){fetch('/wp-json/yk/v1/favs',{method:'POST',credentials:'same-origin',headers:{'X-WP-Nonce':N,'Content-Type':'application/json'},body:JSON.stringify({ids:a})}).catch(function(){})}
var orig=Storage.prototype.setItem;
Storage.prototype.setItem=function(k,v){orig.apply(this,arguments);if(k===K&&!busy){clearTimeout(t);t=setTimeout(function(){push(loc())},600)}};
fetch('/wp-json/yk/v1/favs',{credentials:'same-origin',headers:{'X-WP-Nonce':N}}).then(function(r){return r.ok?r.json():[]}).then(function(s){
 var l=loc(),m=l.slice();(s||[]).forEach(function(id){if(m.indexOf(id)<0)m.push(id)});
 if(m.length!==l.length){busy=true;orig.call(localStorage,K,JSON.stringify(m));busy=false;document.querySelectorAll('.yk-heart').forEach(function(b){b.classList.toggle('on',m.indexOf(+b.getAttribute('data-id'))>-1)});var bar=document.querySelector('.yk-favbar');if(bar){bar.querySelector('b').textContent=m.length;bar.classList.toggle('show',m.length>0&&location.pathname.indexOf('/favorit')!==0)}if(location.pathname.indexOf('/favorit')===0&&!l.length&&m.length)location.reload()}
 if(m.length!==(s||[]).length)push(m);
}).catch(function(){});
})();
</script>
	<?php
}, 99 );

/* ---------- Export database member (CSV) ---------- */
add_action( 'admin_menu', function () {
	add_users_page( 'Export Member', 'Export Member (CSV)', 'manage_woocommerce', 'yk-export', function () {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=yk_export' ), 'yk_export' );
		$url_opt = wp_nonce_url( admin_url( 'admin-post.php?action=yk_export&optin=1' ), 'yk_export' );
		$members = count( get_users( array( 'role__in' => array( 'customer', 'subscriber' ), 'fields' => 'ID' ) ) );
		echo '<div class="wrap"><h1>Export Database Member</h1>';
		echo '<p>Unduh data member terdaftar dan pembeli tamu (tanpa akun) dalam format CSV. File bisa dibuka di Excel / Google Sheets dan diimpor ke WhatsApp broadcast, Meta Ads (Custom Audience), atau email marketing.</p>';
		echo '<p><strong>Member terdaftar saat ini:</strong> ' . (int) $members . '</p>';
		echo '<p><a class="button button-primary" href="' . esc_url( $url ) . '">Unduh semua data (member + pembeli tamu)</a> ';
		echo '<a class="button" href="' . esc_url( $url_opt ) . '">Unduh hanya yang setuju menerima promo</a></p>';
		echo '<p style="color:#666">Catatan: kirim promo hanya ke kontak yang kolom <em>Setuju Promo</em> = Ya, sesuai UU Pelindungan Data Pribadi.</p></div>';
	} );
} );

add_action( 'admin_post_yk_export', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) || ! check_admin_referer( 'yk_export' ) ) {
		wp_die( 'Tidak diizinkan.' );
	}
	$only_optin = ! empty( $_GET['optin'] );
	$head = array( 'Tipe', 'Sumber Daftar', 'Tanggal Daftar', 'Nama Lengkap', 'Email', 'WhatsApp', 'Link WhatsApp', 'Setuju Promo', 'Tanggal Setuju', 'Jumlah Pesanan', 'Total Belanja (Rp)', 'Pesanan Terakhir', 'Kota', 'Nama Penerima', 'WhatsApp Penerima', 'Alamat Penerima', 'Produk Favorit' );
	$rows = array();
	$seen = array();
	$users = get_users( array( 'role__in' => array( 'customer', 'subscriber' ) ) );
	foreach ( $users as $u ) {
		$id  = $u->ID;
		$opt = get_user_meta( $id, 'yk_marketing_optin', true );
		if ( $only_optin && 'yes' !== $opt ) {
			continue;
		}
		$wa   = yk_norm_wa( get_user_meta( $id, 'billing_phone', true ) );
		$last = function_exists( 'wc_get_customer_last_order' ) ? wc_get_customer_last_order( $id ) : null;
		$favs = get_user_meta( $id, 'yk_favs', true );
		$fav_names = array();
		if ( is_array( $favs ) ) {
			foreach ( $favs as $pid ) {
				$t = get_the_title( $pid );
				if ( $t ) {
					$fav_names[] = wp_strip_all_tags( $t );
				}
			}
		}
		$addr = trim( implode( ', ', array_filter( array( get_user_meta( $id, 'shipping_address_1', true ), get_user_meta( $id, 'shipping_address_2', true ), get_user_meta( $id, 'shipping_city', true ), get_user_meta( $id, 'shipping_postcode', true ) ) ) ) );
		$name = trim( get_user_meta( $id, 'billing_first_name', true ) . ' ' . get_user_meta( $id, 'billing_last_name', true ) );
		$rows[] = array(
			'Member',
			get_user_meta( $id, 'yk_signup_source', true ) ?: '-',
			$u->user_registered,
			$name ?: $u->display_name,
			$u->user_email,
			yk_fmt_wa( $wa ),
			$wa ? 'https://wa.me/' . $wa : '',
			'yes' === $opt ? 'Ya' : 'Tidak',
			get_user_meta( $id, 'yk_optin_date', true ),
			function_exists( 'wc_get_customer_order_count' ) ? wc_get_customer_order_count( $id ) : 0,
			function_exists( 'wc_get_customer_total_spent' ) ? round( (float) wc_get_customer_total_spent( $id ) ) : 0,
			$last && $last->get_date_created() ? $last->get_date_created()->date( 'Y-m-d H:i' ) : '',
			get_user_meta( $id, 'billing_city', true ),
			trim( get_user_meta( $id, 'shipping_first_name', true ) . ' ' . get_user_meta( $id, 'shipping_last_name', true ) ),
			yk_fmt_wa( get_user_meta( $id, 'shipping_phone', true ) ),
			$addr,
			implode( '; ', $fav_names ),
		);
		$seen[ strtolower( $u->user_email ) ] = true;
	}
	if ( ! $only_optin && function_exists( 'wc_get_orders' ) ) {
		$guests = array();
		$orders = wc_get_orders( array( 'customer_id' => 0, 'limit' => -1, 'orderby' => 'date', 'order' => 'ASC', 'status' => array_keys( wc_get_order_statuses() ) ) );
		foreach ( $orders as $o ) {
			$em = strtolower( $o->get_billing_email() );
			if ( ! $em || isset( $seen[ $em ] ) ) {
				continue;
			}
			if ( ! isset( $guests[ $em ] ) ) {
				$guests[ $em ] = array( 'n' => 0, 't' => 0, 'o' => $o, 'first' => $o );
			}
			$guests[ $em ]['n']++;
			if ( $o->is_paid() ) {
				$guests[ $em ]['t'] += (float) $o->get_total();
			}
			$guests[ $em ]['o'] = $o;
		}
		foreach ( $guests as $em => $g ) {
			$o  = $g['o'];
			$wa = yk_norm_wa( $o->get_billing_phone() );
			$rows[] = array(
				'Pembeli tamu',
				'checkout tanpa akun',
				$g['first']->get_date_created() ? $g['first']->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
				trim( $o->get_billing_first_name() . ' ' . $o->get_billing_last_name() ),
				$o->get_billing_email(),
				yk_fmt_wa( $wa ),
				$wa ? 'https://wa.me/' . $wa : '',
				'Belum ditanya',
				'',
				$g['n'],
				round( $g['t'] ),
				$o->get_date_created() ? $o->get_date_created()->date( 'Y-m-d H:i' ) : '',
				$o->get_billing_city(),
				trim( $o->get_shipping_first_name() . ' ' . $o->get_shipping_last_name() ),
				yk_fmt_wa( $o->get_shipping_phone() ),
				trim( implode( ', ', array_filter( array( $o->get_shipping_address_1(), $o->get_shipping_address_2(), $o->get_shipping_city(), $o->get_shipping_postcode() ) ) ) ),
				'',
			);
		}
	}
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=yulika-member-' . gmdate( 'Y-m-d' ) . ( $only_optin ? '-optin' : '' ) . '.csv' );
	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" );
	fputcsv( $out, $head );
	foreach ( $rows as $r ) {
		$r = array_map( function ( $v ) {
			$v = (string) $v;
			return preg_match( '/^[=+\-@]/', $v ) ? "'" . $v : $v;
		}, $r );
		fputcsv( $out, $r );
	}
	fclose( $out );
	exit;
} );