<?php

namespace App\Jobs;

use App\Models\Fix;
use App\Services\WordPress\WordPressClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Pushes an approved fix to WordPress and marks the originating issue fixed.
 */
class ApplyFixJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(public int $fixId, public bool $rollback = false) {}

    public function handle(WordPressClient $client): void
    {
        $fix = Fix::with(['project.wpConnection', 'page', 'issue'])->find($this->fixId);
        $connection = $fix?->project->wpConnection;

        if (! $fix || ! $connection) {
            return;
        }

        $apiKey = decrypt_wp_key($connection);

        try {
            if ($this->rollback) {
                $client->rollback($connection, $apiKey, $fix->id);

                $fix->update(['status' => 'rolled_back']);
                $fix->issue?->update(['status' => 'open', 'resolved_by' => null, 'fixed_at' => null]);

                return;
            }

            if ($fix->field === 'alt') {
                $client->updateImageAlt($connection, $apiKey, [
                    'fix_id' => $fix->id,
                    'url' => $fix->old_value,
                    'page_url' => $fix->page?->url,
                    'alt' => $fix->new_value,
                ]);
            } else {
                $client->updateMeta($connection, $apiKey, [
                    'fix_id' => $fix->id,
                    'url' => $fix->page?->url,
                    'field' => $fix->field,
                    'value' => $fix->new_value,
                ]);
            }

            $fix->update(['status' => 'applied', 'applied_at' => now(), 'error' => null]);
            $fix->issue?->update(['status' => 'auto_fixed', 'resolved_by' => 'ai', 'fixed_at' => now()]);

            $this->applyLocally($fix);

            $fix->project->team->currentUsage()->increment('applied_fixes');
        } catch (Throwable $e) {
            $fix->update(['status' => 'failed', 'error' => $e->getMessage()]);
        }
    }

    /** Keep the local page row in step so the panel does not lie until the next crawl. */
    private function applyLocally(Fix $fix): void
    {
        if (! $fix->page || ! in_array($fix->field, ['title', 'meta_description'], true)) {
            return;
        }

        $fix->page->update([$fix->field => $fix->new_value]);
    }
}
