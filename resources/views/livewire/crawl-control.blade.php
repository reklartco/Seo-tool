<div @if ($crawl) wire:poll.3s @endif>
    @if (! $project)
        {{-- Nothing to crawl yet --}}
    @elseif ($crawl)
        <div class="flex items-center gap-3 rounded-lg border border-line bg-canvas px-3 py-1.5">
            <span class="relative flex h-2 w-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-brand-400 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-brand-500"></span>
            </span>

            <div class="w-28">
                <div class="h-1.5 overflow-hidden rounded-full bg-line">
                    <div class="h-full rounded-full bg-brand-500 transition-all" style="width: {{ $crawl->progress() }}%"></div>
                </div>
            </div>

            <span class="whitespace-nowrap text-xs text-ink-muted">
                {{ $crawl->pages_crawled }} / {{ $crawl->pages_total }} sayfa
            </span>
        </div>
    @else
        <button type="button" wire:click="start" wire:loading.attr="disabled" class="btn-primary">
            <x-app.icon name="refresh" class="h-4 w-4" wire:loading.class="animate-spin" wire:target="start" />
            Tara
        </button>
    @endif
</div>
