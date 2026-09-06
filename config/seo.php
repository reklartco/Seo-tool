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

    /*
    | Slide-over help text per rule: why it matters and how to fix it.
    */
    'rule_help' => [
        'title_missing' => [
            'why' => 'Title, arama sonucunda görünen ilk şeydir ve sıralamada en güçlü sayfa içi sinyallerden biridir. Title olmadan Google başlığı kendisi uydurur.',
            'how' => 'Sayfaya 30-60 karakterlik, hedef kelimeyle başlayan ve marka adıyla biten bir başlık ekle.',
        ],
        'title_too_long' => [
            'why' => 'Arama sonucunda 60 karakterden sonrası kırpılır; mesajın yarısı kullanıcıya ulaşmaz.',
            'how' => 'Başlığı kısalt, tekrar eden kelimeleri ve gereksiz ayraçları at.',
        ],
        'title_too_short' => [
            'why' => 'Çok kısa başlık sayfanın ne sunduğunu anlatmaz ve tıklama oranını düşürür.',
            'how' => 'Hedef kelimeyi ve bir fayda ifadesini ekleyerek 30-60 karakter aralığına çıkar.',
        ],
        'title_duplicate' => [
            'why' => 'Aynı başlığı taşıyan sayfalar arama motoru için birbirinin kopyası gibi görünür ve birbirini yer.',
            'how' => 'Her sayfaya kendi içeriğini anlatan benzersiz bir başlık yaz.',
        ],
        'meta_desc_missing' => [
            'why' => 'Description sıralama sinyali değildir ama tıklama oranını doğrudan etkiler; yoksa Google sayfadan rastgele bir cümle seçer.',
            'how' => '140-155 karakterlik, eylem çağrısı içeren bir açıklama ekle.',
        ],
        'meta_desc_too_long' => [
            'why' => '160 karakterden sonrası kırpılır, eylem çağrın görünmez.',
            'how' => 'Açıklamayı 140-155 karaktere indir, en önemli cümleyi başa al.',
        ],
        'h1_missing' => [
            'why' => 'H1 sayfanın konusunu hem kullanıcıya hem tarayıcıya bildirir; erişilebilirlik için de gereklidir.',
            'how' => 'Sayfa başına tek bir H1 ekle ve hedef kelimeyi içinde geçir.',
        ],
        'h1_multiple' => [
            'why' => 'Birden fazla H1 sayfanın odağını dağıtır.',
            'how' => 'Ana başlığı H1 bırak, diğerlerini H2 yap.',
        ],
        'img_alt_missing' => [
            'why' => 'Alt metni olmayan görseller görsel aramada çıkmaz ve ekran okuyucular için erişilemez.',
            'how' => 'Görseli tarif eden, 125 karakteri geçmeyen bir alt metni yaz.',
        ],
        'broken_internal_link' => [
            'why' => 'Kırık iç link hem kullanıcıyı çıkmaza sokar hem link gücünü boşa harcar.',
            'how' => 'Linki doğru sayfaya yönlendir ya da kaldır; taşınmış sayfalar için 301 kur.',
        ],
        'orphan_page' => [
            'why' => 'Site içinden link almayan sayfayı arama motoru zor keşfeder ve önemsiz sayar.',
            'how' => 'İlgili kategori ve içerik sayfalarından en az bir iç link ver.',
        ],
        'canonical_missing' => [
            'why' => 'Canonical olmadan parametreli URL kopyaları ayrı sayfa gibi taranır.',
            'how' => 'Her sayfaya kendini gösteren bir canonical ekle.',
        ],
        'thin_content' => [
            'why' => 'Zayıf içerik sorguyu karşılamaz; Google daha doyurucu sayfaları öne alır.',
            'how' => 'Sayfayı gerçek sorulara cevap verecek şekilde genişlet, en az 300 kelime hedefle.',
        ],
        'duplicate_content' => [
            'why' => 'Birebir aynı içerik taşıyan sayfalar birbiriyle yarışır, ikisi de kaybeder.',
            'how' => 'İçerikleri farklılaştır ya da birini canonical ile diğerine bağla.',
        ],
        'sitemap_missing' => [
            'why' => 'Sitemap olmadan yeni sayfaların keşfi yavaşlar.',
            'how' => 'sitemap.xml üret ve robots.txt içinde adresini bildir.',
        ],
        'robots_txt_blocks_all' => [
            'why' => 'Disallow: / satırı tüm siteyi aramaya kapatır; trafiğin sıfırlanır.',
            'how' => 'robots.txt içindeki Disallow: / satırını kaldır.',
        ],
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
