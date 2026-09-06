@php
    $current = current_project();
    $user = auth()->user();
@endphp

<header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-line bg-surface/90 px-4 backdrop-blur sm:px-6 lg:px-8">
    <button @click="sidebar = true" class="text-ink-muted lg:hidden" aria-label="Menüyü aç">☰</button>

    <div class="min-w-0">
        <p class="truncate text-sm font-semibold">{{ $current?->domain ?? 'Proje seçilmedi' }}</p>
        @if ($current?->last_crawled_at)
            <p class="text-xs text-ink-faint">Son tarama: {{ $current->last_crawled_at->diffForHumans() }}</p>
        @endif
    </div>

    <div class="ml-auto flex items-center gap-2">
        @if ($current)
            <button type="button" class="btn-primary hidden sm:inline-flex">
                <x-app.icon name="refresh" class="h-4 w-4" />
                Tara
            </button>
        @endif

        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open"
                    class="grid h-9 w-9 place-items-center rounded-full bg-canvas text-sm font-semibold text-ink-muted ring-1 ring-line">
                {{ mb_substr($user?->name ?? '?', 0, 1) }}
            </button>

            <div x-show="open" x-cloak @click.outside="open = false"
                 class="absolute right-0 mt-2 w-52 rounded-xl border border-line bg-surface p-1.5 shadow-pop">
                <p class="px-3 py-2 text-xs text-ink-faint">{{ $user?->email }}</p>
                <a href="{{ route('profile') }}" wire:navigate class="nav-item">Profil</a>
                <button type="submit" form="logout-form" class="nav-item w-full text-left">
                    Çıkış yap
                </button>
            </div>
        </div>
    </div>

    <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
</header>
