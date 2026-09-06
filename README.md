# SEO Aracı

Siteyi tarar, SEO hatalarını bulur, anahtar kelime sıralamalarını takip eder,
Search Console verisini gösterir ve WordPress'te hataları otomatik düzeltir.

Ürün spesifikasyonu: [`docs/seo-tool-mvp.md`](docs/seo-tool-mvp.md).
Geliştirme brifingi: [`CLAUDE.md`](CLAUDE.md).

## Stack

| Katman | Seçim |
|---|---|
| Backend | Laravel 11, PHP 8.3+ |
| Frontend | Livewire 3 + Alpine + Tailwind 3 (Breeze auth iskeleti) |
| Veritabanı | MySQL |
| Kuyruk | Redis + Horizon |
| Test | Pest 3 |

Arayüz KAF (kaf.simur.org) yerleşimini takip eder, tema **açık/beyaz**:
`#F7F8FA` zemin, `#FFFFFF` kart, turuncu marka rengi, yükseliş yeşil /
düşüş kırmızı. Renk ve bileşen sınıfları `tailwind.config.js` ve
`resources/css/app.css` içinde tanımlı.

## Kurulum

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# .env içinde MySQL ve Redis bilgilerini doldur
php artisan migrate --seed
npm run build

php artisan serve
php artisan horizon      # kuyruk işçileri
```

Seed bir demo hesabı oluşturur: `demo@seotool.test` / `password`
(1etiket projesi, örnek sayfa/hata/kelime verisiyle).

Dış servis anahtarları (`DATAFORSEO_*`, `ANTHROPIC_API_KEY`, `GOOGLE_*`,
`PAGESPEED_API_KEY`) yalnızca `.env` içinde tutulur; koda gömülmez.

## Test

```bash
vendor/bin/pest
```

## Dizin haritası

```
app/
  Seo/
    PageContext.php      -- bir sayfanın kurallara sunulan hâli
    IssueResult.php      -- tek bir kural ihlali
    RuleRunner.php       -- kayıtlı kuralları sırayla çalıştırır
    Rules/               -- her kural ayrı sınıf (Rule arayüzü)
  Services/PlanLimits.php -- plan limiti + dönemsel kullanım okuması
  Livewire/              -- panel ekranları
  Models/                -- tenant + çekirdek tablolar
resources/views/
  layouts/app.blade.php  -- sidebar + üst bar kabuğu
  components/app/        -- stat-card, rank-slider, sidebar, topbar, icon
  livewire/              -- ekran görünümleri
database/
  migrations/            -- spec §3'teki tüm tablolar
  seeders/               -- PlanSeeder (§9 plan matrisi), DemoSeeder
```

## Modüller

| Modül | Durum | Öne çıkanlar |
|---|---|---|
| Site tarama | ✓ | Guzzle crawler, robots + sitemap keşfi, 35 sayfa kuralı + 13 site kuralı, sağlık skoru, tarama diff'i |
| Anahtar kelime | ✓ | `RankProvider` arayüzü (DataForSEO), gecelik sıra takibi, 90 günlük grafik, ilk 10 rakip |
| Search Console | ✓ | OAuth2, günlük eşitleme, sorgu/sayfa tabloları, fırsat kelimeler |
| WordPress + AI | ✓ | HMAC imzalı `seo-connector` eklentisi, Claude ile title/description/alt üretimi, onay akışı, geri alma |
| Admin | ✓ | Takım/plan yönetimi, kullanım sayaçları, tahmini API maliyeti |

### Kuyruklar

`crawl` (tarama), `serp` (sıra ve hacim), `ai` (üretim ve uygulama), `default`.
Horizon `config/horizon.php` üzerinden bu kuyrukları işler.

### Zamanlanmış işler

| Saat | İş |
|---|---|
| 03:00 | Tüm projelerin kelimeleri için SERP sorgusu |
| 05:00 | Search Console eşitlemesi |
| saatlik | `seo:crawl-due` — tarama sıklığı dolan projeler |

## WordPress eklentisi

`wordpress-plugin/seo-connector/` altında. Panelde Entegrasyonlar ekranından
zip olarak indirilir. Panel ile eklenti arasındaki her istek HMAC-SHA256 ile
imzalanır (method + yol + zaman damgası + gövde özeti, 5 dakikalık pencere).
Eklenti Yoast ve Rank Math meta anahtarlarını tanır, ikisi de yoksa kendi
anahtarlarını kullanıp `wp_head` çıktısını kendisi verir. Değiştirdiği her
alanın eski değerini kendi tablosunda saklar, böylece panelden geri alınabilir.

## Sprint durumu

- [x] Sprint 0 — iskelet, tenant/plan katmanı, şema, açık tema panel
- [x] Sprint 1 — crawler + kural motoru, Hatalar / Sayfalar / Tarama Geçmişi
- [x] Sprint 2 — DataForSEO sıra takibi, kelime kartları ve detay grafiği
- [x] Sprint 3 — Search Console, fırsat kelimeler
- [x] Sprint 4 — WordPress connector + AI düzeltme
- [x] Sprint 5 — dashboard cilası, bildirimler, admin paneli, sihirbaz

v2 için bekleyenler (spec §1): AI blog üretici, uptime monitör, rakip analizi,
backlink, AI bot takibi, Pixel, mobil uygulama, Chrome eklentisi, MCP server.
