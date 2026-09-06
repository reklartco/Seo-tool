<?php

use App\Jobs\ApplyFixJob;
use App\Jobs\GenerateFixJob;
use App\Livewire\Fixes\Index;
use App\Models\Fix;
use App\Models\Issue;
use App\Models\Page;
use App\Models\Project;
use App\Models\WpConnection;
use App\Services\WordPress\Signature;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

function pageWithIssue(Project $project, string $rule = 'meta_desc_missing'): Issue
{
    $url = 'https://ornek.com/etiket-'.Page::where('project_id', $project->id)->count();

    $page = Page::create([
        'project_id' => $project->id,
        'url' => $url,
        'url_hash' => Page::hashUrl($url),
        'status_code' => 200,
        'title' => 'Etiket',
        'h1' => 'Etiket Baskı',
    ]);

    return $project->issues()->create([
        'page_id' => $page->id,
        'rule_key' => $rule,
        'severity' => 'critical',
        'category' => 'meta',
        'message' => 'Sayfada meta description yok.',
        'status' => 'open',
    ]);
}

function connectWordPress(Project $project, string $key = 'sk_test'): WpConnection
{
    $connection = WpConnection::create([
        'project_id' => $project->id,
        'site_url' => 'https://ornek.com',
        'api_key_hash' => Crypt::encryptString($key),
        'last_ping_at' => now(),
    ]);

    $project->update(['wp_connected_at' => now()]);

    return $connection;
}

it('signs and verifies a request, and rejects a replayed one', function () {
    $signature = Signature::make('gizli', 'POST', '/wp-json/seoconnector/v1/meta', time(), '{"a":1}');

    expect(Signature::verify('gizli', $signature, 'POST', '/wp-json/seoconnector/v1/meta', time(), '{"a":1}'))->toBeTrue()
        ->and(Signature::verify('gizli', $signature, 'POST', '/wp-json/seoconnector/v1/image-alt', time(), '{"a":1}'))->toBeFalse()
        ->and(Signature::verify('baska', $signature, 'POST', '/wp-json/seoconnector/v1/meta', time(), '{"a":1}'))->toBeFalse()
        ->and(Signature::verify('gizli', $signature, 'POST', '/wp-json/seoconnector/v1/meta', time() - 600, '{"a":1}'))->toBeFalse();
});

it('accepts a correctly signed handshake', function () {
    $project = projectWithPlan();
    connectWordPress($project, 'sk_handshake');

    $body = json_encode([
        'project_id' => $project->id,
        'site_url' => 'https://ornek.com',
        'plugin_version' => '1.0.0',
        'wp_version' => '6.6',
        'seo_plugin' => 'yoast',
    ]);

    $timestamp = time();

    $this->call('POST', '/api/wp/handshake', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_SEO_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_SEO_SIGNATURE' => Signature::make('sk_handshake', 'POST', '/api/wp/handshake', $timestamp, $body),
    ], $body)->assertOk();

    expect($project->fresh()->wpConnection->seo_plugin)->toBe('yoast')
        ->and($project->fresh()->wp_connected_at)->not->toBeNull();
});

it('rejects a handshake with a bad signature', function () {
    $project = projectWithPlan();
    connectWordPress($project, 'sk_handshake');

    $body = json_encode(['project_id' => $project->id, 'site_url' => 'https://ornek.com']);

    $this->call('POST', '/api/wp/handshake', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_SEO_TIMESTAMP' => (string) time(),
        'HTTP_X_SEO_SIGNATURE' => 'yanlis',
    ], $body)->assertStatus(401);
});

it('drafts a fix from the ai response', function () {
    config(['services.anthropic.key' => 'test-key']);

    Http::fake(['api.anthropic.com/*' => Http::response([
        'content' => [['type' => 'text', 'text' => '{"meta_description": "Etiket baskı hizmetimizle hızlı teslimat."}']],
        'usage' => ['input_tokens' => 200, 'output_tokens' => 40],
    ])]);

    $project = projectWithPlan(['max_monthly_ai_generations' => 50]);
    $issue = pageWithIssue($project);

    app()->call([new GenerateFixJob($issue->id), 'handle']);

    $fix = Fix::where('issue_id', $issue->id)->first();

    expect($fix->status)->toBe('draft')
        ->and($fix->field)->toBe('meta_description')
        ->and($fix->new_value)->toBe('Etiket baskı hizmetimizle hızlı teslimat.')
        ->and($fix->prompt_tokens)->toBe(240)
        ->and($project->team->currentUsage()->ai_generations)->toBe(1);
});

it('records a failed fix when the ai call errors', function () {
    config(['services.anthropic.key' => 'test-key']);
    Http::fake(['api.anthropic.com/*' => Http::response('bozuk', 500)]);

    $project = projectWithPlan(['max_monthly_ai_generations' => 50]);
    $issue = pageWithIssue($project);

    app()->call([new GenerateFixJob($issue->id), 'handle']);

    expect(Fix::where('issue_id', $issue->id)->value('status'))->toBe('failed');
});

it('stops generating once the monthly ai quota is spent', function () {
    config(['services.anthropic.key' => 'test-key']);
    Http::fake();

    $project = projectWithPlan(['max_monthly_ai_generations' => 1]);
    $project->team->currentUsage()->update(['ai_generations' => 1]);
    $issue = pageWithIssue($project);

    app()->call([new GenerateFixJob($issue->id), 'handle']);

    expect(Fix::count())->toBe(0);
    Http::assertNothingSent();
});

it('applies an approved fix to wordpress and closes the issue', function () {
    Http::fake(['ornek.com/wp-json/*' => Http::response(['ok' => true])]);

    $project = projectWithPlan();
    connectWordPress($project);
    $issue = pageWithIssue($project);

    $fix = Fix::create([
        'issue_id' => $issue->id,
        'project_id' => $project->id,
        'page_id' => $issue->page_id,
        'field' => 'meta_description',
        'old_value' => null,
        'new_value' => 'Yeni açıklama.',
        'status' => 'approved',
    ]);

    app()->call([new ApplyFixJob($fix->id), 'handle']);

    expect($fix->fresh()->status)->toBe('applied')
        ->and($issue->fresh()->status)->toBe('auto_fixed')
        ->and($issue->page->fresh()->meta_description)->toBe('Yeni açıklama.');

    Http::assertSent(fn ($request) => $request->hasHeader('X-Seo-Signature'));
});

it('marks the fix failed when wordpress refuses it', function () {
    Http::fake(['ornek.com/wp-json/*' => Http::response(['message' => 'yok'], 404)]);

    $project = projectWithPlan();
    connectWordPress($project);
    $issue = pageWithIssue($project);

    $fix = Fix::create([
        'issue_id' => $issue->id,
        'project_id' => $project->id,
        'page_id' => $issue->page_id,
        'field' => 'meta_description',
        'new_value' => 'Yeni',
        'status' => 'approved',
    ]);

    app()->call([new ApplyFixJob($fix->id), 'handle']);

    expect($fix->fresh()->status)->toBe('failed')
        ->and($fix->fresh()->error)->toContain('404');
});

it('rolls an applied fix back and reopens the issue', function () {
    Http::fake(['ornek.com/wp-json/*' => Http::response(['ok' => true])]);

    $project = projectWithPlan();
    connectWordPress($project);
    $issue = pageWithIssue($project);
    $issue->update(['status' => 'auto_fixed']);

    $fix = Fix::create([
        'issue_id' => $issue->id,
        'project_id' => $project->id,
        'page_id' => $issue->page_id,
        'field' => 'meta_description',
        'new_value' => 'Yeni',
        'status' => 'applied',
        'applied_at' => now(),
    ]);

    app()->call([new ApplyFixJob($fix->id, rollback: true), 'handle']);

    expect($fix->fresh()->status)->toBe('rolled_back')
        ->and($issue->fresh()->status)->toBe('open');
});

it('auto applies when the project opts in and stays under the daily limit', function () {
    config(['services.anthropic.key' => 'test-key']);
    Queue::fake();

    Http::fake(['api.anthropic.com/*' => Http::response([
        'content' => [['type' => 'text', 'text' => '{"meta_description": "Otomatik."}']],
    ])]);

    $project = projectWithPlan(['max_monthly_ai_generations' => 50, 'daily_fix_page_limit' => 20]);
    connectWordPress($project);
    $project->update(['auto_apply_fixes' => true, 'daily_fix_limit' => 20]);

    $issue = pageWithIssue($project);

    app()->call([new GenerateFixJob($issue->id), 'handle']);

    expect(Fix::where('issue_id', $issue->id)->value('status'))->toBe('approved');
    Queue::assertPushed(ApplyFixJob::class);
});

it('keeps fixes in draft when auto apply is off', function () {
    config(['services.anthropic.key' => 'test-key']);
    Queue::fake();

    Http::fake(['api.anthropic.com/*' => Http::response([
        'content' => [['type' => 'text', 'text' => '{"meta_description": "Taslak."}']],
    ])]);

    $project = projectWithPlan(['max_monthly_ai_generations' => 50, 'daily_fix_page_limit' => 20]);
    connectWordPress($project);
    $issue = pageWithIssue($project);

    app()->call([new GenerateFixJob($issue->id), 'handle']);

    expect(Fix::where('issue_id', $issue->id)->value('status'))->toBe('draft');
    Queue::assertNotPushed(ApplyFixJob::class);
});

it('lets a user edit and approve a draft from the fixes screen', function () {
    Queue::fake();

    $project = projectWithPlan();
    connectWordPress($project);
    $issue = pageWithIssue($project);

    $fix = Fix::create([
        'issue_id' => $issue->id,
        'project_id' => $project->id,
        'page_id' => $issue->page_id,
        'field' => 'meta_description',
        'new_value' => 'AI metni',
        'status' => 'draft',
    ]);

    Livewire::actingAs($project->team->owner)
        ->test(Index::class)
        ->set("edited.{$fix->id}", 'Elle düzeltilmiş metin')
        ->call('approve', $fix->id);

    expect($fix->fresh()->new_value)->toBe('Elle düzeltilmiş metin')
        ->and($fix->fresh()->generated_by)->toBe('user')
        ->and($fix->fresh()->status)->toBe('approved');

    Queue::assertPushed(ApplyFixJob::class);
});

it('queues ai drafts for every open issue of a rule', function () {
    Queue::fake();

    $project = projectWithPlan();
    pageWithIssue($project);
    pageWithIssue($project, 'meta_desc_missing');

    Livewire::actingAs($project->team->owner)
        ->test(Index::class)
        ->call('generateForRule', 'meta_desc_missing');

    Queue::assertPushed(GenerateFixJob::class, 2);
});
