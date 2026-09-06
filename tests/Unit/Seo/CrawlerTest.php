<?php

use App\Seo\Crawler\PageParser;
use App\Seo\Crawler\UrlNormalizer;
use App\Seo\HealthScore;

const SAMPLE_HTML = <<<'HTML'
<!doctype html>
<html lang="tr">
<head>
    <title>Etiket Baskı | 1etiket</title>
    <meta name="description" content="Kaliteli etiket baskı.">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://1etiket.com.tr/etiket-baski">
    <meta property="og:title" content="Etiket Baskı">
    <meta name="twitter:card" content="summary">
    <script type="application/ld+json">{"@type":"Product","name":"Etiket"}</script>
</head>
<body>
    <h1>Etiket Baskı</h1>
    <h2>Fiyatlar</h2>
    <p>Etiket baskı hizmeti sunuyoruz.</p>
    <img src="/a.jpg" alt="etiket" width="100" height="100">
    <img src="http://cdn.example.com/b.jpg">
    <a href="/sticker">Sticker</a>
    <a href="https://baska.com/x" rel="nofollow">Dış link</a>
    <a href="#top">Yukarı</a>
    <script>var x = 'bu metin sayilmamali';</script>
</body>
</html>
HTML;

it('parses the meta block of a page', function () {
    $parsed = (new PageParser)->parse(SAMPLE_HTML, 'https://1etiket.com.tr/etiket-baski');

    expect($parsed['title'])->toBe('Etiket Baskı | 1etiket')
        ->and($parsed['meta_description'])->toBe('Kaliteli etiket baskı.')
        ->and($parsed['h1'])->toBe('Etiket Baskı')
        ->and($parsed['h1_count'])->toBe(1)
        ->and($parsed['lang'])->toBe('tr')
        ->and($parsed['canonical'])->toBe('https://1etiket.com.tr/etiket-baski')
        ->and($parsed['og']['title'])->toBe('Etiket Baskı')
        ->and($parsed['twitter_card'])->toBe('summary')
        ->and($parsed['schema']['types'])->toContain('Product');
});

it('resolves relative links against the page url', function () {
    $parsed = (new PageParser)->parse(SAMPLE_HTML, 'https://1etiket.com.tr/etiket-baski');
    $urls = array_column($parsed['links'], 'url');

    expect($urls)->toBe(['https://1etiket.com.tr/sticker', 'https://baska.com/x'])
        ->and($parsed['links'][1]['nofollow'])->toBeTrue();
});

it('keeps script bodies out of the word count', function () {
    $parsed = (new PageParser)->parse(SAMPLE_HTML, 'https://1etiket.com.tr/');

    expect($parsed['text'])->not->toContain('sayilmamali')
        ->and($parsed['word_count'])->toBeGreaterThan(3);
});

it('collects images with and without alt text', function () {
    $parsed = (new PageParser)->parse(SAMPLE_HTML, 'https://1etiket.com.tr/');

    expect($parsed['images'])->toHaveCount(2)
        ->and($parsed['images'][0]['alt'])->toBe('etiket')
        ->and($parsed['images'][1]['alt'])->toBeNull()
        ->and($parsed['images'][1]['width'])->toBeNull();
});

it('normalizes urls consistently', function () {
    expect(UrlNormalizer::normalize('HTTPS://WWW.Ornek.com/Yol/?utm_source=x&b=2#bolum'))
        ->toBe('https://www.ornek.com/Yol?b=2')
        ->and(UrlNormalizer::normalize('https://ornek.com/'))->toBe('https://ornek.com/')
        ->and(UrlNormalizer::normalize('/yol'))->toBeNull();
});

it('resolves relative paths', function () {
    expect(UrlNormalizer::absolute('../b', 'https://ornek.com/a/c/d'))->toBe('https://ornek.com/a/b')
        ->and(UrlNormalizer::absolute('/kok', 'https://ornek.com/a/b'))->toBe('https://ornek.com/kok')
        ->and(UrlNormalizer::absolute('//cdn.com/x.js', 'https://ornek.com/'))->toBe('https://cdn.com/x.js');
});

it('treats subdomains and www as the same site', function () {
    expect(UrlNormalizer::sameSite('https://www.ornek.com/a', 'ornek.com'))->toBeTrue()
        ->and(UrlNormalizer::sameSite('https://blog.ornek.com/a', 'ornek.com'))->toBeTrue()
        ->and(UrlNormalizer::sameSite('https://ornekk.com/a', 'ornek.com'))->toBeFalse();
});

it('scores a clean site high and a broken one low', function () {
    expect(HealthScore::calculate(0, 0, 0, 100))->toBe(100)
        ->and(HealthScore::calculate(2, 4, 10, 100))->toBe(98)
        ->and(HealthScore::calculate(50, 50, 0, 10))->toBe(0);
});
