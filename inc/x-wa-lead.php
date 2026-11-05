<?php
// Kontak WA wajib Nama depan + No. WhatsApp (5 Nov 2026, permintaan Jess).
//
// Alur: pembeli klik link WhatsApp mana pun di web (pilihan JJ/CS di tombol melayang, tombol WA produk,
// kontak footer, link di konten) → popup isi Nama depan + No. WhatsApp (wajib, centang promo opsional)
// → data dicatat ke tabel {prefix}yk_wa_lead → WhatsApp terbuka seperti biasa. Pesan otomatis x-wa-pesan.php
// tetap dipakai, ditambah nama pembeli ("Saya Andi, baru saja cek web …"); konversi Google Ads (#45) terhitung
// saat WhatsApp benar-benar dibuka.
// Data tersimpan di browser pembeli (localStorage yk_wl, 180 hari) → klik WA berikutnya tidak ditanya lagi,
// tetap tercatat (tipe "klik").
// Laporan & export CSV: wp-admin → Kontak WA.
// Tidak ditanya: staff yang login (admin bar tampil), halaman terima kasih order, tombol konfirmasi order,
// tombol WA melayang (membuka pilihan JJ/CS dulu), form custom order (sudah minta nama + WA, dicatat dari s38).
// Tes sebagai admin: tambahkan ?ykwa=1 di URL (popup tetap muncul; data ditandai staff, tidak ikut laporan).
// ?ykwa=reset menghapus data tersimpan di browser.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'YK_WL_DBV', '1' );

function yk_wl_table() {
	global $wpdb;
	return $wpdb->prefix . 'yk_wa_lead';
}

function yk_wl_install() {
	if ( YK_WL_DBV === get_option( 'yk_wl_dbv' ) ) {
		return;
	}
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( 'CREATE TABLE ' . yk_wl_table() . " (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		t datetime NOT NULL,
		hp varchar(20) NOT NULL DEFAULT '',
		nama varchar(60) NOT NULL DEFAULT '',
		promo tinyint(1) NOT NULL DEFAULT 0,
		tipe varchar(10) NOT NULL DEFAULT '',
		ke varchar(20) NOT NULL DEFAULT '',
		btn varchar(40) NOT NULL DEFAULT '',
		item varchar(150) NOT NULL DEFAULT '',
		page varchar(190) NOT NULL DEFAULT '',
		src varchar(60) NOT NULL DEFAULT '',
		lp varchar(190) NOT NULL DEFAULT '',
		dev varchar(10) NOT NULL DEFAULT '',
		staff tinyint(1) NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		KEY hp (hp),
		KEY t (t)
	) " . $wpdb->get_charset_collate() . ';' );
	update_option( 'yk_wl_dbv', YK_WL_DBV, false );
}
add_action( 'admin_init', 'yk_wl_install' );

/* Nomor → format internasional tanpa +, mis. 0812-3456-7890 → 6281234567890. Kosong kalau tidak valid. */
function yk_wl_hp( $v ) {
	$v    = trim( (string) $v );
	$plus = 0 === strpos( $v, '+' );
	$d    = preg_replace( '/\D/', '', $v );
	if ( 0 === strpos( $d, '00' ) ) {
		$d = substr( $d, 2 );
	} elseif ( ! $plus && 0 === strpos( $d, '0' ) ) {
		$d = '62' . substr( $d, 1 );
	} elseif ( ! $plus && 0 === strpos( $d, '8' ) ) {
		$d = '62' . $d;
	}
	if ( 0 === strpos( $d, '620' ) ) {
		$d = '62' . substr( $d, 3 );
	}
	if ( strlen( $d ) < 9 || strlen( $d ) > 15 ) {
		return '';
	}
	if ( 0 === strpos( $d, '62' ) && ! preg_match( '/^628\d{7,11}$/', $d ) ) {
		return '';
	}
	return $d;
}

function yk_wl_nama( $v ) {
	$v = sanitize_text_field( (string) $v );
	$v = preg_replace( '/[\d<>"@#$%^*_=+{}\[\]|\\\\\/;:~`]+/u', '', $v );
	$v = trim( preg_replace( '/\s+/u', ' ', $v ) );
	$v = mb_substr( $v, 0, 40 );
	if ( mb_strlen( $v ) < 2 ) {
		return '';
	}
	return mb_strtoupper( mb_substr( $v, 0, 1 ) ) . mb_substr( $v, 1 );
}

/* Simpan satu kontak. Dipakai popup (AJAX) dan form custom order (s38). */
function yk_wl_add( array $d ) {
	$hp   = yk_wl_hp( isset( $d['hp'] ) ? $d['hp'] : '' );
	$nama = yk_wl_nama( isset( $d['nama'] ) ? $d['nama'] : '' );
	if ( '' === $hp || '' === $nama ) {
		return false;
	}
	yk_wl_install();
	$s = function ( $k, $len ) use ( $d ) {
		return isset( $d[ $k ] ) ? mb_substr( sanitize_text_field( (string) $d[ $k ] ), 0, $len ) : '';
	};
	global $wpdb;
	return (bool) $wpdb->insert(
		yk_wl_table(),
		array(
			't'     => current_time( 'mysql' ),
			'hp'    => $hp,
			'nama'  => $nama,
			'promo' => empty( $d['promo'] ) ? 0 : 1,
			'tipe'  => $s( 'tipe', 10 ),
			'ke'    => preg_replace( '/\D/', '', $s( 'ke', 20 ) ),
			'btn'   => $s( 'btn', 40 ),
			'item'  => $s( 'item', 150 ),
			'page'  => $s( 'page', 190 ),
			'src'   => $s( 'src', 60 ),
			'lp'    => $s( 'lp', 190 ),
			'dev'   => $s( 'dev', 10 ),
			'staff' => ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) ? 1 : 0,
		)
	);
}

function yk_wl_ajax() {
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : ''; // phpcs:ignore
	$p  = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification -- endpoint publik (halaman di-cache), dibatasi rate limit.
	$tp = isset( $p['tipe'] ) ? (string) $p['tipe'] : '';
	if ( '' === $ua || preg_match( '/bot|crawl|spider|slurp|headless|lighthouse|facebookexternalhit/i', $ua )
		|| ! empty( $p['web'] ) || ! in_array( $tp, array( 'form', 'klik' ), true )
		|| ( 'form' === $tp && isset( $p['ms'] ) && (int) $p['ms'] < 400 ) ) {
		status_header( 204 );
		exit;
	}
	$ip = isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ? (string) $_SERVER['HTTP_CF_CONNECTING_IP'] : ( isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '' ); // phpcs:ignore
	$rk = 'yk_wl_rl_' . md5( $ip );
	$n  = (int) get_transient( $rk );
	if ( $n >= 20 ) {
		status_header( 204 );
		exit;
	}
	set_transient( $rk, $n + 1, 10 * MINUTE_IN_SECONDS );
	yk_wl_add( array_intersect_key( (array) $p, array_flip( array( 'hp', 'nama', 'promo', 'tipe', 'ke', 'btn', 'item', 'page', 'src', 'lp', 'dev' ) ) ) );
	status_header( 204 );
	exit;
}
add_action( 'wp_ajax_yk_wl', 'yk_wl_ajax' );
add_action( 'wp_ajax_nopriv_yk_wl', 'yk_wl_ajax' );

/* ---------- Popup (semua halaman depan) ---------- */
add_action( 'wp_head', function () {
	if ( is_admin() || ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) ) {
		return;
	}
	$cfg = array(
		'aj' => admin_url( 'admin-ajax.php' ),
	);
	echo '<script id="yk-wl" data-no-optimize="1" data-no-defer="1" data-cfasync="false">(function(){var C=' . wp_json_encode( $cfg ) . ';' . <<<'JS'
var K='yk_wl',SEL='a[href*="wa.me/"],a[href*="api.whatsapp.com"],a[href*="whatsapp.com/send"]',
SKIP='.ykco-done,.yk-wa-confirm,[data-wa-keep],.yk-wa,#wpadminbar',q=location.search,pass=0,pend=null,M=null,cb=null,t0=0,back=null;
if(/[?&]ykwa=reset/.test(q)){try{localStorage.removeItem(K)}catch(e){}}
var force=/[?&]ykwa=/.test(q);
var S=null;try{S=JSON.parse(sessionStorage.getItem('yk_src')||'null')}catch(e){}
if(!S){var p=new URLSearchParams(q),r='';try{r=document.referrer?new URL(document.referrer).hostname.replace(/^www\./,''):''}catch(e){}
var own=r===location.hostname.replace(/^www\./,''),s='Direct';
if(p.get('gclid')||p.get('gbraid')||p.get('wbraid'))s=/google\./.test(r)?'Google Ads – Search':'Google Ads';
else if(p.get('utm_source'))s=(/cpc|ppc|paid/i.test(p.get('utm_medium')||'')?'Iklan: ':'UTM: ')+p.get('utm_source');
else if(own||!r)s='Direct';
else if(/(^|\.)google\./.test(r))s='Google organik';
else if(/bing\.|yahoo\.|duckduckgo|ecosia/.test(r))s='Search lain';
else if(/instagram/.test(r))s='Instagram';
else if(/facebook|fb\.com|fb\.me/.test(r))s='Facebook';
else if(/tiktok/.test(r))s='TikTok';
else if(/whatsapp|wa\.me/.test(r))s='WhatsApp';
else s='Referral: '+r;
S={s:s,lp:location.pathname};try{sessionStorage.setItem('yk_src',JSON.stringify(S))}catch(e){}}
function staff(){return !force&&!!document.getElementById('wpadminbar')}
function get(){try{var o=JSON.parse(localStorage.getItem(K)||'null');if(o&&o.hp&&o.n&&Date.now()-(o.t||0)<15552e6)return o}catch(e){}return null}
function put(o){o.t=Date.now();try{localStorage.setItem(K,JSON.stringify(o))}catch(e){}}
function norm(v){v=String(v||'').trim();var plus=v.charAt(0)==='+',d=v.replace(/\D/g,'');
if(d.indexOf('00')===0)d=d.slice(2);else if(!plus&&d.charAt(0)==='0')d='62'+d.slice(1);else if(!plus&&d.charAt(0)==='8')d='62'+d;
if(d.indexOf('620')===0)d='62'+d.slice(3);
if(d.length<9||d.length>15)return '';if(d.indexOf('62')===0&&!/^628\d{7,11}$/.test(d))return '';return d}
function nm(v){v=String(v||'').replace(/[\d<>"@#$%^*_=+{}\[\]|\\\/;:~`]+/g,'').replace(/\s+/g,' ').trim().slice(0,40);return v.length>=2?v.charAt(0).toUpperCase()+v.slice(1):''}
function first(v){return String(v||'').trim().split(/\s+/)[0]||''}
function pers(a,n){try{var u=new URL(a.href),t=u.searchParams.get('text')||'';
if(t.indexOf('cek web yulikaflorist.com')<0&&window.ykWaMsg)t=window.ykWaMsg(t);
if(t.indexOf('Saya '+n+',')<0)t=t.replace('Saya baru saja','Saya '+n+', baru saja');
var x=[];u.searchParams.forEach(function(v,k){if(k!=='text')x.push(encodeURIComponent(k)+'='+encodeURIComponent(v))});x.push('text='+encodeURIComponent(t));
a.href=u.origin+u.pathname+'?'+x.join('&')+u.hash}catch(e){}}
function btn(a){if(a.closest('.yk-wa-pick'))return 'Tombol melayang';if(a.classList.contains('ykp-wab'))return 'Halaman produk';if(a.classList.contains('ykp-next'))return 'Produk coming soon';
if(a.closest('.yk-fc'))return 'Kontak footer';if(a.closest('[class*="ykw-"]'))return 'Popup Why Yulika';if(a.closest('.elementor-location-footer,footer'))return 'Footer';
if(a.closest('.elementor-location-header,header'))return 'Header';if(document.body&&document.body.classList.contains('single-product'))return 'Halaman produk';return 'Lainnya'}
function item(a){if(document.body&&document.body.classList.contains('single-product')){var h=document.querySelector('h1');return h?h.textContent.trim():''}
var c=a.closest('li.product,.product');if(c){var t=c.querySelector('h2,h3');return t?t.textContent.trim():''}return ''}
function send(o){o.action='yk_wl';o.src=S.s;o.lp=S.lp;o.page=location.pathname;o.dev=/Mobi|Android|iPhone|iPad/i.test(navigator.userAgent)?'HP':'Desktop';
var f=new FormData(),k;for(k in o)f.append(k,o[k]==null?'':String(o[k]).slice(0,190));
try{if(navigator.sendBeacon&&navigator.sendBeacon(C.aj,f))return}catch(e){}
try{fetch(C.aj,{method:'POST',body:f,keepalive:true,credentials:'same-origin'})}catch(e){}}
function lead(a,me,tipe,x){var u=null;try{u=new URL(a.href)}catch(e){}
var o={hp:me.hp,nama:me.n,promo:me.p?1:0,tipe:tipe,ke:u?((u.pathname.match(/\d{8,}/)||[''])[0]||u.searchParams.get('phone')||''):'',btn:btn(a),item:item(a)},k;
for(k in x||{})o[k]=x[k];send(o)}
function flush(){if(!pend)return;var p=pend;pend=null;lead(p.a,p.me,'klik')}
var IC='<svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 3C8.8 3 3 8.7 3 15.8c0 2.5.7 4.9 2 6.9L3 29l6.5-2c1.9 1.1 4.2 1.7 6.5 1.7 7.2 0 13-5.7 13-12.8S23.2 3 16 3zm0 23.4c-2.1 0-4.1-.6-5.8-1.7l-.4-.3-3.9 1.2 1.3-3.7-.3-.4c-1.2-1.8-1.8-3.8-1.8-5.9C5.1 9.9 10 5.2 16 5.2s10.9 4.7 10.9 10.6S22 26.4 16 26.4zm6-7.9c-.3-.2-1.9-.9-2.2-1s-.5-.2-.7.2-.8 1-1 1.2-.4.2-.7.1a8.9 8.9 0 0 1-4.4-3.8c-.3-.6.3-.5.9-1.7.1-.2 0-.4 0-.5l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4s-1.1 1.1-1.1 2.7 1.2 3.1 1.3 3.3 2.3 3.5 5.6 4.9c2.1.9 2.9.9 3.9.8.6-.1 1.9-.8 2.2-1.5s.3-1.4.2-1.5-.3-.2-.6-.3z"/></svg>';
var CSS='.ykwl{position:fixed;inset:0;z-index:2147483000;display:flex;align-items:flex-end;justify-content:center;font-family:"Plus Jakarta Sans",Montserrat,system-ui,sans-serif;-webkit-font-smoothing:antialiased}'
+'.ykwl[hidden]{display:none!important}'
+'.ykwl-bg{position:absolute;inset:0;background:rgba(43,37,34,.55);opacity:0;transition:opacity .2s}.ykwl.on .ykwl-bg{opacity:1}'
+'.ykwl-box{position:relative;box-sizing:border-box;width:100%;max-width:420px;margin:0!important;background:#fff;border-radius:22px 22px 0 0;padding:22px 20px calc(18px + env(safe-area-inset-bottom));box-shadow:0 -8px 40px rgba(60,40,20,.25);transform:translateY(100%);transition:transform .26s cubic-bezier(.2,.8,.2,1),opacity .2s;max-height:94vh;overflow:auto;color:#2b2522;text-align:left}'
+'.ykwl.on .ykwl-box{transform:none}'
+'@media(min-width:600px){.ykwl{align-items:center;padding:16px}.ykwl-box{border-radius:22px;padding:26px 26px 20px;opacity:0;transform:translateY(14px) scale(.98)}.ykwl.on .ykwl-box{opacity:1}}'
+'.ykwl-x{position:absolute!important;top:12px;right:12px;width:36px!important;height:36px!important;min-height:0!important;border:0!important;background:#f5ece6!important;border-radius:50%!important;color:#5b4a3f!important;font:400 22px/36px Arial,sans-serif!important;padding:0!important;margin:0!important;cursor:pointer;box-shadow:none!important;text-align:center}'
+'.ykwl-ic{width:46px;height:46px;border-radius:50%;background:#25d366;display:flex;align-items:center;justify-content:center;margin:0 0 12px}.ykwl-ic svg{width:26px;height:26px;fill:#fff}'
+'.ykwl-h{margin:0 0 6px!important;padding:0 44px 0 0!important;font:400 25px/1.2 "Sorts Mill Goudy",Georgia,serif!important;color:#2b2522!important;letter-spacing:0!important;text-transform:none!important}'
+'.ykwl-sub{margin:0 0 16px!important;font-size:14px!important;line-height:1.5!important;color:#6b5a4e!important}'
+'.ykwl-l{display:block!important;margin:0 0 12px!important;font-size:13px!important;font-weight:600!important;line-height:1.3!important;color:#3a3330!important;letter-spacing:0!important;text-transform:none!important}'
+'.ykwl .ykwl-in{display:block!important;box-sizing:border-box!important;width:100%!important;height:48px!important;margin:6px 0 0!important;padding:0 14px!important;border:1.5px solid #e2cfc2!important;border-radius:12px!important;background:#fff!important;font:500 16px/1.2 "Plus Jakarta Sans",Montserrat,system-ui,sans-serif!important;color:#2b2522!important;box-shadow:none!important;outline:0!important;-webkit-appearance:none;appearance:none}'
+'.ykwl .ykwl-in:focus{border-color:#8a5a2b!important;box-shadow:0 0 0 3px rgba(138,90,43,.16)!important}.ykwl .ykwl-in.bad{border-color:#b3261e!important}'
+'.ykwl-ck{display:flex!important;gap:10px;align-items:flex-start;margin:2px 0 14px!important;font-size:13.5px!important;line-height:1.45!important;color:#3a3330!important;cursor:pointer;font-weight:500!important}'
+'.ykwl .ykwl-ck input{-webkit-appearance:checkbox!important;appearance:auto!important;display:block!important;width:18px!important;height:18px!important;min-height:0!important;margin:1px 0 0!important;padding:0!important;flex:0 0 18px;accent-color:#138a47;position:static!important;opacity:1!important}'
+'.ykwl .ykwl-hp{position:absolute!important;left:-9999px!important;width:1px!important;height:1px!important;opacity:0!important}'
+'.ykwl-err{margin:-4px 0 12px!important;font-size:13px!important;line-height:1.4!important;color:#b3261e!important}'
+'.ykwl .ykwl-go{display:flex!important;align-items:center;justify-content:center;gap:8px;box-sizing:border-box!important;width:100%!important;height:52px!important;min-height:0!important;margin:0!important;border:0!important;border-radius:999px!important;background:#138a47!important;color:#fff!important;font:700 16px/1 "Plus Jakarta Sans",Montserrat,system-ui,sans-serif!important;cursor:pointer;padding:0 20px!important;text-transform:none!important;letter-spacing:0!important;box-shadow:0 4px 14px rgba(19,138,71,.3)!important}'
+'.ykwl .ykwl-go:hover{background:#0f7a3e!important}.ykwl .ykwl-go:active{transform:scale(.985)}.ykwl-go svg{width:20px;height:20px;fill:#fff;flex:0 0 20px}'
+'.ykwl-note{margin:10px 0 0!important;font-size:12px!important;line-height:1.45!important;color:#8a7a6e!important;text-align:center}'
+'html.ykwl-lock,html.ykwl-lock body{overflow:hidden!important}';
function build(){if(M)return M;var st=document.createElement('style');st.id='ykwl-css';st.textContent=CSS;document.head.appendChild(st);
M=document.createElement('div');M.className='ykwl';M.hidden=true;
M.innerHTML='<div class="ykwl-bg" data-x></div><form class="ykwl-box" role="dialog" aria-modal="true" aria-labelledby="ykwl-h" novalidate>'
+'<button type="button" class="ykwl-x" aria-label="Close" data-x>&times;</button><div class="ykwl-ic">'+IC+'</div>'
+'<p class="ykwl-h" id="ykwl-h">Chat with Yulika Florist</p><p class="ykwl-sub">Enter your first name &amp; WhatsApp number so our team can reply faster.</p>'
+'<label class="ykwl-l">First name<input class="ykwl-in" name="n" autocomplete="given-name" autocapitalize="words" maxlength="40" placeholder="e.g. Andi"></label>'
+'<label class="ykwl-l">WhatsApp number<input class="ykwl-in" name="p" type="tel" inputmode="tel" autocomplete="tel" maxlength="22" placeholder="0812 3456 7890"></label>'
+'<label class="ykwl-ck"><input type="checkbox" name="o"><span>Send me exclusive promos &amp; vouchers on WhatsApp</span></label>'
+'<input class="ykwl-hp" name="web" tabindex="-1" autocomplete="off" aria-hidden="true">'
+'<p class="ykwl-err" role="alert" hidden></p><button type="submit" class="ykwl-go">'+IC+'<span>Continue to WhatsApp</span></button>'
+'<p class="ykwl-note">Your number is only used to reply to your chat, and for promos if you tick the box.</p></form>';
document.body.appendChild(M);var f=M.querySelector('form'),err=M.querySelector('.ykwl-err');
M.addEventListener('click',function(e){if(e.target.closest('[data-x]'))close()});
document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!M.hidden)close()});
[f.n,f.p].forEach(function(i){i.addEventListener('input',function(){i.classList.remove('bad');err.hidden=true})});
f.addEventListener('submit',function(e){e.preventDefault();var n=nm(f.n.value),p=norm(f.p.value);
f.n.classList.toggle('bad',!n);f.p.classList.toggle('bad',!p);
if(!n||!p){err.textContent=!n?'Please enter your first name.':'Please enter a valid WhatsApp number, e.g. 0812 3456 7890.';err.hidden=false;(!n?f.n:f.p).focus();return}
var me={n:n,hp:p,p:f.o.checked?1:0},x={ms:Date.now()-t0,web:f.web.value};put(me);var c=cb;cb=null;close(1);if(c)c(me,x)});
return M}
function open(fn,pre){build();var f=M.querySelector('form'),ck={};try{ck=(JSON.parse(localStorage.getItem('yk_ck_v1')||'{}')||{}).s||{}}catch(e){}
f.n.value=first(pre&&pre.n||ck.n||'');f.p.value=pre&&pre.p||ck.p||'';f.o.checked=false;f.web.value='';
f.n.classList.remove('bad');f.p.classList.remove('bad');M.querySelector('.ykwl-err').hidden=true;
cb=fn;back=document.activeElement;t0=Date.now();M.hidden=false;document.documentElement.classList.add('ykwl-lock');
requestAnimationFrame(function(){requestAnimationFrame(function(){M.classList.add('on')})});
if(window.matchMedia&&matchMedia('(pointer:fine)').matches)setTimeout(function(){(f.n.value?(f.p.value?f.querySelector('.ykwl-go'):f.p):f.n).focus()},60)}
function close(ok){if(!M||M.hidden)return;if(!ok)cb=null;M.classList.remove('on');document.documentElement.classList.remove('ykwl-lock');
setTimeout(function(){M.hidden=true},220);if(!ok&&back&&back.focus)try{back.focus()}catch(e){}}
function go(a,me,x){pers(a,me.n);pass=1;try{a.click()}finally{pass=0}
var pk=document.querySelector('.yk-wa-pick.on');if(pk)pk.classList.remove('on');lead(a,me,'form',x)}
window.addEventListener('click',function(e){if(pass)return;var a=e.target&&e.target.closest&&e.target.closest(SEL);
if(!a||a.closest(SKIP)||staff())return;var me=get();
if(me){pers(a,me.n);pend={a:a,me:me};setTimeout(flush,0);return}
e.preventDefault();e.stopImmediatePropagation();e.stopPropagation();open(function(me,x){go(a,me,x)})},true);
window.addEventListener('click',flush,false);
window.ykWl={remember:function(n,p,promo){var me={n:nm(first(n)),hp:norm(p),p:promo?1:0};if(me.n&&me.hp)put(me)},get:get,open:open};
JS
	. '})();</script>' . "\n";
}, 2 );

/* ---------- Laporan wp-admin: Kontak WA ---------- */
function yk_wl_names() {
	$m = array();
	if ( function_exists( 'yk_contacts' ) ) {
		$c                    = yk_contacts();
		$m[ $c['jj']['wa'] ] = 'JJ';
		foreach ( $c['cs'] as $p ) {
			$m[ $p['wa'] ] = 'CS ' . $p['name'];
		}
	}
	return $m;
}

/* 6281234567890 → 0812-3456-7890 (nomor luar negeri: +kode…) */
function yk_wl_fmt( $hp ) {
	if ( 0 !== strpos( $hp, '62' ) ) {
		return '+' . $hp;
	}
	$l = '0' . substr( $hp, 2 );
	return substr( $l, 0, 4 ) . '-' . substr( $l, 4, 4 ) . '-' . substr( $l, 8 );
}

/* Filter dari URL: d = hari (0 = semua), q = cari nama/nomor, promo = 1, staff = 1 (ikutkan tes staff) */
function yk_wl_filters() {
	$g = function ( $k ) {
		return isset( $_GET[ $k ] ) ? sanitize_text_field( wp_unslash( $_GET[ $k ] ) ) : ''; // phpcs:ignore
	};
	return array(
		'd'     => '' === $g( 'd' ) ? 0 : max( 0, min( 3650, (int) $g( 'd' ) ) ),
		'q'     => mb_substr( $g( 'q' ), 0, 60 ),
		'promo' => '1' === $g( 'promo' ),
		'staff' => '1' === $g( 'staff' ),
	);
}

/* Query kontak per nomor. Mengembalikan array( rows, total ). */
function yk_wl_rows( $f, $limit = 200, $offset = 0 ) {
	global $wpdb;
	$tb    = yk_wl_table();
	$where = $f['staff'] ? '1=1' : 'staff = 0';
	if ( '' !== $f['q'] ) {
		$like  = '%' . $wpdb->esc_like( $f['q'] ) . '%';
		$dig   = preg_replace( '/\D/', '', $f['q'] );
		$dlike = '' !== $dig ? '%' . $wpdb->esc_like( 0 === strpos( $dig, '0' ) ? substr( $dig, 1 ) : $dig ) . '%' : $like;
		$where .= $wpdb->prepare( " AND hp IN (SELECT hp FROM $tb WHERE nama LIKE %s OR hp LIKE %s)", $like, $dlike ); // phpcs:ignore
	}
	$having = array();
	if ( $f['d'] ) {
		$having[] = $wpdb->prepare( 'MAX(t) >= %s', wp_date( 'Y-m-d H:i:s', time() - $f['d'] * DAY_IN_SECONDS ) );
	}
	if ( $f['promo'] ) {
		$having[] = 'MAX(promo) = 1';
	}
	$hv  = $having ? ' HAVING ' . implode( ' AND ', $having ) : '';
	$sel = "SELECT hp,
		SUBSTRING_INDEX(GROUP_CONCAT(nama ORDER BY id DESC SEPARATOR '\\n'), '\\n', 1) AS nama,
		MIN(t) AS t1, MAX(t) AS t2, COUNT(*) AS n, MAX(promo) AS promo,
		SUBSTRING_INDEX(GROUP_CONCAT(ke ORDER BY id DESC SEPARATOR '\\n'), '\\n', 1) AS ke,
		SUBSTRING_INDEX(GROUP_CONCAT(btn ORDER BY id DESC SEPARATOR '\\n'), '\\n', 1) AS btn,
		SUBSTRING_INDEX(GROUP_CONCAT(IF(item <> '', item, page) ORDER BY id DESC SEPARATOR '\\n'), '\\n', 1) AS lastp,
		SUBSTRING_INDEX(GROUP_CONCAT(src ORDER BY id ASC SEPARATOR '\\n'), '\\n', 1) AS src
		FROM $tb WHERE $where GROUP BY hp $hv";
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM ($sel) x" ); // phpcs:ignore
	$rows  = $wpdb->get_results( $sel . ' ORDER BY t2 DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset ); // phpcs:ignore
	return array( $rows, $total );
}

add_action( 'admin_menu', function () {
	add_menu_page( 'Kontak WA', 'Kontak WA', 'manage_woocommerce', 'yk-kontak-wa', 'yk_wl_page', 'dashicons-whatsapp', 56 );
} );

/* Export CSV (sebelum header admin dikirim) */
add_action( 'admin_init', function () {
	if ( empty( $_GET['page'] ) || 'yk-kontak-wa' !== $_GET['page'] || empty( $_GET['csv'] ) ) { // phpcs:ignore
		return;
	}
	if ( ! current_user_can( 'manage_woocommerce' ) || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '', 'yk_wl_csv' ) ) {
		wp_die( 'Tidak diizinkan.' );
	}
	list( $rows ) = yk_wl_rows( yk_wl_filters(), 100000, 0 );
	$names        = yk_wl_names();
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=kontak-wa-yulika-' . wp_date( 'Ymd-Hi' ) . '.csv' );
	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore -- BOM agar Excel membaca UTF-8
	fputcsv( $out, array( 'Nama', 'No WhatsApp', 'No HP', 'Mau promo', 'Pertama kontak', 'Terakhir kontak', 'Jumlah chat', 'Terakhir ke', 'Tombol terakhir', 'Produk / halaman terakhir', 'Sumber awal' ) );
	foreach ( $rows as $r ) {
		fputcsv(
			$out,
			array(
				$r->nama,
				$r->hp,
				yk_wl_fmt( $r->hp ),
				$r->promo ? 'Ya' : 'Tidak',
				mysql2date( 'd/m/Y H:i', $r->t1 ),
				mysql2date( 'd/m/Y H:i', $r->t2 ),
				(int) $r->n,
				isset( $names[ $r->ke ] ) ? $names[ $r->ke ] : $r->ke,
				$r->btn,
				$r->lastp,
				$r->src,
			)
		);
	}
	fclose( $out ); // phpcs:ignore
	exit;
} );

function yk_wl_page() {
	global $wpdb;
	yk_wl_install();
	$tb    = yk_wl_table();
	$f     = yk_wl_filters();
	$names = yk_wl_names();
	$base  = admin_url( 'admin.php?page=yk-kontak-wa' );
	$keep  = function ( $over = array() ) use ( $f, $base ) {
		$a = array_merge(
			array(
				'd'     => $f['d'],
				'q'     => $f['q'],
				'promo' => $f['promo'] ? 1 : '',
				'staff' => $f['staff'] ? 1 : '',
			),
			$over
		);
		return add_query_arg( array_filter( $a, function ( $v ) { return '' !== $v && null !== $v; } ), $base );
	};
	$from  = $f['d'] ? wp_date( 'Y-m-d H:i:s', time() - $f['d'] * DAY_IN_SECONDS ) : '2000-01-01 00:00:00';
	$sw    = $f['staff'] ? '1=1' : 'staff = 0';

	echo '<div class="wrap"><h1>Kontak WA</h1>';
	echo '<style>.ykwa-kpi{display:flex;flex-wrap:wrap;gap:12px;margin:14px 0}.ykwa-kpi div{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:10px 16px;min-width:150px}.ykwa-kpi b{display:block;font-size:24px;margin-top:4px}.ykwa-f a{margin-right:4px}.ykwa-f a.cur{font-weight:700;text-decoration:none;color:#1d2327}.ykwa-t td,.ykwa-t th{vertical-align:middle}.ykwa-t .wa{display:inline-block;background:#25d366;color:#fff;border-radius:4px;padding:1px 7px;font-size:11px;text-decoration:none;margin-left:6px}.ykwa-y{color:#138a47;font-weight:600}</style>';
	echo '<p style="max-width:860px">Pembeli yang klik WhatsApp di website wajib mengisi nama depan &amp; nomor WhatsApp (mulai 5 Nov 2026). Satu baris = satu nomor. Kontak WA sebelum itu ada di Google Sheet &ldquo;Rekap Kontak WA – Yulika Florist&rdquo;.</p>';

	// Riwayat satu nomor
	if ( ! empty( $_GET['hp'] ) ) { // phpcs:ignore
		$hp   = preg_replace( '/\D/', '', wp_unslash( $_GET['hp'] ) ); // phpcs:ignore
		$hist = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $tb WHERE hp = %s ORDER BY id DESC LIMIT 200", $hp ) ); // phpcs:ignore
		echo '<p><a href="' . esc_url( $keep() ) . '">&larr; Semua kontak</a></p><h2>' . esc_html( yk_wl_fmt( $hp ) ) . ' <a class="button" target="_blank" rel="noopener" href="https://wa.me/' . esc_attr( $hp ) . '">Chat WhatsApp</a></h2>';
		echo '<table class="widefat striped ykwa-t"><thead><tr><th>Waktu</th><th>Nama</th><th>Cara</th><th>Ke</th><th>Tombol</th><th>Produk / item</th><th>Halaman</th><th>Sumber</th><th>Perangkat</th><th>Promo</th></tr></thead><tbody>';
		foreach ( $hist as $r ) {
			echo '<tr><td>' . esc_html( mysql2date( 'd M Y H:i', $r->t ) ) . '</td><td>' . esc_html( $r->nama ) . ( $r->staff ? ' <em>(tes staff)</em>' : '' ) . '</td><td>' . esc_html( 'form' === $r->tipe ? 'Isi form' : ( 'co' === $r->tipe ? 'Custom order' : 'Otomatis (sudah pernah isi)' ) ) . '</td><td>' . esc_html( isset( $names[ $r->ke ] ) ? $names[ $r->ke ] : $r->ke ) . '</td><td>' . esc_html( $r->btn ) . '</td><td>' . esc_html( $r->item ) . '</td><td>' . esc_html( $r->page ) . '</td><td>' . esc_html( $r->src ) . '</td><td>' . esc_html( $r->dev ) . '</td><td>' . ( $r->promo ? '<span class="ykwa-y">Ya</span>' : '–' ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
		return;
	}

	$uniq  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT hp) FROM $tb WHERE $sw AND t >= %s", $from ) ); // phpcs:ignore
	$baru  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM (SELECT hp FROM $tb WHERE $sw GROUP BY hp HAVING MIN(t) >= %s) x", $from ) ); // phpcs:ignore
	$promo = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM (SELECT hp FROM $tb WHERE $sw GROUP BY hp HAVING MAX(promo) = 1 AND MAX(t) >= %s) x", $from ) ); // phpcs:ignore
	$chat  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tb WHERE $sw AND t >= %s", $from ) ); // phpcs:ignore
	echo '<div class="ykwa-kpi"><div>Kontak (nomor unik)<b>' . (int) $uniq . '</b></div><div>Kontak baru<b>' . (int) $baru . '</b></div><div>Mau promo WA<b>' . (int) $promo . '</b></div><div>Total klik chat WA<b>' . (int) $chat . '</b></div></div>';

	echo '<p class="ykwa-f">Periode: ';
	foreach ( array( 1 => 'Hari ini (24 jam)', 7 => '7 hari', 30 => '30 hari', 90 => '90 hari', 0 => 'Semua' ) as $d => $lbl ) {
		echo '<a class="' . ( $f['d'] === $d ? 'cur' : '' ) . '" href="' . esc_url( $keep( array( 'd' => $d ) ) ) . '">' . esc_html( $lbl ) . '</a> | ';
	}
	echo '<a class="' . ( $f['promo'] ? 'cur' : '' ) . '" href="' . esc_url( $keep( array( 'promo' => $f['promo'] ? '' : 1 ) ) ) . '">' . ( $f['promo'] ? '&#10003; Hanya yang mau promo' : 'Hanya yang mau promo' ) . '</a>';
	echo '</p><form method="get" style="margin:8px 0 12px"><input type="hidden" name="page" value="yk-kontak-wa"><input type="hidden" name="d" value="' . (int) $f['d'] . '">' . ( $f['promo'] ? '<input type="hidden" name="promo" value="1">' : '' ) . ( $f['staff'] ? '<input type="hidden" name="staff" value="1">' : '' );
	echo '<input type="search" name="q" value="' . esc_attr( $f['q'] ) . '" placeholder="Cari nama / nomor"> <button class="button">Cari</button> ';
	echo '<a class="button button-primary" href="' . esc_url( wp_nonce_url( $keep( array( 'csv' => 1 ) ), 'yk_wl_csv' ) ) . '">Export CSV (sesuai filter)</a> ';
	echo '<a class="button" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'csv' => 1, 'promo' => 1, 'd' => 0 ), $base ), 'yk_wl_csv' ) ) . '">Export untuk WA blast (semua yang mau promo)</a>';
	echo '</form>';

	$pg            = isset( $_GET['pg'] ) ? max( 1, (int) $_GET['pg'] ) : 1; // phpcs:ignore
	list( $rows, $total ) = yk_wl_rows( $f, 100, ( $pg - 1 ) * 100 );
	echo '<table class="widefat striped ykwa-t"><thead><tr><th>Nama</th><th>No. WhatsApp</th><th>Pertama kontak</th><th>Terakhir</th><th>Chat</th><th>Terakhir ke</th><th>Tombol / produk terakhir</th><th>Sumber awal</th><th>Promo</th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="9">Belum ada data untuk filter ini.</td></tr>';
	}
	foreach ( $rows as $r ) {
		$hl = $keep( array( 'hp' => $r->hp ) );
		echo '<tr><td><a href="' . esc_url( $hl ) . '"><b>' . esc_html( $r->nama ) . '</b></a></td>'
			. '<td>' . esc_html( yk_wl_fmt( $r->hp ) ) . '<a class="wa" target="_blank" rel="noopener" href="https://wa.me/' . esc_attr( $r->hp ) . '">Chat</a></td>'
			. '<td>' . esc_html( mysql2date( 'd M Y H:i', $r->t1 ) ) . '</td><td>' . esc_html( mysql2date( 'd M Y H:i', $r->t2 ) ) . '</td>'
			. '<td><a href="' . esc_url( $hl ) . '">' . (int) $r->n . 'x</a></td>'
			. '<td>' . esc_html( isset( $names[ $r->ke ] ) ? $names[ $r->ke ] : $r->ke ) . '</td>'
			. '<td>' . esc_html( $r->btn ) . ( $r->lastp ? '<br><small>' . esc_html( $r->lastp ) . '</small>' : '' ) . '</td>'
			. '<td>' . esc_html( $r->src ) . '</td><td>' . ( $r->promo ? '<span class="ykwa-y">Ya</span>' : '–' ) . '</td></tr>';
	}
	echo '</tbody></table>';
	$pages = (int) ceil( $total / 100 );
	if ( $pages > 1 ) {
		echo '<p>Halaman: ';
		for ( $i = 1; $i <= $pages; $i++ ) {
			echo $i === $pg ? '<b>' . (int) $i . '</b> ' : '<a href="' . esc_url( $keep( array( 'pg' => $i ) ) ) . '">' . (int) $i . '</a> '; // phpcs:ignore
		}
		echo '</p>';
	}
	echo '<p style="color:#666;margin-top:14px">' . (int) $total . ' nomor. Klik nama untuk melihat riwayat chat per nomor. Data tes staff disembunyikan (<a href="' . esc_url( $keep( array( 'staff' => $f['staff'] ? '' : 1 ) ) ) . '">' . ( $f['staff'] ? 'sembunyikan' : 'tampilkan' ) . '</a>).</p></div>';
}
