<?php
// Dari Code Snippets #9 "Homepage: grid produk 2 kolom (Yulika)" — scope: front-end
/* Homepage: semua section produk setelah "Our Latest Products" → grid 2 kolom (HP) */

function yk_home_hidden_sections() {
	/* Section Elementor lama di halaman Home (post 11) yang diganti grid baru */
	return array( '1b2fc2e', '681be81', 'adea81e', '4fbda92', '3106f39', '3a93318', '03f7e53', '7890982', '187dbe8', 'd51468a', '476a730' );
}

add_filter( 'elementor/frontend/section/should_render', function ( $should, $el ) {
	if ( is_front_page() && in_array( $el->get_id(), yk_home_hidden_sections(), true ) ) {
		return false;
	}
	return $should;
}, 10, 2 );

function yk_home_grids_html() {
	$sections = array(
		array( 'Best Sellers', 'Our most-loved arrangements, chosen by our customers.', 'limit="8" best_selling="true" class="yk-home"', '/collections/' ),
		array( 'New Arrivals', 'Freshly designed by our florists.', 'limit="8" orderby="date" order="DESC" category="hampers" cat_operator="NOT IN" class="yk-home yk-nolebaran"', '/collections/' ),
	);
	ob_start();
	?>
<style>
.yk-hgrid{max-width:1240px;margin:0 auto;padding:28px 12px 8px}
.yk-hgrid .yk-hh{text-align:center;margin:0 0 14px}
.yk-hgrid .yk-hh h2{font-family:'Sorts Mill Goudy',Georgia,serif!important;font-weight:400!important;font-size:26px!important;line-height:1.2!important;margin:0 0 4px!important;color:#2f3337;text-transform:none!important;letter-spacing:0!important}
.yk-hgrid .yk-hh p{font:15px/1.5 Montserrat,sans-serif;color:#555;margin:0}
@media(min-width:768px){.yk-hgrid .yk-hh h2{font-size:32px!important}}
.yk-hgrid .woocommerce{width:100%}
.yk-hgrid ul.products{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:14px 12px!important;margin:0!important;padding:0!important;list-style:none}
.yk-hgrid ul.products:before,.yk-hgrid ul.products:after{display:none!important}
@media(min-width:768px){.yk-hgrid ul.products{grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:20px 16px!important}}
@media(min-width:1100px){.yk-hgrid ul.products{grid-template-columns:repeat(4,minmax(0,1fr))!important;gap:28px 20px!important}}
.yk-hgrid ul.products li.product{width:auto!important;float:none!important;margin:0!important;padding:0!important;background:#fff;border-radius:6px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08);display:flex;flex-direction:column;position:relative}
.yk-hgrid ul.products li.product a img,.yk-hgrid ul.products li.product img.attachment-woocommerce_thumbnail{width:100%!important;height:auto!important;aspect-ratio:1/1;object-fit:cover;object-position:center 35%;display:block;margin:0!important}
.yk-hgrid .vamtam-product-content,.yk-hgrid li.product>a+div{padding:10px 8px 12px!important;text-align:center;display:block!important}
.yk-hgrid .vamtam-product-content a.woocommerce-loop-product__link{display:block!important}
.yk-hgrid .woocommerce-loop-product__title{font-family:'Sorts Mill Goudy',Georgia,serif!important;font-weight:400!important;font-size:17px!important;line-height:1.3!important;margin:0 0 6px!important;color:#222;text-transform:none!important;letter-spacing:0!important;overflow:hidden;display:-webkit-box!important;-webkit-line-clamp:2;-webkit-box-orient:vertical;min-height:2.5em;padding:0!important}
.yk-hgrid .woocommerce-loop-product__title img.emoji{display:none!important}
.yk-hgrid .price,.yk-hgrid .price .amount{display:block!important;text-align:center!important;margin:0!important;font:700 16px Montserrat,sans-serif!important;color:#8a5a2b!important}
.yk-hgrid .vamtam-product-tags,.yk-hgrid .button,.yk-hgrid .add_to_cart_button,.yk-hgrid .onsale{display:none!important}
@media(min-width:768px){.yk-hgrid .woocommerce-loop-product__title{font-size:18px!important}}
.yk-hgrid .yk-more{display:block;width:max-content;margin:16px auto 0;padding:11px 22px;border:1.5px solid #a0784a;border-radius:22px;color:#a0784a!important;text-decoration:none!important;font:600 14px Montserrat,sans-serif;letter-spacing:.04em;text-transform:uppercase}
.yk-hgrid .yk-more:hover{background:#a0784a;color:#fff!important}
</style>
	<?php
	foreach ( $sections as $i => $s ) {
		$grid = do_shortcode( '[products ' . $s[2] . ' columns="4"]' );
		if ( false === strpos( $grid, '<li' ) ) {
			continue;
		}
		echo '<section class="yk-hgrid"' . ( 0 === $i ? ' id="latest_products"' : '' ) . '><div class="yk-hh"><h2>' . esc_html( $s[0] ) . '</h2><p>' . esc_html( $s[1] ) . '</p></div>';
		echo $grid; // phpcs:ignore
		echo '<a class="yk-more" href="' . esc_url( home_url( $s[3] ) ) . '">View all &rarr;</a></section>';
	}
	return ob_get_clean();
}

add_action( 'elementor/frontend/section/after_render', function ( $el ) {
	if ( is_front_page() && '7bd38f3' === $el->get_id() ) {
		echo yk_home_grids_html(); // phpcs:ignore
	}
} );

/* Grid homepage: sembunyikan produk tanpa harga & hindari duplikat Lebaran */
add_filter( 'woocommerce_shortcode_products_query', function ( $q, $atts ) {
	$cls = isset( $atts['class'] ) ? $atts['class'] : '';
	if ( false === strpos( $cls, 'yk-home' ) ) {
		return $q;
	}
	if ( ! isset( $q['meta_query'] ) || ! is_array( $q['meta_query'] ) ) {
		$q['meta_query'] = array();
	}
	$q['meta_query'][] = array( 'key' => '_price', 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC' );
	if ( false !== strpos( $cls, 'yk-nolebaran' ) ) {
		if ( ! isset( $q['tax_query'] ) || ! is_array( $q['tax_query'] ) ) {
			$q['tax_query'] = array();
		}
		$q['tax_query'][] = array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => array( 'lebaran' ), 'operator' => 'NOT IN' );
	}
	if ( false !== strpos( $cls, 'yk-nohampers' ) ) {
		if ( ! isset( $q['tax_query'] ) || ! is_array( $q['tax_query'] ) ) {
			$q['tax_query'] = array();
		}
		$q['tax_query'][] = array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => array( 'hampers' ), 'operator' => 'NOT IN' );
	}
	return $q;
}, 10, 2 );