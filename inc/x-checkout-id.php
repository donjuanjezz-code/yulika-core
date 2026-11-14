<?php
/**
 * Checkout berbahasa Indonesia (14 Nov 2026, disetujui Jess — usulan 5 audit checkout).
 *
 * Lapisan terjemahan untuk halaman /checkout/ (form pemesanan) saja; halaman lain tidak berubah.
 * Sumber teks Inggris tetap di modul aslinya (s06, s15, s39, x-checkout-sumber, WooCommerce, gateway),
 * jadi untuk kembali ke bahasa Inggris cukup hapus file ini.
 *
 * Diterapkan di 4 tempat dengan satu peta kata:
 *   1. HTML halaman checkout (output buffer)        2. fragmen AJAX update_order_review
 *   3. pesan error (wc_add_notice / validasi)        4. gettext WooCommerce (string inti)
 * Nilai yang disimpan ke order (value input, kunci survei, metode) TIDAK diterjemahkan.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Konteks: halaman form checkout, atau AJAX checkout WooCommerce */
function yk_coid_on() {
	static $on = null;
	if ( null !== $on ) {
		return $on;
	}
	$ajax = isset( $_GET['wc-ajax'] ) ? sanitize_key( wp_unslash( $_GET['wc-ajax'] ) ) : ''; // phpcs:ignore
	if ( in_array( $ajax, array( 'update_order_review', 'checkout', 'apply_coupon', 'remove_coupon' ), true ) ) {
		$on = true;
		return $on;
	}
	if ( ! did_action( 'wp' ) ) {
		return false; // belum bisa ditentukan; jangan di-cache
	}
	$on = function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url( 'order-received' ) && ! is_wc_endpoint_url( 'order-pay' );
	return $on;
}

function yk_coid_map() {
	static $m = null;
	if ( null !== $m ) {
		return $m;
	}
	$m = array(
		/* Metode */
		'How would you like to send your order?' => 'Pesanan mau dikirim atau diambil?',
		'Delivery or Store Pick Up'              => 'Pilih kirim ke alamat atau ambil di toko',
		'<strong>Delivery</strong><small>Jakarta &amp; nearby Bekasi &middot; from ' => '<strong>Kirim ke Alamat</strong><small>Jakarta &amp; Bekasi sekitarnya &middot; mulai ',
		'<strong>Store Pickup</strong><small>Free &middot; Kelapa Gading &middot; daily ' => '<strong>Ambil di Toko</strong><small>Gratis &middot; Kelapa Gading &middot; setiap hari ',
		/* Jadwal */
		'Delivery Schedule</h3>'                 => 'Jadwal Kirim</h3>',
		"'Pickup Schedule':'Delivery Schedule'"  => "'Jadwal Ambil':'Jadwal Kirim'",
		"text(p?'Pickup':'Delivery')"            => "text(p?'Ambil':'Kirim')",
		'<span class="yk-lbl-d">Delivery</span> Date&nbsp;' => 'Tanggal <span class="yk-lbl-d">Kirim</span>&nbsp;',
		'<span class="yk-lbl-d">Delivery</span> Time&nbsp;' => 'Jam <span class="yk-lbl-d">Kirim</span>&nbsp;',
		'<strong>Pickup location:</strong>'      => '<strong>Lokasi pengambilan:</strong>',
		'Open daily 08:00'                       => 'Buka setiap hari 08:00',
		'Open in Google Maps'                    => 'Buka di Google Maps',
		'Every arrangement is made fresh and needs about 4 working hours (our florists work ' => 'Setiap rangkaian dibuat fresh dan butuh sekitar 4 jam kerja (florist kami bekerja ',
		' daily). Orders placed outside working hours are prepared the next day. Earliest available:' => ' setiap hari). Pesanan di luar jam kerja dikerjakan hari berikutnya. Paling cepat tersedia:',
		'</strong>. Need it sooner? '            => '</strong>. Butuh lebih cepat? ',
		'>Chat with us on WhatsApp</a>'          => '>Chat kami di WhatsApp</a>',
		/* Penerima & pengirim */
		'Recipient Details'                      => 'Data Penerima',
		'Sender Details'                         => 'Data Pengirim',
		'Full Name&nbsp;'                        => 'Nama Lengkap&nbsp;',
		'WhatsApp Number&nbsp;'                  => 'Nomor WhatsApp&nbsp;',
		'Recipient Address&nbsp;'                => 'Alamat Penerima&nbsp;',
		'Search street, building or housing complex…' => 'Cari nama jalan, gedung, atau perumahan…',
		'class="button">Search</button>'         => 'class="button">Cari</button>',
		'&#128205; Use my current location'      => '&#128205; Pakai lokasi saya sekarang',
		'Choose the address from the suggestions, then drag the pin to the exact spot if needed.' => 'Pilih alamat dari saran yang muncul, lalu geser pin ke titik yang tepat bila perlu.',
		'House No. / Building, Floor / Landmark' => 'No. Rumah / Gedung, Lantai / Patokan',
		'e.g. Block A3 No. 12, Tower B Fl. 15, next to the mosque' => 'contoh: Blok A3 No. 12, Tower B Lt. 15, sebelah masjid',
		'<div class="yk-sp-t">Surprise?</div>'   => '<div class="yk-sp-t">Kejutan?</div>',
		"<b>Yes</b><small>recipient doesn't know yet</small>" => '<b>Ya</b><small>penerima belum tahu</small>',
		'<b>No</b><small>recipient already knows</small>' => '<b>Tidak</b><small>penerima sudah tahu</small>',
		"Our courier won't contact the recipient. Please keep your WhatsApp number active." => 'Kurir kami tidak akan menghubungi penerima. Mohon pastikan nomor WhatsApp Anda aktif.',
		'Our courier may contact the recipient first.' => 'Kurir kami mungkin menghubungi penerima lebih dulu.',
		'Greeting Card Message&nbsp;'            => 'Ucapan di Kartu&nbsp;',
		'This message will be written on the greeting card (max. 300 characters).' => 'Ucapan ini akan ditulis di kartu (maks. 300 karakter).',
		'e.g. Happy birthday, Mom! With love, Sarah' => 'contoh: Selamat ulang tahun, Ma! Salam sayang, Sarah',
		/* Peta (pesan dari skrip) */
		'Address not found. Please drag the pin to the exact spot.' => 'Alamat tidak ditemukan. Mohon geser pin ke titik yang tepat.',
		'Could not load the address details. Please drag the pin to the exact spot.' => 'Detail alamat gagal dimuat. Mohon geser pin ke titik yang tepat.',
		'Getting your location…'                 => 'Mengambil lokasi Anda…',
		'Location permission denied. Please search the address or drag the pin.' => 'Izin lokasi ditolak. Mohon cari alamat atau geser pin.',
		'Search failed. Please drag the pin to the exact spot.' => 'Pencarian gagal. Mohon geser pin ke titik yang tepat.',
		"text('Searching…'"                      => "text('Mencari…'",
		'Your browser does not support location.' => 'Browser Anda tidak mendukung lokasi.',
		/* Ringkasan & ongkir */
		'&larr; Choose a different arrangement'  => '&larr; Pilih rangkaian lain',
		'Select the recipient address to see the delivery fee.' => 'Pilih alamat penerima untuk melihat ongkos kirim.',
		'<th>Delivery</th>'                      => '<th>Ongkos Kirim</th>',
		'<th>Store Pickup</th>'                  => '<th>Ambil di Toko</th>',
		'Store Pickup – Kelapa Gading (free)'    => 'Ambil di Toko – Kelapa Gading (gratis)',
		' is outside our delivery area (estimated travel time over 90 minutes). ' => ' di luar area pengiriman kami (perkiraan waktu tempuh lebih dari 90 menit). ',
		'<span class="no">Sorry, '               => '<span class="no">Maaf, ',
		'</a> for a special arrangement.'        => '</a> untuk pengaturan khusus.',
		"'Place Order · '"                       => "'Pesan Sekarang · '",
		/* Pembayaran */
		'Direct bank transfer'                   => 'Transfer Bank',
		'Make your payment directly into our bank account. Please use your Order ID as the payment reference. Your order will not be shipped until the funds have cleared in our account.' => 'Transfer langsung ke rekening kami. Tulis nomor pesanan sebagai berita transfer. Pesanan dikirim setelah dana masuk ke rekening kami.',
		'Pay instantly via QRIS (any e-wallet / m-banking), GoPay, ShopeePay or bank Virtual Account. Your order is confirmed automatically.' => 'Bayar langsung lewat QRIS (semua e-wallet / m-banking), GoPay, ShopeePay, atau Virtual Account bank. Pesanan terkonfirmasi otomatis.',
		'Pay with your PayPal account or card in US dollars. After placing your order you will receive a secure PayPal payment link.' => 'Bayar dengan akun PayPal atau kartu dalam dolar AS. Setelah memesan, Anda akan menerima link pembayaran PayPal yang aman.',
		'Estimated PayPal total:'                => 'Perkiraan total PayPal:',
		'PayPal fee &amp; currency conversion)'  => 'biaya PayPal &amp; konversi mata uang)',
		'(includes '                             => '(termasuk ',
		/* Survei sumber */
		'How did you find us for this order?'    => 'Dari mana Anda mengenal Yulika untuk pesanan ini?',
		'Choose one. It helps us serve you better.' => 'Pilih satu. Ini membantu kami melayani Anda lebih baik.',
		'</i>Yulika Admin (WhatsApp)</span>'     => '</i>Admin Yulika (WhatsApp)</span>',
		'</i>Friend / Family</span>'             => '</i>Teman / Keluarga</span>',
		'</i>I&#039;ve ordered before</span>'    => '</i>Sudah pernah pesan</span>',
		'</i>Other</span>'                       => '</i>Lainnya</span>',
		'Please choose one option.'              => 'Mohon pilih satu.',
		'Please tell us where (e.g. Facebook, an event)' => 'Sebutkan dari mana (contoh: Facebook, acara)',
		/* Privasi & syarat */
		'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our' => 'Data pribadi Anda digunakan untuk memproses pesanan, mendukung pengalaman Anda di situs ini, dan keperluan lain yang dijelaskan dalam',
		'>privacy policy</a>'                    => '>kebijakan privasi</a>',
		'I have read and agree to the '          => 'Saya sudah membaca dan menyetujui ',
		'>Return &amp; Cancellation Policy</a>, and I agree to be contacted via WhatsApp about this order.' => '>Kebijakan Retur &amp; Pembatalan</a>, serta bersedia dihubungi lewat WhatsApp terkait pesanan ini.',
		/* Pesan error */
		'Please choose the <strong>recipient address</strong> from the suggestions or pin it on the map.' => 'Mohon pilih <strong>alamat penerima</strong> dari saran yang muncul atau tandai di peta.',
		'Sorry, the recipient address is <strong>outside our delivery area</strong>. Please ' => 'Maaf, alamat penerima berada <strong>di luar area pengiriman</strong> kami. Silakan ',
		'>chat with us on WhatsApp</a>'          => '>chat kami di WhatsApp</a>',
		'We could not determine the delivery zone for this address. Please move the pin to the exact location.' => 'Zona pengiriman untuk alamat ini belum bisa ditentukan. Mohon geser pin ke lokasi yang tepat.',
		'Please choose a <strong>delivery date</strong>.' => 'Mohon pilih <strong>tanggal kirim</strong>.',
		'Please choose a <strong>pickup date</strong>.'   => 'Mohon pilih <strong>tanggal ambil</strong>.',
		'Please choose a <strong>delivery time</strong>.' => 'Mohon pilih <strong>jam kirim</strong>.',
		'Please choose a <strong>pickup time</strong>.'   => 'Mohon pilih <strong>jam ambil</strong>.',
		'The selected delivery time is no longer available. The earliest available time is <strong>' => 'Jam kirim yang dipilih sudah tidak tersedia. Paling cepat tersedia <strong>',
		'The selected pickup time is no longer available. The earliest available time is <strong>' => 'Jam ambil yang dipilih sudah tidak tersedia. Paling cepat tersedia <strong>',
		'</strong>. Please choose another time.' => '</strong>. Mohon pilih jam lain.',
		'Orders can be scheduled up to 30 days in advance.' => 'Pesanan bisa dijadwalkan paling lama 30 hari ke depan.',
		"Please tell us <strong>how you found us</strong>. It's just one tap above the Place Order button." => 'Mohon pilih <strong>dari mana Anda mengenal kami</strong>. Cukup satu ketukan, di atas tombol Pesan Sekarang.',
		'Please tell us <strong>where you found us</strong> in the "Other" box above the Place Order button.' => 'Mohon tulis <strong>dari mana Anda mengenal kami</strong> di kotak "Lainnya", di atas tombol Pesan Sekarang.',
		'Please tell us <strong>where you found us</strong> in the &quot;Other&quot; box above the Place Order button.' => 'Mohon tulis <strong>dari mana Anda mengenal kami</strong> di kotak &quot;Lainnya&quot;, di atas tombol Pesan Sekarang.',
		'Please write the <strong>Greeting Card Message</strong>.' => 'Mohon tulis <strong>Ucapan di Kartu</strong>.',
		'<strong>Sender Full Name</strong>'      => '<strong>Nama Lengkap Pengirim</strong>',
		'<strong>Sender WhatsApp Number</strong>' => '<strong>Nomor WhatsApp Pengirim</strong>',
		'<strong>Recipient Full Name</strong>'   => '<strong>Nama Lengkap Penerima</strong>',
		'<strong>Recipient WhatsApp Number</strong>' => '<strong>Nomor WhatsApp Penerima</strong>',
		'<strong>Recipient Recipient Address</strong>' => '<strong>Alamat Penerima</strong>',
		'<strong>Recipient Address</strong>'     => '<strong>Alamat Penerima</strong>',
	);
	return $m;
}

function yk_coid( $html ) {
	if ( ! is_string( $html ) || '' === $html ) {
		return $html;
	}
	$html = strtr( $html, yk_coid_map() );
	/* Tanggal "Saturday, 14 November 2026" → "Sabtu, 14 November 2026" (hanya teks tampilan; value tetap Y-m-d) */
	static $D = array( 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu' );
	static $M = array( 'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 'April' => 'April', 'May' => 'Mei', 'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September', 'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember' );
	return preg_replace_callback(
		'/\b(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday), (\d{1,2}) (January|February|March|April|May|June|July|August|September|October|November|December) (\d{4})\b/',
		function ( $x ) use ( $D, $M ) {
			return $D[ $x[1] ] . ', ' . $x[2] . ' ' . $M[ $x[3] ] . ' ' . $x[4];
		},
		$html
	);
}

/* 1. HTML halaman checkout */
add_action( 'template_redirect', function () {
	if ( yk_coid_on() ) {
		ob_start( 'yk_coid' );
	}
}, 1 );

/* 2. Fragmen AJAX (ringkasan pesanan, info zona, dll.) */
add_filter( 'woocommerce_update_order_review_fragments', function ( $f ) {
	if ( is_array( $f ) ) {
		foreach ( $f as $k => $v ) {
			$f[ $k ] = yk_coid( $v );
		}
	}
	return $f;
}, 9999 );

/* 3. Pesan error & notice */
foreach ( array( 'woocommerce_add_error', 'woocommerce_add_notice', 'woocommerce_add_success' ) as $yk_coid_h ) {
	add_filter( $yk_coid_h, function ( $msg ) {
		return yk_coid_on() ? yk_coid( $msg ) : $msg;
	}, 9999 );
}

/* 4. String inti WooCommerce */
add_filter( 'gettext', function ( $t, $text, $domain ) {
	static $G = array(
		'Product'                        => 'Produk',
		'Subtotal'                       => 'Subtotal',
		'Total'                          => 'Total',
		'Have a coupon?'                 => 'Punya kode voucher?',
		'Click here to enter your code'  => 'Klik di sini untuk memasukkan kode',
		'If you have a coupon code, please apply it below.' => 'Kalau punya kode voucher, masukkan di bawah ini.',
		'Coupon code'                    => 'Kode voucher',
		'Coupon:'                        => 'Voucher:',
		'Apply coupon'                   => 'Pakai voucher',
		'Coupon code applied successfully.' => 'Kode voucher berhasil dipakai.',
		'Coupon has been removed.'       => 'Voucher sudah dihapus.',
		'Please enter a coupon code.'    => 'Mohon masukkan kode voucher.',
		'[Remove]'                       => '[Hapus]',
		'(optional)'                     => '(opsional)',
		'%s is a required field.'        => '%s wajib diisi.',
		'%s is not a valid phone number.' => '%s bukan nomor telepon yang valid.',
		'Please read and accept the terms and conditions to proceed with your order.' => 'Mohon centang persetujuan Kebijakan Retur & Pembatalan untuk melanjutkan pesanan.',
		'Invalid payment method.'        => 'Metode pembayaran tidak valid.',
		'No payment method provided.'    => 'Mohon pilih metode pembayaran.',
		'Sorry, your session has expired.' => 'Maaf, sesi Anda sudah berakhir.',
		'Return to shop'                 => 'Kembali ke toko',
		'There was an error processing your order. Please check for any charges in your payment method and review your <a href="%s">order history</a> before placing the order again.' => 'Terjadi kesalahan saat memproses pesanan. Mohon cek dulu apakah pembayaran sudah terpotong dan lihat <a href="%s">riwayat pesanan</a> sebelum memesan ulang.',
		'We were unable to process your order, please try again.' => 'Pesanan belum berhasil diproses, mohon coba lagi.',
		'Error processing checkout. Please try again.' => 'Checkout gagal diproses. Mohon coba lagi.',
		'Shipping'                       => 'Ongkos Kirim',
		'Free!'                          => 'Gratis',
	);
	if ( 'woocommerce' === $domain && isset( $G[ $text ] ) && yk_coid_on() ) {
		return $G[ $text ];
	}
	return $t;
}, 30, 3 );

/* Tombol pesan */
add_filter( 'woocommerce_order_button_text', function ( $t ) {
	return yk_coid_on() ? 'Pesan Sekarang' : $t;
}, 200 );
