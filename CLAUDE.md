# SEO Aracı — Claude Code Başlangıç Brifingi

Bu klasörde iki dosya var:
- `CLAUDE.md` (bu dosya) — ne yapacağın
- `seo-tool-mvp.md` — ürün spesifikasyonu (veritabanı şeması, kural motoru, ekranlar, sprint planı). Her adımda buraya bak.

Dil: Türkçe konuş, kod ve commit mesajları İngilizce.

---

## Adım 0 — DigiKart stack'ini tespit et (ÖNCE BUNU YAP)

DigiKart projesi bu makinede mevcut. Yolunu sor, sonra şunları oku:
- `composer.json` → `livewire/livewire` mi, `inertiajs/inertia-laravel` mi? Laravel sürümü?
- `package.json` → Vue/Alpine/Tailwind sürümleri, Vite config
- `app/Models/` → tenant modeli (Team/Company/Firma), Plan, Subscription, User ilişkileri
- `routes/` → tenant/subdomain routing nasıl çözülüyor
- `app/Http/Middleware/` → tenant ve plan-limit middleware'leri
- Ödeme entegrasyonu (iyzico?) hangi paketle

Bulduklarını 10 satırda özetle ve **onay iste**. Onaysız yeni proje oluşturma.

---

## Adım 1 — Yeni projeyi kur (Sprint 0)

1. `composer create-project laravel/laravel seotool` (DigiKart ile aynı Laravel major sürümü)
2. DigiKart ile **aynı frontend stack**'i kur (Livewire+Alpine veya Inertia+Vue — Adım 0'da ne bulduysan). Tailwind, koyu tema (spec §8 renk paleti).
3. DigiKart'tan şunları **kopyala ve uyarla** (yeniden yazma):
   - User, Team/tenant, Plan, Subscription modelleri + migration'ları
   - Auth, team switching, plan-limit middleware
   - Admin panel iskeleti
   - Ödeme entegrasyonu (şimdilik devre dışı, yapı dursun)
4. Plan tablosuna spec §3'teki limit kolonlarını ekle: `max_projects, max_keywords, max_monthly_crawl_pages, max_auto_fix_sites, daily_fix_page_limit`
5. Spec §3'teki tüm çekirdek tabloların migration'larını yaz: `projects, crawls, pages, page_snapshots, issues, keywords, rank_history, serp_top10, gsc_daily, gsc_queries, gsc_tokens, wp_connections, fixes, integrations_log, team_usage`
6. Model'leri ilişkileriyle oluştur.
7. Redis + Horizon kur, `.env.example` güncelle.
8. Sidebar layout'u spec §8'deki menüyle yap. Proje CRUD (spec §8 Ekran 8 sihirbazı, şimdilik sadece 1. adım: domain/dil/ülke/CMS).
9. `app/Seo/Rules/Rule.php` arayüzü + `RuleRunner` + 5 örnek kural: `TitleMissing, TitleTooLong, MetaDescMissing, H1Missing, ImgAltMissing`. Her kurala Pest testi.

Sprint 0 bitince `php artisan migrate` temiz geçmeli, testler yeşil olmalı, panelde proje oluşturulabilmeli.

---

## Kurallar

- Her sprint sonunda kısa özet ver, sonraki sprinte geçmeden **sor**.
- Spec'te olmayan bir şey gerekirse önce sor, sonra spec dosyasını güncelle.
- Dış API anahtarları (DataForSEO, Claude, Google) `.env`'de; koda gömme.
- Google'ı doğrudan scrape eden kod yazma; sıra verisi sadece `RankProvider` arayüzü üzerinden gelir.
- Commit'ler küçük ve sık: `feat(crawler): add TitleTooLong rule` formatı.
