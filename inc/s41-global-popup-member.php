<?php
// Dari Code Snippets #41 "#41 Popup member baru (10% welcome)" — scope: global

// #41 Popup member baru -> /welcome/ (10% off) untuk pengunjung yang belum login (13 Okt 2026)
add_action('wp_footer', function () {
	if (is_admin() || is_user_logged_in()) return;
	if (function_exists('is_checkout') && (is_checkout() || is_cart() || is_account_page())) return;
	if (is_page(array('welcome', 'custom-order'))) return;
	$wel  = home_url('/welcome/');
	$goog = add_query_arg(array('loginSocial' => 'google', 'redirect' => rawurlencode(home_url('/my-account/?welcome=1'))), site_url('wp-login.php'));
	$img  = content_url('/uploads/2026/10/aurora-pink-lily-bouquet-420x630.jpg');
	?>
<div class="ykm" id="ykm" hidden role="dialog" aria-modal="true" aria-labelledby="ykm-t">
 <div class="ykm-bg" data-x></div>
 <div class="ykm-box">
  <button class="ykm-x" type="button" aria-label="Close" data-x>×</button>
  <div class="ykm-img" style="background-image:url('<?php echo esc_url($img); ?>')"></div>
  <div class="ykm-c">
   <p class="ykm-e">Welcome to Yulika Florist</p>
   <h3 id="ykm-t">Get 10% off your first order</h3>
   <p class="ykm-s">Join as a member — your voucher is applied automatically at checkout. Valid 24 hours after sign-up.</p>
   <a class="ykm-b" href="<?php echo esc_url($wel); ?>">Sign up &amp; get 10%</a>
   <a class="ykm-g" href="<?php echo esc_url($goog); ?>"><svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.2-.1-2.3-.4-3.5z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.2-.1-2.3-.4-3.5z"/></svg>Continue with Google</a>
   <button class="ykm-n" type="button" data-x>No thanks</button>
  </div>
 </div>
</div>
<style>
.ykm[hidden]{display:none!important}
.ykm{position:fixed;inset:0;z-index:100000;display:flex;align-items:center;justify-content:center;font-family:'Plus Jakarta Sans',Montserrat,-apple-system,Arial,sans-serif}
.ykm-bg{position:absolute;inset:0;background:rgba(30,20,10,.45);opacity:0;transition:opacity .25s}
.ykm-box{position:relative;display:flex;width:min(720px,calc(100% - 32px));background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.25);transform:translateY(16px);opacity:0;transition:transform .3s,opacity .3s}
.ykm.on .ykm-bg{opacity:1}.ykm.on .ykm-box{transform:none;opacity:1}
.ykm-img{flex:0 0 42%;background:#f4ede4 center/cover no-repeat;min-height:420px}
.ykm-c{flex:1;padding:36px 30px 26px;text-align:center;display:flex;flex-direction:column;justify-content:center}
.ykm-e{font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:#a0784a;margin:0 0 8px}
.ykm-c h3{font-family:'Sorts Mill Goudy',Georgia,serif!important;font-weight:400!important;font-size:30px!important;line-height:1.15!important;color:#1d1d1f!important;margin:0 0 10px!important;text-transform:none!important;letter-spacing:0!important}
.ykm-s{font-size:14px;line-height:1.5;color:#6b625a;margin:0 0 20px}
.ykm-b,.ykm-g{display:flex;align-items:center;justify-content:center;gap:8px;height:50px;border-radius:30px;font-size:15px;font-weight:700;text-decoration:none!important;margin:0 0 10px}
.ykm-b{background:#a0784a;color:#fff!important;box-shadow:0 3px 10px rgba(160,120,74,.35)}
.ykm-b:hover{background:#8a6338}
.ykm-g{background:#fff;color:#1d1d1f!important;border:1px solid #ddd}
.ykm-n{background:none!important;border:0!important;color:#8a8077!important;font-size:13px!important;text-decoration:underline;cursor:pointer;padding:6px!important;margin:0 auto!important;box-shadow:none!important;text-transform:none!important;letter-spacing:0!important}
.ykm-x{position:absolute;top:10px;right:10px;z-index:2;width:34px;height:34px;border-radius:50%!important;border:0!important;background:rgba(255,255,255,.92)!important;color:#333!important;font-size:22px!important;line-height:34px!important;padding:0!important;cursor:pointer;box-shadow:0 1px 4px rgba(0,0,0,.15)}
@media(max-width:640px){
 .ykm{align-items:flex-end}
 .ykm-box{width:100%;border-radius:18px 18px 0 0;transform:translateY(100%)}
 .ykm-img{display:none}
 .ykm-c{padding:26px 20px calc(16px + env(safe-area-inset-bottom))}
 .ykm-c h3{font-size:26px!important}
 .ykm-s{margin-bottom:16px}
}
</style>
<script data-no-optimize="1" data-no-defer="1" data-cfasync="false">
(function(){
 var m=document.getElementById('ykm');if(!m||document.body.classList.contains('logged-in'))return;
 var K='yk_pop_v1',now=Date.now();
 function get(){try{return JSON.parse(localStorage.getItem(K)||'{}')||{};}catch(e){return {};}}
 function set(o){try{localStorage.setItem(K,JSON.stringify(o));}catch(e){}}
 var st=get();if(st.until&&st.until>now)return;
 try{if(sessionStorage.getItem('yk_why_seen'))return;}catch(e){}
 var shown=false;
 function show(){if(shown)return;try{if(sessionStorage.getItem('yk_why_seen'))return;}catch(e){}if(document.querySelector('.ykw.on'))return;shown=true;cleanup();m.hidden=false;requestAnimationFrame(function(){m.classList.add('on');});st.until=now+3*864e5;set(st);if(window.gtag)gtag('event','view_promotion',{promotion_name:'welcome_10'});}
 function hide(days){m.classList.remove('on');setTimeout(function(){m.hidden=true;},300);st.until=Date.now()+days*864e5;set(st);}
 [].forEach.call(m.querySelectorAll('[data-x]'),function(b){b.addEventListener('click',function(){hide(7);});});
 document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!m.hidden)hide(7);});
 [].forEach.call(m.querySelectorAll('.ykm-b,.ykm-g'),function(a){a.addEventListener('click',function(){st.until=Date.now()+30*864e5;set(st);});});
 var t=setTimeout(show,12000);
 function onScroll(){var h=document.documentElement;if((h.scrollTop+innerHeight)/h.scrollHeight>.45)show();}
 function onOut(e){if(!e.relatedTarget&&e.clientY<=0)show();}
 window.addEventListener('scroll',onScroll,{passive:true});document.addEventListener('mouseout',onOut);
 function cleanup(){clearTimeout(t);window.removeEventListener('scroll',onScroll);document.removeEventListener('mouseout',onOut);}
})();
</script>
<?php
}, 30);
