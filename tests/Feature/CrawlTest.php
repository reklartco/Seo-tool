<?php

use App\Actions\StartCrawl;
use App\Jobs\CrawlPageJob;
use App\Jobs\FinishCrawlJob;
use App\Jobs\StartCrawlJob;
use App\Models\Crawl;
use App\Models\Issue;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Seo\Crawler\HttpFetcher;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;

/**
 * Serves a small fake site so the crawler can be exercised end to end
 * without touching the network.
 */
function fakeSite(array $routes): void
{
    $handler = function ($request) use ($routes) {
        $path = $request->getUri()->getPath();
        $body = $routes[$path] ?? null;

        if ($body === null) {
            return new Response(404, ['Content-Type' => 'text/html'], '<html><body>yok</body></html>');
        }

        $type = str_ends_with($path, '.xml') ? 'application/xml'
            : (str_ends_with($path, '.txt') ? 'text/plain' : 'text/html');

        return new Response(200, ['Content-Type' => $type], $body);
    };

    app()->instance(HttpFetcher::class, new HttpFetcher(
        new Client(['handler' => HandlerStack::create(new class($handler)
        {
            public function __construct(private $handler) {}

            public function __invoke($request, array $options)
            {
                return Create::promiseFor(($this->handler)($request));
            }
        })])
    ));
}

function crawlProject(array $routes): Crawl
{
    fakeSite($routes);

    $user = User::factory()->create();
    $plan = Plan::factory()->create(['max_monthly_crawl_pages' => 500]);
    $team = Team::factory()->create(['owner_id' => $user->id, 'plan_id' => $plan->id]);
    $team->users()->attach($user, ['role' => 'owner']);

    $project = Project::factory()->create([
        'team_id' => $team->id,
        'domain' => 'ornek.test',
        'max_pages' => 20,
    ]);

    $crawl = $project->crawls()->create(['status' => 'queued']);

    Bus::fake([FinishCrawlJob::class]);
    app(StartCrawlJob::class, ['crawl' => $crawl])->handle(app(HttpFetcher::class));

    return $crawl->fresh();
}

const HOME = <<<'HTML'
<html lang="tr"><head><title>Ana sayfa başlığı yeterince uzun bir metin</title>
<meta name="description" content="Bu ana sayfanın açıklaması yeterince uzun olsun diye yazılmış bir cümledir ve devam ediyor.">
<meta name="viewport" content="width=device-width"><link rel="canonical" href="https://ornek.test/">
<meta property="og:title" content="Ana"><meta property="og:image" content="/a.png">
<meta name="twitter:card" content="summary">
<script type="application/ld+json">{"@type":"WebSite"}</script></head>
<body><h1>Ana sayfa</h1><a href="/hakkimizda">Hakkımızda</a><a href="/kirik">Kırık</a>
<img src="/a.png" alt="a" width="10" height="10"></body></html>
HTML;

const ABOUT = <<<'HTML'
<html><head></head><body><img src="/b.png"><a href="/">Ana</a></body></html>
HTML;

it('crawls a site, stores pages and reports issues', function () {
    Notification::fake();

    $crawl = crawlProject([
        '/' => HOME,
        '/hakkimizda' => ABOUT,
        '/robots.txt' => "User-agent: *\nDisallow: /gizli\nSitemap: https://ornek.test/sitemap.xml",
        '/sitemap.xml' => '<?xml version="1.0"?><urlset><url><loc>https://ornek.test/</loc></url>'
            .'<url><loc>https://ornek.test/hakkimizda</loc></url></urlset>',
    ]);

    // The batch runs synchronously in tests: execute the queued page jobs.
    foreach (['https://ornek.test/', 'https://ornek.test/hakkimizda', 'https://ornek.test/kirik'] as $i => $url) {
        app()->call([new CrawlPageJob($crawl->id, $url, $i === 0 ? 0 : 1), 'handle']);
    }

    app()->call([new FinishCrawlJob($crawl->id), 'handle']);

    $crawl->refresh();
    $pages = Page::where('project_id', $crawl->project_id)->get();

    expect($crawl->status)->toBe('done')
        ->and($pages->pluck('url'))->toContain('https://ornek.test/', 'https://ornek.test/hakkimizda')
        ->and($pages->firstWhere('url', 'https://ornek.test/')->title)->toBe('Ana sayfa başlığı yeterince uzun bir metin')
        ->and($pages->firstWhere('url', 'https://ornek.test/kirik')->status_code)->toBe(404)
        ->and($crawl->health_score)->toBeGreaterThanOrEqual(0);

    $rules = Issue::where('crawl_id', $crawl->id)->pluck('rule_key')->unique();

    expect($rules)->toContain('title_missing')          // /hakkimizda has no title
        ->and($rules)->toContain('img_alt_missing')      // /hakkimizda image
        ->and($rules)->toContain('status_4xx')           // /kirik
        ->and($rules)->toContain('broken_internal_link'); // home links to /kirik
});

it('marks sitemap membership and counts incoming links', function () {
    Notification::fake();

    $crawl = crawlProject([
        '/' => HOME,
        '/hakkimizda' => ABOUT,
        '/robots.txt' => "User-agent: *\nSitemap: https://ornek.test/sitemap.xml",
        '/sitemap.xml' => '<?xml version="1.0"?><urlset><url><loc>https://ornek.test/</loc></url></urlset>',
    ]);

    foreach (['https://ornek.test/', 'https://ornek.test/hakkimizda'] as $i => $url) {
        app()->call([new CrawlPageJob($crawl->id, $url, $i), 'handle']);
    }

    app()->call([new FinishCrawlJob($crawl->id), 'handle']);

    $home = Page::where('url', 'https://ornek.test/')->first();
    $about = Page::where('url', 'https://ornek.test/hakkimizda')->first();

    expect($home->in_sitemap)->toBeTrue()
        ->and($about->in_sitemap)->toBeFalse()
        ->and($about->internal_links_in)->toBe(1)
        ->and($home->internal_links_in)->toBe(1);
});

it('refuses a second crawl while one is running', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['owner_id' => $user->id]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $project->crawls()->create(['status' => 'running']);

    expect(fn () => app(StartCrawl::class)($project))
        ->toThrow(RuntimeException::class);
});

it('refuses to crawl when the monthly page budget is spent', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['max_monthly_crawl_pages' => 10]);
    $team = Team::factory()->create(['owner_id' => $user->id, 'plan_id' => $plan->id]);
    $team->currentUsage()->update(['crawled_pages' => 10]);
    $project = Project::factory()->create(['team_id' => $team->id]);

    expect(fn () => app(StartCrawl::class)($project))
        ->toThrow(RuntimeException::class);
});
