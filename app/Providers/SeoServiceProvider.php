<?php

namespace App\Providers;

use App\Seo\RuleRunner;
use App\Seo\Rules\CanonicalMismatch;
use App\Seo\Rules\CanonicalMissing;
use App\Seo\Rules\H1EqualsTitle;
use App\Seo\Rules\H1Missing;
use App\Seo\Rules\H1Multiple;
use App\Seo\Rules\HeadingHierarchyBroken;
use App\Seo\Rules\HtmlTooLarge;
use App\Seo\Rules\HttpsMissing;
use App\Seo\Rules\ImgAltEmpty;
use App\Seo\Rules\ImgAltMissing;
use App\Seo\Rules\ImgMissingDimensions;
use App\Seo\Rules\ImgTooLarge;
use App\Seo\Rules\KeywordMissingInTitle;
use App\Seo\Rules\LangAttrMissing;
use App\Seo\Rules\LinkTextGeneric;
use App\Seo\Rules\MetaDescMissing;
use App\Seo\Rules\MetaDescTooLong;
use App\Seo\Rules\MetaDescTooShort;
use App\Seo\Rules\MixedContent;
use App\Seo\Rules\OgImageMissing;
use App\Seo\Rules\OgTitleMissing;
use App\Seo\Rules\PageTooSlow;
use App\Seo\Rules\ProductSchemaMissing;
use App\Seo\Rules\RedirectChain;
use App\Seo\Rules\Rule;
use App\Seo\Rules\SchemaInvalidJson;
use App\Seo\Rules\SchemaMissing;
use App\Seo\Rules\Status4xx;
use App\Seo\Rules\Status5xx;
use App\Seo\Rules\ThinContent;
use App\Seo\Rules\TitleMissing;
use App\Seo\Rules\TitleTooLong;
use App\Seo\Rules\TitleTooShort;
use App\Seo\Rules\TooManyLinks;
use App\Seo\Rules\TwitterCardMissing;
use App\Seo\Rules\ViewportMissing;
use App\Seo\SiteRuleRunner;
use App\Seo\SiteRules\BrokenExternalLink;
use App\Seo\SiteRules\BrokenInternalLink;
use App\Seo\SiteRules\CanonicalTo404;
use App\Seo\SiteRules\DuplicateContent;
use App\Seo\SiteRules\IndexablePageNotInSitemap;
use App\Seo\SiteRules\MetaDescDuplicate;
use App\Seo\SiteRules\NoindexInSitemap;
use App\Seo\SiteRules\OrphanPage;
use App\Seo\SiteRules\RobotsTxtBlocksAll;
use App\Seo\SiteRules\RobotsTxtMissing;
use App\Seo\SiteRules\SitemapInvalid;
use App\Seo\SiteRules\SitemapMissing;
use App\Seo\SiteRules\SiteRule;
use App\Seo\SiteRules\TitleDuplicate;
use Illuminate\Support\ServiceProvider;

class SeoServiceProvider extends ServiceProvider
{
    /**
     * Rules the crawler runs on every page, in report order.
     *
     * @var list<class-string<Rule>>
     */
    public const RULES = [
        // Meta / başlık
        TitleMissing::class,
        TitleTooShort::class,
        TitleTooLong::class,
        MetaDescMissing::class,
        MetaDescTooShort::class,
        MetaDescTooLong::class,
        H1Missing::class,
        H1Multiple::class,
        H1EqualsTitle::class,
        HeadingHierarchyBroken::class,

        // Teknik
        Status4xx::class,
        Status5xx::class,
        RedirectChain::class,
        CanonicalMissing::class,
        CanonicalMismatch::class,
        HttpsMissing::class,
        MixedContent::class,
        LangAttrMissing::class,
        ViewportMissing::class,
        PageTooSlow::class,
        HtmlTooLarge::class,

        // İçerik
        ThinContent::class,
        KeywordMissingInTitle::class,

        // Görseller
        ImgAltMissing::class,
        ImgAltEmpty::class,
        ImgTooLarge::class,
        ImgMissingDimensions::class,

        // Linkler
        TooManyLinks::class,
        LinkTextGeneric::class,

        // Schema / sosyal
        OgTitleMissing::class,
        OgImageMissing::class,
        TwitterCardMissing::class,
        SchemaMissing::class,
        SchemaInvalidJson::class,
        ProductSchemaMissing::class,
    ];

    /**
     * Rules that need the whole crawl before they can judge anything.
     *
     * @var list<class-string<SiteRule>>
     */
    public const SITE_RULES = [
        TitleDuplicate::class,
        MetaDescDuplicate::class,
        DuplicateContent::class,
        CanonicalTo404::class,
        NoindexInSitemap::class,
        IndexablePageNotInSitemap::class,
        RobotsTxtMissing::class,
        RobotsTxtBlocksAll::class,
        SitemapMissing::class,
        SitemapInvalid::class,
        BrokenInternalLink::class,
        BrokenExternalLink::class,
        OrphanPage::class,
    ];

    public function register(): void
    {
        $this->app->singleton(RuleRunner::class, fn () => new RuleRunner(
            array_map(fn (string $rule) => $this->app->make($rule), self::RULES),
        ));

        $this->app->singleton(SiteRuleRunner::class, fn () => new SiteRuleRunner(
            array_map(fn (string $rule) => $this->app->make($rule), self::SITE_RULES),
        ));
    }
}
