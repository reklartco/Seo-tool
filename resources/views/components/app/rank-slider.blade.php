@props(['start', 'current', 'scale' => 20])

@php
    // The track runs from the worst observed rank (left) to #1 (right),
    // mirroring the KAF card: hollow dot = first measurement, filled = today.
    $scale = max($scale, (int) $start, (int) $current, 2);
    $pct = fn (?int $rank) => $rank === null ? 0 : (int) round(($scale - $rank) / ($scale - 1) * 100);

    $startPct = $pct($start ? (int) $start : null);
    $currentPct = $pct($current ? (int) $current : null);
    $improved = $current !== null && $start !== null && $current <= $start;

    $from = min($startPct, $currentPct);
    $width = abs($currentPct - $startPct);
@endphp

<div class="mt-4">
    <div class="relative h-1.5 w-full rounded-full bg-canvas">
        <div class="absolute inset-y-0 rounded-full {{ $improved ? 'bg-up' : 'bg-warn' }}"
             style="left: {{ $from }}%; width: {{ $width }}%"></div>

        @if ($start)
            <span class="absolute top-1/2 h-3 w-3 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-line bg-surface"
                  style="left: {{ $startPct }}%" title="Başlangıç: {{ $start }}."></span>
        @endif

        @if ($current)
            <span class="absolute top-1/2 h-3.5 w-3.5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-surface shadow-card {{ $improved ? 'bg-up' : 'bg-warn' }}"
                  style="left: {{ $currentPct }}%" title="Güncel: {{ $current }}."></span>
        @endif
    </div>

    <div class="mt-1.5 flex justify-between text-[11px] text-ink-faint">
        <span>{{ $scale }}.</span>
        <span>#1</span>
    </div>
</div>
