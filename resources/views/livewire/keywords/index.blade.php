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
                    <button type="button" wire:click="$toggle('showAdd')" class="btn-primary">
                        <x-app.icon name="plus" class="h-4 w-4" /> Kelime ekle
                    </button>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2 border-t border-line pt-4">
                @foreach (['all' => 'Tümü', 'up' => 'Yükselenler', 'down' => 'Düşenler', 'top10' => 'İlk 10', 'lost' => 'İlk 100 dışı'] as $key => $label)
                    <button wire:click="$set('filter', '{{ $key }}')"
                            class="{{ $filter === $key ? 'chip-brand' : 'chip-neutral' }}">{{ $label }}</button>
                @endforeach

                @foreach ($tags as $item)
                    <button wire:click="$set('tag', '{{ $tag === $item ? '' : $item }}')"
                            class="{{ $tag === $item ? 'chip-brand' : 'chip-neutral' }}">{{ $item }}</button>
                @endforeach
            </div>
        </div>

        @if ($showAdd)
            <form wire:submit="addKeywords" class="card card-pad space-y-4">
                <div>
                    <label for="bulk" class="block text-sm font-medium">Anahtar kelimeler</label>
                    <p class="mt-0.5 text-xs text-ink-muted">Her satıra bir kelime yaz. Hacim ve sıra verisi arka planda çekilir.</p>
                    <textarea id="bulk" wire:model="bulk" rows="6" class="field mt-2 font-mono text-xs"
                              placeholder="etiket baskı&#10;sticker baskı&#10;ürün etiketi"></textarea>
                    @error('bulk') <p class="mt-1.5 text-xs text-down">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-wrap items-end gap-3">
                    <div class="w-48">
                        <label for="newTag" class="block text-sm font-medium">Etiket (opsiyonel)</label>
                        <input id="newTag" type="text" wire:model="newTag" class="field mt-1.5" placeholder="Baskı">
                    </div>

                    <div class="ml-auto flex gap-2">
                        <button type="button" wire:click="$set('showAdd', false)" class="btn-ghost">Vazgeç</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled">Ekle</button>
                    </div>
                </div>
            </form>
        @endif

        @if ($keywords->isEmpty())
            <x-app.empty-state
                title="Bu filtrede kelime yok"
                description="Filtreyi değiştir ya da yeni anahtar kelime ekle." />
        @elseif ($view === 'grid')
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($keywords as $keyword)
                    <article wire:key="kw-{{ $keyword->id }}" class="card card-pad group relative">
                        <div class="absolute right-3 top-3 hidden gap-1 group-hover:flex">
                            <button wire:click="refreshKeyword({{ $keyword->id }})" title="Sırayı yenile"
                                    class="rounded-md p-1 text-ink-faint hover:bg-canvas hover:text-ink">
                                <x-app.icon name="refresh" class="h-3.5 w-3.5" />
                            </button>
                            <button wire:click="deleteKeyword({{ $keyword->id }})"
                                    wire:confirm="Bu kelimeyi takipten çıkaralım mı?" title="Sil"
                                    class="rounded-md p-1 text-ink-faint hover:bg-canvas hover:text-down">✕</button>
                        </div>

                        <div class="flex items-start gap-2">
                            <button wire:click="showDetail({{ $keyword->id }})"
                                    class="min-w-0 flex-1 truncate text-left text-[15px] font-semibold hover:text-brand-600">{{ $keyword->keyword }}</button>
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
                            <tr wire:key="kwrow-{{ $keyword->id }}" wire:click="showDetail({{ $keyword->id }})" class="cursor-pointer hover:bg-canvas">
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

        @if ($detail)
            <div class="fixed inset-0 z-40 flex justify-end">
                <div class="absolute inset-0 bg-ink/20" wire:click="closeDetail"></div>

                <aside class="relative z-10 flex w-full max-w-lg flex-col overflow-y-auto border-l border-line bg-surface shadow-pop">
                    <div class="flex items-start gap-3 border-b border-line px-5 py-4">
                        <div class="min-w-0 flex-1">
                            <h2 class="truncate text-base font-semibold">{{ $detail->keyword }}</h2>
                            <p class="mt-0.5 text-xs text-ink-muted">
                                {{ $detail->search_volume ? '~'.number_format($detail->search_volume, 0, ',', '.').' arama/ay' : 'Hacim verisi yok' }}
                                @if ($detail->cpc) · CPC ₺{{ number_format((float) $detail->cpc, 2, ',', '.') }} @endif
                                @if ($detail->last_checked_at) · {{ $detail->last_checked_at->diffForHumans() }} @endif
                            </p>
                        </div>
                        <button wire:click="closeDetail" class="text-ink-faint hover:text-ink">✕</button>
                    </div>

                    <div class="space-y-6 px-5 py-5">
                        <div class="flex items-center gap-4">
                            <div>
                                <p class="stat-label">Güncel</p>
                                <p class="text-3xl font-bold">#{{ $detail->current_rank ?? '100+' }}</p>
                            </div>
                            <div>
                                <p class="stat-label">En iyi</p>
                                <p class="text-xl font-semibold">#{{ $detail->best_rank ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="stat-label">Başlangıç</p>
                                <p class="text-xl font-semibold">#{{ $detail->start_rank ?? '—' }}</p>
                            </div>
                            @if ($detail->rank_delta !== 0)
                                <span class="{{ $detail->rank_delta > 0 ? 'chip-up' : 'chip-down' }} ml-auto">
                                    {{ $detail->rank_delta > 0 ? '+' : '' }}{{ $detail->rank_delta }} sıra
                                </span>
                            @endif
                        </div>

                        <div>
                            <p class="mb-3 text-sm font-semibold">Son 90 gün</p>
                            <x-app.rank-chart :history="$history" />
                        </div>

                        @if ($detail->serp_url)
                            <div>
                                <p class="mb-1 text-sm font-semibold">Sıralanan sayfa</p>
                                <a href="{{ $detail->serp_url }}" target="_blank" rel="noopener"
                                   class="block truncate text-xs text-brand-600">{{ $detail->serp_url }}</a>
                            </div>
                        @endif

                        <div>
                            <p class="mb-2 text-sm font-semibold">İlk 10 rakip</p>
                            @if ($competitors->isEmpty())
                                <p class="text-xs text-ink-faint">Henüz SERP verisi çekilmedi.</p>
                            @else
                                <ol class="divide-y divide-line rounded-lg border border-line">
                                    @foreach ($competitors as $row)
                                        <li class="flex items-center gap-3 px-3 py-2 text-xs">
                                            <span class="w-5 shrink-0 font-semibold text-ink-faint">{{ $row->position }}</span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate font-medium">{{ $row->domain }}</span>
                                                <span class="block truncate text-ink-faint">{{ $row->title }}</span>
                                            </span>
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </div>

                        <div class="flex gap-2 border-t border-line pt-4">
                            <button wire:click="refreshKeyword({{ $detail->id }})" class="btn-ghost">Sırayı yenile</button>
                            <button wire:click="deleteKeyword({{ $detail->id }})"
                                    wire:confirm="Bu kelimeyi takipten çıkaralım mı?" class="btn-ghost text-down">Takipten çıkar</button>
                        </div>
                    </div>
                </aside>
            </div>
        @endif
    @endif
</div>
