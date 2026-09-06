@props(['title', 'description' => '', 'action' => null, 'href' => null])

<div class="card flex flex-col items-center justify-center px-6 py-16 text-center">
    <div class="grid h-12 w-12 place-items-center rounded-full bg-brand-50 text-brand-500">
        <x-app.icon name="search" class="h-5 w-5" />
    </div>

    <h2 class="mt-4 text-base font-semibold">{{ $title }}</h2>

    @if ($description)
        <p class="mt-1 max-w-md text-sm text-ink-muted">{{ $description }}</p>
    @endif

    @if ($action && $href)
        <a href="{{ $href }}" wire:navigate class="btn-primary mt-5">{{ $action }}</a>
    @endif
</div>
