<?php
// Dari Code Snippets #36 "#36 Kontak JJ & CS: WA melayang + footer" — scope: global

// #36 Kontak JJ & CS: pilihan di tombol WA melayang + blok kontak di footer (12 Okt 2026)
// 30 Okt 2026 (Jess): nomor CS lama dihapus; CS sekarang Mia, Yati, Umu.
function yk_contacts() {
    return array(
        'office' => array('label' => 'Office', 'num' => '021-453-2630', 'tel' => '+62214532630'),
        'jj'     => array('label' => 'JJ', 'num' => '0813-1588-1538', 'wa' => '6281315881538'),
        'cs'     => array(
            array('name' => 'Mia',  'num' => '0877-8196-8243', 'wa' => '6287781968243'),
            array('name' => 'Yati', 'num' => '0877-7776-3020', 'wa' => '6287777763020'),
            array('name' => 'Umu',  'num' => '0812-8134-5402', 'wa' => '6281281345402'),
        ),
        'addr'   => 'Jl. Boulevard Raya Blok WA 2 No. 12–14, Kelapa Gading Permai, Jakarta Utara 14240',
        'maps'   => 'https://maps.google.com/?cid=6874201013014114178',
        'hours'  => '08.00–19.00',
    );
}

// 1) Tombol WA melayang: tap -> pilih JJ atau CS
add_action('wp_footer', function () {
    if (is_admin()) return; $c = yk_contacts(); ?>
<style>
.yk-wa-pick{position:fixed;right:16px;bottom:84px;z-index:99991;background:#fff;border-radius:16px;box-shadow:0 10px 30px rgba(60,40,20,.22);padding:12px;width:236px;font-family:'Plus Jakarta Sans',Montserrat,sans-serif;opacity:0;transform:translateY(8px);pointer-events:none;transition:opacity .15s,transform .15s}
.yk-wa-pick.on{opacity:1;transform:none;pointer-events:auto}
.yk-wa-pick p{margin:0 4px 8px;font-size:12.5px;color:#7a6a5e}
.yk-wa-pick a{display:flex;align-items:center;gap:10px;padding:10px;border-radius:12px;text-decoration:none!important;color:#2b2522!important}
.yk-wa-pick a:hover,.yk-wa-pick a:active{background:#f1f8f1}
.yk-wa-pick i{flex:0 0 36px;height:36px;border-radius:50%;background:#25d366;color:#fff;display:flex;align-items:center;justify-content:center;font-style:normal;font-weight:700;font-size:13px}
.yk-wa-pick b{display:block;font-size:14px}
.yk-wa-pick small{font-size:12.5px;color:#6b6b6b}
</style>
<div class="yk-wa-pick" role="dialog" aria-label="Chat with Yulika Florist">
  <p>Chat with us on WhatsApp</p>
  <a data-wa="<?php echo esc_attr($c['jj']['wa']); ?>" href="https://wa.me/<?php echo esc_attr($c['jj']['wa']); ?>" target="_blank" rel="noopener"><i>JJ</i><span><b>JJ</b><small><?php echo esc_html($c['jj']['num']); ?></small></span></a>
  <?php foreach ($c['cs'] as $cs) : ?>
  <a data-wa="<?php echo esc_attr($cs['wa']); ?>" href="https://wa.me/<?php echo esc_attr($cs['wa']); ?>" target="_blank" rel="noopener"><i>CS</i><span><b>CS <?php echo esc_html($cs['name']); ?></b><small><?php echo esc_html($cs['num']); ?></small></span></a>
  <?php endforeach; ?>
</div>
<script>(function(){
  var p=document.querySelector('.yk-wa-pick');if(!p)return;
  document.addEventListener('click',function(e){
    var b=e.target.closest&&e.target.closest('a.yk-wa');
    if(b){e.preventDefault();e.stopPropagation();
      var q='';try{q=new URL(b.href).searchParams.get('text')||'';}catch(x){}
      [].forEach.call(p.querySelectorAll('a[data-wa]'),function(a){a.href='https://wa.me/'+a.getAttribute('data-wa')+(q?'?text='+encodeURIComponent(q):'');});
      p.classList.toggle('on');return;}
    if(p.classList.contains('on')&&!e.target.closest('.yk-wa-pick'))p.classList.remove('on');
  },true);
})();</script>
<?php }, 40);

// 2) Blok kontak di footer semua halaman
function yk_footer_contact_html() {
    $c = yk_contacts();
    $wa = function ($label, $num, $wa) { return '<a href="https://wa.me/' . esc_attr($wa) . '" target="_blank" rel="noopener"><span class="yk-fc-ic wa">WA</span>' . esc_html($label) . ' · ' . esc_html($num) . '</a>'; };
    $cs = '';
    foreach ($c['cs'] as $p) {
        $cs .= $wa('CS ' . $p['name'], $p['num'], $p['wa']);
    }
    return '<section class="yk-fc" aria-label="Contact Yulika Florist">'
        . '<div class="yk-fc-col"><b>Visit Our Store</b><a href="' . esc_url($c['maps']) . '" target="_blank" rel="noopener"><span class="yk-fc-ic">📍</span>' . esc_html($c['addr']) . '</a>'
        . '<span class="yk-fc-row"><span class="yk-fc-ic">🕗</span>Opening hours ' . esc_html($c['hours']) . '</span></div>'
        . '<div class="yk-fc-col"><b>Contact Us</b><a href="tel:' . esc_attr($c['office']['tel']) . '"><span class="yk-fc-ic">📞</span>Office · ' . esc_html($c['office']['num']) . '</a>' . $wa('JJ', $c['jj']['num'], $c['jj']['wa']) . $cs . '</div>'
        . '</section>';
}
add_action('elementor/theme/after_do_footer', function () { echo yk_footer_contact_html(); });
add_action('wp_head', function () { ?>
<style>
.yk-fc{display:grid;gap:18px;margin:0;padding:22px max(20px,calc((100% - 1100px)/2)) 26px;background:#F3E2D8;font-family:'Plus Jakarta Sans',Montserrat,sans-serif;font-size:14px;line-height:1.5;color:#3a3330}
.yk-fc b{display:block;font-family:'Sorts Mill Goudy',Georgia,serif;font-weight:400;font-size:19px;color:#2b2522;margin-bottom:8px}
.yk-fc a,.yk-fc-row{display:flex;align-items:flex-start;gap:8px;color:#3a3330!important;text-decoration:none!important;padding:5px 0}
.yk-fc a:hover{color:#8a5a2b!important}
.yk-fc-ic{flex:0 0 22px;text-align:center}
.yk-fc-ic.wa{flex:0 0 26px;height:20px;border-radius:10px;background:#25d366;color:#fff;font-size:10px;font-weight:700;line-height:20px;margin-right:-2px}
@media(min-width:768px){.yk-fc{grid-template-columns:1.3fr 1fr;padding:26px 24px 30px}}
.yk-fc-wrap{background:#F3E2D8}
</style>
<?php }, 30);

// Link sosial media resmi (12 Okt 2026)
add_action('template_redirect', function () {
    if (is_admin() || wp_doing_ajax()) return;
    ob_start(function ($html) {
        $map = array(
            '#href=(["\'])https?://(www\.)?instagram\.com/?\1#i' => 'href=$1https://www.instagram.com/yulikaflorist/$1',
            '#href=(["\'])https?://(www\.)?facebook\.com/?\1#i'  => 'href=$1https://www.facebook.com/YulikaFlorist/$1',
            '#href=(["\'])https?://(www\.)?tiktok\.com/?\1#i'    => 'href=$1https://www.tiktok.com/@yulikaflorist_$1',
        );
        return preg_replace(array_keys($map), array_values($map), $html);
    });
}, 1);
function yk_social_fix(){}
