<?php
// Dari Code Snippets #8 "Header: tab Masuk / Akun (Yulika)" — scope: global
/* Tab Masuk / Akun di pojok kanan header — semua halaman */
add_action( 'wp_footer', function () {
	if ( is_admin() ) {
		return;
	}
	$in    = is_user_logged_in();
	$url   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
	$label = $in ? 'Akun' : 'Masuk';
	?>
<style>
.yk-acct{position:absolute;z-index:9990;display:flex;align-items:center;gap:5px;text-decoration:none!important;color:#2f3337!important;font:600 12px/1 Montserrat,sans-serif;letter-spacing:.03em;padding:8px 11px;border:1.5px solid #d9d2c8;border-radius:18px;background:#fff;white-space:nowrap;visibility:hidden}
.yk-acct.ok{visibility:visible}
.yk-acct:hover{border-color:#a0784a;color:#a0784a!important}
.yk-acct svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2}
.yk-acct.in{background:#a0784a;border-color:#a0784a;color:#fff!important}
@media(max-width:360px){.yk-acct span{display:none}.yk-acct{padding:7px}}
</style>
<a class="yk-acct<?php echo $in ? ' in' : ''; ?>" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo $in ? 'Akun saya' : 'Masuk atau daftar'; ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg><span><?php echo esc_html( $label ); ?></span></a>
<script>
(function(){
var a=document.querySelector('.yk-acct');if(!a)return;
function place(){
 var hdr=document.querySelector('[data-elementor-type="header"]');if(!hdr){a.style.position='fixed';a.style.top='12px';a.style.right='12px';a.classList.add('ok');return}
 var c=[].slice.call(hdr.querySelectorAll('.elementor-widget')).filter(function(e){var b=e.getBoundingClientRect();return e.offsetParent&&b.width>0&&b.height>0&&b.height<80&&!/logo|html/.test(e.className)});
 if(!c.length){a.style.position='fixed';a.style.top='12px';a.style.right='12px';a.classList.add('ok');return}
 var t=c.reduce(function(m,e){return e.getBoundingClientRect().right>m.getBoundingClientRect().right?e:m});
 var host=t.parentElement;
 if(a.parentNode!==host){if(getComputedStyle(host).position==='static')host.style.position='relative';host.appendChild(a)}
 var hb=host.getBoundingClientRect(),tb=t.getBoundingClientRect();
 a.style.right=Math.max(8,Math.round(hb.right-tb.left+10))+'px';
 a.style.top=Math.round(tb.top-hb.top+tb.height/2-a.offsetHeight/2)+'px';
 a.classList.add('ok');
}
place();setTimeout(place,500);setTimeout(place,1800);
window.addEventListener('load',place);
window.addEventListener('resize',function(){clearTimeout(window.__ykA);window.__ykA=setTimeout(place,200)});
})();
</script>
	<?php
}, 50 );

/* Tombol "Masuk dengan Google" di form login & daftar (aktif setelah Google diaktifkan di Nextend Social Login) */
function yk_google_btn() {
	if ( ! class_exists( 'NextendSocialLogin' ) || empty( NextendSocialLogin::$enabledProviders['google'] ) ) {
		return '';
	}
	$redirect = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );
	return '<div class="yk-gl" style="margin:0 0 16px">' . preg_replace( '#(Continue|Sign in|Login) with (<b>|&lt;b&gt;)?Google(</b>|&lt;/b&gt;)?#', 'Masuk dengan <b>Google</b>', do_shortcode( '[nextend_social_login provider="google" redirect="' . esc_url( $redirect ) . '"]' ) ) . '<p style="text-align:center;color:#9a9a9a;font-size:12px;margin:12px 0 0">atau gunakan email</p></div>';
}
add_action( 'woocommerce_login_form_start', function () {
	echo yk_google_btn(); // phpcs:ignore
}, 5 );
add_action( 'woocommerce_register_form_start', function () {
	echo yk_google_btn(); // phpcs:ignore
}, 5 );