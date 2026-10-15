<?php
// Dari Code Snippets #42 "#42 Checkout khusus Flower Board" — scope: global
// #42 Checkout khusus Flower Board (13 Okt 2026): ucapan Header/Sub/Penerima/Pengirim + batas huruf per ukuran,
// preview, upload logo opsional, venue/jam acara/kontak lokasi. Kartu ucapan, Surprise & Pickup disembunyikan.
function yk_bd_cart_size() {
	if (!function_exists('WC') || !WC()->cart) return 0;
	$h = 0;
	foreach (WC()->cart->get_cart() as $ci) {
		$pid = $ci['product_id'];
		if (has_term('flower-boards', 'product_cat', $pid)) $h = max($h, (int) get_post_meta($pid, 'yk_board_h', true) ?: 140);
	}
	return $h;
}
function yk_bd_limits($h) {
	if ($h <= 120) return array('area' => 60, 'foot' => 40, 'label' => '120 × 200 cm');
	if ($h >= 160) return array('area' => 100, 'foot' => 60, 'label' => '160 × 240 cm');
	return array('area' => 80, 'foot' => 50, 'label' => '140 × 200 cm');
}
function yk_bd_len($s) { return function_exists('mb_strlen') ? mb_strlen($s) : strlen($s); }
function yk_bd_up($s) { return function_exists('mb_strtoupper') ? mb_strtoupper($s) : strtoupper($s); }

/* ---------- Upload logo (opsional) ---------- */
function yk_bd_logo_upload() {
	$ip = class_exists('WC_Geolocation') ? WC_Geolocation::get_ip_address() : $_SERVER['REMOTE_ADDR'];
	$rk = 'yk_bdl_' . md5($ip); $n = (int) get_transient($rk);
	if ($n >= 10) wp_send_json_error(array('m' => 'Terlalu banyak upload. Kirim logo via WhatsApp saja.'), 429);
	set_transient($rk, $n + 1, HOUR_IN_SECONDS);
	if (empty($_FILES['logo']) || (int) $_FILES['logo']['error'] !== UPLOAD_ERR_OK || (int) $_FILES['logo']['size'] > 5 * 1024 * 1024) wp_send_json_error(array('m' => 'File gagal diupload (maks 5 MB).'), 400);
	$info = @getimagesize($_FILES['logo']['tmp_name']);
	$map = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp');
	if (!$info || !isset($map[$info['mime']])) wp_send_json_error(array('m' => 'Format logo harus JPG, PNG atau WEBP.'), 400);
	require_once ABSPATH . 'wp-admin/includes/file.php';
	$dirf = function ($u) { $u['subdir'] = '/board-logo'; $u['path'] = $u['basedir'] . $u['subdir']; $u['url'] = $u['baseurl'] . $u['subdir']; return $u; };
	add_filter('upload_dir', $dirf);
	$file = array('name' => 'logo-' . gmdate('ymd') . '-' . strtolower(wp_generate_password(10, false)) . '.' . $map[$info['mime']], 'type' => $info['mime'], 'tmp_name' => $_FILES['logo']['tmp_name'], 'error' => 0, 'size' => $_FILES['logo']['size']);
	$r = wp_handle_upload($file, array('test_form' => false, 'mimes' => array('jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp')));
	remove_filter('upload_dir', $dirf);
	if (empty($r['url'])) wp_send_json_error(array('m' => 'Upload gagal. Kirim logo via WhatsApp saja.'), 500);
	wp_send_json_success(array('url' => $r['url']));
}
add_action('wp_ajax_yk_bd_logo', 'yk_bd_logo_upload');
add_action('wp_ajax_nopriv_yk_bd_logo', 'yk_bd_logo_upload');

/* ---------- Form: ucapan + pengirim (di posisi kartu ucapan) ---------- */
add_action('woocommerce_after_checkout_billing_form', function () {
	$h = yk_bd_cart_size();
	if (!$h) return;
	$L = yk_bd_limits($h);
	$heads = array('CONGRATULATIONS','HAPPY WEDDING','SELAMAT & SUKSES','SELAMAT & BAHAGIA','SELAMAT MILAD','SELAMAT ULANG TAHUN','SELAMAT HUT','TURUT BERDUKA CITA','HAPPY GRADUATION','HAPPY ANNIVERSARY','SELAMAT BERBAHAGIA');
	?>
<div id="yk-bd" data-area="<?php echo (int) $L['area']; ?>" data-foot="<?php echo (int) $L['foot']; ?>" data-ajax="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
 <h3 class="yk-bd-h">Tulisan Flower Board</h3>
 <p class="yk-bd-sub">Ukuran papan <?php echo esc_html($L['label']); ?> · area ucapan maks. <b><?php echo (int) $L['area']; ?> huruf</b>, pengirim maks. <b><?php echo (int) $L['foot']; ?> huruf</b>.</p>

 <div class="yk-bd-f"><label>Ucapan utama (Header) <abbr class="required">*</abbr></label>
  <div class="yk-bd-chips" data-for="yk_bd_head"><?php foreach ($heads as $x) echo '<button type="button" data-v="' . esc_attr($x) . '">' . esc_html($x) . '</button>'; ?></div>
  <input type="text" name="yk_bd_head" id="yk_bd_head" maxlength="20" placeholder="Pilih di atas atau tulis sendiri" autocomplete="off">
 </div>
 <div class="yk-bd-f"><label>Lanjutan ucapan (Sub Header)</label>
  <div class="yk-bd-chips" id="yk-bd-subchips" data-for="yk_bd_subh"></div>
  <input type="text" name="yk_bd_subh" id="yk_bd_subh" maxlength="45" placeholder="Contoh: ATAS PEMBUKAAN" autocomplete="off">
  <div class="yk-bd-note" id="yk-bd-duka" hidden>Untuk duka cita, warna rangkaian akan kami sesuaikan (putih/lembut). Tim kami akan konfirmasi via WhatsApp.</div>
 </div>
 <div class="yk-bd-f"><label>Penerima <abbr class="required">*</abbr></label>
  <div class="yk-bd-seg"><label><input type="radio" name="yk_bd_rtype" value="nama" checked><span>Nama orang / pasangan</span></label><label><input type="radio" name="yk_bd_rtype" value="perusahaan"><span>Perusahaan / instansi</span></label></div>
  <input type="text" name="yk_bd_recv" id="yk_bd_recv" maxlength="45" placeholder="Contoh: RENO &amp; SUPHI / MELANGLANG KAFE" autocomplete="off">
  <div class="yk-bd-logo" data-k="recv" hidden><label class="yk-bd-up"><input type="file" accept="image/png,image/jpeg,image/webp" hidden><span>+ Upload logo penerima (opsional)</span></label><input type="hidden" name="yk_bd_recv_logo"></div>
 </div>
 <div class="yk-bd-count">Area ucapan: <b id="yk-bd-c1">0</b> / <?php echo (int) $L['area']; ?> huruf</div>

 <div class="yk-bd-f"><label>Pengirim (bawah papan) <abbr class="required">*</abbr></label>
  <div class="yk-bd-seg yk-bd-seg3"><label><input type="radio" name="yk_bd_ftype" value="logo" checked><span>Logo &amp; nama perusahaan</span></label><label><input type="radio" name="yk_bd_ftype" value="perusahaan_nama"><span>Perusahaan &amp; nama pengirim</span></label><label><input type="radio" name="yk_bd_ftype" value="nama"><span>Nama pengirim</span></label></div>
  <textarea name="yk_bd_foot" id="yk_bd_foot" rows="2" maxlength="<?php echo (int) $L['foot']; ?>" placeholder="Contoh: PT MAJU JAYA / Keluarga Besar Budi"></textarea>
  <div class="yk-bd-logo" data-k="foot"><label class="yk-bd-up"><input type="file" accept="image/png,image/jpeg,image/webp" hidden><span>+ Upload logo pengirim (opsional)</span></label><input type="hidden" name="yk_bd_foot_logo"></div>
  <div class="yk-bd-count">Pengirim: <b id="yk-bd-c2">0</b> / <?php echo (int) $L['foot']; ?> huruf · logo juga bisa dikirim via WhatsApp</div>
 </div>

 <div class="yk-bd-prev" aria-label="Preview papan"><div class="yk-bd-pv"><div class="pv-h" id="pv-h">SELAMAT &amp; SUKSES</div><div class="pv-s" id="pv-s"></div><div class="pv-r" id="pv-r">NAMA PENERIMA</div><div class="pv-f" id="pv-f">Nama pengirim</div></div><small>Preview susunan tulisan (desain &amp; warna mengikuti foto produk)</small></div>
</div>
<?php }, 20);

/* ---------- Detail acara (di bagian penerima) ---------- */
add_action('woocommerce_after_checkout_shipping_form', function () {
	if (!yk_bd_cart_size()) return; ?>
<div id="yk-bd-ev">
 <h3 class="yk-bd-h">Detail Acara &amp; Pemasangan</h3>
 <div class="yk-bd-f"><label for="yk_bd_venue">Nama venue / gedung <abbr class="required">*</abbr></label><input type="text" name="yk_bd_venue" id="yk_bd_venue" maxlength="100" placeholder="Contoh: Gedung Sasana Kriya, Ballroom A"></div>
 <div class="yk-bd-f"><label for="yk_bd_time">Jam acara dimulai <abbr class="required">*</abbr></label><input type="time" name="yk_bd_time" id="yk_bd_time"><span class="yk-bd-hint">Papan kami pasang minimal 1 jam sebelum acara — pilih jam kirim yang sesuai di Jadwal Pengiriman.</span></div>
 <div class="yk-bd-f"><label>Siapa yang dihubungi kurir saat tiba di lokasi? <abbr class="required">*</abbr></label>
  <div class="yk-bd-seg yk-bd-seg3"><label><input type="radio" name="yk_bd_contact" value="pengirim" checked><span>Pengirim</span></label><label><input type="radio" name="yk_bd_contact" value="pic"><span>PIC Venue</span></label><label><input type="radio" name="yk_bd_contact" value="cs"><span>CS Yulika</span></label></div>
  <div class="yk-bd-pic" hidden><input type="text" name="yk_bd_pic_name" maxlength="60" placeholder="Nama PIC venue"><input type="tel" name="yk_bd_pic_wa" maxlength="20" placeholder="No. WhatsApp PIC"></div>
 </div>
 <div class="yk-bd-f"><label for="yk_bd_note">Catatan pemasangan (opsional)</label><input type="text" name="yk_bd_note" id="yk_bd_note" maxlength="150" placeholder="Contoh: taruh di lobby sebelah kiri pintu masuk"></div>
</div>
<?php }, 5);

/* ---------- CSS + JS ---------- */
add_action('wp_footer', function () {
	if (!function_exists('is_checkout') || !is_checkout() || is_order_received_page() || !yk_bd_cart_size()) return; ?>
<style>
body #yk_greeting_field,body .yk-chips,body #yk-sp,body .yk-mopt:has(input[value=pickup]){display:none!important}
#yk-bd,#yk-bd-ev{margin:6px 0 18px;font-family:'Plus Jakarta Sans',Montserrat,Arial,sans-serif}
.yk-bd-h{font-size:20px!important;margin:8px 0 4px!important}
.yk-bd-sub{font-size:13px;color:#6e6e73;margin:0 0 12px;line-height:1.45}
.yk-bd-f{margin:0 0 14px}
.yk-bd-f>label{display:block;font-weight:600;font-size:14px;margin:0 0 6px}
.yk-bd-f input[type=text],.yk-bd-f input[type=tel],.yk-bd-f input[type=time],.yk-bd-f textarea{width:100%;box-sizing:border-box;border:1px solid #e3d9cc!important;border-radius:10px!important;padding:0 12px!important;height:48px;font-size:16px!important;background:#fff!important}
.yk-bd-f textarea{height:auto;min-height:64px;padding:10px 12px!important}
#yk_bd_head,#yk_bd_subh,#yk_bd_recv{text-transform:uppercase}
#yk-bd ::placeholder,#yk-bd-ev ::placeholder{text-transform:none;color:#a9a39b}
.yk-bd-count,.yk-bd-hint,.yk-bd-sub,.yk-bd-prev small{line-height:1.45!important}
.yk-bd-chips{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 8px}
.yk-bd-chips button{border:1px solid #e3d9cc!important;background:#fff!important;color:#333!important;border-radius:18px!important;padding:7px 11px!important;font-size:12px!important;line-height:1.15!important;font-weight:600!important;text-transform:none!important;letter-spacing:0!important;cursor:pointer;min-height:0!important;height:auto!important}
.yk-bd-chips button.on{background:#a0784a!important;border-color:#a0784a!important;color:#fff!important}
.yk-bd-seg{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:0 0 8px}
.yk-bd-seg3{grid-template-columns:1fr 1fr 1fr}
.yk-bd-seg label{margin:0!important;cursor:pointer}
.yk-bd-seg input{position:absolute;opacity:0;width:1px;height:1px}
.yk-bd-seg span{display:flex;align-items:center;justify-content:center;text-align:center;min-height:46px;padding:6px;border:1.5px solid #e3d9cc;border-radius:10px;font-size:12.5px;line-height:1.25;background:#fff}
.yk-bd-seg input:checked+span{border-color:#a0784a;background:#f8f4ee;color:#8a5a2b;font-weight:700}
.yk-bd-count{font-size:12.5px;color:#6e6e73;margin:-4px 0 14px}
.yk-bd-count.over b{color:#c0392b}
.yk-bd-count.over{color:#c0392b}
.yk-bd-up{display:inline-block;margin:8px 0 0!important;cursor:pointer}
.yk-bd-up span{display:inline-block;border:1.5px dashed #c9b49a;border-radius:10px;padding:8px 12px;font-size:13px;color:#8a5a2b;background:#fdfaf6}
.yk-bd-logo img{height:40px;margin:8px 8px 0 0;vertical-align:middle;border-radius:4px}
.yk-bd-note{margin-top:8px;padding:8px 10px;border-left:3px solid #a0784a;background:#f8f4ee;font-size:13px;color:#5a3d22;border-radius:0 8px 8px 0}
.yk-bd-hint{display:block;font-size:12.5px;color:#6e6e73;margin-top:6px;line-height:1.4}
.yk-bd-pic{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.yk-bd-pic[hidden],.yk-bd-logo[hidden],.yk-bd-note[hidden]{display:none!important}
.yk-bd-prev{margin:4px 0 18px;text-align:center}
.yk-bd-prev small{display:block;color:#8a8077;font-size:12px;margin-top:6px}
.yk-bd-pv{background:#c8102e;border:8px solid #f5d04c;outline:6px solid #e8559a;border-radius:6px;padding:14px 10px 0;margin:8px 6px 0;color:#fff;font-family:Impact,'Arial Black',Arial,sans-serif;letter-spacing:.02em}
.pv-h{font-size:22px;color:#ffe46b;line-height:1.1;word-break:break-word}
.pv-s{font-size:13px;margin-top:4px;line-height:1.2;word-break:break-word}
.pv-r{font-size:19px;margin:6px 0 10px;line-height:1.15;word-break:break-word}
.pv-f{background:#fff6c8;color:#8a1020;margin:0 -10px;padding:8px 10px;font-size:15px;line-height:1.2;white-space:pre-line;word-break:break-word}
.pv-f img,.pv-r img{height:22px;vertical-align:middle;margin-right:6px;background:#fff;border-radius:3px}
@media(max-width:480px){.yk-bd-seg3{grid-template-columns:1fr}.yk-bd-seg3 span{min-height:40px}}
</style>
<script data-no-optimize="1" data-no-defer="1" data-cfasync="false">
(function(){
 var B=document.getElementById('yk-bd');if(!B)return;
 var AREA=+B.getAttribute('data-area'),FOOT=+B.getAttribute('data-foot'),AJ=B.getAttribute('data-ajax');
 var S={'CONGRATULATIONS':['ATAS GRAND OPENING','ATAS PEMBUKAAN','ATAS PERESMIAN','ATAS DIBUKANYA KEMBALI','ATAS DILANTIK SEBAGAI '],
 'SELAMAT & SUKSES':['ATAS GRAND OPENING','ATAS PEMBUKAAN','ATAS PERESMIAN','ATAS DIBUKANYA KEMBALI','ATAS DILANTIK SEBAGAI '],
 'HAPPY WEDDING':['ATAS PERNIKAHAN','SEMOGA SAKINAH, MAWADDAH, WARAHMAH','WISHING YOU A LIFETIME OF LOVE'],
 'SELAMAT & BAHAGIA':['ATAS PERNIKAHAN','SEMOGA SAKINAH, MAWADDAH, WARAHMAH','WISHING YOU A LIFETIME OF LOVE'],
 'SELAMAT BERBAHAGIA':['ATAS PERNIKAHAN','SEMOGA SAKINAH, MAWADDAH, WARAHMAH','WISHING YOU A LIFETIME OF LOVE'],
 'SELAMAT MILAD':['KE-','SEMAKIN SUKSES & JAYA SELALU','SEMOGA SEMAKIN BERKAH'],
 'SELAMAT ULANG TAHUN':['KE-','SEMAKIN SUKSES & JAYA SELALU','SEMOGA SEMAKIN BERKAH'],
 'SELAMAT HUT':['KE-','SEMAKIN SUKSES & JAYA SELALU','SEMOGA SEMAKIN BERKAH'],
 'HAPPY ANNIVERSARY':['KE-','SEMOGA SELALU HARMONIS & BAHAGIA'],
 'HAPPY GRADUATION':['ATAS KELULUSAN','CONGRATULATIONS ON YOUR ACHIEVEMENT'],
 'TURUT BERDUKA CITA':['ATAS WAFATNYA','SEMOGA HUSNUL KHATIMAH','BERISTIRAHAT DALAM DAMAI','SEMOGA KELUARGA DIBERI KETABAHAN']};
 function $(id){return document.getElementById(id);}
 var H=$('yk_bd_head'),SB=$('yk_bd_subh'),R=$('yk_bd_recv'),F=$('yk_bd_foot');
 function q(n){var x=B.querySelector('[name='+n+']:checked')||document.querySelector('[name='+n+']:checked');return x?x.value:'';}
 function subchips(){var box=$('yk-bd-subchips'),list=S[H.value.trim().toUpperCase()]||[];box.innerHTML='';list.forEach(function(v){var b=document.createElement('button');b.type='button';b.setAttribute('data-v',v);b.textContent=v.trim()==='KE-'?'KE-… (isi angka)':v;box.appendChild(b);});$('yk-bd-duka').hidden=H.value.trim().toUpperCase()!=='TURUT BERDUKA CITA';}
 function upd(){
  var t=(H.value+SB.value+R.value).length,f=F.value.length;
  $('yk-bd-c1').textContent=t;$('yk-bd-c1').parentNode.classList.toggle('over',t>AREA);
  $('yk-bd-c2').textContent=f;$('yk-bd-c2').parentNode.classList.toggle('over',f>FOOT);
  $('pv-h').textContent=(H.value||'SELAMAT & SUKSES').toUpperCase();$('pv-s').textContent=SB.value.toUpperCase();
  var rl=B.querySelector('[name=yk_bd_recv_logo]').value,fl=B.querySelector('[name=yk_bd_foot_logo]').value;
  $('pv-r').innerHTML='';if(rl&&q('yk_bd_rtype')==='perusahaan'){var i=new Image();i.src=rl;$('pv-r').appendChild(i);} $('pv-r').appendChild(document.createTextNode((R.value||'NAMA PENERIMA').toUpperCase()));
  $('pv-f').innerHTML='';if(fl&&q('yk_bd_ftype')==='logo'){var j=new Image();j.src=fl;$('pv-f').appendChild(j);} $('pv-f').appendChild(document.createTextNode(F.value||'Nama pengirim'));
  [].forEach.call(B.querySelectorAll('.yk-bd-chips button'),function(b){var inp=$(b.parentNode.getAttribute('data-for'));b.classList.toggle('on',inp&&inp.value.trim().toUpperCase()===b.getAttribute('data-v').trim());});
  try{sessionStorage.setItem('yk_bd',JSON.stringify({h:H.value,s:SB.value,r:R.value,f:F.value}));}catch(e){}
 }
 B.addEventListener('click',function(e){var b=e.target.closest('.yk-bd-chips button');if(!b)return;e.preventDefault();var inp=$(b.parentNode.getAttribute('data-for'));inp.value=b.getAttribute('data-v');if(inp===H){subchips();}inp.focus();try{inp.setSelectionRange(inp.value.length,inp.value.length);}catch(x){}upd();});
 H.addEventListener('input',function(){subchips();upd();});[SB,R,F].forEach(function(x){x.addEventListener('input',upd);});
 document.addEventListener('change',function(e){var n=e.target.name;
  if(n==='yk_bd_rtype'){B.querySelector('.yk-bd-logo[data-k=recv]').hidden=e.target.value!=='perusahaan';upd();}
  if(n==='yk_bd_ftype'){B.querySelector('.yk-bd-logo[data-k=foot]').hidden=e.target.value!=='logo';upd();}
  if(n==='yk_bd_contact'){document.querySelector('.yk-bd-pic').hidden=e.target.value!=='pic';}
  if(e.target.type==='file'&&e.target.closest('.yk-bd-logo')){var w=e.target.closest('.yk-bd-logo'),fl=e.target.files[0];if(!fl)return;var sp=w.querySelector('.yk-bd-up span');sp.textContent='Mengupload…';var fd=new FormData();fd.append('action','yk_bd_logo');fd.append('logo',fl);
   fetch(AJ,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){if(!j.success)throw new Error(j.data&&j.data.m||'Upload gagal');w.querySelector('input[type=hidden]').value=j.data.url;var im=w.querySelector('img')||document.createElement('img');im.src=j.data.url;w.insertBefore(im,w.firstChild);sp.textContent='Ganti logo';upd();}).catch(function(x){sp.textContent=x.message+' — coba lagi atau kirim via WhatsApp';});}
 });
 try{var st=JSON.parse(sessionStorage.getItem('yk_bd')||'null');if(st){H.value=st.h||'';SB.value=st.s||'';R.value=st.r||'';F.value=st.f||'';}}catch(e){}
 subchips();upd();
 document.addEventListener('change',function(e){if(e.target.name==='yk_slot'&&window.jQuery)jQuery(document.body).trigger('update_checkout');});
 var pk=document.querySelector('input[name=yk_method][value=pickup]'),dv=document.querySelector('input[name=yk_method][value=delivery]');
 if(pk&&pk.checked&&dv){dv.checked=true;dv.dispatchEvent(new Event('change',{bubbles:true}));if(window.jQuery)jQuery(dv).trigger('change');}
})();
</script>
<?php }, 30);

/* ---------- Validasi + paksa delivery + isi kartu ucapan otomatis ---------- */
add_action('woocommerce_checkout_process', function () {
	$h = yk_bd_cart_size();
	if (!$h) return;
	$L = yk_bd_limits($h);
	$g = function ($k, $max = 200) { return isset($_POST[$k]) ? trim(mb_substr(sanitize_text_field(wp_unslash($_POST[$k])), 0, $max)) : ''; };
	$_POST['yk_method'] = 'delivery';
	unset($_POST['yk_surprise']);
	$head = yk_bd_up($g('yk_bd_head', 20)); $sub = yk_bd_up($g('yk_bd_subh', 45)); $recv = yk_bd_up($g('yk_bd_recv', 45));
	$foot = isset($_POST['yk_bd_foot']) ? trim(mb_substr(sanitize_textarea_field(wp_unslash($_POST['yk_bd_foot'])), 0, 80)) : '';
	if ($head === '') wc_add_notice('Isi <strong>ucapan utama (Header)</strong> flower board.', 'error');
	if ($recv === '') wc_add_notice('Isi <strong>nama penerima</strong> di flower board.', 'error');
	if ($foot === '') wc_add_notice('Isi <strong>nama pengirim</strong> di flower board.', 'error');
	if (yk_bd_len($head . $sub . $recv) > $L['area']) wc_add_notice('Tulisan area ucapan terlalu panjang (maks. ' . $L['area'] . ' huruf untuk papan ' . $L['label'] . ').', 'error');
	if (yk_bd_len($foot) > $L['foot']) wc_add_notice('Nama pengirim terlalu panjang (maks. ' . $L['foot'] . ' huruf).', 'error');
	if ($g('yk_bd_venue', 100) === '') wc_add_notice('Isi <strong>nama venue / gedung</strong> acara.', 'error');
	if (!preg_match('/^\d{2}:\d{2}$/', $g('yk_bd_time', 5))) wc_add_notice('Isi <strong>jam acara dimulai</strong>.', 'error');
	if ($g('yk_bd_contact', 20) === 'pic' && ($g('yk_bd_pic_name', 60) === '' || strlen(preg_replace('/\D/', '', $g('yk_bd_pic_wa', 20))) < 9)) wc_add_notice('Isi <strong>nama & WhatsApp PIC venue</strong>.', 'error');
	$_POST['yk_greeting'] = 'Flower board — lihat Tulisan Flower Board';
}, 0);

add_action('woocommerce_checkout_create_order', function ($order) {
	if (!yk_bd_cart_size()) return;
	$g = function ($k, $max = 200) { return isset($_POST[$k]) ? trim(mb_substr(sanitize_text_field(wp_unslash($_POST[$k])), 0, $max)) : ''; };
	$up = wp_upload_dir(); $base = $up['baseurl'] . '/board-logo/';
	$logo = function ($k) use ($g, $base) { $u = esc_url_raw($g($k, 300)); return (strpos($u, $base) === 0) ? $u : ''; };
	$ct = $g('yk_bd_contact', 20);
	$d = array(
		'head' => yk_bd_up($g('yk_bd_head', 20)), 'sub' => yk_bd_up($g('yk_bd_subh', 45)),
		'rtype' => $g('yk_bd_rtype', 20), 'recv' => yk_bd_up($g('yk_bd_recv', 45)), 'recv_logo' => $logo('yk_bd_recv_logo'),
		'ftype' => $g('yk_bd_ftype', 20), 'foot' => isset($_POST['yk_bd_foot']) ? trim(mb_substr(sanitize_textarea_field(wp_unslash($_POST['yk_bd_foot'])), 0, 80)) : '', 'foot_logo' => $logo('yk_bd_foot_logo'),
		'venue' => $g('yk_bd_venue', 100), 'time' => $g('yk_bd_time', 5),
		'contact' => $ct === 'pic' ? 'PIC Venue: ' . $g('yk_bd_pic_name', 60) . ' (' . $g('yk_bd_pic_wa', 20) . ')' : ($ct === 'cs' ? 'CS Yulika' : 'Pengirim'),
		'note' => $g('yk_bd_note', 150),
	);
	$order->update_meta_data('_yk_bd', $d);
});

function yk_bd_rows($order) {
	$d = $order->get_meta('_yk_bd');
	if (!is_array($d) || empty($d['head'])) return array();
	$ft = array('logo' => 'Logo & nama perusahaan', 'perusahaan_nama' => 'Perusahaan & nama pengirim', 'nama' => 'Nama pengirim');
	return array(
		'Header' => $d['head'], 'Sub Header' => $d['sub'],
		'Penerima' => $d['recv'] . ($d['rtype'] === 'perusahaan' ? ' (perusahaan)' : ''), 'Logo penerima' => $d['recv_logo'],
		'Pengirim' => $d['foot'] . (isset($ft[$d['ftype']]) ? ' — ' . $ft[$d['ftype']] : ''), 'Logo pengirim' => $d['foot_logo'],
		'Venue' => $d['venue'], 'Jam acara' => $d['time'], 'Kontak di lokasi' => $d['contact'], 'Catatan pemasangan' => $d['note'],
	);
}
function yk_bd_html($order, $title = '🪧 Tulisan Flower Board') {
	$rows = yk_bd_rows($order);
	if (!$rows) return '';
	$h = '<div style="margin:12px 0;padding:10px 12px;border-left:4px solid #a0784a;background:#faf7f2"><strong>' . esc_html($title) . '</strong><table style="margin-top:6px;border-collapse:collapse">';
	foreach ($rows as $k => $v) {
		if ($v === '' || $v === null) continue;
		$val = (strpos($k, 'Logo') === 0) ? '<a href="' . esc_url($v) . '" target="_blank"><img src="' . esc_url($v) . '" style="height:40px;vertical-align:middle"></a>' : nl2br(esc_html($v));
		$h .= '<tr><td style="padding:2px 10px 2px 0;color:#666;vertical-align:top;white-space:nowrap">' . esc_html($k) . '</td><td style="padding:2px 0"><b>' . $val . '</b></td></tr>';
	}
	return $h . '</table></div>';
}
add_action('woocommerce_admin_order_data_after_shipping_address', function ($order) { echo yk_bd_html($order); }, 5);
add_action('woocommerce_email_after_order_table', function ($order) { echo yk_bd_html($order); }, 15);
add_action('woocommerce_order_details_after_order_table', function ($order) { echo yk_bd_html($order, 'Flower Board Text'); }, 5);


/* ---------- Revisi 13 Okt 19:10: slot 06:00–08:00 (+50% ongkir) khusus papan ---------- */
add_filter('yk_dlv_slots', function ($s) {
	if (is_admin() && !wp_doing_ajax()) return $s;
	if (!yk_bd_cart_size()) return $s;
	return array('06-08' => array('06:00', '08:00', '06:00 – 08:00 (+50% ongkir)')) + $s;
});
add_action('woocommerce_checkout_update_order_review', function ($post_data) {
	parse_str((string) $post_data, $d);
	if (function_exists('WC') && WC()->session) WC()->session->set('yk_bd_slot', isset($d['yk_slot']) ? sanitize_text_field($d['yk_slot']) : '');
});
add_action('woocommerce_cart_calculate_fees', function ($cart) {
	if (!yk_bd_cart_size()) return;
	$slot = isset($_POST['yk_slot']) ? sanitize_text_field(wp_unslash($_POST['yk_slot'])) : ((function_exists('WC') && WC()->session) ? (string) WC()->session->get('yk_bd_slot') : '');
	if ($slot !== '06-08') return;
	$ship = (float) $cart->get_shipping_total();
	if ($ship <= 0) return;
	$cart->add_fee('Pengiriman pagi 06:00–08:00 (+50% ongkir)', round($ship * 0.5), false);
});
/* Produk papan: sembunyikan teks Specifications umum */
add_action('wp_head', function () {
	if (function_exists('is_product') && is_product() && has_term('flower-boards', 'product_cat', get_queried_object_id())) echo '<style>.ykp-spec{display:none!important}</style>';
}, 99);


/* ---------- Revisi 13 Okt 19:30: preview papan di WA, halaman terima kasih, admin & email ---------- */
function yk_bd_preview_html($d, $w = 520) {
	if (!is_array($d) || empty($d['head'])) return '';
	$e = 'esc_html';
	$rl = (!empty($d['recv_logo']) && $d['rtype'] === 'perusahaan') ? '<img src="' . esc_url($d['recv_logo']) . '" style="height:28px;vertical-align:middle;margin-right:8px;background:#fff;border-radius:3px">' : '';
	$fl = (!empty($d['foot_logo']) && $d['ftype'] === 'logo') ? '<img src="' . esc_url($d['foot_logo']) . '" style="height:26px;vertical-align:middle;margin-right:8px;background:#fff;border-radius:3px">' : '';
	return '<div style="max-width:' . (int) $w . 'px;margin:10px auto;background:#c8102e;border:8px solid #f5d04c;outline:6px solid #e8559a;border-radius:6px;padding:16px 12px 0;color:#fff;text-align:center;font-family:Impact,\'Arial Black\',Arial,sans-serif;letter-spacing:.02em">'
		. '<div style="font-size:26px;color:#ffe46b;line-height:1.1">' . $e($d['head']) . '</div>'
		. ($d['sub'] !== '' ? '<div style="font-size:15px;margin-top:5px;line-height:1.2">' . $e($d['sub']) . '</div>' : '')
		. '<div style="font-size:22px;margin:7px 0 12px;line-height:1.15">' . $rl . $e($d['recv']) . '</div>'
		. '<div style="background:#fff6c8;color:#8a1020;margin:0 -12px;padding:10px 12px;font-size:17px;line-height:1.25;white-space:pre-line">' . $fl . $e($d['foot']) . '</div></div>';
}
function yk_bd_preview_url($order) {
	/* tanpa underscore (WhatsApp mengubah _teks_ jadi miring & memutus link) */
	return home_url('/?papan=' . $order->get_id() . '-' . substr(md5($order->get_order_key()), 0, 12));
}
/* Halaman preview publik (pakai order key) — dibuka dari link di WhatsApp */
add_action('template_redirect', function () {
	$o = null;
	if (!empty($_GET['papan']) && preg_match('/^(\d+)-([a-f0-9]{12})$/', (string) wp_unslash($_GET['papan']), $mm)) {
		$o = wc_get_order((int) $mm[1]);
		if ($o && !hash_equals(substr(md5($o->get_order_key()), 0, 12), $mm[2])) $o = null;
	} elseif (!empty($_GET['yk_board']) && !empty($_GET['k'])) {
		$o = wc_get_order((int) $_GET['yk_board']);
		if ($o && !hash_equals($o->get_order_key(), (string) wp_unslash($_GET['k']))) $o = null;
	} else {
		return;
	}
	if (!$o) { status_header(404); echo 'Preview tidak ditemukan.'; exit; }
	if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
	do_action('litespeed_control_set_nocache', 'yk board preview');
	nocache_headers();
	$d = $o->get_meta('_yk_bd');
	echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Preview Flower Board – Order #' . (int) $o->get_id() . '</title></head><body style="margin:0;background:#fbfaf9;font-family:-apple-system,Arial,sans-serif;color:#1d1d1f;padding:20px 14px">';
	echo '<p style="text-align:center;margin:0 0 4px;color:#a0784a;font-size:12px;letter-spacing:.12em;text-transform:uppercase">Yulika Florist</p><h1 style="text-align:center;font-size:20px;margin:0 0 14px">Preview Tulisan Flower Board · Order #' . (int) $o->get_id() . '</h1>';
	echo yk_bd_preview_html($d, 560);
	echo '<p style="text-align:center;color:#8a8077;font-size:12px;margin:14px 0">Preview susunan tulisan — desain &amp; warna mengikuti foto produk.</p>';
	echo yk_bd_html($o, 'Detail');
	echo '</body></html>';
	exit;
}, 1);
/* Baris untuk pesan WhatsApp (dipanggil snippet #6) */
function yk_bd_wa_lines($order) {
	$d = $order->get_meta('_yk_bd');
	if (!is_array($d) || empty($d['head'])) return array();
	$L = array('', '🪧 *TULISAN FLOWER BOARD*', 'Header: ' . $d['head']);
	if ($d['sub'] !== '') $L[] = 'Sub Header: ' . $d['sub'];
	$L[] = 'Penerima: ' . $d['recv'] . ($d['recv_logo'] ? ' (+logo)' : '');
	$L[] = 'Pengirim: ' . str_replace(array("\r", "\n"), array('', ' / '), $d['foot']) . ($d['foot_logo'] ? ' (+logo)' : '');
	$L[] = 'Venue: ' . $d['venue'] . ' · Jam acara ' . $d['time'];
	$L[] = 'Kontak di lokasi: ' . $d['contact'];
	if ($d['note'] !== '') $L[] = 'Catatan: ' . $d['note'];
	$L[] = 'Preview papan: ' . yk_bd_preview_url($order);
	return $L;
}
add_action('woocommerce_order_details_after_order_table', function ($order) { $d = $order->get_meta('_yk_bd'); if (is_array($d) && !empty($d['head'])) echo '<h3 style="margin-top:18px">Preview Flower Board</h3>' . yk_bd_preview_html($d, 520); }, 4);
add_action('woocommerce_admin_order_data_after_shipping_address', function ($order) { $d = $order->get_meta('_yk_bd'); if (is_array($d) && !empty($d['head'])) echo yk_bd_preview_html($d, 380) . '<p><a href="' . esc_url(yk_bd_preview_url($order)) . '" target="_blank">Buka preview papan ↗</a></p>'; }, 4);
add_action('woocommerce_email_after_order_table', function ($order) { $d = $order->get_meta('_yk_bd'); if (is_array($d) && !empty($d['head'])) echo yk_bd_preview_html($d, 480); }, 14);
