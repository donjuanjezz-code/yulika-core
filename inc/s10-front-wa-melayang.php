<?php
// Dari Code Snippets #10 "Tombol WhatsApp melayang kanan bawah (Yulika)" — scope: front-end
/* Tombol WhatsApp melayang di pojok kanan bawah — semua halaman */
add_action( 'wp_footer', function () {
	if ( is_admin() ) {
		return;
	}
	// 30 Okt 2026: pesan standar "baru saja mengunjungi website" (x-wa-pesan.php); di halaman produk menyebut produknya
	$msg = function_exists( 'yk_wa_default_text' ) ? yk_wa_default_text() : 'Halo Admin, saya baru saja cek web yulikaflorist.com dan ingin bertanya lebih lanjut mengenai produknya, boleh dibantu?';
	$url = 'https://wa.me/6281315881538?text=' . rawurlencode( $msg );
	?>
<style>
.elementor-element-3cc3489{display:none!important}
.yk-wa{position:fixed;right:16px;bottom:16px;z-index:99990;width:56px;height:56px;border-radius:50%;background:#25d366;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(0,0,0,.25);text-decoration:none!important;transition:transform .15s}
.yk-wa:active{transform:scale(.92)}
.yk-wa svg{width:30px;height:30px;fill:#fff}
.yk-wa:after{content:"";position:absolute;inset:0;border-radius:50%;box-shadow:0 0 0 0 rgba(37,211,102,.55);animation:ykwa 2.4s infinite}
@keyframes ykwa{0%{box-shadow:0 0 0 0 rgba(37,211,102,.55)}70%{box-shadow:0 0 0 14px rgba(37,211,102,0)}100%{box-shadow:0 0 0 0 rgba(37,211,102,0)}}
@media (prefers-reduced-motion:reduce){.yk-wa:after{animation:none}}
.yk-wa-tip{position:fixed;right:80px;bottom:28px;z-index:99990;background:#fff;color:#2f3337;font:600 12px Montserrat,sans-serif;padding:7px 11px;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,.15);white-space:nowrap;pointer-events:none;transition:opacity .4s}
.yk-wa-tip.hide{opacity:0}
#scroll-to-top.vamtam-scroll-to-top{bottom:84px!important;right:20px!important}
.yk-favbar{bottom:20px!important}
body.woocommerce-checkout .yk-wa-tip{display:none}
</style>
<a class="yk-wa" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" aria-label="Chat WhatsApp Yulika Florist"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 3C8.8 3 3 8.7 3 15.8c0 2.5.7 4.9 2 6.9L3 29l6.5-2c1.9 1.1 4.2 1.7 6.5 1.7 7.2 0 13-5.7 13-12.8S23.2 3 16 3zm0 23.4c-2.1 0-4.1-.6-5.8-1.7l-.4-.3-3.9 1.2 1.3-3.7-.3-.4c-1.2-1.8-1.8-3.8-1.8-5.9C5.1 9.9 10 5.2 16 5.2s10.9 4.7 10.9 10.6S22 26.4 16 26.4zm6-7.9c-.3-.2-1.9-.9-2.2-1s-.5-.2-.7.2-.8 1-1 1.2-.4.2-.7.1a8.9 8.9 0 0 1-4.4-3.8c-.3-.6.3-.5.9-1.7.1-.2 0-.4 0-.5l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4s-1.1 1.1-1.1 2.7 1.2 3.1 1.3 3.3 2.3 3.5 5.6 4.9c2.1.9 2.9.9 3.9.8.6-.1 1.9-.8 2.2-1.5s.3-1.4.2-1.5-.3-.2-.6-.3z"/></svg></a>
<span class="yk-wa-tip">Order cepat? Chat kami</span>
<script>setTimeout(function(){var t=document.querySelector('.yk-wa-tip');if(t)t.classList.add('hide')},6000);</script>
	<?php
}, 60 );