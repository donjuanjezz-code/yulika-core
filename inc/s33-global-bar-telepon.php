<?php
// Dari Code Snippets #33 "#33 Bar telepon kantor di header" — scope: global

// #33 Bar telepon kantor di atas header semua halaman (12 Okt 2026)
function yk_phone_bar() {
    static $done = false; if ($done || is_admin()) return; $done = true; ?>
<div class="yk-pbar"><a href="tel:+62214532630" aria-label="Call Yulika Florist office 021 453 2630">
<svg width="13" height="13" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1z"/></svg>
<span>Office <b>021-453-2630</b></span><span class="yk-pbar-h" id="yk-pbar-h">08:00–19:00</span></a></div>
<script>(function(){try{var d=new Date(new Date().toLocaleString("en-US",{timeZone:"Asia/Jakarta"})).getDay();if(d===0)document.getElementById("yk-pbar-h").textContent="Sun 08:00–16:00";}catch(e){}})();</script>
<?php }
add_action('wp_body_open', 'yk_phone_bar', 1);
add_action('wp_footer', function () { // cadangan bila tema tidak memanggil wp_body_open
    if (did_action('wp_body_open')) return; yk_phone_bar(); ?>
<script>(function(){var b=document.querySelector('.yk-pbar');if(b)document.body.insertBefore(b,document.body.firstChild);})();</script>
<?php }, 1);
add_action('wp_head', function () { ?>
<style>.yk-pbar{background:#F3E2D8;text-align:center;font-family:'Plus Jakarta Sans',Montserrat,sans-serif;font-size:12.5px;line-height:30px;height:30px;position:relative;z-index:100}.yk-pbar a{color:#5a3a1c!important;text-decoration:none!important;display:flex;align-items:center;justify-content:center;gap:6px;position:fixed;left:0;right:0;top:0;height:30px;background:#F3E2D8;z-index:1000;box-shadow:0 1px 6px rgba(90,58,28,.08)}.admin-bar .yk-pbar a{top:32px}@media(max-width:782px){.admin-bar .yk-pbar a{top:46px}}@media(max-width:600px){.admin-bar .yk-pbar a{top:0}}.yk-pbar b{font-weight:600;letter-spacing:.02em}.yk-pbar-h{color:#8a5a2b;opacity:.85}.yk-pbar-h:before{content:"·";margin-right:6px}@media(min-width:768px){.yk-pbar{font-size:13px}}</style>
<?php }, 20);
