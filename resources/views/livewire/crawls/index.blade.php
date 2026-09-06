<div class="space-y-5" @if ($poll) wire:poll.{{ $poll }} @endif>
    @if (! $project)
        <x-app.empty-state title="Önce bir proje oluştur" action="Yeni proje oluştur" :href="route('projects.create')" />
    @elseif ($crawls->isEmpty())
        <x-app.empty-state title="Henüz tarama yapılmadı"
                           description="Üst bardaki Tara düğmesiyle ilk taramayı başlat." />
    @else
        <div class="card divide-y divide-line">
            @foreach ($crawls as $crawl)
                <div class="px-5 py-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <span @class([
                            'chip-up' => $crawl->status === 'done',
                            'chip-brand' => in_array($crawl->status, ['queued', 'running']),
                            'chip-down' => $crawl->status === 'failed',
                        ])>
                            {{ ['queued' => 'Kuyrukta', 'running' => 'Sürüyor', 'done' => 'Bitti', 'failed' => 'Başarısız'][$crawl->status] }}
                        </span>

                        <span class="text-sm font-medium">#{{ $crawl->id }}</span>
                        <span class="text-xs text-ink-muted">{{ $crawl->created_at->format('d.m.Y H:i') }}</span>

                        <span class="ml-auto flex items-center gap-4 text-xs text-ink-muted">
                            <span>{{ $crawl->pages_crawled }} / {{ $crawl->pages_total }} sayfa</span>
                            <span>{{ $crawl->issues_count }} hata</span>
                            @if ($crawl->new_issues)
                                <span class="chip-down">+{{ $crawl->new_issues }} yeni</span>
                            @endif
                            @if ($crawl->resolved_issues)
                                <span class="chip-up">−{{ $crawl->resolved_issues }} çözüldü</span>
                            @endif
                            @if ($crawl->health_score !== null)
                                <span class="font-semibold text-ink">skor {{ $crawl->health_score }}</span>
                            @endif
                        </span>
                    </div>

                    @if (in_array($crawl->status, ['queued', 'running']))
                        <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-canvas">
                            <div class="h-full rounded-full bg-brand-500 transition-all" style="width: {{ $crawl->progress() }}%"></div>
                        </div>
                    @endif

                    @if ($crawl->error)
                        <p class="mt-2 text-xs text-down">{{ $crawl->error }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
