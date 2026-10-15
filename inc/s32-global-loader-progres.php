<?php
// Dari Code Snippets #32 "#32 Loader progres lingkaran saat pindah halaman (v2, head)" — scope: global

// #32 Loader progres lingkaran saat pindah halaman — v2 (12 Okt 2026)
// Dimuat paling awal di <head>, tidak di-optimize/defer LiteSpeed, muncul bila navigasi > 300 ms.
add_action('wp_head', function () {
    if (is_admin()) return; ?>
<script data-no-optimize="1" data-no-defer="1" data-cfasync="false">
(function(){
  if(window.ykLoader)return;
  var T=169.6,v=0,iv=null,dl=null,L,C,P,lastSkip=0;
  function el(){if(L)return L;if(!document.getElementById('yk-ld-css')){var st=document.createElement('style');st.id='yk-ld-css';st.textContent='#yk-ld{position:fixed;inset:0;z-index:2147483000;display:flex;align-items:center;justify-content:center;background:rgba(255,252,249,.6);-webkit-backdrop-filter:blur(2px);backdrop-filter:blur(2px);opacity:0;visibility:hidden;transition:opacity .12s;pointer-events:none}#yk-ld.on{opacity:1;visibility:visible;pointer-events:auto}#yk-ld .b{position:relative;width:88px;height:88px;border-radius:50%;background:#fff;box-shadow:0 8px 28px rgba(80,50,20,.18);display:flex;align-items:center;justify-content:center}#yk-ld svg{position:absolute;inset:12px}#yk-ld-c{transition:stroke-dashoffset .25s linear}#yk-ld-p{font:600 13px/1 \'Plus Jakarta Sans\',Montserrat,Arial,sans-serif;color:#8a5a2b}';(document.head||document.documentElement).appendChild(st);}L=document.createElement('div');L.id='yk-ld';L.setAttribute('aria-hidden','true');
    L.innerHTML='<div class="b"><svg viewBox="0 0 64 64" width="64" height="64"><circle cx="32" cy="32" r="27" fill="none" stroke="#f0e4da" stroke-width="5"/><circle id="yk-ld-c" cx="32" cy="32" r="27" fill="none" stroke="#8a5a2b" stroke-width="5" stroke-linecap="round" stroke-dasharray="169.6" stroke-dashoffset="169.6" transform="rotate(-90 32 32)"/></svg><span id="yk-ld-p">0%</span></div>';
    (document.body||document.documentElement).appendChild(L);C=L.querySelector('#yk-ld-c');P=L.querySelector('#yk-ld-p');return L;}
  function set(x){v=x;C.style.strokeDashoffset=(T*(1-x/100)).toFixed(1);P.textContent=Math.round(x)+'%';}
  function start(){if(iv||dl)return;dl=setTimeout(function(){dl=null;el();set(5);L.classList.add('on');iv=setInterval(function(){set(v+(95-v)*0.06);},90);},300);}
  function done(){clearTimeout(dl);dl=null;if(iv){clearInterval(iv);iv=null;}if(L&&L.classList.contains('on')){set(100);setTimeout(function(){L.classList.remove('on');},180);}}
  window.ykLoader={start:start,done:done};
  function skipLink(a,e){
    if(e&&(e.button!==0||e.metaKey||e.ctrlKey||e.shiftKey||e.altKey))return true;
    if(a.target&&a.target!=='_self')return true; if(a.hasAttribute('download'))return true;
    if(a.closest('.pswp,[data-pswp-width],.ajax_add_to_cart,.yk-noload'))return true;
    var h=a.getAttribute('href')||'';if(!h||h.charAt(0)==='#'||/^(tel|mailto|sms|whatsapp|javascript):/i.test(h))return true;
    var u;try{u=new URL(a.href,location.href);}catch(x){return true;}
    if(!/^https?:$/.test(u.protocol)||u.origin!==location.origin)return true;
    if(/\.(jpe?g|png|webp|gif|pdf|zip)$/i.test(u.pathname))return true;
    if(u.pathname===location.pathname&&u.search===location.search&&u.hash)return true;
    if(/\/wp-admin|add-to-cart=|wc-ajax/.test(u.href))return true;
    return false;}
  // capture: tetap jalan walau script lain stopPropagation
  document.addEventListener('click',function(e){
    var a=e.target&&e.target.closest&&e.target.closest('a[href]');if(!a)return;
    if(skipLink(a,e)){lastSkip=Date.now();return;}
    setTimeout(function(){if(!e.defaultPrevented)start();},0);
  },true);
  document.addEventListener('submit',function(e){var f=e.target;if(f.target==='_blank'||(f.closest&&f.closest('.woocommerce-checkout,.yk-noload')))return;setTimeout(function(){if(!e.defaultPrevented)start();},0);},true);
  // cadangan: navigasi lewat JavaScript (location.href, tombol non-link)
  window.addEventListener('beforeunload',function(){if(Date.now()-lastSkip>1500)start();});
  window.addEventListener('pageshow',done);
  window.addEventListener('pagehide',function(){if(iv)set(100);});
  document.addEventListener('visibilitychange',function(){if(document.visibilityState==='visible'&&(iv||dl))setTimeout(done,6000);});
})();
</script>
<?php }, 1);
