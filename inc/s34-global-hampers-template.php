<?php
// Dari Code Snippets #34 "#34 Hampers pakai template arsip standar" — scope: global

// #34 Hampers pakai template arsip produk standar (4964) menggantikan template lama 988657 (12 Okt 2026)
add_filter('elementor/theme/get_location_templates/template_id', function ($id) {
    return ((int) $id === 988657) ? 4964 : $id;
});
