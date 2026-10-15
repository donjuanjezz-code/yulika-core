<?php
// Dari Code Snippets #20 "Cart & checkout: tanpa footer + urutan langkah (Yulika)" — scope: global
/* ===== Yulika: cart & checkout tanpa footer; urutan checkout: Metode → Jadwal → Penerima → Pengirim → Your Order ===== */
add_action( 'wp_head', function () {
	if ( ! function_exists( 'is_checkout' ) || ( ! is_cart() && ! is_checkout() ) ) {
		return;
	}
	echo '<style id="yk-co-layout">.elementor-location-footer,footer.main-footer,#scroll-to-top{display:none!important}';
	if ( is_checkout() && ! is_order_received_page() ) {
		echo 'form.woocommerce-checkout{display:flex!important;flex-direction:column;max-width:760px;margin:0 auto}'
			. 'form.woocommerce-checkout #customer_details,form.woocommerce-checkout #customer_details>.col-1,form.woocommerce-checkout #customer_details>.col-2{display:contents!important}'
			. 'form.woocommerce-checkout #yk-method{order:1}'
			. 'form.woocommerce-checkout #yk-dlv{order:2;margin:0 0 26px!important}'
			. 'form.woocommerce-checkout .woocommerce-shipping-fields{order:3;margin-bottom:10px}'
			. 'form.woocommerce-checkout .woocommerce-billing-fields{order:4;margin-bottom:10px}'
			. 'form.woocommerce-checkout #order_review_heading{order:5;margin-top:10px!important}'
			. 'form.woocommerce-checkout #order_review{order:6}'
			. '#yk-method h3{margin:0 0 2px!important}#yk-method .yk-msub{margin:0 0 12px;color:#666;font-size:15px}';
	}
	echo '</style>';
}, 99 );

add_filter( 'gettext', function ( $t, $text, $domain ) {
	if ( 'Your order' === $text ) {
		return 'Your Order';
	}
	if ( 'Place order' === $text ) {
		return 'Place Order';
	}
	return $t;
}, 20, 3 );
add_filter( 'woocommerce_order_button_text', function () {
	return 'Place Order';
}, 99 );
