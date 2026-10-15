<?php
// Florist Journal (blog): meta description, Open Graph & schema Article untuk artikel + arsip kategori blog.
// s18 hanya menangani homepage & kategori produk, jadi file ini mengisi bagian blog. Selalu dimuat.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function yk_journal_desc( $text, $len = 158 ) {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $text ) ) );
	if ( function_exists( 'mb_strlen' ) && mb_strlen( $text ) > $len ) {
		$cut  = mb_substr( $text, 0, $len - 1 );
		$sp   = mb_strrpos( $cut, ' ' );
		$text = ( $sp ? mb_substr( $cut, 0, $sp ) : $cut ) . '…';
	}
	return $text;
}

add_action( 'wp_head', function () {
	if ( is_admin() ) {
		return;
	}
	if ( is_singular( 'post' ) ) {
		$p     = get_queried_object();
		$title = html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' );
		$desc  = yk_journal_desc( has_excerpt( $p ) ? $p->post_excerpt : $p->post_content );
		$url   = get_permalink( $p );
		$img   = get_the_post_thumbnail_url( $p, 'large' );
		echo "\n" . '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
		echo '<meta property="og:type" content="article">' . "\n";
		echo '<meta property="og:site_name" content="Yulika Florist">' . "\n";
		echo '<meta property="og:locale" content="id_ID">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
		if ( $img ) {
			echo '<meta property="og:image" content="' . esc_url( $img ) . '">' . "\n";
		}
		echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c', $p ) ) . '">' . "\n";
		echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c', $p ) ) . '">' . "\n";
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";

		$cats  = get_the_category( $p->ID );
		$crumb = array( array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url( '/' ) ) );
		if ( $cats ) {
			$crumb[] = array( '@type' => 'ListItem', 'position' => 2, 'name' => html_entity_decode( $cats[0]->name, ENT_QUOTES, 'UTF-8' ), 'item' => get_category_link( $cats[0] ) );
		}
		$crumb[] = array( '@type' => 'ListItem', 'position' => count( $crumb ) + 1, 'name' => $title, 'item' => $url );
		$logo    = (int) get_option( 'site_icon' );
		$graph   = array(
			array(
				'@type'            => 'BlogPosting',
				'headline'         => $title,
				'description'      => $desc,
				'image'            => $img ? array( $img ) : array(),
				'datePublished'    => get_the_date( 'c', $p ),
				'dateModified'     => get_the_modified_date( 'c', $p ),
				'inLanguage'       => 'id-ID',
				'mainEntityOfPage' => $url,
				'author'           => array( '@type' => 'Organization', 'name' => 'Yulika Florist', 'url' => home_url( '/' ) ),
				'publisher'        => array_filter( array(
					'@type' => 'Organization',
					'name'  => 'Yulika Florist',
					'url'   => home_url( '/' ),
					'logo'  => $logo ? array( '@type' => 'ImageObject', 'url' => wp_get_attachment_image_url( $logo, 'full' ) ) : null,
				) ),
			),
			array( '@type' => 'BreadcrumbList', 'itemListElement' => $crumb ),
		);
		echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
		return;
	}
	if ( is_category() ) {
		$t    = get_queried_object();
		$desc = $t ? yk_journal_desc( term_description( $t ) ) : '';
		if ( $desc ) {
			echo "\n" . '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
			echo '<meta property="og:type" content="website">' . "\n";
			echo '<meta property="og:title" content="' . esc_attr( html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' ) . ' | Yulika Florist' ) . '">' . "\n";
			echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
			echo '<meta property="og:url" content="' . esc_url( get_category_link( $t ) ) . '">' . "\n";
		}
	}
}, 2 );

/* Judul arsip: "Florist Journal", bukan "Category: Florist Journal" */
add_filter( 'get_the_archive_title_prefix', function ( $prefix ) {
	return is_category() ? '' : $prefix;
} );

/* Judul tab: "Judul Artikel | Yulika Florist" dan "Florist Journal | Yulika Florist" */
add_filter( 'document_title_parts', function ( $parts ) {
	if ( is_singular( 'post' ) || is_category() ) {
		$parts['site'] = 'Yulika Florist';
		unset( $parts['tagline'] );
	}
	return $parts;
}, 20 );
