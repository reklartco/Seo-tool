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

## Sprint durumu

- [x] **Sprint 0** — iskelet: tenant/plan/limit katmanı, tüm migration'lar,
      modeller, sidebar layout + açık tema, proje oluşturma, `Rule` arayüzü
      ve ilk 5 kural (testleriyle).
- [ ] Sprint 1 — Crawler + kural motorunun tamamı (~40 kural)
- [ ] Sprint 2 — DataForSEO sıra takibi
- [ ] Sprint 3 — Search Console
- [ ] Sprint 4 — WordPress connector + AI düzeltme
- [ ] Sprint 5 — Dashboard cilası, bildirimler, admin paneli
