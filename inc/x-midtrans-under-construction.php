<?php
// Midtrans masih proses aktivasi: opsi tetap tampil di checkout, tapi tidak bisa dipilih + label "Under Construction".
// Hapus file ini kalau Midtrans sudah siap dipakai. (17 Okt 2026)
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function yk_midtrans_uc_ids() {
	return array( 'midtrans' );
}

/* Server: tolak order yang tetap mengirim Midtrans (mis. lewat kiriman form manual). */
add_action( 'woocommerce_after_checkout_validation', function ( $data, $errors ) {
	if ( isset( $data['payment_method'] ) && in_array( $data['payment_method'], yk_midtrans_uc_ids(), true ) ) {
		$errors->add( 'payment', 'Payment via Midtrans (QRIS, GoPay, ShopeePay & Virtual Account) is under construction. Please choose another payment method.' );
	}
}, 10, 2 );

/* Kalau sesi masih menyimpan Midtrans sebagai pilihan, pindahkan ke metode lain. */
add_action( 'woocommerce_checkout_init', function () {
	if ( function_exists( 'WC' ) && WC()->session && in_array( WC()->session->get( 'chosen_payment_method' ), yk_midtrans_uc_ids(), true ) ) {
		WC()->session->set( 'chosen_payment_method', '' );
	}
} );

/* Tampilan + kunci di checkout. */
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url() ) {
		return;
	}
	?>
<style>
.yk-uc{opacity:.55;cursor:not-allowed!important}
.yk-uc label{cursor:not-allowed!important;pointer-events:none}
.yk-uc input[type=radio]{pointer-events:none}
.yk-uc .payment_box{display:none!important}
.yk-uc-badge{display:inline-block;margin-left:8px;padding:2px 8px;border-radius:999px;background:#f1e6d8;color:#8a5a2b;font-size:11px;font-weight:600;letter-spacing:.02em;vertical-align:middle;white-space:nowrap}
</style>
<script>
(function(){
	var IDS = <?php echo wp_json_encode( yk_midtrans_uc_ids() ); ?>;
	function lock(){
		var moved = false;
		IDS.forEach(function(id){
			var input = document.getElementById('payment_method_' + id);
			if (!input) return;
			var li = input.closest('li') || input.parentNode;
			li.classList.add('yk-uc');
			li.setAttribute('aria-disabled','true');
			li.title = 'Under Construction';
			input.disabled = true;
			if (input.checked) { input.checked = false; moved = true; }
			var label = li.querySelector('label[for="payment_method_' + id + '"]');
			if (label && !label.querySelector('.yk-uc-badge')) {
				var b = document.createElement('span');
				b.className = 'yk-uc-badge';
				b.textContent = 'Under Construction';
				label.appendChild(b);
			}
		});
		var any = document.querySelector('input[name="payment_method"]:checked:not(:disabled)');
		if (!any || moved) {
			var first = document.querySelector('input[name="payment_method"]:not(:disabled)');
			if (first) {
				first.checked = true;
				if (window.jQuery) { jQuery(first).trigger('click'); }
			}
		}
	}
	document.addEventListener('DOMContentLoaded', lock);
	if (window.jQuery) { jQuery(document.body).on('updated_checkout payment_method_selected', lock); }
	lock();
})();
</script>
	<?php
}, 99 );
