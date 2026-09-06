<div class="space-y-5">
    @if (! $project)
        <x-app.empty-state title="Önce bir proje oluştur" action="Yeni proje oluştur" :href="route('projects.create')" />
    @else
        <div class="card card-pad flex flex-wrap items-center gap-3">
            <div class="relative min-w-56 flex-1">
                <x-app.icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-ink-faint" />
                <input type="search" wire:model.live.debounce.400ms="search" placeholder="URL ara…" class="field pl-9">
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach (['all' => 'Tümü', 'ok' => '2xx', 'redirect' => '3xx', 'error' => '4xx/5xx', 'noindex' => 'Noindex'] as $key => $label)
                    <button wire:click="$set('status', '{{ $key }}')"
                            class="{{ $status === $key ? 'chip-brand' : 'chip-neutral' }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        @if ($pages->isEmpty())
            <x-app.empty-state title="Henüz taranmış sayfa yok"
                               description="Üst bardaki Tara düğmesiyle ilk taramayı başlat." />
        @else
            <div class="card overflow-x-auto">
                <table class="min-w-full divide-y divide-line text-sm">
                    <thead class="bg-canvas text-left text-xs uppercase tracking-wide text-ink-faint">
                        <tr>
                            <th class="px-5 py-3 font-medium"><button wire:click="sortBy('url')">URL</button></th>
                            <th class="px-4 py-3 font-medium"><button wire:click="sortBy('status_code')">Durum</button></th>
                            <th class="px-4 py-3 font-medium">Title</th>
                            <th class="px-4 py-3 font-medium">Desc</th>
                            <th class="px-4 py-3 font-medium"><button wire:click="sortBy('word_count')">Kelime</button></th>
                            <th class="px-4 py-3 font-medium"><button wire:click="sortBy('internal_links_in')">Gelen link</button></th>
                            <th class="px-4 py-3 font-medium"><button wire:click="sortBy('issues_count')">Hata</button></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($pages as $page)
                            <tr wire:key="page-{{ $page->id }}" wire:click="select({{ $page->id }})"
                                class="cursor-pointer hover:bg-canvas {{ $selected === $page->id ? 'bg-brand-50/50' : '' }}">
                                <td class="max-w-md truncate px-5 py-3">{{ \Illuminate\Support\Str::after($page->url, $project->domain) ?: '/' }}</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'chip-up' => $page->status_code >= 200 && $page->status_code < 300,
                                        'chip-neutral' => $page->status_code >= 300 && $page->status_code < 400,
                                        'chip-down' => $page->status_code >= 400 || ! $page->status_code,
                                    ])>{{ $page->status_code ?: '—' }}</span>
                                </td>
                                <td class="px-4 py-3 text-ink-muted">{{ $page->title ? mb_strlen($page->title) : '—' }}</td>
                                <td class="px-4 py-3 text-ink-muted">{{ $page->meta_description ? mb_strlen($page->meta_description) : '—' }}</td>
                                <td class="px-4 py-3 text-ink-muted">{{ $page->word_count }}</td>
                                <td class="px-4 py-3 text-ink-muted">{{ $page->internal_links_in }}</td>
                                <td class="px-4 py-3">
                                    <span class="{{ $page->issues_count > 0 ? 'chip-down' : 'chip-up' }}">{{ $page->issues_count }}</span>
                                </td>
                            </tr>

                            @if ($selected === $page->id && $detail)
                                <tr class="bg-canvas/60">
                                    <td colspan="7" class="px-5 py-4">
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div class="space-y-2 text-xs">
                                                <p class="text-ink-faint">URL</p>
                                                <a href="{{ $detail->url }}" target="_blank" rel="noopener"
                                                   class="block truncate font-medium text-brand-600">{{ $detail->url }}</a>
                                                <p class="text-ink-faint">Title</p>
                                                <p>{{ $detail->title ?? '—' }}</p>
                                                <p class="text-ink-faint">Meta description</p>
                                                <p>{{ $detail->meta_description ?? '—' }}</p>
                                                <p class="text-ink-faint">H1 · Canonical</p>
                                                <p class="truncate">{{ $detail->h1 ?? '—' }} · {{ $detail->canonical ?? '—' }}</p>
                                            </div>

                                            <div>
                                                <p class="mb-2 text-xs text-ink-faint">Açık hatalar</p>
                                                <ul class="space-y-1.5">
                                                    @forelse ($detail->issues as $issue)
                                                        <li class="flex items-center gap-2 text-xs">
                                                            <span @class([
                                                                'h-1.5 w-1.5 rounded-full',
                                                                'bg-down' => $issue->severity === 'critical',
                                                                'bg-warn' => $issue->severity === 'warning',
                                                                'bg-ink-faint' => $issue->severity === 'notice',
                                                            ])></span>
                                                            {{ rule_label($issue->rule_key, $issue->message) }}
                                                        </li>
                                                    @empty
                                                        <li class="text-xs text-ink-faint">Bu sayfada açık hata yok.</li>
                                                    @endforelse
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div>{{ $pages->links() }}</div>
        @endif
    @endif
</div>
