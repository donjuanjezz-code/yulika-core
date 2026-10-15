<?php
// Dari Code Snippets #31 "#31 Permintaan review Google otomatis" — scope: global

// #31 Permintaan review Google otomatis (12 Okt 2026)
if (!defined('YK_REVIEW_URL')) define('YK_REVIEW_URL', 'https://g.page/r/CYIvVndmEWZfEBM/review');

// 1) Email otomatis saat order Completed (sekali per order)
add_action('woocommerce_order_status_completed', function ($order_id) {
    $order = wc_get_order($order_id);
    if (!$order || $order->get_meta('_yk_review_sent')) return;
    $to = $order->get_billing_email();
    if (!$to || !is_email($to)) return;
    $name = esc_html($order->get_billing_first_name() ?: 'there');
    $subject = 'Thank you for ordering at Yulika Florist 💐';
    $btn = '<a href="' . esc_url(YK_REVIEW_URL) . '" style="display:inline-block;background:#8a5a2b;color:#fff;text-decoration:none;padding:14px 26px;border-radius:30px;font-weight:600">⭐ Leave a Google Review</a>';
    $body = '<p>Hi ' . $name . ',</p>'
        . '<p>Thank you for trusting Yulika Florist with order <b>#' . $order->get_order_number() . '</b>. We hope the flowers made the moment special.</p>'
        . '<p>Would you spare 30 seconds to share your experience on Google? It helps other customers find us.</p>'
        . '<p style="text-align:center;margin:28px 0">' . $btn . '</p>'
        . '<p style="color:#777;font-size:13px">Terima kasih sudah berbelanja di Yulika Florist. Ulasan Anda di Google sangat berarti bagi kami 🙏</p>';
    $mailer = WC()->mailer();
    $html = $mailer->wrap_message('How were your flowers?', $body);
    $ok = $mailer->send($to, $subject, $html, "Content-Type: text/html\r\n");
    if ($ok) {
        $order->update_meta_data('_yk_review_sent', current_time('mysql'));
        $order->add_order_note('Email permintaan review Google terkirim otomatis.');
        $order->save();
    }
}, 20);

// 2) Tombol review di halaman terima kasih
add_action('woocommerce_thankyou', function ($order_id) {
    echo '<div style="margin:24px 0;padding:20px;border:1px solid #ecdccf;border-radius:14px;background:#fbf6f2;text-align:center;font-family:\'Plus Jakarta Sans\',sans-serif">'
       . '<p style="margin:0 0 12px;font-size:15px">Love shopping with us? A quick Google review helps a lot 🌷</p>'
       . '<a href="' . esc_url(YK_REVIEW_URL) . '" target="_blank" rel="noopener" style="display:inline-block;background:#8a5a2b;color:#fff;padding:12px 24px;border-radius:30px;text-decoration:none;font-weight:600">⭐ Review us on Google</a></div>';
}, 5);

// 3) Tombol WA di halaman edit order admin
function yk_review_wa_link($order) {
    $ph = preg_replace('/\D+/', '', (string) $order->get_billing_phone());
    if (!$ph) return '';
    if (strpos($ph, '0') === 0) $ph = '62' . substr($ph, 1);
    elseif (strpos($ph, '8') === 0) $ph = '62' . $ph;
    $nm = $order->get_billing_first_name();
    $msg = 'Halo' . ($nm ? ' ' . $nm : '') . ', terima kasih sudah order di Yulika Florist (#' . $order->get_order_number() . ') 💐' . "\n\n"
         . 'Semoga bunganya berkesan. Boleh minta waktu 30 detik untuk kasih ulasan di Google? Sangat membantu kami 🙏' . "\n" . YK_REVIEW_URL;
    return 'https://wa.me/' . $ph . '?text=' . rawurlencode($msg);
}
add_action('add_meta_boxes', function () {
    foreach (array('shop_order', 'woocommerce_page_wc-orders') as $screen) {
        add_meta_box('yk_review_box', '⭐ Google Review', function ($post_or_order) {
            $order = ($post_or_order instanceof WC_Order) ? $post_or_order : wc_get_order($post_or_order->ID);
            if (!$order) return;
            $link = yk_review_wa_link($order);
            $sent = $order->get_meta('_yk_review_sent');
            echo '<p style="margin:0 0 8px">Email review: ' . ($sent ? 'terkirim ' . esc_html($sent) : 'otomatis saat status <b>Completed</b>') . '</p>';
            if ($link) echo '<a class="button button-primary" style="width:100%;text-align:center;background:#25D366;border-color:#1ea952" target="_blank" href="' . esc_url($link) . '">Kirim link review via WA</a>';
            else echo '<p>Nomor HP pembeli kosong.</p>';
        }, $screen, 'side', 'high');
    }
});
