<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WpConnection;
use App\Services\WordPress\Signature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WordPressController extends Controller
{
    /**
     * The plugin announces itself after activation. The request is signed with
     * the same API key the panel generated, which is how we know it is ours.
     */
    public function handshake(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'integer'],
            'site_url' => ['required', 'url'],
            'plugin_version' => ['nullable', 'string', 'max:20'],
            'wp_version' => ['nullable', 'string', 'max:20'],
            'seo_plugin' => ['nullable', 'string', 'max:40'],
            'capabilities' => ['nullable', 'array'],
        ]);

        $connection = WpConnection::where('project_id', $data['project_id'])->first();

        if (! $connection) {
            return response()->json(['message' => 'Bilinmeyen proje.'], 404);
        }

        $valid = Signature::verify(
            decrypt_wp_key($connection),
            (string) $request->header('X-Seo-Signature'),
            'POST',
            '/api/wp/handshake',
            (int) $request->header('X-Seo-Timestamp'),
            $request->getContent(),
        );

        if (! $valid) {
            return response()->json(['message' => 'İmza doğrulanamadı.'], 401);
        }

        $connection->update([
            'site_url' => rtrim($data['site_url'], '/'),
            'plugin_version' => $data['plugin_version'] ?? null,
            'wp_version' => $data['wp_version'] ?? null,
            'seo_plugin' => $data['seo_plugin'] ?? null,
            'capabilities' => $data['capabilities'] ?? null,
            'last_ping_at' => now(),
        ]);

        $connection->project->update(['wp_connected_at' => now()]);

        return response()->json(['message' => 'Bağlantı kuruldu.', 'project' => $connection->project->domain]);
    }
}
