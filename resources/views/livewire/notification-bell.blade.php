<div class="relative">
    <button wire:click="toggle" class="relative grid h-9 w-9 place-items-center rounded-lg text-ink-muted hover:bg-canvas hover:text-ink"
            aria-label="Bildirimler">
        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
             stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8a6 6 0 1 0-12 0c0 7-3 8-3 8h18s-3-1-3-8M13.7 21a2 2 0 0 1-3.4 0" />
        </svg>

        @if ($unread > 0)
            <span class="absolute right-1.5 top-1.5 grid h-4 min-w-4 place-items-center rounded-full bg-brand-500 px-1 text-[10px] font-bold text-white">
                {{ $unread > 9 ? '9+' : $unread }}
            </span>
        @endif
    </button>

    @if ($open)
        <div class="absolute right-0 z-30 mt-2 w-80 rounded-xl border border-line bg-surface p-1.5 shadow-pop">
            <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-ink-faint">Bildirimler</p>

            @forelse ($items as $item)
                <div class="flex items-start gap-2 rounded-lg px-3 py-2 hover:bg-canvas">
                    <span @class([
                        'mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full',
                        'bg-brand-500' => $item->read_at === null,
                        'bg-line' => $item->read_at !== null,
                    ])></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-xs">{{ $item->data['message'] ?? 'Bildirim' }}</span>
                        <span class="block text-[11px] text-ink-faint">{{ $item->created_at->diffForHumans() }}</span>
                    </span>
                </div>
            @empty
                <p class="px-3 py-6 text-center text-xs text-ink-faint">Henüz bildirim yok.</p>
            @endforelse
        </div>
    @endif
</div>
