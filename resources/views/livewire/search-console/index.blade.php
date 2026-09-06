<div class="space-y-5">
    @if (! $project)
        <x-app.empty-state title="Önce bir proje oluştur" action="Yeni proje oluştur" :href="route('projects.create')" />
    @elseif (! $project->gscToken)
        <div class="card flex flex-col items-center px-6 py-16 text-center">
            <div class="grid h-12 w-12 place-items-center rounded-full bg-brand-50 text-brand-500">
                <x-app.icon name="google" class="h-5 w-5" />
            </div>
            <h2 class="mt-4 text-base font-semibold">Search Console'u bağla</h2>
            <p class="mt-1 max-w-md text-sm text-ink-muted">
                Gerçek tıklama, gösterim ve sıra verisini panele getir. Ücretsiz ve salt okunur yetki ister.
            </p>
            <a href="{{ route('search-console.connect', $project) }}" class="btn-primary mt-5">Google ile bağlan</a>

            @if (session('error'))
                <p class="mt-3 text-xs text-down">{{ session('error') }}</p>
            @endif
        </div>
    @elseif (! $project->gscToken->property)
        <div class="card card-pad space-y-4">
            <div>
                <h2 class="text-base font-semibold">Property seç</h2>
                <p class="mt-1 text-sm text-ink-muted">Bu projeye hangi Search Console mülkü karşılık geliyor?</p>
            </div>

            <div class="flex flex-wrap items-end gap-3">
                <div class="min-w-64 flex-1">
                    <select wire:model="property" class="field">
                        <option value="">— seç —</option>
                        @foreach ($availableProperties as $item)
                            <option value="{{ $item }}">{{ $item }}</option>
                        @endforeach
                    </select>
                </div>
                <button wire:click="loadProperties" class="btn-ghost">Mülkleri getir</button>
                <button wire:click="saveProperty" class="btn-primary">Kaydet</button>
            </div>
        </div>
    @else
        <div class="card card-pad">
            <div class="flex flex-wrap items-center gap-4">
                <div>
                    <p class="stat-label">Tıklama</p>
                    <p class="mt-0.5 text-2xl font-semibold">{{ number_format($totals['clicks'], 0, ',', '.') }}</p>
                </div>
                <div>
                    <p class="stat-label">Gösterim</p>
                    <p class="mt-0.5 text-2xl font-semibold">{{ number_format($totals['impressions'], 0, ',', '.') }}</p>
                </div>
                <div>
                    <p class="stat-label">CTR</p>
                    <p class="mt-0.5 text-2xl font-semibold">%{{ number_format($totals['ctr'], 2, ',', '.') }}</p>
                </div>
                <div>
                    <p class="stat-label">Ort. Sıra</p>
                    <p class="mt-0.5 text-2xl font-semibold">{{ number_format($totals['position'], 1, ',', '.') }}</p>
                </div>

                <div class="ml-auto flex items-center gap-2">
                    <div class="flex rounded-lg border border-line p-0.5">
                        @foreach ([7 => '7g', 28 => '28g', 90 => '90g'] as $value => $label)
                            <button wire:click="$set('days', {{ $value }})"
                                    class="rounded-md px-2.5 py-1 text-xs font-medium {{ $days === $value ? 'bg-canvas text-ink' : 'text-ink-faint' }}">{{ $label }}</button>
                        @endforeach
                    </div>
                    <button wire:click="sync" class="btn-ghost">Eşitle</button>
                    <button wire:click="disconnect" wire:confirm="Search Console bağlantısını kaldıralım mı?"
                            class="btn-ghost text-down">Bağlantıyı kes</button>
                </div>
            </div>

            <div class="mt-5 border-t border-line pt-5">
                <x-app.line-chart
                    :series="$daily->pluck('clicks')"
                    :labels="$daily->pluck('date')->map(fn ($d) => $d->format('d.m'))->all()" />
            </div>

            <p class="mt-2 text-xs text-ink-faint">{{ $project->gscToken->property }}</p>
        </div>

        @if ($opportunities->isNotEmpty())
            <section class="card">
                <div class="border-b border-line px-5 py-4">
                    <h2 class="text-sm font-semibold">Fırsat kelimeler</h2>
                    <p class="mt-0.5 text-xs text-ink-muted">Sıra 8-20 arası, gösterimi yüksek ama tıklaması düşük sorgular.</p>
                </div>

                <ul class="divide-y divide-line">
                    @foreach ($opportunities as $row)
                        <li class="flex flex-wrap items-center gap-3 px-5 py-3">
                            <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $row->query }}</span>
                            <span class="text-xs text-ink-muted">sıra {{ number_format((float) $row->position, 1, ',', '.') }}</span>
                            <span class="text-xs text-ink-muted">{{ number_format($row->impressions, 0, ',', '.') }} gösterim</span>
                            <span class="text-xs text-ink-muted">{{ $row->clicks }} tık</span>
                            <button wire:click="trackQuery(@js($row->query))" class="btn-ghost px-2.5 py-1 text-xs">Takibe al</button>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="card">
            <div class="flex items-center gap-2 border-b border-line px-5 py-3">
                @foreach (['queries' => 'Sorgular', 'pages' => 'Sayfalar'] as $key => $label)
                    <button wire:click="$set('tab', '{{ $key }}')"
                            class="{{ $tab === $key ? 'chip-brand' : 'chip-neutral' }}">{{ $label }}</button>
                @endforeach
            </div>

            @if ($rows->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-ink-faint">Bu aralıkta veri yok. Eşitlemeyi çalıştırmayı dene.</p>
            @else
                <table class="min-w-full divide-y divide-line text-sm">
                    <thead class="bg-canvas text-left text-xs uppercase tracking-wide text-ink-faint">
                        <tr>
                            <th class="px-5 py-3 font-medium">{{ $tab === 'pages' ? 'Sayfa' : 'Sorgu' }}</th>
                            <th class="px-4 py-3 font-medium">Tıklama</th>
                            <th class="px-4 py-3 font-medium">Gösterim</th>
                            <th class="px-4 py-3 font-medium">Sıra</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($rows as $row)
                            <tr class="hover:bg-canvas">
                                <td class="max-w-md truncate px-5 py-3">{{ $row['label'] }}</td>
                                <td class="px-4 py-3 font-medium">{{ number_format($row['clicks'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-ink-muted">{{ number_format($row['impressions'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-ink-muted">{{ number_format($row['position'], 1, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($tab === 'queries')
                                        <button wire:click="trackQuery(@js($row['label']))"
                                                class="btn-ghost px-2.5 py-1 text-xs">Takibe al</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @endif
</div>
