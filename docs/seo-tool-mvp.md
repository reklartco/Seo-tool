# SEO Aracı — MVP Spesifikasyonu ve Arayüz Tasarımı

> Referans: KAF AI (kaf.simur.org). Amaç: önce kendi sitelerim (1etiket, Stickki, Exklusiv One, Reklart, DigiKart) için kullanmak, sonra müşterilere SaaS olarak satmak.
> Stack: Laravel 11, PHP 8.3, MySQL, Redis, Horizon, Livewire 3 + Alpine + Tailwind (veya Inertia/Vue). Hetzner VPS, DigiKart ile aynı tenant/plan/limit yapısı.

---

## 1. Ürün Özeti

**Tek cümle:** Siteyi tarar, SEO hatalarını bulur, anahtar kelime sıralamalarını takip eder, Search Console verisini gösterir ve WordPress'te hataları otomatik düzeltir.

**MVP'nin kapsamı (4 modül):**

| # | Modül | Neden MVP'de |
|---|-------|--------------|
| 1 | Site Tarama (Audit) | Aracın omurgası, tüm hatalar buradan çıkar |
| 2 | Anahtar Kelime Sıra Takibi | KAF videosundaki "wow" efekti; müşteriye satışta en ikna edici ekran |
| 3 | Search Console Entegrasyonu | Ücretsiz gerçek veri; sıra takibini destekler |
| 4 | WordPress Connector + AI Düzeltme | "Sadece raporlamıyor, düzeltiyor" farkı |

**MVP'de OLMAYANLAR (v2):** AI blog üretici, uptime monitör, rakip analizi, backlink, AI bot takibi, Pixel (JS enjeksiyon), mobil app, Chrome eklentisi, MCP server.

---

## 2. Mimari

```
┌─────────────┐   ┌──────────────┐   ┌──────────────┐
│  Web (UI)   │   │  Scheduler   │   │  WP Eklentisi │
│  Livewire   │   │  (cron)      │   │  (müşteri     │
└──────┬──────┘   └──────┬───────┘   │   sitesinde)  │
       │                 │           └──────┬────────┘
       ▼                 ▼                  │ REST + HMAC
┌─────────────────────────────────────┐     │
│           Laravel App               │◄────┘
│  Controllers / Livewire / API       │
└──────┬───────────────┬──────────────┘
       │               │
       ▼               ▼
┌─────────────┐  ┌─────────────────┐
│ MySQL       │  │ Redis + Horizon │
│             │  │ (queue)         │
└─────────────┘  └───────┬─────────┘
                         ▼
        ┌────────────────────────────────┐
        │  Jobs                          │
        │  CrawlPageJob (kural motoru)   │
        │  FetchSerpJob (DataForSEO)     │
        │  SyncSearchConsoleJob          │
        │  GenerateFixJob (Claude API)   │
        │  ApplyFixJob (WP REST)         │
        └────────────────────────────────┘
```

**Dış servisler:**
- **DataForSEO** — SERP sırası + arama hacmi. (Alternatif: SerpApi. Google'ı kendin scrape ETME, banlanırsın.)
- **Google Search Console API** — OAuth2, günlük performans verisi.
- **Claude API** (veya OpenAI) — title/description/alt üretimi.
- **PageSpeed Insights API** — Core Web Vitals (ücretsiz, günlük kota var).

---

## 3. Veritabanı Şeması

### Tenant / Plan katmanı (DigiKart'tan taşınacak)

```
users, teams (tenant), team_user, plans, subscriptions, invoices
```

`plans` tablosuna eklenecek limit kolonları:
- `max_projects`, `max_keywords`, `max_monthly_crawl_pages`, `max_auto_fix_sites`, `daily_fix_page_limit`

### Çekirdek tablolar

```sql
projects
  id, team_id, name, domain, protocol, language (tr/en/de),
  country (tr), search_engine_location_code, cms (wordpress|woocommerce|custom),
  crawl_frequency (daily|weekly|manual), max_pages, health_score (0-100),
  last_crawled_at, gsc_connected_at, wp_connected_at, created_at

crawls
  id, project_id, status (queued|running|done|failed),
  pages_total, pages_crawled, issues_count, health_score,
  started_at, finished_at

pages
  id, project_id, url (unique per project), url_hash,
  status_code, content_type, title, meta_description, h1,
  canonical, robots_meta, word_count, load_time_ms,
  internal_links_in, internal_links_out, depth,
  first_seen_at, last_crawled_at, last_crawl_id

page_snapshots            -- değişim izleme için
  id, page_id, crawl_id, title, meta_description, h1, canonical,
  robots_meta, status_code, content_hash

issues
  id, project_id, crawl_id, page_id,
  rule_key (title_missing, title_too_long, meta_desc_missing, ...),
  severity (critical|warning|notice), category (meta|content|technical|links|images|schema),
  message, details (json: mevcut değer, beklenen, öneri),
  status (open|fixed|ignored|auto_fixed), fixed_at, resolved_by (manual|ai|crawl)

keywords
  id, project_id, keyword, target_url (nullable),
  search_volume, cpc, competition, tag (sektör/grup),
  current_rank, previous_rank, best_rank, rank_delta,
  serp_url (hangi sayfa sıralanmış), last_checked_at

rank_history
  id, keyword_id, checked_at (date), rank (null = ilk 100'de yok),
  serp_url, serp_features (json)

serp_top10                -- rakip verisi (ileride rakip analizi için)
  id, keyword_id, checked_at, position, domain, url, title

gsc_daily
  id, project_id, date, clicks, impressions, ctr, position

gsc_queries
  id, project_id, date, query, page, clicks, impressions, ctr, position

gsc_tokens
  project_id, access_token (encrypted), refresh_token (encrypted), expires_at, property

wp_connections
  project_id, site_url, api_key_hash, plugin_version, wp_version,
  last_ping_at, capabilities (json)

fixes
  id, issue_id, project_id, page_id, field (title|meta_description|alt|...),
  old_value, new_value, generated_by (ai|user), model, prompt_tokens,
  status (draft|approved|applied|failed|rolled_back),
  applied_at, error, wp_object_type, wp_object_id

integrations_log
  id, project_id, source (wp|gsc|dataforseo|pagespeed), action, payload (json), created_at

notifications
  standard Laravel
```

---

## 4. Modül 1 — Site Tarama (Audit)

### Akış
1. Kullanıcı proje oluşturur → `StartCrawlJob` kuyruğa girer.
2. `robots.txt` ve `sitemap.xml` okunur, başlangıç URL'leri toplanır.
3. `CrawlPageJob` her sayfa için:
   - Guzzle ile GET (User-Agent: `SeoBot/1.0 (+https://domain)`), timeout 15s, redirect takibi.
   - HTML parse (Symfony DomCrawler).
   - `pages` kaydını upsert, `page_snapshots` yaz.
   - Bulunan iç linkleri kuyruğa ekle (depth ≤ 5, plan limitine kadar).
   - **Kural motorunu** çalıştır → `issues` yaz.
4. Tarama bitince `health_score` hesaplanır, önceki taramayla **diff** çıkarılır (yeni/çözülen hatalar).
5. Plan limiti: aylık taranan sayfa sayacı (`team_usage` tablosu).

### Kural Motoru

```php
interface Rule {
    public function key(): string;
    public function category(): string;
    public function severity(): string;
    public function check(PageContext $ctx): ?IssueResult;
}
```

Her kural ayrı class, `app/Seo/Rules/` altında. Servis provider'da register, `RuleRunner` hepsini sırayla çalıştırır.

### MVP kural listesi (≈40 kural)

**Meta / Başlık**
- `title_missing`, `title_too_short` (<30), `title_too_long` (>60), `title_duplicate` (proje genelinde)
- `meta_desc_missing`, `meta_desc_too_short` (<70), `meta_desc_too_long` (>160), `meta_desc_duplicate`
- `h1_missing`, `h1_multiple`, `h1_equals_title`
- `heading_hierarchy_broken` (H2'siz H3)

**Teknik**
- `status_4xx`, `status_5xx`, `redirect_chain` (>2 hop), `redirect_loop`
- `canonical_missing`, `canonical_mismatch` (farklı domain/protokol), `canonical_to_404`
- `noindex_in_sitemap`, `indexable_page_not_in_sitemap`
- `robots_txt_missing`, `robots_txt_blocks_all`, `sitemap_missing`, `sitemap_invalid`
- `https_missing`, `mixed_content`
- `lang_attr_missing`, `viewport_missing`
- `page_too_slow` (>3s), `html_too_large` (>2MB)

**İçerik**
- `thin_content` (<300 kelime, sayfa türüne göre), `duplicate_content` (hash eşleşmesi)
- `keyword_missing_in_title` (proje kelimelerine göre)

**Görseller**
- `img_alt_missing`, `img_alt_empty`, `img_too_large` (>300KB), `img_missing_dimensions`

**Linkler**
- `broken_internal_link`, `broken_external_link`, `orphan_page` (gelen iç link 0), `too_many_links` (>150), `link_text_generic` ("tıkla", "buraya")

**Schema / Sosyal**
- `og_title_missing`, `og_image_missing`, `twitter_card_missing`
- `schema_missing`, `schema_invalid_json`
- `product_schema_missing` (WooCommerce ürün sayfalarında)

### Sağlık Skoru
```
score = 100 - (critical × 5 + warning × 2 + notice × 0.5) / pages_total × 10
clamp(0, 100)
```
Basit ama tutarlı; skor kartında "önceki taramaya göre +/−" gösterilir.

---

## 5. Modül 2 — Anahtar Kelime Sıra Takibi

### Akış
1. Kullanıcı kelime ekler (tek tek veya toplu, satır satır). Hedef URL opsiyonel.
2. Ekleme anında `FetchVolumeJob` → DataForSEO Keyword Data API → `search_volume`, `cpc`, `competition`.
3. Her gece 03:00 `scheduler`: proje başına `FetchSerpJob` → DataForSEO SERP API (google, location: Turkey, language: tr, depth 100).
4. Sonuç: projenin domain'i ilk 100'de kaçıncı sırada → `rank_history` + `keywords.current_rank` güncelle; ilk 10 rakip → `serp_top10`.
5. Sıra değişimi ±3'ten büyükse bildirim.

### DataForSEO maliyet notu
- SERP (standart, depth 100): ~$0.002 / sorgu. 40 kelime × 30 gün = ~$2.4/ay/proje.
- Keyword volume: ~$0.05 / 1000 kelime. İhmal edilebilir.
- Plan fiyatına gömülür.

### Servis soyutlaması
```php
interface RankProvider {
    public function fetchRank(string $keyword, string $domain, Location $loc): SerpResult;
    public function fetchVolume(array $keywords, Location $loc): array;
}
// DataForSeoProvider implements RankProvider
// SerpApiProvider implements RankProvider (yedek)
```

---

## 6. Modül 3 — Search Console

1. Google Cloud'da OAuth client oluştur (scope: `webmasters.readonly`).
2. Proje ayarlarında "Search Console Bağla" → OAuth → property seç → `gsc_tokens`.
3. Günlük `SyncSearchConsoleJob`: son 3 günü çek (GSC 2-3 gün gecikmeli), `gsc_daily` ve `gsc_queries` upsert. İlk bağlantıda 16 aylık geçmişi çek.
4. Kullanımlar:
   - Dashboard grafiği (tıklama/gösterim/ort. sıra).
   - "Fırsat kelimeler": sıra 8–20 arası, gösterim yüksek, tıklama düşük → keyword takibine tek tıkla ekle.
   - Sayfa bazlı performans (audit ile birleşince: "bu sayfanın title'ı eksik VE 1200 gösterim alıyor" → öncelik).

---

## 7. Modül 4 — WordPress Connector + AI Düzeltme

### WP Eklentisi (`seo-connector`)
Ayarlar sayfası: API key + panel URL. Aktivasyonda panele ping atar (`POST /api/wp/handshake`).

REST endpoint'leri (`/wp-json/seoconnector/v1/`), HMAC-SHA256 imzalı istekler, timestamp ±5dk:

| Endpoint | İş |
|---|---|
| `GET /ping` | WP sürümü, aktif SEO eklentisi (Yoast/RankMath/none), yetenekler |
| `GET /posts?type=post,page,product` | ID, URL, title, mevcut meta (Yoast/RankMath alanlarından) |
| `POST /meta` | `{object_id, type, title, meta_description}` → doğru post_meta anahtarına yaz (`_yoast_wpseo_title`, `rank_math_title` veya kendi anahtarımız + `wp_head` çıktısı) |
| `POST /image-alt` | `{attachment_id, alt}` → `_wp_attachment_image_alt` |
| `POST /redirect` | `{from, to, code}` → eklentinin kendi redirect tablosu |
| `POST /rollback` | fix_id ile eski değeri geri yaz |

Eklenti kendi tablosunda her değişikliğin `old_value` yedeğini tutar.

### AI Düzeltme Akışı
1. Issue listesinde düzeltilebilir kurallar (`title_missing`, `title_too_long`, `meta_desc_*`, `img_alt_missing`) "AI ile Düzelt" butonu gösterir.
2. `GenerateFixJob`: sayfanın H1, ilk 500 kelime, proje anahtar kelimeleri, dil → Claude API prompt → JSON `{title, meta_description}` → `fixes` (status: draft).
3. Kullanıcı önizler: eski / yeni yan yana, düzenleyebilir, "Onayla ve Uygula".
4. `ApplyFixJob` → WP REST → status `applied`, issue `auto_fixed`.
5. **Otomatik mod** (plan izin veriyorsa): proje ayarında "onay beklemeden uygula" açılırsa günlük limit dahilinde otomatik gider. Varsayılan kapalı.
6. Geri alma: fix detayında "Geri Al".

Prompt iskeleti:
```
Sen bir SEO uzmanısın. Dil: {lang}.
Sayfa URL: {url}
H1: {h1}
İçerik özeti: {excerpt}
Hedef anahtar kelimeler: {keywords}
Mevcut title: {title} (sorun: {issue})
Kurallar: title 50-60 karakter, anahtar kelime başta, marka sonda " | {brand}".
Description 140-155 karakter, eylem çağrısı içersin.
SADECE JSON döndür: {"title": "...", "meta_description": "..."}
```

---

## 8. Arayüz Tasarımı

### Genel
- **Tema:** koyu (KAF gibi) — `#0B0F1A` zemin, kart `#131A2B`, vurgu yeşil `#22C55E` (yükseliş), turuncu `#F97316` (uyarı), kırmızı `#EF4444` (kritik). Açık tema v2.
- **Layout:** sol sabit sidebar (240px, mobilde drawer) + üst bar (proje seçici, arama, bildirim, avatar).
- **Bileşenler:** Tailwind + Livewire. Grafikler ApexCharts. Tablolar Livewire datatable (sıralama, filtre, sayfalama).
- **Yükleme:** tarama sırasında ilerleme çubuğu + "X / Y sayfa" (Livewire polling 3sn).

### Sidebar
```
🏠 Genel Bakış
🔍 Site Taraması
   ├ Hatalar
   ├ Sayfalar
   └ Tarama Geçmişi
📈 Anahtar Kelimeler
🟢 Search Console
🔧 Düzeltmeler
🔌 Entegrasyonlar
⚙️ Proje Ayarları
──────────
Projeler (liste, + Yeni Proje)
Plan: Pegasus · 3/10 proje
```

### Ekran 1 — Genel Bakış (Dashboard)

```
┌──────────────────────────────────────────────────────────────┐
│ 1etiket.com.tr ▾          Son tarama: 2 saat önce  [Tara]    │
├──────────────┬──────────────┬──────────────┬─────────────────┤
│ SAĞLIK SKORU │ AÇIK HATA    │ KELİME       │ GSC TIKLAMA     │
│    78  ▲+6   │  142  ▼-18   │ 28 takipte   │ 1.240  ▲12%     │
│  (halka)     │ 12 kritik    │ 9 ilk sayfa  │ son 28 gün      │
├──────────────┴──────────────┴──────────────┴─────────────────┤
│ SIRA DEĞİŞİMLERİ (7 gün)          │ GSC TIKLAMA / GÖSTERİM   │
│ ▲ etiket baskı        14 → #7 +7  │  (çizgi grafik, 28 gün)  │
│ ▲ sticker baskı       11 → #8 +3  │                          │
│ ▼ ürün etiketi         6 → #9 -3  │                          │
│ [tümü →]                          │                          │
├───────────────────────────────────┴──────────────────────────┤
│ ÖNCELİKLİ HATALAR                                            │
│ 🔴 23 sayfada meta description yok        [AI ile düzelt]    │
│ 🔴 8 kırık iç link                        [gör]              │
│ 🟠 41 görselde alt eksik                  [AI ile düzelt]    │
│ 🟠 12 sayfada title 60+ karakter          [AI ile düzelt]    │
├──────────────────────────────────────────────────────────────┤
│ FIRSAT KELİMELER (GSC: sıra 8-20, gösterim yüksek)           │
│ "kare etiket baskı"  sıra 11 · 890 gösterim · 12 tık [Takibe al] │
└──────────────────────────────────────────────────────────────┘
```

### Ekran 2 — Hatalar (Issues)

- Üstte filtre çipleri: **Tümü / Kritik / Uyarı / Bilgi** + kategori sekmeleri (Meta, Teknik, İçerik, Görsel, Link, Schema) + durum (Açık / Düzeltildi / Yoksayıldı).
- **Gruplu görünüm** (varsayılan): kural bazlı satırlar → "Meta description eksik — 23 sayfa" → tıklayınca sayfalar açılır (accordion).
- Her satır: severity noktası, kural adı, sayfa sayısı, "AI ile düzelt (23)", "Yoksay".
- Sayfa satırı: URL, mevcut değer, öneri, [Düzelt] [Yoksay] [Sayfayı aç].
- Sağ panel (slide-over): hatanın açıklaması, neden önemli, nasıl düzeltilir.

### Ekran 3 — Sayfalar

Tablo: URL · Durum kodu · Title (karakter) · Desc (karakter) · H1 · Kelime · Gelen link · Hata sayısı · Son tarama. Satır tıklanınca sayfa detayı: tüm meta, snapshot geçmişi (değişim diff'i), sayfa hataları, GSC verisi (o sayfanın sorguları).

### Ekran 4 — Anahtar Kelimeler (KAF'taki kart görünümü)

Üstte özet: **Takipte 28 · İlk 3: 4 · İlk 10: 9 · İlk 100: 24 · Ortalama sıra 18.3** + "Kelime ekle" butonu + grid/tablo toggle.

**Kart görünümü** (videodaki):
```
┌──────────────────────────────────┐
│ etiket baskı            [Reklam] │  ← tag rozeti (turuncu)
│ 1etiket.com.tr/etiket-baskı      │
│                                  │
│  14. ▲  #7          [+7 sıra]    │  ← eski, ok, yeni (büyük), delta rozeti (yeşil)
│  ●━━━━━━━━━━●━━━━━━━━━━━━━━○ #1  │  ← 20→1 slider: gri = başlangıç, yeşil = şimdi
│  20.                             │
│  ~1.600 arama/ay  ·  CPC ₺4,20   │
└──────────────────────────────────┘
```
- Slider: sol 20 (veya 100), sağ #1. İçi boş nokta = ilk ölçüm, dolu yeşil = güncel. Düşüşte çubuk turuncu/kırmızı.
- Filtre: tag, yükselen/düşen, ilk 10'dakiler.

**Kelime detayı** (modal veya sayfa): 90 günlük sıra grafiği (ters eksen, 1 üstte), sıralanan URL, arama hacmi, ilk 10 rakip listesi (domain, sıra, title) — bu ileride rakip analizi modülünün tohumu.

### Ekran 5 — Search Console

- Property seçili değilse: "Bağla" kartı (Google butonu).
- Bağlıysa: tarih aralığı seçici (7/28/90 gün, karşılaştır), 4 metrik toggle (tıklama, gösterim, CTR, sıra) + çizgi grafik.
- Alt sekmeler: **Sorgular · Sayfalar · Ülkeler · Cihazlar**. Sorgu satırında "Takibe al" ikonu.

### Ekran 6 — Düzeltmeler (Fixes)

- Sekmeler: **Onay bekleyen · Uygulandı · Başarısız**.
- Onay bekleyen satır: sayfa URL, alan (title/desc/alt), **eski değer** (üstü çizili) → **yeni değer** (düzenlenebilir input, karakter sayacı), [Onayla] [Reddet]. Üstte "Tümünü onayla (12)".
- Uygulandı: tarih, kim (AI/kullanıcı), [Geri al].
- Proje ayarı: "Otomatik uygula" toggle + günlük limit göstergesi (12/20).

### Ekran 7 — Entegrasyonlar

Üç kart: **WordPress** (API key üret, eklenti indir, bağlantı durumu, tespit edilen SEO eklentisi), **Search Console** (bağlı property, yeniden yetkilendir), **Google Analytics** (v2, gri).

### Ekran 8 — Proje Oluşturma Sihirbazı

1. Domain + dil + ülke + CMS.
2. Anahtar kelimeler (textarea, satır satır; hacimler anında çekilir ve tabloda gösterilir).
3. Entegrasyonlar (atlanabilir).
4. "Taramayı başlat" → dashboard'a yönlendir, ilerleme çubuğu.

### Ekran 9 — Admin (süper admin, DigiKart'tan)

Team listesi, plan atama, kullanım (tarama sayfası, SERP sorgusu, AI token), DataForSEO/Claude maliyet özeti, Horizon linki.

---

## 9. Plan / Limit Modeli (DigiKart yapısı)

| | Başlangıç | Pro | Ajans |
|---|---|---|---|
| Proje | 3 | 10 | 30 |
| Kelime | 20 | 60 | 200 |
| Aylık tarama sayfası | 5.000 | 30.000 | 150.000 |
| WP otomatik düzeltme | — | 1 site, 20 sayfa/gün | 5 site, 50 sayfa/gün |
| AI üretim | 50/ay | 500/ay | 2.000/ay |
| Search Console | ✓ | ✓ | ✓ |

Limit kontrolü: `Gate`/middleware + `team_usage` (aylık sayaçlar, ayın 1'inde sıfırlanır). Fiyatı DataForSEO + Claude maliyetinin en az 4 katı olacak şekilde kur.

---

## 10. Geliştirme Sırası (Sprint Planı)

**Sprint 0 — İskelet (2-3 gün)**
- DigiKart'tan auth/team/plan/subscription kopyala. Sidebar layout, proje CRUD, dark tema.

**Sprint 1 — Crawler + Kural motoru (1 hafta)**
- Crawl job'ları, pages/issues tabloları, 40 kural, sağlık skoru, Hatalar ve Sayfalar ekranı, tarama ilerleme çubuğu.
- Test: 1etiket.com.tr'yi tara.

**Sprint 2 — Anahtar kelime takibi (4-5 gün)**
- DataForSEO entegrasyonu, keyword CRUD, günlük scheduler, rank_history, kart görünümü + slider bileşeni, detay grafiği.

**Sprint 3 — Search Console (3 gün)**
- OAuth, sync job, GSC ekranı, fırsat kelimeler, dashboard grafiği.

**Sprint 4 — WP Connector + AI düzeltme (1 hafta)**
- WP eklentisi (handshake, posts, meta, alt, rollback), HMAC, GenerateFixJob + Claude, Düzeltmeler ekranı, otomatik mod + günlük limit.
- Test: 1etiket ve Stickki'de gerçek düzeltme.

**Sprint 5 — Dashboard + cila (3 gün)**
- Genel Bakış ekranı, bildirimler (e-posta: tarama bitti, sıra ±3, kritik hata), admin kullanım paneli, boş durum ekranları.

Toplam: ~4-5 hafta tek kişi, AI destekli.

---

## 11. Sonraki Adım

Sprint 0'dan başla: Laravel projesi + DigiKart tenant katmanı + migration'lar (bölüm 3) + sidebar layout. İlk oturumda migration dosyalarını ve `Rule` arayüzü + 5 örnek kuralı yazacağız.
