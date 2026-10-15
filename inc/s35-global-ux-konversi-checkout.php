<?php
// Dari Code Snippets #35 "#35 UX konversi: sticky Order Now, checkout ringkas, chip ucapan, simpan data" — scope: global

// #35 UX konversi: sticky Order Now, checkout ringkas, chip ucapan, simpan data & kirim lagi (12 Okt 2026)

// ---------- Halaman produk: bar Order Now melekat di bawah (HP) ----------
add_action('wp_footer', function () {
    if (!is_product()) return; ?>
<style>@media(max-width:767px){
.single-product .ykp-act{position:fixed!important;left:0;right:0;bottom:0;z-index:9990;display:flex!important;gap:10px;align-items:center;margin:0!important;padding:10px 14px calc(10px + env(safe-area-inset-bottom));background:#fff;box-shadow:0 -6px 22px rgba(60,40,20,.13);border-radius:16px 16px 0 0}
.single-product .ykp-act .ykp-next{flex:1;height:52px;min-height:0!important;padding:0 14px!important;line-height:1!important;margin:0!important;display:flex!important;align-items:center;justify-content:center;gap:8px;border-radius:14px!important;font-size:15.5px!important;white-space:nowrap}
.single-product .ykp-act .ykp-next small{font-weight:500;opacity:.9;font-size:13.5px}
.single-product .ykp-act .ykp-wab{flex:0 0 52px;width:52px;height:52px;margin:0!important;border-radius:14px!important;display:flex!important;align-items:center;justify-content:center;padding:0!important}
.single-product .ykp-act .ykp-wab svg{width:24px;height:24px}
.single-product .ykp-act .ykp-wab span,.single-product .ykp-act .ykp-wab:not(:has(svg)){font-size:0}
.single-product .yk-wa{display:none!important}
.single-product .yk-favbar{display:none!important}
.single-product #ykp-side{padding-bottom:84px!important}
}</style>
<script>(function(){var b=document.querySelector('.ykp-act .ykp-next'),p=document.querySelector('.ykp-price');
if(b&&/yk_buy=/.test(b.href)){b.innerHTML='Order Now'+(p&&p.textContent.trim()?' <small>· '+p.textContent.trim()+'</small>':'')+' <span aria-hidden="true">→</span>';}
var w=document.querySelector('.ykp-act .ykp-wab');if(w){[].forEach.call(w.childNodes,function(n){if(n.nodeType===3)n.textContent='';});}
})();</script>
<?php }, 30);

// ---------- Checkout: ringkas, chip ucapan, tombol total, simpan data ----------
add_action('wp_footer', function () {
    if (!function_exists('is_checkout') || !is_checkout() || is_order_received_page()) return; ?>
<style>
@media(max-width:767px){
 body.woocommerce-checkout .page-header{display:none!important}
 .woocommerce-checkout form.checkout .form-row{margin-bottom:12px!important}
 .woocommerce-checkout form.checkout .form-row label{margin-bottom:4px!important;line-height:20px!important;font-size:14px!important}
 .woocommerce-checkout form.checkout .form-row input.input-text,.woocommerce-checkout form.checkout .form-row select{height:50px!important;min-height:50px!important;font-size:16px!important}
 .woocommerce-form-coupon-toggle{margin:0 0 6px!important}
}
.woocommerce-form-coupon-toggle .woocommerce-info{margin:0!important;padding:9px 12px!important;font-size:13px!important;background:transparent!important;border:1px dashed #e3d2c4!important;text-align:left!important}
.woocommerce-form-coupon-toggle .woocommerce-info:before{display:none!important}
.woocommerce-form-coupon-toggle .woocommerce-info,.woocommerce-form-coupon-toggle .woocommerce-info *{line-height:1.4!important}
.woocommerce-form-coupon-toggle{padding:0!important;border:0!important;background:none!important}
#place_order{background:#8a5a2b!important;border-color:#8a5a2b!important;color:#fff!important;border-radius:14px!important;min-height:56px;font-size:16px!important;letter-spacing:.02em!important;text-transform:none!important}
.yk-chips{display:flex;flex-wrap:wrap;gap:7px;margin:2px 0 10px}
.yk-chip{border:1px solid #e3d2c4!important;background:#fbf6f2!important;color:#5a3a1c!important;border-radius:20px!important;padding:7px 12px!important;margin:0!important;min-height:0!important;height:auto!important;font:500 13px/1.2 'Plus Jakarta Sans',Montserrat,sans-serif!important;text-transform:none!important;letter-spacing:0!important;box-shadow:none!important;cursor:pointer}
.yk-chip.on{background:#8a5a2b!important;border-color:#8a5a2b!important;color:#fff!important}
.yk-prev .yk-x{text-transform:none!important;letter-spacing:0!important;min-height:0!important;box-shadow:none!important;background:none!important}
.yk-prev{margin:0 0 14px;padding:12px 14px;border:1px solid #ecdccf;border-radius:14px;background:#fbf6f2;font-family:'Plus Jakarta Sans',Montserrat,sans-serif}
.yk-prev .yk-to{font-size:13px;color:#3a3330;margin:2px 0 4px;line-height:1.45}
.yk-prev b{display:block;font-size:13px;color:#5a3a1c;margin-bottom:8px}
.yk-prev .yk-chip{background:#fff;text-align:left}
.yk-prev .yk-x{font-size:12px;color:#8a5a2b;background:none;border:0;padding:6px 0 0;cursor:pointer;text-decoration:underline}
</style>
<script>
jQuery(function($){
  var KEY='yk_ck_v1',st={};try{st=JSON.parse(localStorage.getItem(KEY)||'{}')||{};}catch(e){st={};}
  function save(){try{localStorage.setItem(KEY,JSON.stringify(st));}catch(e){}}

  // 1) Chip ucapan
  var T=[['🎂 Birthday','Happy Birthday! Wishing you a wonderful year ahead. With love, '],
         ['❤️ Anniversary','Happy Anniversary! Thank you for every moment together. With love, '],
         ['🎓 Graduation','Congratulations on your graduation! So proud of you. '],
         ['🎉 Congrats','Congratulations on your success! Wishing you all the best. '],
         ['🌷 Get Well','Get well soon! Sending warm wishes and big hugs. '],
         ['🙏 Thank You','Thank you for everything. '],
         ['🕊️ Duka Cita','Turut berduka cita. Semoga keluarga yang ditinggalkan diberi ketabahan. ']];
  function chips(){
    var ta=$('#yk_greeting');if(!ta.length||ta.prev('.yk-chips').length)return;
    var w=$('<div class="yk-chips" role="group" aria-label="Quick messages"></div>');
    T.forEach(function(t){$('<button type="button" class="yk-chip"></button>').text(t[0]).on('click',function(){
      var cur=ta.val(),isTpl=!cur||T.some(function(x){return cur.indexOf(x[1])===0;});
      var nv=isTpl?t[1]:(t[1]+cur);
      ta.val(nv.slice(0,300)).trigger('input').trigger('change');w.find('.yk-chip').removeClass('on');$(this).addClass('on');
      var el=ta[0];el.focus();el.setSelectionRange(el.value.length,el.value.length);
    }).appendTo(w);});
    ta.before(w);
  }

  // 2) Prefill pengirim + pilihan penerima sebelumnya
  function prefill(){
    if(st.s){ if(!$('#billing_first_name').val())$('#billing_first_name').val(st.s.n); if(!$('#billing_phone').val())$('#billing_phone').val(st.s.p); }
    var R=st.r||[];var box=$('.yk-prev');
    if(!R.length||box.length||!$('#shipping_first_name').length)return;
    box=$('<div class="yk-prev"><b>Send to a previous recipient</b><div class="yk-chips"></div><button type="button" class="yk-x">Forget saved details on this device</button></div>');
    R.slice(0,3).forEach(function(r,i){
      var ar='';try{var o=JSON.parse(r.yk_area);ar=o.kel||o.kec||o.city||'';}catch(e){ar=r.yk_area||'';}var label=r.shipping_first_name+' · '+(ar||String(r.shipping_address_1||'').split(',')[0]);
      $('<button type="button" class="yk-chip"></button>').text('↺ '+label).on('click',function(){applyR(r);box.find('.yk-chip').removeClass('on');$(this).addClass('on');}).appendTo(box.find('.yk-chips'));
    });
    box.find('.yk-x').on('click',function(){st={};save();box.remove();});
    $('#shipping_first_name').closest('.form-row').before(box);
    var ag=null;try{ag=sessionStorage.getItem('yk_again');sessionStorage.removeItem('yk_again');}catch(e){}
    if(ag!==null&&R[+ag]){applyR(R[+ag]);box.find('.yk-chip').eq(+ag).addClass('on');}
  }
  function applyR(r){
    ['shipping_first_name','shipping_phone','shipping_address_1','shipping_address_2','yk_lat','yk_lng','yk_area'].forEach(function(k){var e=$('[name="'+k+'"]');if(e.length)e.val(r[k]||'').trigger('change');});
    var lat=parseFloat(r.yk_lat),lng=parseFloat(r.yk_lng);
    try{ if(window.ykMap&&window.google&&google.maps&&!isNaN(lat)){var ll=new google.maps.LatLng(lat,lng);window.ykMap.setCenter(ll);window.ykMap.setZoom(16);google.maps.event.trigger(window.ykMap,'click',{latLng:ll});} }catch(e){}
    setTimeout(function(){ $('[name="shipping_address_2"]').val(r.shipping_address_2||''); btn(); },2500);
    var box=$('.yk-prev');box.find('.yk-to').remove();$('<div class="yk-to"></div>').text('📍 '+(r.shipping_address_1||'')+(r.shipping_address_2?' — '+r.shipping_address_2:'')).insertAfter(box.find('.yk-chips'));
    $(document.body).trigger('update_checkout');
  }

  // 3) Simpan saat Place Order
  $('form.checkout').on('checkout_place_order',function(){
    st.s={n:$('#billing_first_name').val()||'',p:$('#billing_phone').val()||''};
    if($('input[name=yk_method]:checked').val()!=='pickup'&&$('#shipping_first_name').val()&&$('[name=yk_lat]').val()){
      var r={};['shipping_first_name','shipping_phone','shipping_address_1','shipping_address_2','yk_lat','yk_lng','yk_area'].forEach(function(k){r[k]=$('[name="'+k+'"]').val()||'';});
      st.r=[r].concat((st.r||[]).filter(function(x){return String(x.shipping_phone).replace(/\D/g,'')!==String(r.shipping_phone).replace(/\D/g,'');})).slice(0,3);
    }
    save();return true;
  });

  // 4) Tombol Place Order menampilkan total
  function btn(){var t=$('.order-total .amount').last().text().trim(),b=$('#place_order');if(b.length&&t){b.text('Place Order · '+t);}}

  function all(){chips();prefill();btn();}
  all();$(document.body).on('updated_checkout',all);
});
</script>
<?php }, 30);

// ---------- Thank-you: tombol kirim lagi ke penerima yang sama ----------
add_action('woocommerce_thankyou', function ($order_id) {
    $o = wc_get_order($order_id); if (!$o) return;
    $pid = 0; foreach ($o->get_items() as $it) { $pid = $it->get_product_id(); break; }
    $name = $o->get_shipping_first_name(); if (!$name) return;
    echo '<div style="margin:0 0 24px;text-align:center"><a href="' . esc_url(home_url('/')) . '" class="yk-again" data-pid="' . (int) $pid . '" style="display:inline-block;padding:12px 22px;border:1.5px solid #8a5a2b;border-radius:30px;color:#8a5a2b;text-decoration:none;font-weight:600;font-family:\'Plus Jakarta Sans\',sans-serif">🔁 Send more flowers to ' . esc_html($name) . '</a></div>'
       . '<script>document.addEventListener("click",function(e){var a=e.target.closest&&e.target.closest(".yk-again");if(!a)return;try{sessionStorage.setItem("yk_again","0");}catch(x){}});</script>';
}, 6);
