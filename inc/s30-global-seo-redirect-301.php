<?php
// Dari Code Snippets #30 "#30 SEO 301 redirect URL lama (Search Console)" — scope: global

// SEO: 301 untuk URL lama/404 dari Search Console (12 Okt 2026)
add_action('template_redirect', function () {
    if (is_admin() || wp_doing_ajax()) return;
    $path = rawurldecode(strtok($_SERVER['REQUEST_URI'], '?'));
    $go = function ($to) { wp_safe_redirect($to, 301); exit; };

    // ?page_id= halaman lama yang dihapus
    if (isset($_GET['page_id'])) {
        $m = array(50 => 989559, 52 => 989584, 43 => 11);
        $pid = (int) $_GET['page_id'];
        if (isset($m[$pid]) && get_post_status($m[$pid]) === 'publish') $go(get_permalink($m[$pid]));
    }

    // feed produk/kategori/komentar -> halaman induknya (feed utama /feed/ tetap)
    if (is_feed() && !preg_match('#^/feed/?$#', $path)) {
        if (preg_match('#^/comments/feed#', $path)) $go(home_url('/'));
        $to = preg_replace('#/feed(/(rss2|atom|rss|rdf))?/?$#', '/', $path);
        if ($to !== $path) $go(home_url($to));
    }

    // kategori kosong
    if (is_tax('product_cat', 'installation')) $go(home_url('/'));

    if (!is_404()) return;
    $map = array(
        '/2/' => '/',
        '/product/lazeer/' => '/product-category/arrangements/hampers/',
        '/product/hamimah-artisan-food-hamper-2/' => '/product/hamimah-artisan-food-hamper/',
        '/product/heartfelt-harmony-basket/' => '/product-category/arrangements/flower-boxes-baskets/',
        '/product/fireside-red-twig/' => '/product-category/arrangements/table-arrangements/',
        '/product/winter-dawn-quartz/' => '/product-category/arrangements/table-arrangements/',
        '/macam-macam-bunga-yang-identik-dengan-pernikahan/' => '/product-category/arrangements/bouquet/',
        '/ide-membuat-parcel-natal-yang-berkesan/' => '/product-category/arrangements/hampers/',
        '/makna-warna-bunga-chrysanthemum/' => '/product-category/arrangements/bouquet/',
    );
    $k = trailingslashit($path);
    if (isset($map[$k])) $go(home_url($map[$k]));

    // produk draft/terhapus -> kategori utamanya
    if (preg_match('#^/product/([^/]+)/?$#', $path, $mm)) {
        $p = get_page_by_path(sanitize_title($mm[1]), OBJECT, 'product');
        if ($p) {
            $best = null;
            foreach ((array) wp_get_post_terms($p->ID, 'product_cat') as $t) {
                if (in_array($t->slug, array('coming-soon', 'uncategorized'), true)) continue;
                if (!$best || ($t->parent && !$best->parent)) $best = $t;
            }
            if ($best) { $l = get_term_link($best); if (!is_wp_error($l)) $go($l); }
        }
        $go(home_url('/product-category/arrangements/bouquet/'));
    }
}, 1);
