<div class="space-y-5">
    @if (! $project)
        <x-app.empty-state
            title="Henüz proje yok"
            description="Bir alan adı ekle, taramayı başlatalım ve ilk hatalarını çıkaralım."
            action="Yeni proje oluştur"
            :href="route('projects.create')" />
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-app.stat-card label="Sağlık Skoru" :value="$stats['health']" :delta="$stats['health_delta']" suffix="/100">
                <x-slot:footer>
                    <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-canvas">
                        <div @class([
                            'h-full rounded-full transition-all',
                            'bg-up' => $stats['health'] >= 70,
                            'bg-warn' => $stats['health'] >= 40 && $stats['health'] < 70,
                            'bg-down' => $stats['health'] < 40,
                        ]) style="width: {{ $stats['health'] }}%"></div>
                    </div>
                </x-slot:footer>
            </x-app.stat-card>

            <x-app.stat-card label="Açık Hata" :value="$stats['issues']" :delta="-$stats['issues_delta']">
                <x-slot:footer>
                    <p class="mt-3 text-xs text-ink-muted">
                        <span class="font-semibold text-down">{{ $stats['critical'] }}</span> kritik
                    </p>
                </x-slot:footer>
            </x-app.stat-card>

            <x-app.stat-card label="Anahtar Kelime" :value="$stats['keywords']" suffix="takipte">
                <x-slot:footer>
                    <p class="mt-3 text-xs text-ink-muted">
                        <span class="font-semibold text-up">{{ $stats['keywords_top10'] }}</span> ilk sayfada
                    </p>
                </x-slot:footer>
            </x-app.stat-card>

            <x-app.stat-card label="GSC Tıklama" :value="$stats['gsc_clicks']" :delta="$stats['gsc_delta']">
                <x-slot:footer>
                    <p class="mt-3 text-xs text-ink-muted">son 28 gün</p>
                </x-slot:footer>
            </x-app.stat-card>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <section class="card">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <h2 class="text-sm font-semibold">Sıra Değişimleri</h2>
                    <a href="{{ route('keywords') }}" wire:navigate class="text-xs font-medium text-brand-600 hover:text-brand-700">tümü →</a>
                </div>

                <ul class="divide-y divide-line">
                    @forelse ($movers as $keyword)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <span @class([
                                'text-sm font-bold',
                                'text-up' => $keyword->rank_delta > 0,
                                'text-down' => $keyword->rank_delta < 0,
                            ])>{{ $keyword->rank_delta > 0 ? '▲' : '▼' }}</span>

                            <span class="min-w-0 flex-1 truncate text-sm">{{ $keyword->keyword }}</span>

                            <span class="text-xs text-ink-faint">{{ $keyword->previous_rank ?? $keyword->start_rank ?? '—' }} →</span>
                            <span class="text-sm font-semibold">#{{ $keyword->current_rank }}</span>

                            <span class="{{ $keyword->rank_delta > 0 ? 'chip-up' : 'chip-down' }}">
                                {{ $keyword->rank_delta > 0 ? '+' : '' }}{{ $keyword->rank_delta }}
                            </span>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-sm text-ink-faint">Henüz sıra verisi yok.</li>
                    @endforelse
                </ul>
            </section>

            <section class="card">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <h2 class="text-sm font-semibold">Search Console · 28 gün</h2>
                    <a href="{{ route('search-console') }}" wire:navigate class="text-xs font-medium text-brand-600 hover:text-brand-700">detay →</a>
                </div>

                <div class="px-5 py-4">
                    @if ($daily->isEmpty())
                        <p class="py-10 text-center text-sm text-ink-faint">
                            Search Console bağlı değil.
                            <a href="{{ route('integrations') }}" wire:navigate class="font-medium text-brand-600">Bağla →</a>
                        </p>
                    @else
                        <x-app.line-chart
                            :series="$daily->pluck('clicks')"
                            :labels="$daily->pluck('date')->map(fn ($d) => $d->format('d.m'))->all()"
                            height="h-40" />
                    @endif
                </div>
            </section>
        </div>

        <section class="card">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="text-sm font-semibold">Öncelikli Hatalar</h2>
                <a href="{{ route('issues') }}" wire:navigate class="text-xs font-medium text-brand-600 hover:text-brand-700">tümü →</a>
            </div>

            <ul class="divide-y divide-line">
                @forelse ($topIssues as $issue)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span @class([
                            'h-2 w-2 shrink-0 rounded-full',
                            'bg-down' => $issue->severity === 'critical',
                            'bg-warn' => $issue->severity === 'warning',
                            'bg-ink-faint' => $issue->severity === 'notice',
                        ])></span>

                        <span class="min-w-0 flex-1 truncate text-sm">
                            <span class="font-semibold">{{ $issue->pages }}</span> sayfada {{ rule_label($issue->rule_key, $issue->message) }}
                        </span>

                        @if (\App\Jobs\GenerateFixJob::fieldFor($issue->rule_key))
                            <a href="{{ route('fixes') }}" wire:navigate class="btn-ghost px-2.5 py-1 text-xs">AI ile düzelt</a>
                        @else
                            <a href="{{ route('issues') }}" wire:navigate class="btn-ghost px-2.5 py-1 text-xs">gör</a>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-6 text-sm text-ink-faint">Açık hata yok. Tarama yaparak başlayabilirsin.</li>
                @endforelse
            </ul>
        </section>

        @if ($opportunities->isNotEmpty())
            <section class="card">
                <div class="border-b border-line px-5 py-4">
                    <h2 class="text-sm font-semibold">Fırsat Kelimeler</h2>
                    <p class="mt-0.5 text-xs text-ink-muted">Search Console'da sıra 8-20 arası, gösterimi yüksek sorgular.</p>
                </div>

                <ul class="divide-y divide-line">
                    @foreach ($opportunities as $row)
                        <li class="flex flex-wrap items-center gap-3 px-5 py-3">
                            <span class="min-w-0 flex-1 truncate text-sm">{{ $row->query }}</span>
                            <span class="text-xs text-ink-muted">sıra {{ number_format((float) $row->position, 1, ',', '.') }}</span>
                            <span class="text-xs text-ink-muted">{{ number_format($row->impressions, 0, ',', '.') }} gösterim</span>
                            <a href="{{ route('search-console') }}" wire:navigate class="btn-ghost px-2.5 py-1 text-xs">Takibe al</a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    @endif
</div>
