<?php
// Dari Code Snippets #39 "#39 Checkout Surprise? (Recipient Details)" — scope: global

// #39 Checkout: opsi Surprise? di bawah "Recipient Details" (13 Okt 2026)
function yk_surprise_label($v, $lang = 'en') {
	if ($v === 'yes') return $lang === 'id' ? 'YA – surprise, kurir JANGAN hubungi penerima (hubungi pengirim)' : 'Yes – surprise (recipient doesn\'t know)';
	if ($v === 'no')  return $lang === 'id' ? 'TIDAK – kurir boleh hubungi penerima dulu' : 'No – recipient already knows';
	return '';
}

add_action('woocommerce_after_checkout_shipping_form', function () { ?>
<div class="yk-sp" id="yk-sp">
 <div class="yk-sp-t">Surprise?</div>
 <div class="yk-sp-o">
  <label><input type="radio" name="yk_surprise" value="yes" checked><span><b>Yes</b><small>recipient doesn't know yet</small></span></label>
  <label><input type="radio" name="yk_surprise" value="no"><span><b>No</b><small>recipient already knows</small></span></label>
 </div>
 <div class="yk-sp-n" data-v="yes">Our courier won't contact the recipient. Please keep your WhatsApp number active.</div>
 <div class="yk-sp-n" data-v="no" hidden>Our courier may contact the recipient first.</div>
</div>
<style>
.yk-sp{margin:4px 0 16px}
.yk-sp-t{font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#1d1d1f;margin:0 0 8px}
.yk-sp-o{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.yk-sp-o label{margin:0!important;cursor:pointer;display:block}
.yk-sp-o input{position:absolute;opacity:0;width:1px;height:1px;pointer-events:none}
.yk-sp-o span{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;min-height:64px;padding:10px 8px;border:1.5px solid #e3d9cc;border-radius:12px;background:#fff;transition:border-color .15s,background .15s}
.yk-sp-o b{font-size:16px;color:#1d1d1f;line-height:1.2}
.yk-sp-o small{font-weight:400!important;font-size:12.5px;color:#6e6e73;line-height:1.3;margin-top:2px}
.yk-sp-o input:checked+span{border-color:#a0784a;background:#f8f4ee;box-shadow:inset 0 0 0 1px #a0784a}
.yk-sp-o input:checked+span b{color:#8a5a2b}
.yk-sp-o input:focus-visible+span{outline:2px solid #a0784a;outline-offset:2px}
.yk-sp-n{margin-top:10px;padding:10px 12px;border-left:3px solid #a0784a;background:#f8f4ee;border-radius:0 8px 8px 0;font-size:13.5px;line-height:1.45;color:#5a3d22}
.yk-sp-n[hidden]{display:none!important}
</style>
<script data-no-optimize="1" data-no-defer="1" data-cfasync="false">
(function(){
 function place(){var b=document.getElementById('yk-sp'),h=document.querySelector('.yk-rcp-h');if(b&&h&&h.nextElementSibling!==b)h.parentNode.insertBefore(b,h.nextSibling);}
 place();document.addEventListener('DOMContentLoaded',place);if(window.jQuery)jQuery(document.body).on('updated_checkout',place);
 document.addEventListener('change',function(e){if(e.target.name!=='yk_surprise')return;[].forEach.call(document.querySelectorAll('.yk-sp-n'),function(n){n.hidden=n.getAttribute('data-v')!==e.target.value;});});
})();
</script>
<?php });

add_action('woocommerce_checkout_create_order', function ($order) {
	$v = isset($_POST['yk_surprise']) ? sanitize_key(wp_unslash($_POST['yk_surprise'])) : '';
	foreach ($order->get_shipping_methods() as $m) {
		if (false !== strpos($m->get_method_id(), 'pickup')) return;
	}
	if (in_array($v, array('yes', 'no'), true)) $order->update_meta_data('_yk_surprise', $v);
});

add_action('woocommerce_admin_order_data_after_shipping_address', function ($order) {
	$v = $order->get_meta('_yk_surprise');
	if (!$v) return;
	$c = $v === 'yes' ? '#b3261e' : '#2e7d32';
	echo '<p style="margin:10px 0 0;padding:8px 10px;border-left:4px solid ' . $c . ';background:#faf7f2"><strong>🎁 Surprise:</strong> ' . esc_html(yk_surprise_label($v, 'id')) . '</p>';
});

add_action('woocommerce_email_after_order_table', function ($order, $sent_to_admin) {
	$v = $order->get_meta('_yk_surprise');
	if (!$v) return;
	echo '<p style="margin:12px 0"><strong>🎁 Surprise:</strong> ' . esc_html(yk_surprise_label($v, $sent_to_admin ? 'id' : 'en')) . '</p>';
}, 20, 2);

add_action('woocommerce_order_details_after_order_table', function ($order) {
	$v = $order->get_meta('_yk_surprise');
	if ($v) echo '<p><strong>Surprise:</strong> ' . esc_html(yk_surprise_label($v)) . '</p>';
});
