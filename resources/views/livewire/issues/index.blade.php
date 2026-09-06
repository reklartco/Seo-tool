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

                <span class="mx-2 hidden h-4 w-px bg-line sm:block"></span>

                @foreach (['open' => 'Açık', 'auto_fixed' => 'Düzeltildi', 'ignored' => 'Yoksayılan'] as $key => $label)
                    <button wire:click="$set('status', '{{ $key }}')"
                            class="{{ $status === $key ? 'chip-brand' : 'chip-neutral' }}">{{ $label }}</button>
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

                        <div class="-mt-2 flex flex-wrap items-center gap-2 px-5 pb-3 pl-10">
                            @if (\App\Jobs\GenerateFixJob::fieldFor($group->rule_key) && $status === 'open')
                                <button wire:click="generateForRule('{{ $group->rule_key }}')"
                                        class="btn-ghost px-2.5 py-1 text-xs">AI ile düzelt ({{ $group->pages }})</button>
                            @endif

                            @if (config('seo.rule_help.'.$group->rule_key))
                                <button wire:click="explain('{{ $group->rule_key }}')"
                                        class="btn-ghost px-2.5 py-1 text-xs">Neden önemli?</button>
                            @endif

                            @if ($status === 'open')
                                <button wire:click="ignoreRule('{{ $group->rule_key }}')"
                                        wire:confirm="Bu kuralın tüm açık hataları yoksayılsın mı?"
                                        class="btn-ghost px-2.5 py-1 text-xs">Yoksay</button>
                            @endif
                        </div>

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

        @if ($help)
            <div class="fixed inset-0 z-40 flex justify-end">
                <div class="absolute inset-0 bg-ink/20" wire:click="explain(null)"></div>

                <aside class="relative z-10 w-full max-w-md overflow-y-auto border-l border-line bg-surface p-5 shadow-pop">
                    <div class="flex items-start gap-3">
                        <h2 class="flex-1 text-base font-semibold">{{ rule_label($explaining) }}</h2>
                        <button wire:click="explain(null)" class="text-ink-faint hover:text-ink">✕</button>
                    </div>

                    <p class="mt-1 text-xs text-ink-faint">{{ $explaining }}</p>

                    <div class="mt-5 space-y-4">
                        <div>
                            <p class="stat-label">Neden önemli?</p>
                            <p class="mt-1 text-sm text-ink-muted">{{ $help['why'] }}</p>
                        </div>

                        <div>
                            <p class="stat-label">Nasıl düzeltilir?</p>
                            <p class="mt-1 text-sm text-ink-muted">{{ $help['how'] }}</p>
                        </div>

                        @if (\App\Jobs\GenerateFixJob::fieldFor($explaining))
                            <button wire:click="generateForRule('{{ $explaining }}')" class="btn-primary w-full">
                                AI ile düzeltmeyi dene
                            </button>
                        @endif
                    </div>
                </aside>
            </div>
        @endif
    @endif
</div>
