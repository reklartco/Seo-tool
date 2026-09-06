<?php

namespace App\Providers;

use App\Seo\RuleRunner;
use App\Seo\Rules\H1Missing;
use App\Seo\Rules\ImgAltMissing;
use App\Seo\Rules\MetaDescMissing;
use App\Seo\Rules\Rule;
use App\Seo\Rules\TitleMissing;
use App\Seo\Rules\TitleTooLong;
use Illuminate\Support\ServiceProvider;

class SeoServiceProvider extends ServiceProvider
{
    /**
     * Rules the crawler runs on every page, in report order.
     *
     * @var list<class-string<Rule>>
     */
    public const RULES = [
        TitleMissing::class,
        TitleTooLong::class,
        MetaDescMissing::class,
        H1Missing::class,
        ImgAltMissing::class,
    ];

    public function register(): void
    {
        $this->app->singleton(RuleRunner::class, function () {
            return new RuleRunner(array_map(
                fn (string $rule) => $this->app->make($rule),
                self::RULES,
            ));
        });
    }
}
