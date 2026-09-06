=== SEO Connector ===
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

SEO Aracı panelinin bu siteyi okumasına ve onayladığın SEO düzeltmelerini
uygulamasına izin verir.

== Kurulum ==

1. Eklentiyi yükle ve etkinleştir.
2. Ayarlar → SEO Connector ekranına panel adresini, proje numarasını ve
   panelde ürettiğin API anahtarını gir.
3. Kaydet. Eklenti panele bağlanır ve panelde "Bağlı" rozeti görünür.

== Güvenlik ==

Panelden gelen her istek HMAC-SHA256 ile imzalanır; imza method, yol, zaman
damgası ve gövde özetini kapsar. 5 dakikadan eski istekler reddedilir.
Değiştirilen her alanın eski değeri eklentinin kendi tablosunda saklanır,
böylece panelden tek tıkla geri alınabilir.
