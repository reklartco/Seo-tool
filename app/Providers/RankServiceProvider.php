<?php

namespace App\Providers;

use App\Seo\Rank\DataForSeoProvider;
use App\Seo\Rank\NullRankProvider;
use App\Seo\Rank\RankProvider;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;

class RankServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RankProvider::class, function ($app) {
            $login = config('services.dataforseo.login');
            $password = config('services.dataforseo.password');

            if (blank($login) || blank($password)) {
                return new NullRankProvider;
            }

            return match (config('services.dataforseo.provider', 'dataforseo')) {
                default => new DataForSeoProvider($app->make(HttpFactory::class), $login, $password),
            };
        });
    }
}
