<?php

namespace App\Livewire\Integrations;

use App\Livewire\Concerns\WithCurrentProject;
use App\Models\WpConnection;
use App\Services\WordPress\WordPressClient;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
#[Title('Entegrasyonlar')]
class Index extends Component
{
    use WithCurrentProject;

    /** Shown once, right after generation. */
    public ?string $freshKey = null;

    public function mount(): void
    {
        $this->mountCurrentProject();
    }

    public function generateKey(): void
    {
        if (! $this->project) {
            return;
        }

        $key = 'sk_'.Str::random(48);

        WpConnection::updateOrCreate(
            ['project_id' => $this->project->id],
            [
                'site_url' => $this->project->url(),
                'api_key_hash' => Crypt::encryptString($key),
            ],
        );

        $this->freshKey = $key;
        $this->dispatch('toast', message: 'API anahtarı üretildi. Bu ekranı kapatmadan kopyala.', type: 'success');
    }

    public function testConnection(WordPressClient $client): void
    {
        $connection = $this->project?->wpConnection;

        if (! $connection) {
            return;
        }

        try {
            $info = $client->ping($connection, decrypt_wp_key($connection));

            $connection->update([
                'wp_version' => $info['wp_version'] ?? $connection->wp_version,
                'plugin_version' => $info['plugin_version'] ?? $connection->plugin_version,
                'seo_plugin' => $info['seo_plugin'] ?? $connection->seo_plugin,
                'capabilities' => $info['capabilities'] ?? $connection->capabilities,
                'last_ping_at' => now(),
            ]);

            $this->project->update(['wp_connected_at' => now()]);
            $this->dispatch('toast', message: 'Bağlantı çalışıyor.', type: 'success');
        } catch (Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function disconnectWordPress(): void
    {
        $this->project?->wpConnection?->delete();
        $this->project?->update(['wp_connected_at' => null]);
        $this->freshKey = null;

        $this->dispatch('toast', message: 'WordPress bağlantısı kaldırıldı.', type: 'success');
    }

    public function render()
    {
        return view('livewire.integrations.index', [
            'connection' => $this->project?->wpConnection,
            'gsc' => $this->project?->gscToken,
        ]);
    }
}
