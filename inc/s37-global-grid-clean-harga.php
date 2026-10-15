<?php
// Dari Code Snippets #37 "#37 Grid produk clean ala Apple 2x2 HP + format harga Rp" — scope: global

// #37 Grid produk clean ala Apple: foto besar potret, sudut tajam, nama tengah, harga "Rp 2.850.000" (12 Okt 2026, v4 rasio 2:3)
add_filter('woocommerce_price_format', function () { return '%1$s&nbsp;%2$s'; });
add_filter('wc_get_price_thousand_separator', function () { return '.'; });
add_filter('wc_get_price_decimal_separator', function () { return ','; });
add_filter('wc_get_price_decimals', function () { return 0; });
add_action('wp_head', function () { if (is_admin()) return; ?>
<style id="yk-clean-grid">
/* Background halaman +50% lebih terang: #f6f4f2 -> #fbfaf9 (13 Okt 2026) */
html:root{--vamtam-body-background-color:#fbfaf9!important;--vamtam-accent-color-8:#fbfaf9!important;--vamtam-accent-color-8-rgb:251,250,249!important}
html body,html body.elementor-kit-16{background-color:#fbfaf9!important;--e-global-color-vamtam_accent_8:#fbfaf9!important}
@media(max-width:767px){
 html body.woocommerce.woocommerce-page ul.products.products{display:grid!important;grid-template-columns:1fr 1fr!important;gap:24px 8px!important;margin:0 -8px!important;width:calc(100% + 16px)!important;max-width:none!important}
 html body.woocommerce.woocommerce-page ul.products.products li.product.product{width:auto!important;margin:0!important;padding:0!important;background:none!important;border:0!important;box-shadow:none!important;text-align:center!important}
 html body.woocommerce.woocommerce-page ul.products.products li.product.product *{box-shadow:none!important}
 html body.woocommerce.woocommerce-page ul.products.products li.product.product .woocommerce-loop-product__title{font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Helvetica Neue',Arial,sans-serif!important;font-weight:600!important;font-size:14.5px!important;line-height:1.25!important;letter-spacing:-.01em!important;text-transform:none!important;color:#1d1d1f!important;text-align:center!important;margin:.5em 0 1px!important;padding:0 4px!important}
 html body.woocommerce.woocommerce-page ul.products.products li.product.product .price,html body.woocommerce.woocommerce-page ul.products.products li.product.product .price *{font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Helvetica Neue',Arial,sans-serif!important;font-weight:500!important;font-size:16.5px!important;letter-spacing:0!important;text-transform:none!important;color:#6e6e73!important;text-align:center!important}
 html body.woocommerce.woocommerce-page ul.products.products li.product.product .price{display:block!important;margin:0!important;padding:0!important;line-height:1.25!important}
 html body.woocommerce.woocommerce-page ul.products.products li.product.product .button,html body.woocommerce.woocommerce-page ul.products.products li.product.product .add_to_cart_button,html body.woocommerce.woocommerce-page ul.products.products li.product.product .added_to_cart{display:none!important}
 html body.woocommerce.woocommerce-page ul.products.products li.product.product .woocommerce-LoopProduct-link{padding:0!important;background:none!important;text-align:center!important;display:block!important}
}
/* Rasio foto 2:3 (potret, ala madameflorist) di semua grid produk & halaman produk, tanpa stretch (object-fit:cover) */
html body ul.products.products li.product.product img.attachment-woocommerce_thumbnail.attachment-woocommerce_thumbnail,html body ul.products.products li.product.product a img{width:100%!important;height:auto!important;aspect-ratio:2/3!important;max-height:none!important;min-height:0!important;object-fit:cover!important;object-position:50% 50%!important;border-radius:10px!important;margin:0!important;display:block!important}
html body .ykp-gal img{aspect-ratio:2/3!important;object-fit:cover!important;object-position:50% 50%!important;max-height:none!important;width:100%!important;height:auto!important;border-radius:10px!important}
html body .ykp-gal{background:transparent!important}
@media(min-width:900px){html body .ykp-gal{width:fit-content!important;max-width:100%!important;margin:0 auto!important}html body .ykp-gal img{width:auto!important;max-width:100%!important;height:min(calc(100svh - 210px),860px)!important}}
html body .ykp-thumbs button{flex:0 0 46px!important;height:69px!important}
/* Home "Shop by" tiles: rasio 2:3 + rounded, sama dengan Best Sellers/New Arrivals */
html body .yk-t .yk-ti{display:block!important;aspect-ratio:2/3!important;height:auto!important;border-radius:10px!important;overflow:hidden!important}
html body .yk-t .yk-ti img{width:100%!important;height:100%!important;aspect-ratio:2/3!important;object-fit:cover!important;object-position:50% 50%!important;border-radius:10px!important;display:block!important;max-height:none!important}
/* Sudut foto tajam (selain grid produk kategori) */
.yk-lp img,.yk-sg-i,.yk-sg-i img,.woocommerce-product-gallery img{border-radius:0!important}
</style>
<?php }, 99);
