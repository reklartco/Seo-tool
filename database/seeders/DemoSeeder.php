<?php

namespace Database\Seeders;

use App\Models\Crawl;
use App\Models\Fix;
use App\Models\GscDaily;
use App\Models\GscQuery;
use App\Models\GscToken;
use App\Models\Issue;
use App\Models\Keyword;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Project;
use App\Models\RankHistory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * A single logged-in-able account with one project full of realistic data,
 * so the UI can be reviewed before the crawler and SERP jobs exist.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'demo@seotool.test'],
            ['name' => 'Demo Kullanıcı', 'password' => 'password', 'email_verified_at' => now(), 'is_super_admin' => true],
        );

        $team = Team::firstOrCreate(
            ['slug' => 'demo-takim'],
            [
                'owner_id' => $user->id,
                'name' => 'Reklart',
                'plan_id' => Plan::where('slug', 'pro')->value('id'),
            ],
        );

        $team->users()->syncWithoutDetaching([$user->id => ['role' => 'owner']]);
        $user->forceFill(['current_team_id' => $team->id])->save();

        $project = Project::firstOrCreate(
            ['team_id' => $team->id, 'domain' => '1etiket.com.tr'],
            [
                'name' => '1etiket',
                'cms' => 'woocommerce',
                'crawl_frequency' => 'weekly',
                'max_pages' => 500,
                'health_score' => 78,
                'health_score_delta' => 6,
                'last_crawled_at' => now()->subHours(2),
            ],
        );

        $crawl = Crawl::firstOrCreate(
            ['project_id' => $project->id, 'status' => 'done'],
            [
                'pages_total' => 142,
                'pages_crawled' => 142,
                'issues_count' => 142,
                'health_score' => 78,
                'started_at' => now()->subHours(2)->subMinutes(6),
                'finished_at' => now()->subHours(2),
            ],
        );

        $this->seedPagesAndIssues($project, $crawl);
        $this->seedKeywords($project);
        $this->seedSearchConsole($project);
        $this->seedFixes($project);
    }

    private function seedSearchConsole(Project $project): void
    {
        if ($project->gscDaily()->exists()) {
            return;
        }

        $project->update(['gsc_connected_at' => now()]);

        // Demo token: enough for the screen to render, not a working grant.
        GscToken::updateOrCreate(
            ['project_id' => $project->id],
            [
                'access_token' => 'demo-access-token',
                'refresh_token' => 'demo-refresh-token',
                'expires_at' => now()->addYear(),
                'property' => 'sc-domain:'.$project->domain,
            ],
        );

        for ($i = 55; $i >= 2; $i--) {
            $impressions = random_int(900, 1800) + (55 - $i) * 12;
            $clicks = (int) round($impressions * (random_int(30, 55) / 1000));

            GscDaily::create([
                'project_id' => $project->id,
                'date' => now()->subDays($i)->toDateString(),
                'clicks' => $clicks,
                'impressions' => $impressions,
                'ctr' => round($clicks / $impressions, 4),
                'position' => round(random_int(90, 160) / 10, 2),
            ]);
        }

        $queries = [
            ['kare etiket baskı', 890, 12, 11.2],
            ['etiket baskı fiyatları', 1240, 64, 6.4],
            ['şeffaf sticker', 610, 9, 13.8],
            ['barkod etiketi çeşitleri', 430, 5, 15.1],
            ['ürün etiketi tasarımı', 380, 41, 4.2],
        ];

        foreach ($queries as [$query, $impressions, $clicks, $position]) {
            GscQuery::create([
                'project_id' => $project->id,
                'date' => now()->subDays(2)->toDateString(),
                'query' => $query,
                'page' => $project->url().'/'.Str::slug($query),
                'clicks' => $clicks,
                'impressions' => $impressions,
                'ctr' => round($clicks / $impressions, 4),
                'position' => $position,
            ]);
        }
    }

    private function seedFixes(Project $project): void
    {
        if ($project->fixes()->exists()) {
            return;
        }

        $issues = $project->issues()
            ->whereIn('rule_key', ['meta_desc_missing', 'title_missing'])
            ->with('page')
            ->limit(3)
            ->get();

        $suggestions = [
            'Etiket baskı fiyatları, kağıt seçenekleri ve 3 iş gününde teslimat: hemen teklif al.',
            'Kare etiket baskısında ölçü, malzeme ve lak seçenekleri | 1etiket',
            'Ürün etiketi baskısı için tasarım desteği ve hızlı üretim; fiyat teklifini dakikalar içinde al.',
        ];

        foreach ($issues as $index => $issue) {
            Fix::create([
                'issue_id' => $issue->id,
                'project_id' => $project->id,
                'page_id' => $issue->page_id,
                'field' => $issue->rule_key === 'title_missing' ? 'title' : 'meta_description',
                'old_value' => $issue->rule_key === 'title_missing' ? $issue->page?->title : $issue->page?->meta_description,
                'new_value' => $suggestions[$index] ?? $suggestions[0],
                'generated_by' => 'ai',
                'model' => 'claude-sonnet-5',
                'prompt_tokens' => random_int(180, 420),
                'status' => 'draft',
            ]);
        }
    }

    private function seedPagesAndIssues(Project $project, Crawl $crawl): void
    {
        if ($project->pages()->exists()) {
            return;
        }

        $samples = [
            ['/etiket-baski', 'Etiket Baskı', null, 'Etiket Baskı'],
            ['/sticker-baski', 'Sticker Baskı | 1etiket', 'Özel kesim sticker baskı, hızlı teslimat.', 'Sticker Baskı'],
            ['/kare-etiket-baski', null, null, 'Kare Etiket'],
            ['/urun-etiketi', 'Ürün etiketi baskısı için uygun fiyatlı ve hızlı çözümler burada sizi bekliyor', null, null],
            ['/barkod-etiketi', 'Barkod Etiketi', 'Barkod etiketi çeşitleri ve fiyatları.', 'Barkod Etiketi'],
        ];

        foreach ($samples as [$path, $title, $desc, $h1]) {
            $url = $project->url().$path;

            $page = Page::create([
                'project_id' => $project->id,
                'url' => $url,
                'url_hash' => Page::hashUrl($url),
                'status_code' => 200,
                'title' => $title,
                'meta_description' => $desc,
                'h1' => $h1,
                'word_count' => random_int(180, 1400),
                'internal_links_in' => random_int(0, 20),
                'internal_links_out' => random_int(5, 40),
                'depth' => 1,
                'first_seen_at' => now()->subMonth(),
                'last_crawled_at' => $crawl->finished_at,
                'last_crawl_id' => $crawl->id,
            ]);

            $issues = [];

            if ($title === null) {
                $issues[] = ['title_missing', 'critical', 'meta', 'Sayfada title etiketi yok.'];
            } elseif (mb_strlen($title) > 60) {
                $issues[] = ['title_too_long', 'warning', 'meta', 'Title 60 karakterden uzun.'];
            }

            if ($desc === null) {
                $issues[] = ['meta_desc_missing', 'critical', 'meta', 'Sayfada meta description yok.'];
            }

            if ($h1 === null) {
                $issues[] = ['h1_missing', 'critical', 'content', 'Sayfada H1 başlığı yok.'];
            }

            $issues[] = ['img_alt_missing', 'warning', 'images', 'Görselde alt metni yok.'];

            foreach ($issues as [$key, $severity, $category, $message]) {
                Issue::create([
                    'project_id' => $project->id,
                    'crawl_id' => $crawl->id,
                    'page_id' => $page->id,
                    'rule_key' => $key,
                    'severity' => $severity,
                    'category' => $category,
                    'message' => $message,
                    'details' => ['url' => $url],
                ]);
            }
        }
    }

    private function seedKeywords(Project $project): void
    {
        $samples = [
            ['etiket baskı', 'Baskı', 14, 7, 1600, 4.20],
            ['sticker baskı', 'Baskı', 11, 8, 980, 3.10],
            ['ürün etiketi', 'E-ticaret', 6, 9, 720, 2.40],
            ['barkod etiketi', 'E-ticaret', 20, 12, 480, 1.90],
            ['kare etiket baskı', 'Baskı', 17, 11, 260, 2.80],
            ['şeffaf etiket', 'Baskı', 25, 19, 190, 2.10],
        ];

        foreach ($samples as [$keyword, $tag, $start, $current, $volume, $cpc]) {
            $model = Keyword::firstOrCreate(
                ['project_id' => $project->id, 'keyword' => $keyword],
                [
                    'target_url' => $project->url().'/'.Str::slug($keyword),
                    'tag' => $tag,
                    'search_volume' => $volume,
                    'cpc' => $cpc,
                    'competition' => 0.45,
                    'start_rank' => $start,
                    'previous_rank' => $start,
                    'current_rank' => $current,
                    'best_rank' => min($start, $current),
                    'rank_delta' => $start - $current,
                    'last_checked_at' => now(),
                ],
            );

            if ($model->history()->exists()) {
                continue;
            }

            $steps = 30;

            for ($i = $steps; $i >= 0; $i--) {
                $progress = ($steps - $i) / $steps;
                $rank = (int) round($start + ($current - $start) * $progress);

                RankHistory::create([
                    'keyword_id' => $model->id,
                    'checked_at' => now()->subDays($i)->toDateString(),
                    'rank' => max(1, $rank + random_int(-1, 1)),
                    'serp_url' => $model->target_url,
                ]);
            }
        }
    }
}
