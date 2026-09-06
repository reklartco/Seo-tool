<div class="space-y-5">
    @if (! $project)
        <x-app.empty-state
            title="Önce bir proje oluştur"
            description="Hata listesi bir tarama sonucundan doğar."
            action="Yeni proje oluştur"
            :href="route('projects.create')" />
    @else
        <div class="card card-pad">
            <div class="flex flex-wrap items-center gap-2">
                @foreach (['all' => 'Tümü', 'critical' => 'Kritik', 'warning' => 'Uyarı', 'notice' => 'Bilgi'] as $key => $label)
                    <button wire:click="$set('severity', '{{ $key }}')"
                            class="{{ $severity === $key ? 'chip-brand' : 'chip-neutral' }}">{{ $label }}</button>
                @endforeach

                <span class="mx-2 hidden h-4 w-px bg-line sm:block"></span>

                @foreach (['all' => 'Tüm kategoriler', 'meta' => 'Meta', 'technical' => 'Teknik', 'content' => 'İçerik', 'images' => 'Görsel', 'links' => 'Link', 'schema' => 'Schema'] as $key => $label)
                    <button wire:click="$set('category', '{{ $key }}')"
                            class="{{ $category === $key ? 'chip-brand' : 'chip-neutral' }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        @if ($groups->isEmpty())
            <x-app.empty-state title="Bu filtrede açık hata yok" description="Filtreyi genişletmeyi dene." />
        @else
            <div class="card divide-y divide-line">
                @foreach ($groups as $group)
                    <div>
                        <button wire:click="toggle('{{ $group->rule_key }}')"
                                class="flex w-full items-center gap-3 px-5 py-4 text-left hover:bg-canvas">
                            <span @class([
                                'h-2 w-2 shrink-0 rounded-full',
                                'bg-down' => $group->severity === 'critical',
                                'bg-warn' => $group->severity === 'warning',
                                'bg-ink-faint' => $group->severity === 'notice',
                            ])></span>

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium">{{ rule_label($group->rule_key, $group->message) }}</span>
                                <span class="text-xs text-ink-faint">{{ $group->rule_key }} · {{ config("seo.category_labels.{$group->category}", $group->category) }}</span>
                            </span>

                            <span class="chip-neutral">{{ $group->pages }} sayfa</span>
                            <span class="text-ink-faint">{{ $expanded === $group->rule_key ? '▾' : '▸' }}</span>
                        </button>

                        @if ($expanded === $group->rule_key)
                            <ul class="divide-y divide-line border-t border-line bg-canvas/60">
                                @foreach ($expandedPages as $issue)
                                    <li class="flex items-center gap-3 px-5 py-2.5">
                                        <span class="min-w-0 flex-1 truncate text-xs text-ink-muted">
                                            {{ $issue->page?->url ?? $project->url() }}
                                        </span>
                                        <a href="{{ $issue->page?->url ?? $project->url() }}" target="_blank" rel="noopener"
                                           class="btn-ghost px-2.5 py-1 text-xs">Sayfayı aç</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
