<?php

use App\Seo\PageContext;
use App\Seo\RuleRunner;
use App\Seo\Rules\H1Missing;
use App\Seo\Rules\ImgAltMissing;
use App\Seo\Rules\MetaDescMissing;
use App\Seo\Rules\TitleMissing;
use App\Seo\Rules\TitleTooLong;

function ctx(string $html): PageContext
{
    return new PageContext(url: 'https://example.test/', html: $html);
}

it('flags a page with no title', function () {
    $issues = (new TitleMissing)->check(ctx('<html><head></head><body></body></html>'));

    expect($issues)->toHaveCount(1)
        ->and($issues[0]->ruleKey)->toBe('title_missing')
        ->and($issues[0]->severity)->toBe('critical');
});

it('accepts a page with a title', function () {
    expect((new TitleMissing)->check(ctx('<html><head><title>Etiket Baskı</title></head></html>')))->toBeEmpty();
});

it('treats a whitespace-only title as missing', function () {
    expect((new TitleMissing)->check(ctx('<html><head><title>   </title></head></html>')))->toHaveCount(1);
});

it('flags titles longer than 60 characters', function () {
    $title = str_repeat('a', 61);
    $issues = (new TitleTooLong)->check(ctx("<html><head><title>{$title}</title></head></html>"));

    expect($issues)->toHaveCount(1)
        ->and($issues[0]->details['length'])->toBe(61);
});

it('accepts a title of exactly 60 characters', function () {
    $title = str_repeat('a', 60);

    expect((new TitleTooLong)->check(ctx("<html><head><title>{$title}</title></head></html>")))->toBeEmpty();
});

it('does not report length when the title is missing', function () {
    expect((new TitleTooLong)->check(ctx('<html><head></head></html>')))->toBeEmpty();
});

it('flags a missing meta description', function () {
    expect((new MetaDescMissing)->check(ctx('<html><head><title>x</title></head></html>')))->toHaveCount(1);
});

it('accepts a present meta description', function () {
    $html = '<html><head><meta name="description" content="Etiket baskı hizmeti."></head></html>';

    expect((new MetaDescMissing)->check(ctx($html)))->toBeEmpty();
});

it('flags a page with no h1', function () {
    expect((new H1Missing)->check(ctx('<html><body><h2>Alt başlık</h2></body></html>')))->toHaveCount(1);
});

it('accepts a page with an h1', function () {
    expect((new H1Missing)->check(ctx('<html><body><h1>Etiket Baskı</h1></body></html>')))->toBeEmpty();
});

it('flags every image without an alt attribute', function () {
    $html = '<html><body><img src="/a.jpg"><img src="/b.jpg" alt="b"><img src="/c.jpg"></body></html>';
    $issues = (new ImgAltMissing)->check(ctx($html));

    expect($issues)->toHaveCount(2)
        ->and(array_column(array_map(fn ($i) => $i->details, $issues), 'src'))
        ->toBe(['/a.jpg', '/c.jpg']);
});

it('accepts an empty alt attribute as a deliberate decorative image', function () {
    expect((new ImgAltMissing)->check(ctx('<html><body><img src="/a.jpg" alt=""></body></html>')))->toBeEmpty();
});

it('collects issues from every registered rule', function () {
    $runner = new RuleRunner([
        new TitleMissing,
        new MetaDescMissing,
        new H1Missing,
    ]);

    $issues = $runner->run(ctx('<html><head></head><body></body></html>'));

    expect(array_map(fn ($i) => $i->ruleKey, $issues))
        ->toBe(['title_missing', 'meta_desc_missing', 'h1_missing']);
});

it('returns no issues for a healthy page', function () {
    $runner = app(RuleRunner::class);

    $html = '<html><head><title>Etiket Baskı | 1etiket</title>'
        .'<meta name="description" content="Kaliteli etiket baskı hizmeti, hızlı teslimat."></head>'
        .'<body><h1>Etiket Baskı</h1><img src="/a.jpg" alt="etiket"></body></html>';

    expect($runner->run(ctx($html)))->toBeEmpty();
});
