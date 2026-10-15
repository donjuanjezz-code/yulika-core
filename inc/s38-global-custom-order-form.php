<?php
// Dari Code Snippets #38 "#38 Custom Order form -> WhatsApp JJ" — scope: global

// #38 Custom Order: form compact -> simpan (CPT) + foto referensi -> WhatsApp JJ (12 Okt 2026)
if (!defined('YK_CO_WA')) define('YK_CO_WA', '6281315881538');

add_action('init', function () {
	register_post_type('yk_custom_order', array(
		'labels' => array('name' => 'Custom Orders', 'singular_name' => 'Custom Order', 'menu_name' => 'Custom Orders'),
		'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'menu_position' => 56, 'menu_icon' => 'dashicons-art',
		'supports' => array('title'), 'map_meta_cap' => true, 'capabilities' => array('create_posts' => 'do_not_allow'),
	));
});

function yk_co_message($pid, $d, $urls) {
	$days = array('Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu');
	$mon  = array('','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des');
	$t  = strtotime($d['date'] . ' 12:00:00');
	$dt = $t ? $days[(int) date('w', $t)] . ', ' . (int) date('j', $t) . ' ' . $mon[(int) date('n', $t)] . ' ' . date('Y', $t) : $d['date'];
	if ($d['time'] !== '') $dt .= ' · ' . $d['time'];
	$L = array('Halo Yulika Florist, saya mau *Custom Order* 🌷', 'No. ref: *CO-' . $pid . '*', '');
	$rows = array(
		array('👤 Nama', $d['name']), array('📱 WA', $d['wa']),
		array('🎉 Acara/Tema', $d['occ']), array('💐 Rangkaian', $d['type']),
		array('🌸 Bunga', $d['flowers']), array('🎨 Warna bunga', $d['fcolor']),
		array('🎀 Warna kemasan', $d['wcolor']), array('💰 Perkiraan budget', $d['budget']),
		array('📅 Tanggal acara', $dt), array('🚚 Pengiriman', trim($d['method'] . ($d['area'] !== '' ? ' – ' . $d['area'] : ''))),
		array('📝 Catatan', $d['notes']),
	);
	foreach ($rows as $r) if ($r[1] !== '') $L[] = $r[0] . ': ' . $r[1];
	if ($urls) { $L[] = ''; $L[] = '🖼️ Foto referensi:'; foreach ($urls as $u) $L[] = $u; }
	return implode("\n", $L);
}

function yk_co_submit() {
	if (!empty($_POST['website'])) wp_send_json_error(array('m' => 'Error'), 400);
	$ip = !empty($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : $_SERVER['REMOTE_ADDR'];
	$rk = 'yk_co_rl_' . md5($ip);
	$n  = (int) get_transient($rk);
	if ($n >= 6) wp_send_json_error(array('m' => 'Too many attempts. Please chat us directly on WhatsApp.'), 429);
	set_transient($rk, $n + 1, HOUR_IN_SECONDS);

	$d = array();
	foreach (array('occ','type','flowers','fcolor','wcolor','budget','date','time','method','area','name','wa','notes') as $k) {
		$v = isset($_POST[$k]) ? sanitize_textarea_field(wp_unslash($_POST[$k])) : '';
		$d[$k] = trim(mb_substr($v, 0, $k === 'notes' ? 800 : 250));
	}
	$wa = preg_replace('/\D/', '', $d['wa']);
	if (strpos($wa, '62') === 0) $wa = '0' . substr($wa, 2);
	if (strpos($wa, '8') === 0) $wa = '0' . $wa;
	$d['wa'] = $wa;
	if ($d['name'] === '' || strlen($wa) < 9 || strlen($wa) > 15 || $d['occ'] === '' || $d['type'] === '' || $d['budget'] === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['date'])) {
		wp_send_json_error(array('m' => 'Please complete the required fields.'), 400);
	}

	$urls = array();
	if (!empty($_FILES['img']) && is_array($_FILES['img']['name'])) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$dirf = function ($u) { $u['subdir'] = '/custom-order'; $u['path'] = $u['basedir'] . $u['subdir']; $u['url'] = $u['baseurl'] . $u['subdir']; return $u; };
		add_filter('upload_dir', $dirf);
		$map = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp');
		$cnt = min(3, count($_FILES['img']['name']));
		for ($i = 0; $i < $cnt; $i++) {
			if ((int) $_FILES['img']['error'][$i] !== UPLOAD_ERR_OK || (int) $_FILES['img']['size'][$i] > 8 * 1024 * 1024) continue;
			$info = @getimagesize($_FILES['img']['tmp_name'][$i]);
			if (!$info || !isset($map[$info['mime']])) continue;
			$file = array(
				'name' => 'ref-' . gmdate('ymd') . '-' . strtolower(wp_generate_password(10, false)) . '.' . $map[$info['mime']],
				'type' => $info['mime'], 'tmp_name' => $_FILES['img']['tmp_name'][$i], 'error' => 0, 'size' => $_FILES['img']['size'][$i],
			);
			$r = wp_handle_upload($file, array('test_form' => false, 'mimes' => array('jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp')));
			if (!empty($r['url'])) $urls[] = $r['url'];
		}
		remove_filter('upload_dir', $dirf);
	}

	$pid = wp_insert_post(array('post_type' => 'yk_custom_order', 'post_status' => 'private', 'post_title' => $d['name'] . ' – ' . $d['occ'] . ' – ' . $d['date']));
	if (!$pid || is_wp_error($pid)) wp_send_json_error(array('m' => 'Could not save. Please chat us directly on WhatsApp.'), 500);
	foreach ($d as $k => $v) update_post_meta($pid, '_yk_' . $k, $v);
	update_post_meta($pid, '_yk_imgs', $urls);
	$msg = yk_co_message($pid, $d, $urls);
	update_post_meta($pid, '_yk_msg', $msg);
	@wp_mail(get_option('admin_email'), 'Custom Order CO-' . $pid . ' – ' . $d['name'], $msg);
	wp_send_json_success(array('id' => $pid, 'wa' => 'https://wa.me/' . YK_CO_WA . '?text=' . rawurlencode($msg)));
}
add_action('wp_ajax_yk_co_submit', 'yk_co_submit');
add_action('wp_ajax_nopriv_yk_co_submit', 'yk_co_submit');

add_action('add_meta_boxes_yk_custom_order', function () {
	add_meta_box('yk_co_d', 'Detail Custom Order', function ($p) {
		echo '<pre style="white-space:pre-wrap;font:14px/1.6 -apple-system,Arial,sans-serif;margin:0">' . esc_html(get_post_meta($p->ID, '_yk_msg', true)) . '</pre>';
		foreach ((array) get_post_meta($p->ID, '_yk_imgs', true) as $u) if ($u) echo '<a href="' . esc_url($u) . '" target="_blank"><img src="' . esc_url($u) . '" style="width:160px;height:240px;object-fit:cover;margin:8px 8px 0 0;border-radius:8px"></a>';
		$wa = get_post_meta($p->ID, '_yk_wa', true);
		if ($wa) echo '<p><a class="button button-primary" target="_blank" href="https://wa.me/62' . esc_attr(ltrim($wa, '0')) . '">💬 Chat pemesan (' . esc_html($wa) . ')</a></p>';
	}, 'yk_custom_order', 'normal', 'high');
});

function yk_co_chips($name, $opts, $multi = false) {
	$h = '<div class="ykco-chips" data-name="' . esc_attr($name) . '">';
	foreach ($opts as $o) {
		$lab = is_array($o) ? $o[0] : $o; $col = is_array($o) ? $o[1] : '';
		$h .= '<label class="ykco-chip"><input type="' . ($multi ? 'checkbox' : 'radio') . '" name="' . esc_attr($name) . '" value="' . esc_attr($lab) . '"><span>' . ($col ? '<i style="background:' . esc_attr($col) . '"></i>' : '') . esc_html($lab) . '</span></label>';
	}
	return $h . '</div>';
}

add_shortcode('yk_custom_order', function () {
	$sw_f = array(array('White','#fff'),array('Cream','#f3e9d2'),array('Blush','#f6d5d5'),array('Pink','#ef8fb0'),array('Red','#c8102e'),array('Peach','#f6b08f'),array('Yellow','#f5d04c'),array('Lavender','#b9a3d6'),array('Purple','#6b3fa0'),array('Orange','#f08a3c'),array('Mixed','linear-gradient(135deg,#ef8fb0,#f5d04c,#b9a3d6)'),"Florist's choice");
	$sw_w = array(array('White','#fff'),array('Cream','#f3e9d2'),array('Blush Pink','#f6d5d5'),array('Black','#222'),array('Kraft Brown','#a47148'),array('Navy','#1f2a44'),array('Champagne','#d8c08a'),'Match the flowers');
	ob_start(); ?>
<div class="ykco">
<p class="ykco-lead">Design your own arrangement with our florists. Fill in the brief (± 1 minute) — we'll reply on WhatsApp with design ideas &amp; price.</p>
<ol class="ykco-steps"><li><b>1</b> Fill the brief</li><li><b>2</b> Quote on WhatsApp</li><li><b>3</b> Confirm &amp; pay</li></ol>
<form id="ykco" novalidate>
<input class="ykco-hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
<div class="ykco-sec"><h3><em>1</em>The occasion</h3>
<span class="ykco-l">Occasion / theme *</span>
<?php echo yk_co_chips('occ', array('Birthday','Love & Anniversary','Wedding','Graduation','Grand Opening','Congratulations','Sympathy','Corporate / Event','Other')); ?>
<div class="ykco-other" data-other="occ"><input type="text" placeholder="Tell us the occasion"></div>
<span class="ykco-l">Arrangement type *</span>
<?php echo yk_co_chips('type', array('Hand Bouquet','Flower Box','Basket','Vase / Table','Standing Flower','Flower Board','Hampers','Event Decor','Other')); ?>
<div class="ykco-other" data-other="type"><input type="text" placeholder="Describe the arrangement"></div>
</div>
<div class="ykco-sec"><h3><em>2</em>Design &amp; budget</h3>
<span class="ykco-l">Flowers <small>(pick any)</small></span>
<?php echo yk_co_chips('flowers', array('Rose','Lily','Hydrangea','Tulip','Orchid','Peony','Lisianthus','Gerbera','Sunflower',"Baby's Breath","Florist's choice"), true); ?>
<span class="ykco-l">Flower colour <small>(pick any)</small></span>
<?php echo yk_co_chips('fcolor', $sw_f, true); ?>
<span class="ykco-l">Wrapping / packaging colour</span>
<?php echo yk_co_chips('wcolor', $sw_w, true); ?>
<span class="ykco-l">Estimated budget *</span>
<?php echo yk_co_chips('budget', array('< Rp500rb','Rp500rb – 1 jt','Rp1 – 2 jt','Rp2 – 3 jt','Rp3 – 5 jt','> Rp5 jt','Not sure yet')); ?>
</div>
<div class="ykco-sec"><h3><em>3</em>Date &amp; delivery</h3>
<div class="ykco-row"><div><label class="ykco-l" for="ykco-date">Event date *</label><input id="ykco-date" type="date" name="date" required></div>
<div><label class="ykco-l" for="ykco-time">Time</label><select id="ykco-time" name="time"><option value="">Any time</option><option>Morning 08–11</option><option>Midday 11–15</option><option>Afternoon 15–19</option></select></div></div>
<div class="ykco-dnote">For custom designs we recommend ordering at least 2 days ahead — we'll confirm flower availability on WhatsApp.</div>
<span class="ykco-l">Delivery or pick up</span>
<?php echo yk_co_chips('method', array('Delivery','Pick up at store')); ?>
<div class="ykco-area" style="display:none"><input type="text" name="area" placeholder="Delivery area / address (e.g. Kelapa Gading, PIK 2)"></div>
</div>
<div class="ykco-sec"><h3><em>4</em>Reference &amp; notes <small style="font-weight:400;color:#999">(optional)</small></h3>
<span class="ykco-l">Reference photos <small>(up to 3 — Pinterest / IG screenshots welcome)</small></span>
<div class="ykco-up"><div class="ykco-pv"></div><label class="ykco-add" for="ykco-file"><b>+</b>Add photo</label></div>
<input id="ykco-file" type="file" accept="image/*" multiple hidden>
<label class="ykco-l" for="ykco-notes">Notes</label>
<textarea id="ykco-notes" name="notes" placeholder="Size, card message, recipient, anything else…"></textarea>
</div>
<div class="ykco-sec"><h3><em>5</em>Your details</h3>
<div class="ykco-row"><div><label class="ykco-l" for="ykco-name">Your name *</label><input id="ykco-name" type="text" name="name" autocomplete="name" required></div>
<div><label class="ykco-l" for="ykco-wa">WhatsApp number *</label><input id="ykco-wa" type="tel" name="wa" inputmode="tel" autocomplete="tel" placeholder="08xx" required></div></div>
</div>
<button class="ykco-send" type="submit"><svg width="20" height="20" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.2c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2.1 1-2.4c.3-.3.6-.3.8-.3h.6c.2 0 .4 0 .6.5l.9 2.1c.1.2.1.4 0 .5l-.4.6-.4.4c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.4 2.4 1.5.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.6-.1l2 1c.3.1.4.2.5.3 0 .1 0 .7-.3 1.3z"/></svg>Send brief via WhatsApp</button>
<p class="ykco-err" role="alert"></p>
<p class="ykco-fine">WhatsApp opens with your brief pre-filled — just tap send. Your data is only used to process this order (<a href="/privacy-policy/">Privacy Policy</a>).</p>
</form>
<div class="ykco-done"><p style="font-size:34px;margin:0">💐</p><h3 style="margin:6px 0">Brief ready — <span class="ykco-ref"></span></h3><p style="color:#555;font-size:14px;margin:0 0 14px">WhatsApp should open now. If it didn't, tap the button below.</p><a class="ykco-send ykco-walink" href="#" style="text-decoration:none">Open WhatsApp</a></div>
</div>
<style>
.ykco{max-width:640px;margin:0 auto;padding:0 0 40px;font-family:'Plus Jakarta Sans',Montserrat,-apple-system,Arial,sans-serif;color:#1d1d1f}
.ykco-lead{text-align:center;font-size:15px;line-height:1.5;color:#555;margin:0 0 12px}
.ykco-steps{display:flex;flex-wrap:wrap;gap:6px;justify-content:center;margin:0 0 18px!important;padding:0!important;list-style:none!important;font-size:12px;color:#6e6e73}
.ykco-steps li{background:#f8f4ee;border-radius:20px;padding:6px 10px;margin:0!important;list-style:none!important}
.ykco-steps b{color:#8a5a2b;margin-right:2px}
.ykco-sec{background:#fff;border:1px solid #eee5da;border-radius:14px;padding:16px;margin:0 0 12px}
.ykco-sec h3{font-family:inherit!important;font-size:16px!important;font-weight:700!important;margin:0 0 12px!important;color:#1d1d1f!important;letter-spacing:0!important;text-transform:none!important;line-height:1.3!important}
.ykco-sec h3 em{font-style:normal;display:inline-flex;width:22px;height:22px;border-radius:50%;background:#a0784a;color:#fff;font-size:12px;align-items:center;justify-content:center;margin-right:8px;vertical-align:1px}
.ykco-l{display:block;font-size:13px;font-weight:600;margin:14px 0 7px;color:#333}
.ykco-sec>.ykco-l:first-of-type,.ykco-row .ykco-l{margin-top:0}
.ykco-l small{font-weight:400;color:#999}
.ykco-chips{display:flex;flex-wrap:wrap;gap:6px}
.ykco-chip{position:relative;cursor:pointer;margin:0!important}
.ykco-chip input{position:absolute;opacity:0;width:1px;height:1px;pointer-events:none}
.ykco-chip span{display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border:1px solid #e3d9cc;border-radius:20px;font-size:13px;line-height:1.15;background:#fff;color:#333;transition:background .15s,border-color .15s}
.ykco-chip input:checked+span{background:#a0784a;border-color:#a0784a;color:#fff}
.ykco-chip input:focus-visible+span{outline:2px solid #a0784a;outline-offset:2px}
.ykco-chip i{width:14px;height:14px;border-radius:50%;border:1px solid rgba(0,0,0,.18);display:inline-block;flex:0 0 14px}
.ykco input[type=text],.ykco input[type=tel],.ykco input[type=date],.ykco select,.ykco textarea{width:100%!important;height:46px;border:1px solid #e3d9cc!important;border-radius:10px!important;padding:0 12px!important;font-size:16px!important;background:#fff!important;box-sizing:border-box;font-family:inherit;color:#1d1d1f;margin:0!important}
.ykco textarea{height:84px;padding:10px 12px!important;resize:vertical}
.ykco-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.ykco-other,.ykco-area{margin-top:8px}
.ykco-other{display:none}
.ykco-dnote{display:none;font-size:12px;line-height:1.45;color:#8a5a2b;background:#fbf3e8;border-radius:8px;padding:7px 10px;margin-top:8px}
.ykco-up{display:flex;gap:8px;flex-wrap:wrap}
.ykco-pv{display:contents}
.ykco-add{width:84px;height:126px;border:1.5px dashed #c9b49a;border-radius:10px;display:flex;flex-direction:column;align-items:center;justify-content:center;font-size:12px;color:#8a5a2b;cursor:pointer;text-align:center;gap:2px;margin:0!important;background:#fdfaf6}
.ykco-add b{font-size:26px;line-height:1;font-weight:400}
.ykco-th{position:relative;width:84px;height:126px}
.ykco-th img{width:100%;height:100%;object-fit:cover;border-radius:10px!important;display:block}
.ykco-th button{position:absolute;top:4px;right:4px;width:24px;height:24px;border-radius:50%;border:0;background:rgba(0,0,0,.6);color:#fff;font-size:16px;line-height:24px;padding:0;cursor:pointer}
.ykco-send{width:100%;display:flex;align-items:center;justify-content:center;gap:8px;height:56px;border:0;border-radius:30px;background:#a0784a;color:#fff!important;font-family:inherit;font-size:17px;font-weight:700;cursor:pointer;box-shadow:0 3px 10px rgba(160,120,74,.35);margin-top:4px}
.ykco-send:hover{background:#8a6338}
.ykco-send:disabled{opacity:.6;cursor:wait}
html body .ykco button.ykco-send,html body .ykco a.ykco-send,html body .ykco button.ykco-send:focus,html body .ykco a.ykco-send:focus{background:#a0784a!important;background-image:none!important;color:#fff!important;border:0!important;text-transform:none!important;letter-spacing:0!important;padding:0 20px!important;opacity:1;border-radius:30px!important;height:56px!important;font-size:17px!important;font-weight:700!important;line-height:1!important;box-shadow:0 3px 10px rgba(160,120,74,.35)!important}
html body .ykco button.ykco-send:hover,html body .ykco a.ykco-send:hover{background:#8a6338!important;color:#fff!important}
html body .ykco button.ykco-send svg{fill:#fff!important;flex:0 0 20px}
html body .ykco button.ykco-send:disabled{opacity:.6!important}
.ykco-err{color:#c0392b;font-size:13px;text-align:center;margin:8px 0 0;min-height:1em}
.ykco-fine{text-align:center;font-size:12px;color:#888;margin:6px 0 0;line-height:1.5}
.ykco-fine a{color:#8a5a2b}
.ykco-done{display:none;text-align:center;background:#f8f4ee;border-radius:14px;padding:24px 16px}
.ykco-hp{position:absolute!important;left:-9999px!important;width:1px;height:1px}
body.page-id-989900 .meta-header-inside{padding-bottom:8px!important}
body.page-id-989900 .page-header h1{font-size:clamp(34px,9vw,52px)!important;margin:0!important}
body.page-id-989900 .yk-favbar{display:none!important}
.ykco-steps{flex-wrap:nowrap;font-size:11.5px}.ykco-steps li{white-space:nowrap;padding:6px 9px}
@media(min-width:768px){.ykco-sec{padding:20px 22px}.ykco-steps{font-size:13px}}
</style>
<script data-no-optimize="1" data-no-defer="1" data-cfasync="false">
(function(){
var f=document.getElementById('ykco');if(!f)return;
var AJ=<?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>,files=[],busy=false;
var now=new Date(Date.now()+7*36e5),today=now.toISOString().slice(0,10),di=f.querySelector('[name=date]');di.min=today;
di.addEventListener('change',function(){var df=(new Date(di.value)-new Date(today))/864e5;f.querySelector('.ykco-dnote').style.display=(di.value&&df<2)?'block':'none';});
f.addEventListener('change',function(e){var t=e.target;
 if(t.name==='occ'||t.name==='type'){var o=f.querySelector('[data-other='+t.name+']');if(o){o.style.display=t.value==='Other'?'block':'none';if(t.value==='Other')o.querySelector('input').focus();}}
 if(t.name==='method'){f.querySelector('.ykco-area').style.display=t.value==='Delivery'?'block':'none';}
 if(t.name==='flowers'&&t.value==="Florist's choice"&&t.checked){[].forEach.call(f.querySelectorAll('[name=flowers]'),function(x){if(x!==t)x.checked=false;});}
 if(t.name==='flowers'&&t.value!=="Florist's choice"&&t.checked){var fc=f.querySelector('[name=flowers][value="Florist\'s choice"]');if(fc)fc.checked=false;}
});
try{var st=JSON.parse(localStorage.getItem('yk_ck_v1')||'{}')||{};if(st.s){f.querySelector('[name=name]').value=st.s.n||'';f.querySelector('[name=wa]').value=st.s.p||'';}}catch(e){}
var inp=document.getElementById('ykco-file'),pv=f.querySelector('.ykco-pv'),add=f.querySelector('.ykco-add');
inp.addEventListener('change',function(){[].slice.call(inp.files).forEach(function(x){if(files.length<3&&/^image\//.test(x.type))files.push(x);});inp.value='';render();});
function render(){pv.innerHTML='';files.forEach(function(x,i){var b=document.createElement('div');b.className='ykco-th';b.innerHTML='<img alt=""><button type="button" aria-label="Remove photo">×</button>';b.querySelector('img').src=URL.createObjectURL(x);b.querySelector('button').onclick=function(){files.splice(i,1);render();};pv.appendChild(b);});add.style.display=files.length>=3?'none':'';}
function shrink(x){return new Promise(function(res){var im=new Image();im.onload=function(){var w=im.naturalWidth,h=im.naturalHeight,s=Math.min(1,1600/Math.max(w,h)),c=document.createElement('canvas');c.width=Math.round(w*s);c.height=Math.round(h*s);var g=c.getContext('2d');g.fillStyle='#fff';g.fillRect(0,0,c.width,c.height);g.drawImage(im,0,0,c.width,c.height);c.toBlob(function(b){res(b||x);},'image/jpeg',.85);};im.onerror=function(){res(x);};im.src=URL.createObjectURL(x);});}
function vals(n){var v=[].slice.call(f.querySelectorAll('[name="'+n+'"]:checked')).map(function(x){return x.value;});var o=f.querySelector('[data-other='+n+'] input'),k=v.indexOf('Other');if(o&&k>-1&&o.value.trim())v[k]=o.value.trim();return v.join(', ');}
var err=f.querySelector('.ykco-err'),btn=f.querySelector('.ykco-send');
function fail(m,el){err.textContent=m;if(el)el.scrollIntoView({behavior:'smooth',block:'center'});}
f.addEventListener('submit',function(e){e.preventDefault();if(busy)return;err.textContent='';
 var need=[['occ','Occasion / theme'],['type','Arrangement type'],['budget','Estimated budget']];
 for(var i=0;i<need.length;i++){if(!vals(need[i][0])){return fail('Please choose: '+need[i][1],f.querySelector('[data-name='+need[i][0]+']'));}}
 if(!di.value)return fail('Please pick the event date',di);
 var nm=f.querySelector('[name=name]'),wa=f.querySelector('[name=wa]');
 if(!nm.value.trim())return fail('Please fill in your name',nm);
 if(wa.value.replace(/\D/g,'').length<9)return fail('Please fill in a valid WhatsApp number',wa);
 var fd=new FormData();fd.append('action','yk_co_submit');
 ['occ','type','flowers','fcolor','wcolor','budget','method'].forEach(function(n){fd.append(n,vals(n));});
 ['date','time','area','notes','name','wa','website'].forEach(function(n){var el=f.querySelector('[name='+n+']');fd.append(n,el?el.value:'');});
 busy=true;btn.disabled=true;btn.lastChild.textContent=files.length?'Uploading photos…':'Sending…';
 Promise.all(files.map(shrink)).then(function(bs){bs.forEach(function(b,i){fd.append('img[]',b,'ref'+i+'.jpg');});return fetch(AJ,{method:'POST',body:fd,credentials:'same-origin'});})
 .then(function(r){return r.json();}).then(function(j){
  if(!j||!j.success)throw new Error((j&&j.data&&j.data.m)||'Something went wrong.');
  try{var s=JSON.parse(localStorage.getItem('yk_ck_v1')||'{}')||{};s.s={n:nm.value.trim(),p:wa.value.trim()};localStorage.setItem('yk_ck_v1',JSON.stringify(s));}catch(x){}
  var d=document.querySelector('.ykco-done');d.querySelector('.ykco-ref').textContent='CO-'+j.data.id;d.querySelector('.ykco-walink').href=j.data.wa;
  f.style.display='none';d.style.display='block';d.scrollIntoView({block:'center'});
  if(window.gtag)gtag('event','generate_lead',{form:'custom_order'});
  setTimeout(function(){location.href=j.data.wa;},350);
  setTimeout(function(){if(window.ykLoader)ykLoader.done();},1800);
 }).catch(function(x){busy=false;btn.disabled=false;btn.lastChild.textContent='Send brief via WhatsApp';if(window.ykLoader)ykLoader.done();fail(x.message+' You can also chat us directly on WhatsApp.');});
});
document.addEventListener('visibilitychange',function(){if(!document.hidden&&window.ykLoader)ykLoader.done();});
})();
</script>
<?php return ob_get_clean();
});

/* CTA di bawah setiap halaman kategori */
add_action('elementor/theme/before_do_footer', function () {
	if (!(function_exists('is_product_category') && (is_product_category() || is_shop()))) return;
	echo '<div style="max-width:1180px;margin:8px auto 28px;padding:0 16px"><a href="' . esc_url(home_url('/custom-order/')) . '" style="display:flex;align-items:center;justify-content:space-between;gap:12px;background:#f8f4ee;border:1px solid #eee5da;border-radius:14px;padding:16px 18px;text-decoration:none;font-family:\'Plus Jakarta Sans\',Montserrat,Arial,sans-serif"><span style="line-height:1.35"><b style="display:block;color:#1d1d1f;font-size:15px;line-height:1.35;margin-bottom:2px">Can\'t find the one? 🌷</b><span style="display:block;color:#6e6e73;font-size:13px;line-height:1.4">Design it with our florists — any theme, colour &amp; budget.</span></span><span style="flex:0 0 auto;background:#a0784a;color:#fff;border-radius:22px;padding:10px 14px;font-weight:700;font-size:14px">Custom Order →</span></a></div>';
});
