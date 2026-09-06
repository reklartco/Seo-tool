@props(['label', 'value', 'delta' => null, 'suffix' => ''])

<div class="card card-pad">
    <p class="stat-label">{{ $label }}</p>

    <div class="mt-2 flex items-baseline gap-2">
        <span class="text-3xl font-semibold tracking-tight">{{ is_numeric($value) ? number_format((float) $value, 0, ',', '.') : $value }}</span>
        @if ($suffix)
            <span class="text-sm text-ink-faint">{{ $suffix }}</span>
        @endif

        @if ($delta)
            <span class="{{ $delta > 0 ? 'chip-up' : 'chip-down' }} ml-auto">
                {{ $delta > 0 ? '▲' : '▼' }} {{ abs($delta) }}
            </span>
        @endif
    </div>

    {{ $footer ?? '' }}
</div>
