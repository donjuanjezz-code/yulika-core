<?php
// Dari Code Snippets #44 "#44 Event & Interior Projects di homepage (di atas footer)" — scope: front-end
/* Sumber tunggal: section .yk-pj di halaman Our Story (ID 18). Cache 12 jam, dihapus otomatis saat Our Story disimpan. */
function yk_pj_section_html() {
	$c = get_transient( 'yk_pj_html_v1' );
	if ( false === $c ) {
		$c = '';
		$p = get_post( 18 );
		if ( $p && preg_match( '~<section class=.yk-pj.*?</section>~s', $p->post_content, $m ) ) {
			$c = $m[0];
		}
		set_transient( 'yk_pj_html_v1', $c, 12 * HOUR_IN_SECONDS );
	}
	return $c;
}
add_action( 'save_post_page', function ( $id ) {
	if ( 18 === (int) $id ) {
		delete_transient( 'yk_pj_html_v1' );
	}
} );

/* Homepage: tampil tepat di atas footer */
add_action( 'get_footer', function () {
	static $done = false;
	if ( $done || ! is_front_page() ) {
		return;
	}
	$h = yk_pj_section_html();
	if ( '' === $h ) {
		return;
	}
	$done = true;
	echo '<style id="yk-pj-home-css">
.yk-pj-home{clear:both;background:#fbf7f3;border-top:1px solid #efe3d8;margin:48px 0 0;padding:56px 16px 64px;scroll-margin-top:90px;font-family:Montserrat,Arial,sans-serif;color:#2f3337}
.yk-pj-home-in{max-width:1240px;margin:0 auto}
.yk-pj-home .yk-pj{margin:0!important}
.yk-pj-home .yk-pj h2{font-family:"Sorts Mill Goudy",Georgia,serif!important;font-weight:400!important;font-size:28px!important;line-height:1.2!important;color:#222!important;text-align:center;margin:0 0 8px!important;text-transform:none!important;letter-spacing:0!important}
.yk-pj-home .yk-pj>p{text-align:center;font-size:15px;line-height:1.55;color:#555;max-width:640px;margin:0 auto 22px}
.yk-pj-home .yk-pj-tabs{margin-bottom:18px}
.yk-pj-home .yk-pj-hint{text-align:center}
.yk-pj-home .yk-pj-c{max-width:760px;margin:30px auto 0;text-align:center;background:#fff}
.yk-pj-home .yk-btns{display:flex;flex-wrap:wrap;justify-content:center;gap:10px;margin:14px 0 0}
.yk-pj-home .yk-btn{display:inline-block;padding:12px 18px;border-radius:6px;font-weight:600;font-size:13px;letter-spacing:.04em;text-decoration:none!important;line-height:1.3}
.yk-pj-home .yk-btn.w{background:#25a35a;color:#fff!important}
.yk-pj-home .yk-btn.s{background:#fff;color:#a0784a!important;border:1.5px solid #a0784a}
@media(min-width:768px){.yk-pj-home{padding:72px 24px 80px}.yk-pj-home .yk-pj h2{font-size:34px!important}.yk-pj-home .yk-pj-tabs{justify-content:center;margin:0 0 24px;padding:0}}
</style>
<div class="yk-pj-home" id="event-interior-projects"><div class="yk-pj-home-in">' . $h . '</div></div>'; // phpcs:ignore
}, 1 );
