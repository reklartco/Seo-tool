<div class="space-y-5">
    @if (! $project)
        <x-app.empty-state title="Önce bir proje oluştur" action="Yeni proje oluştur" :href="route('projects.create')" />
    @else
        @if (session('error'))
            <div class="card card-pad border-down/30 bg-down/5 text-sm text-down">{{ session('error') }}</div>
        @endif

        <div class="grid gap-5 lg:grid-cols-3">
            {{-- WordPress --}}
            <section class="card card-pad flex flex-col">
                <div class="flex items-center gap-2">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-canvas text-ink-muted">
                        <x-app.icon name="plug" class="h-4 w-4" />
                    </span>
                    <h2 class="text-sm font-semibold">WordPress</h2>
                    @if ($connection?->last_ping_at)
                        <span class="chip-up ml-auto">Bağlı</span>
                    @else
                        <span class="chip-neutral ml-auto">Bağlı değil</span>
                    @endif
                </div>

                <p class="mt-2 flex-1 text-xs text-ink-muted">
                    Eklentiyi kur, API anahtarını yapıştır; hataları doğrudan sitede düzeltelim.
                </p>

                @if ($freshKey)
                    <div class="mt-3 rounded-lg border border-brand-200 bg-brand-50 p-3">
                        <p class="text-[11px] font-medium text-brand-700">API anahtarı (yalnızca şimdi görünür)</p>
                        <code class="mt-1 block break-all text-[11px]">{{ $freshKey }}</code>
                    </div>
                @endif

                @if ($connection)
                    <dl class="mt-3 space-y-1 text-[11px] text-ink-muted">
                        <div class="flex justify-between"><dt>Site</dt><dd class="truncate">{{ $connection->site_url }}</dd></div>
                        <div class="flex justify-between"><dt>WP sürümü</dt><dd>{{ $connection->wp_version ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt>SEO eklentisi</dt><dd>{{ $connection->seo_plugin ?? 'tespit edilmedi' }}</dd></div>
                        <div class="flex justify-between"><dt>Son ping</dt><dd>{{ $connection->last_ping_at?->diffForHumans() ?? '—' }}</dd></div>
                    </dl>
                @endif

                <div class="mt-4 flex flex-wrap gap-2">
                    <button wire:click="generateKey" class="btn-primary px-3 py-1.5 text-xs">
                        {{ $connection ? 'Anahtarı yenile' : 'API anahtarı üret' }}
                    </button>
                    <a href="{{ route('integrations.plugin') }}" class="btn-ghost px-3 py-1.5 text-xs">Eklentiyi indir</a>
                    @if ($connection)
                        <button wire:click="testConnection" class="btn-ghost px-3 py-1.5 text-xs">Bağlantıyı test et</button>
                        <button wire:click="disconnectWordPress" wire:confirm="Bağlantıyı kaldıralım mı?"
                                class="btn-ghost px-3 py-1.5 text-xs text-down">Kaldır</button>
                    @endif
                </div>
            </section>

            {{-- Search Console --}}
            <section class="card card-pad flex flex-col">
                <div class="flex items-center gap-2">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-canvas text-ink-muted">
                        <x-app.icon name="google" class="h-4 w-4" />
                    </span>
                    <h2 class="text-sm font-semibold">Search Console</h2>
                    @if ($gsc?->property)
                        <span class="chip-up ml-auto">Bağlı</span>
                    @else
                        <span class="chip-neutral ml-auto">Bağlı değil</span>
                    @endif
                </div>

                <p class="mt-2 flex-1 text-xs text-ink-muted">
                    Gerçek tıklama ve gösterim verisi; fırsat kelimelerin kaynağı.
                </p>

                @if ($gsc?->property)
                    <p class="mt-3 truncate text-[11px] text-ink-muted">{{ $gsc->property }}</p>
                @endif

                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('search-console.connect', $project) }}" class="btn-primary px-3 py-1.5 text-xs">
                        {{ $gsc ? 'Yeniden yetkilendir' : 'Google ile bağlan' }}
                    </a>
                    @if ($gsc)
                        <a href="{{ route('search-console') }}" wire:navigate class="btn-ghost px-3 py-1.5 text-xs">Veriyi gör</a>
                    @endif
                </div>
            </section>

            {{-- Analytics, v2 --}}
            <section class="card card-pad flex flex-col opacity-60">
                <div class="flex items-center gap-2">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-canvas text-ink-faint">
                        <x-app.icon name="trend" class="h-4 w-4" />
                    </span>
                    <h2 class="text-sm font-semibold">Google Analytics</h2>
                    <span class="chip-neutral ml-auto">Yakında</span>
                </div>
                <p class="mt-2 flex-1 text-xs text-ink-muted">Dönüşüm ve oturum verisi bir sonraki sürümde.</p>
            </section>
        </div>

        <section class="card card-pad">
            <h2 class="text-sm font-semibold">WordPress eklentisi nasıl kurulur?</h2>
            <ol class="mt-3 space-y-2 text-sm text-ink-muted">
                <li><span class="font-medium text-ink">1.</span> "Eklentiyi indir" ile zip dosyasını al, WordPress → Eklentiler → Yeni Ekle → Eklenti Yükle ekranından kur ve etkinleştir.</li>
                <li><span class="font-medium text-ink">2.</span> Panelde "API anahtarı üret" düğmesine bas ve anahtarı kopyala.</li>
                <li><span class="font-medium text-ink">3.</span> WordPress → Ayarlar → SEO Connector ekranına panel adresini ({{ config('app.url') }}), proje no'sunu ({{ $project->id }}) ve anahtarı yapıştır, kaydet.</li>
                <li><span class="font-medium text-ink">4.</span> Kaydettiğinde eklenti panele bağlanır; bu sayfada "Bağlı" rozetini görürsün.</li>
            </ol>
        </section>
    @endif
</div>
