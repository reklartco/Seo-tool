@php
    use App\Models\Project;

    $team = auth()->user()?->currentTeam;
    $projects = $team ? $team->projects()->orderBy('name')->get() : collect();
    $current = current_project();
    $limits = $team?->plan ? \App\Services\PlanLimits::for($team) : null;

    $nav = [
        ['route' => 'dashboard', 'label' => 'Genel Bakış', 'icon' => 'home'],
        ['route' => 'issues', 'label' => 'Hatalar', 'icon' => 'alert', 'group' => 'Site Taraması'],
        ['route' => 'pages', 'label' => 'Sayfalar', 'icon' => 'file', 'group' => 'Site Taraması'],
        ['route' => 'crawls', 'label' => 'Tarama Geçmişi', 'icon' => 'history', 'group' => 'Site Taraması'],
        ['route' => 'keywords', 'label' => 'Anahtar Kelimeler', 'icon' => 'trend'],
        ['route' => 'search-console', 'label' => 'Search Console', 'icon' => 'google'],
        ['route' => 'fixes', 'label' => 'Düzeltmeler', 'icon' => 'wrench'],
        ['route' => 'integrations', 'label' => 'Entegrasyonlar', 'icon' => 'plug'],
        ['route' => 'project-settings', 'label' => 'Proje Ayarları', 'icon' => 'cog'],
    ];
@endphp

<aside
    x-cloak
    :class="sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="fixed inset-y-0 left-0 z-40 flex w-60 shrink-0 transform flex-col border-r border-line bg-surface transition-transform duration-200 lg:static lg:translate-x-0"
>
    <div class="flex h-16 items-center gap-2.5 border-b border-line px-5">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5" wire:navigate>
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-500 text-sm font-bold text-white">S</span>
            <span class="text-base font-semibold tracking-tight">SEO Aracı</span>
        </a>
        <button @click="sidebar = false" class="ml-auto text-ink-faint lg:hidden" aria-label="Menüyü kapat">✕</button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        @php $renderedGroup = null; @endphp
        @foreach ($nav as $item)
            @if (($item['group'] ?? null) && $item['group'] !== $renderedGroup)
                @php $renderedGroup = $item['group']; @endphp
                <p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-ink-faint">
                    {{ $item['group'] }}
                </p>
            @elseif (! ($item['group'] ?? null))
                @php $renderedGroup = null; @endphp
            @endif

            <a href="{{ route($item['route']) }}" wire:navigate
               class="nav-item {{ request()->routeIs($item['route']) ? 'nav-item-active' : '' }}">
                <x-app.icon :name="$item['icon']" class="h-4 w-4" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="border-t border-line px-3 py-4">
        <div class="flex items-center justify-between px-3 pb-2">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-ink-faint">Projeler</p>
            <a href="{{ route('projects.create') }}" wire:navigate
               class="text-xs font-semibold text-brand-600 hover:text-brand-700">+ Yeni</a>
        </div>

        <div class="space-y-0.5">
            @forelse ($projects as $project)
                <a href="{{ route('projects.switch', $project) }}"
                   class="nav-item text-[13px] {{ $current?->is($project) ? 'nav-item-active' : '' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $current?->is($project) ? 'bg-brand-500' : 'bg-line' }}"></span>
                    <span class="truncate">{{ $project->domain }}</span>
                </a>
            @empty
                <p class="px-3 text-xs text-ink-faint">Henüz proje yok.</p>
            @endforelse
        </div>

        @if ($team?->plan)
            <div class="mt-4 rounded-lg bg-canvas px-3 py-2.5">
                <p class="text-xs font-medium text-ink">Plan: {{ $team->plan->name }}</p>
                <p class="mt-0.5 text-[11px] text-ink-muted">
                    {{ $limits->used('max_projects') }}/{{ $limits->limit('max_projects') }} proje ·
                    {{ $limits->used('max_keywords') }}/{{ $limits->limit('max_keywords') }} kelime
                </p>
            </div>
        @endif
    </div>
</aside>
