<?php

return [
    /*
    | Short, human readable label per rule key. Used wherever issues are
    | grouped (dashboard, issue list) so the row does not repeat the full
    | per-page message.
    */
    'rule_labels' => [
        'title_missing' => 'Title etiketi eksik',
        'title_too_short' => 'Title çok kısa',
        'title_too_long' => 'Title 60 karakterden uzun',
        'title_duplicate' => 'Title tekrar ediyor',
        'meta_desc_missing' => 'Meta description eksik',
        'meta_desc_too_short' => 'Meta description çok kısa',
        'meta_desc_too_long' => 'Meta description çok uzun',
        'meta_desc_duplicate' => 'Meta description tekrar ediyor',
        'h1_missing' => 'H1 başlığı eksik',
        'h1_multiple' => 'Birden fazla H1 var',
        'h1_equals_title' => 'H1 ile title aynı',
        'img_alt_missing' => 'Görsel alt metni eksik',
        'img_alt_empty' => 'Görsel alt metni boş',
        'broken_internal_link' => 'Kırık iç link',
        'orphan_page' => 'Öksüz sayfa (gelen iç link yok)',
        'canonical_missing' => 'Canonical etiketi eksik',
    ],

    'severity_labels' => [
        'critical' => 'Kritik',
        'warning' => 'Uyarı',
        'notice' => 'Bilgi',
    ],

    'category_labels' => [
        'meta' => 'Meta',
        'technical' => 'Teknik',
        'content' => 'İçerik',
        'links' => 'Link',
        'images' => 'Görsel',
        'schema' => 'Schema',
    ],
];
