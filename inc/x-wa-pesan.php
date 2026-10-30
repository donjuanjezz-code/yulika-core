<?php
// Pesan otomatis WhatsApp dari website (30 Okt 2026, permintaan Jess; format final 18:29).
// Semua link WA dari yulikaflorist.com memakai pembuka + catatan rekening resmi:
//
//   Halo Admin,
//   Saya baru saja cek web yulikaflorist.com dan ingin bertanya lebih lanjut mengenai produknya, boleh dibantu?
//
//   Rekening resmi:
//   • BCA a.n. Jessica P H BDN Yulika F
//   • Midtrans & PayPal a.n. Yulika Florist
//
// - Link buatan kode memakai yk_wa_text() (pesan lengkap sesuai konteks).
// - Link lain (konten halaman/Elementor, kontak footer, pilihan CS) diseragamkan oleh JS:
//   tanpa teks -> pesan standar; dengan teks -> sapaan diganti pembuka standar, maksud aslinya dipertahankan.
// - Tidak diubah: konfirmasi order di halaman terima kasih, form custom order, tombol admin.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'yk_wa_footer' ) ) {
	/* Catatan rekening resmi (anti transfer ke rekening pribadi). */
	function yk_wa_footer() {
		return "Rekening resmi:\n• BCA a.n. Jessica P H BDN Yulika F\n• Midtrans & PayPal a.n. Yulika Florist";
	}
}

if ( ! function_exists( 'yk_wa_text' ) ) {
	/**
	 * @param string $ask   Mis. "ingin menanyakan harga produk *Nama*". Kosong = pertanyaan umum soal produk.
	 * @param string $url   Link produk/halaman (opsional, baris sendiri).
	 * @param string $extra Baris tambahan untuk diisi pembeli (opsional, paragraf sendiri).
	 */
	function yk_wa_text( $ask = '', $url = '', $extra = '' ) {
		$head = "Halo Admin,\nSaya baru saja cek web yulikaflorist.com dan ";
		if ( '' === $ask ) {
			$ask = 'ingin bertanya lebih lanjut mengenai produknya';
		}
		if ( '' === $url && '' === $extra ) {
			$body = $head . $ask . ', boleh dibantu?';
		} else {
			$body = $head . $ask . ( '' !== $url ? ":\n" . $url : '.' );
			if ( '' !== $extra ) {
				$body .= "\n\n" . $extra;
			}
			$body .= "\n\nBoleh dibantu?";
		}
		return $body . "\n\n" . yk_wa_footer();
	}
}

if ( ! function_exists( 'yk_wa_default_text' ) ) {
	/* Pesan default halaman ini: halaman produk = sebut nama + link produk, selain itu pertanyaan umum. */
	function yk_wa_default_text() {
		if ( function_exists( 'is_product' ) && is_product() ) {
			$id = get_queried_object_id();
			return yk_wa_text( 'ingin bertanya lebih lanjut mengenai produk *' . wp_strip_all_tags( get_the_title( $id ) ) . '*', get_permalink( $id ) );
		}
		return yk_wa_text();
	}
}

add_action( 'wp_footer', function () {
	if ( is_admin() ) {
		return;
	}
	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) {
		return; // konfirmasi order: pesan berisi detail pesanan, biarkan apa adanya
	}
	?>
<script data-no-optimize="1" data-no-defer="1" data-cfasync="false">(function(){
var HEAD='Halo Admin,\nSaya baru saja cek web yulikaflorist.com',MARK='cek web yulikaflorist.com';
var FOOT=<?php echo wp_json_encode( yk_wa_footer() ); ?>;
var DEF=<?php echo wp_json_encode( yk_wa_default_text() ); ?>;
var SEL='a[href*="wa.me"],a[href*="whatsapp.com"]',SKIP='.ykco-done,.yk-wa-confirm,[data-wa-keep]';
var VERB=/^(ingin|akan|hendak|perlu|butuh|tertarik|berminat|bertanya|menanyakan|konsultasi|memesan|pesan|order|mengubah|mengganti|mengirim|kirim|klaim|komplain|mengajukan|berkunjung|datang)\b/i;
function msg(t){
 var r=t.replace(/^\s*(hi|halo|hallo|hai|hello)(\s+(kak|min|admin|yulika(\s+florist)?))?\s*[,!.]?\s*/i,'').trim();
 if(!r)return DEF;
 var v=r.replace(/^saya\s+/i,'').replace(/^mau\s+/i,'ingin ').replace(/^ingin\s+tanya\b/i,'ingin bertanya');
 if(VERB.test(v)){
  v=v.charAt(0).toLowerCase()+v.slice(1);
  if(/[:#]$/.test(v))return HEAD+'.\n\n'+FOOT+'\n\nSaya '+v; // pembeli melanjutkan mengetik di akhir (mis. nomor order)
  if(/\?$/.test(v))return HEAD+' dan '+v+'\n\n'+FOOT;
  return HEAD+' dan '+v.replace(/[.!,\s]+$/,'')+', boleh dibantu?\n\n'+FOOT;
 }
 return HEAD+'.\n'+r.charAt(0).toUpperCase()+r.slice(1)+'\n\n'+FOOT;
}
function fix(a){
 if(!a||!a.href||a.closest(SKIP))return;
 var u;try{u=new URL(a.href);}catch(e){return;}
 if(!/(^|\.)wa\.me$|(^|\.)whatsapp\.com$/i.test(u.hostname))return;
 var t=u.searchParams.get('text')||'';
 if(t.indexOf(MARK)>-1)return;
 var q=[];u.searchParams.forEach(function(val,key){if(key!=='text')q.push(encodeURIComponent(key)+'='+encodeURIComponent(val));});
 q.push('text='+encodeURIComponent(msg(t)));
 a.href=u.origin+u.pathname+'?'+q.join('&')+u.hash;
}
function all(){[].forEach.call(document.querySelectorAll(SEL),fix);}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',all);else all();
['click','auxclick','contextmenu','touchstart'].forEach(function(ev){document.addEventListener(ev,function(e){var a=e.target&&e.target.closest&&e.target.closest(SEL);if(a)fix(a);},{capture:true,passive:true});});
window.ykWaMsg=msg;
})();</script>
	<?php
}, 45 );
