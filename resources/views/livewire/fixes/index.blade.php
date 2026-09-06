<div class="space-y-5">
    @if (! $project)
        <x-app.empty-state title="Önce bir proje oluştur" action="Yeni proje oluştur" :href="route('projects.create')" />
    @else
        @if (! $project->wpConnection)
            <div class="card card-pad flex flex-wrap items-center gap-3 border-brand-200 bg-brand-50/40">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium">WordPress bağlı değil</p>
                    <p class="text-xs text-ink-muted">AI düzeltmeleri üretilebilir, ancak siteye uygulanabilmesi için eklentiyi bağlaman gerekir.</p>
                </div>
                <a href="{{ route('integrations') }}" wire:navigate class="btn-primary">Entegrasyonlar</a>
            </div>
        @endif

        @if ($groups->isNotEmpty())
            <section class="card">
                <div class="border-b border-line px-5 py-4">
                    <h2 class="text-sm font-semibold">AI ile düzeltilebilir hatalar</h2>
                </div>
                <ul class="divide-y divide-line">
                    @foreach ($groups as $group)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <span class="min-w-0 flex-1 truncate text-sm">{{ rule_label($group->rule_key) }}</span>
                            <span class="chip-neutral">{{ $group->pages }} sayfa</span>
                            <button wire:click="generateForRule('{{ $group->rule_key }}')" class="btn-ghost px-2.5 py-1 text-xs">
                                AI ile düzelt
                            </button>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <div class="card card-pad flex flex-wrap items-center gap-2">
            @foreach (['draft' => 'Onay bekleyen', 'applied' => 'Uygulandı', 'failed' => 'Başarısız'] as $key => $label)
                <button wire:click="$set('tab', '{{ $key }}')"
                        class="{{ $tab === $key ? 'chip-brand' : 'chip-neutral' }}">{{ $label }}</button>
            @endforeach

            <div class="ml-auto flex items-center gap-3 text-xs text-ink-muted">
                <span>Bugün uygulanan: <span class="font-semibold text-ink">{{ $appliedToday }}</span>@if ($project->daily_fix_limit) / {{ $project->daily_fix_limit }} @endif</span>
                @if ($tab === 'draft' && $fixes->isNotEmpty())
                    <button wire:click="approveAll" wire:confirm="Bekleyen tüm düzeltmeler uygulansın mı?"
                            class="btn-primary">Tümünü onayla ({{ $fixes->count() }})</button>
                @endif
            </div>
        </div>

        @if ($fixes->isEmpty())
            <x-app.empty-state
                title="Bu sekmede düzeltme yok"
                description="Yukarıdaki listeden bir kural seçip AI düzeltmesi üretebilirsin." />
        @else
            <div class="card divide-y divide-line">
                @foreach ($fixes as $fix)
                    <div wire:key="fix-{{ $fix->id }}" class="px-5 py-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="chip-neutral">{{ ['title' => 'Title', 'meta_description' => 'Description', 'alt' => 'Alt metni'][$fix->field] ?? $fix->field }}</span>
                            <a href="{{ $fix->page?->url }}" target="_blank" rel="noopener"
                               class="min-w-0 flex-1 truncate text-xs text-brand-600">{{ $fix->page?->url }}</a>

                            <span @class([
                                'chip-neutral' => in_array($fix->status, ['draft', 'approved']),
                                'chip-up' => $fix->status === 'applied',
                                'chip-down' => $fix->status === 'failed',
                            ])>
                                {{ ['draft' => 'Taslak', 'approved' => 'Onaylandı', 'applied' => 'Uygulandı', 'failed' => 'Başarısız', 'rolled_back' => 'Geri alındı'][$fix->status] }}
                            </span>
                        </div>

                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                            <div>
                                <p class="stat-label">Eski</p>
                                <p class="mt-1 text-sm text-ink-muted line-through decoration-down/40">{{ $fix->old_value ?: '—' }}</p>
                            </div>

                            <div>
                                <p class="stat-label">Yeni</p>
                                @if ($fix->status === 'draft')
                                    <input type="text" wire:model.live.debounce.500ms="edited.{{ $fix->id }}"
                                           class="field mt-1 text-sm">
                                    <p class="mt-1 text-[11px] text-ink-faint">
                                        {{ mb_strlen($edited[$fix->id] ?? $fix->new_value ?? '') }} karakter
                                    </p>
                                @else
                                    <p class="mt-1 text-sm">{{ $fix->new_value ?: '—' }}</p>
                                @endif
                            </div>
                        </div>

                        @if ($fix->error)
                            <p class="mt-2 text-xs text-down">{{ $fix->error }}</p>
                        @endif

                        <div class="mt-3 flex items-center gap-2">
                            @if ($fix->status === 'draft')
                                <button wire:click="approve({{ $fix->id }})" class="btn-primary px-3 py-1.5 text-xs">Onayla ve uygula</button>
                                <button wire:click="reject({{ $fix->id }})" class="btn-ghost px-3 py-1.5 text-xs">Reddet</button>
                            @elseif ($fix->status === 'applied')
                                <span class="text-xs text-ink-faint">
                                    {{ $fix->applied_at?->format('d.m.Y H:i') }} · {{ $fix->generated_by === 'ai' ? 'AI' : 'Kullanıcı' }}
                                </span>
                                <button wire:click="rollback({{ $fix->id }})" class="btn-ghost ml-auto px-3 py-1.5 text-xs">Geri al</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
