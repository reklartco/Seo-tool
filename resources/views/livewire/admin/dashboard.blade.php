<div class="space-y-5">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-app.stat-card label="Takım" :value="$totals['teams']">
            <x-slot:footer><p class="mt-3 text-xs text-ink-muted">{{ $totals['users'] }} kullanıcı</p></x-slot:footer>
        </x-app.stat-card>

        <x-app.stat-card label="Proje" :value="$totals['projects']">
            <x-slot:footer><p class="mt-3 text-xs text-ink-muted">{{ $totals['keywords'] }} takipli kelime</p></x-slot:footer>
        </x-app.stat-card>

        <x-app.stat-card label="Bu Ay Taranan Sayfa" :value="$totals['crawled_pages']">
            <x-slot:footer><p class="mt-3 text-xs text-ink-muted">bugün {{ $totals['crawls_today'] }} tarama</p></x-slot:footer>
        </x-app.stat-card>

        <x-app.stat-card label="Tahmini Maliyet" :value="'$'.number_format($totals['cost_usd'], 2)">
            <x-slot:footer>
                <p class="mt-3 text-xs text-ink-muted">
                    {{ number_format($totals['serp_queries'], 0, ',', '.') }} SERP ·
                    {{ number_format($totals['ai_tokens'], 0, ',', '.') }} token
                </p>
            </x-slot:footer>
        </x-app.stat-card>
    </div>

    <section class="card overflow-x-auto">
        <div class="flex items-center justify-between border-b border-line px-5 py-4">
            <h2 class="text-sm font-semibold">Takımlar</h2>
            <a href="/horizon" target="_blank" rel="noopener" class="text-xs font-medium text-brand-600">Horizon →</a>
        </div>

        <table class="min-w-full divide-y divide-line text-sm">
            <thead class="bg-canvas text-left text-xs uppercase tracking-wide text-ink-faint">
                <tr>
                    <th class="px-5 py-3 font-medium">Takım</th>
                    <th class="px-4 py-3 font-medium">Plan</th>
                    <th class="px-4 py-3 font-medium">Proje</th>
                    <th class="px-4 py-3 font-medium">Kelime</th>
                    <th class="px-4 py-3 font-medium">Sayfa</th>
                    <th class="px-4 py-3 font-medium">SERP</th>
                    <th class="px-4 py-3 font-medium">AI</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($teams as $row)
                    @php $team = $row['model']; @endphp
                    <tr wire:key="team-{{ $team->id }}" class="hover:bg-canvas">
                        <td class="px-5 py-3">
                            <span class="block font-medium">{{ $team->name }}</span>
                            <span class="block text-xs text-ink-faint">{{ $team->owner?->email }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if ($editingTeam === $team->id)
                                <div class="flex items-center gap-1.5">
                                    <select wire:model="selectedPlan" class="field py-1 text-xs">
                                        @foreach ($plans as $plan)
                                            <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                                        @endforeach
                                    </select>
                                    <button wire:click="assignPlan" class="btn-primary px-2 py-1 text-xs">Kaydet</button>
                                </div>
                            @else
                                <span class="chip-neutral">{{ $team->plan?->name ?? 'Plansız' }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-ink-muted">{{ $team->projects_count }}</td>
                        <td class="px-4 py-3 text-ink-muted">{{ $row['keywords'] }}</td>
                        <td class="px-4 py-3 text-ink-muted">{{ number_format($row['crawled_pages'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-ink-muted">{{ number_format($row['serp_queries'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-ink-muted">{{ $row['ai_generations'] }}</td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="editPlan({{ $team->id }})" class="btn-ghost px-2.5 py-1 text-xs">Plan ata</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="card overflow-x-auto">
        <div class="border-b border-line px-5 py-4">
            <h2 class="text-sm font-semibold">Planlar</h2>
        </div>

        <table class="min-w-full divide-y divide-line text-sm">
            <thead class="bg-canvas text-left text-xs uppercase tracking-wide text-ink-faint">
                <tr>
                    <th class="px-5 py-3 font-medium">Plan</th>
                    <th class="px-4 py-3 font-medium">Aylık</th>
                    <th class="px-4 py-3 font-medium">Proje</th>
                    <th class="px-4 py-3 font-medium">Kelime</th>
                    <th class="px-4 py-3 font-medium">Sayfa/ay</th>
                    <th class="px-4 py-3 font-medium">Oto. düzeltme</th>
                    <th class="px-4 py-3 font-medium">AI/ay</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($plans as $plan)
                    <tr class="hover:bg-canvas">
                        <td class="px-5 py-3 font-medium">{{ $plan->name }}</td>
                        <td class="px-4 py-3 text-ink-muted">₺{{ number_format($plan->price_monthly, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-ink-muted">{{ $plan->max_projects }}</td>
                        <td class="px-4 py-3 text-ink-muted">{{ $plan->max_keywords }}</td>
                        <td class="px-4 py-3 text-ink-muted">{{ number_format($plan->max_monthly_crawl_pages, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-ink-muted">
                            {{ $plan->max_auto_fix_sites ? $plan->max_auto_fix_sites.' site · '.$plan->daily_fix_page_limit.'/gün' : '—' }}
                        </td>
                        <td class="px-4 py-3 text-ink-muted">{{ $plan->max_monthly_ai_generations }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</div>
