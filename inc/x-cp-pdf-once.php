<?php
/**
 * Sekali jalan: salin company profile v1.2 dari assets/ ke file lampiran Media Library #990615.
 * Unggahan lewat browser terpotong (544 KB dari 4,08 MB), jadi file disalin di sisi server.
 * Modul ini dan assets/ dihapus lagi setelah salinan terverifikasi.
 */
add_action( 'admin_init', function () {
	if ( get_option( 'yk_cp_pdf_v12' ) || wp_doing_ajax() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$name = 'yulika-florist-company-profile-v1-2.pdf';
	$src  = YK_CORE_DIR . '/assets/' . $name;
	$dst  = get_attached_file( 990615 );
	if ( ! $dst || basename( $dst ) !== $name || ! is_readable( $src ) || 4080396 !== filesize( $src ) ) {
		return;
	}
	if ( ! copy( $src, $dst ) ) {
		return;
	}
	clearstatcache();
	$size = (int) filesize( $dst );
	if ( 4080396 === $size ) {
		$meta = wp_get_attachment_metadata( 990615 );
		if ( ! is_array( $meta ) ) {
			$meta = array();
		}
		$meta['filesize'] = $size;
		wp_update_attachment_metadata( 990615, $meta );
	}
	update_option( 'yk_cp_pdf_v12', $size, false );
} );
