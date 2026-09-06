<div class="space-y-5">
    @if (! $project)
        <x-app.empty-state
            title="Önce bir proje oluştur"
            description="Anahtar kelime takibi bir projeye bağlıdır."
            action="Yeni proje oluştur"
            :href="route('projects.create')" />
    @else
        <div class="card card-pad">
            <div class="flex flex-wrap items-center gap-x-8 gap-y-3">
                <div>
                    <p class="stat-label">Takipte</p>
                    <p class="mt-0.5 text-2xl font-semibold">{{ $summary['tracked'] }}</p>
                </div>
                <div>
                    <p class="stat-label">İlk 3</p>
                    <p class="mt-0.5 text-2xl font-semibold text-up">{{ $summary['top3'] }}</p>
                </div>
                <div>
                    <p class="stat-label">İlk 10</p>
                    <p class="mt-0.5 text-2xl font-semibold">{{ $summary['top10'] }}</p>
                </div>
                <div>
                    <p class="stat-label">İlk 100</p>
                    <p class="mt-0.5 text-2xl font-semibold">{{ $summary['top100'] }}</p>
                </div>
                <div>
                    <p class="stat-label">Ortalama Sıra</p>
                    <p class="mt-0.5 text-2xl font-semibold">{{ number_format($summary['average'], 1, ',', '.') }}</p>
                </div>

                <div class="ml-auto flex items-center gap-2">
                    <div class="flex rounded-lg border border-line p-0.5">
                        <button wire:click="$set('view', 'grid')"
                                class="rounded-md px-2.5 py-1 text-xs font-medium {{ $view === 'grid' ? 'bg-canvas text-ink' : 'text-ink-faint' }}">Kart</button>
                        <button wire:click="$set('view', 'table')"
                                class="rounded-md px-2.5 py-1 text-xs font-medium {{ $view === 'table' ? 'bg-canvas text-ink' : 'text-ink-faint' }}">Tablo</button>
                    </div>
                    <button type="button" class="btn-primary">
                        <x-app.icon name="plus" class="h-4 w-4" /> Kelime ekle
                    </button>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2 border-t border-line pt-4">
                @foreach (['all' => 'Tümü', 'up' => 'Yükselenler', 'down' => 'Düşenler', 'top10' => 'İlk 10'] as $key => $label)
                    <button wire:click="$set('filter', '{{ $key }}')"
                            class="{{ $filter === $key ? 'chip-brand' : 'chip-neutral' }}">{{ $label }}</button>
                @endforeach

                @foreach ($tags as $item)
                    <button wire:click="$set('tag', '{{ $tag === $item ? '' : $item }}')"
                            class="{{ $tag === $item ? 'chip-brand' : 'chip-neutral' }}">{{ $item }}</button>
                @endforeach
            </div>
        </div>

        @if ($keywords->isEmpty())
            <x-app.empty-state
                title="Bu filtrede kelime yok"
                description="Filtreyi değiştir ya da yeni anahtar kelime ekle." />
        @elseif ($view === 'grid')
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($keywords as $keyword)
                    <article class="card card-pad">
                        <div class="flex items-start gap-2">
                            <h3 class="min-w-0 flex-1 truncate text-[15px] font-semibold">{{ $keyword->keyword }}</h3>
                            @if ($keyword->tag)
                                <span class="chip-brand shrink-0">{{ $keyword->tag }}</span>
                            @endif
                        </div>

                        @if ($keyword->serp_url || $keyword->target_url)
                            <p class="mt-1 flex items-center gap-1.5 truncate text-xs text-ink-muted">
                                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-brand-500"></span>
                                {{ \Illuminate\Support\Str::after($keyword->serp_url ?? $keyword->target_url, '://') }}
                            </p>
                        @endif

                        <div class="mt-4 flex items-end gap-2">
                            <span class="text-sm text-ink-faint">{{ $keyword->start_rank ?? '—' }}.</span>

                            @if ($keyword->rank_delta !== 0)
                                <span @class([
                                    'text-sm font-bold',
                                    'text-up' => $keyword->rank_delta > 0,
                                    'text-down' => $keyword->rank_delta < 0,
                                ])>{{ $keyword->rank_delta > 0 ? '▲' : '▼' }}</span>
                            @endif

                            <span class="text-3xl font-bold leading-none tracking-tight">
                                <span class="text-lg align-top text-ink-faint">#</span>{{ $keyword->current_rank ?? '100+' }}
                            </span>

                            @if ($keyword->rank_delta !== 0)
                                <span class="{{ $keyword->rank_delta > 0 ? 'chip-up' : 'chip-down' }} ml-auto">
                                    {{ $keyword->rank_delta > 0 ? '+' : '' }}{{ $keyword->rank_delta }} sıra
                                </span>
                            @endif
                        </div>

                        <x-app.rank-slider :start="$keyword->start_rank" :current="$keyword->current_rank" />

                        <p class="mt-3 border-t border-line pt-3 text-xs text-ink-muted">
                            @if ($keyword->search_volume)
                                ~{{ number_format($keyword->search_volume, 0, ',', '.') }} arama/ay
                            @else
                                Hacim verisi yok
                            @endif
                            @if ($keyword->cpc)
                                · CPC ₺{{ number_format((float) $keyword->cpc, 2, ',', '.') }}
                            @endif
                        </p>
                    </article>
                @endforeach
            </div>
        @else
            <div class="card overflow-hidden">
                <table class="min-w-full divide-y divide-line text-sm">
                    <thead class="bg-canvas text-left text-xs uppercase tracking-wide text-ink-faint">
                        <tr>
                            <th class="px-5 py-3 font-medium">Kelime</th>
                            <th class="px-5 py-3 font-medium">Etiket</th>
                            <th class="px-5 py-3 font-medium">Sıra</th>
                            <th class="px-5 py-3 font-medium">Değişim</th>
                            <th class="px-5 py-3 font-medium">Hacim</th>
                            <th class="px-5 py-3 font-medium">Son kontrol</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($keywords as $keyword)
                            <tr class="hover:bg-canvas">
                                <td class="px-5 py-3 font-medium">{{ $keyword->keyword }}</td>
                                <td class="px-5 py-3 text-ink-muted">{{ $keyword->tag ?? '—' }}</td>
                                <td class="px-5 py-3 font-semibold">#{{ $keyword->current_rank ?? '100+' }}</td>
                                <td class="px-5 py-3">
                                    <span class="{{ $keyword->rank_delta > 0 ? 'chip-up' : ($keyword->rank_delta < 0 ? 'chip-down' : 'chip-neutral') }}">
                                        {{ $keyword->rank_delta > 0 ? '+' : '' }}{{ $keyword->rank_delta }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-ink-muted">{{ $keyword->search_volume ? number_format($keyword->search_volume, 0, ',', '.') : '—' }}</td>
                                <td class="px-5 py-3 text-ink-muted">{{ $keyword->last_checked_at?->diffForHumans() ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</div>
