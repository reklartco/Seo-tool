@props(['history'])

@php
    // 90 day rank chart with an inverted axis: #1 sits at the top.
    $points = $history->filter(fn ($row) => $row->rank !== null)->values();
    $max = max(20, (int) $points->max('rank'));
    $count = max(1, $points->count() - 1);

    $coords = $points->map(function ($row, $index) use ($max, $count) {
        return [
            'x' => round($index / $count * 100, 2),
            'y' => round(($row->rank - 1) / max(1, $max - 1) * 100, 2),
            'rank' => $row->rank,
            'date' => $row->checked_at->format('d.m'),
        ];
    });

    $path = $coords->map(fn ($p) => $p['x'].','.$p['y'])->implode(' ');
@endphp

@if ($coords->count() < 2)
    <p class="py-8 text-center text-sm text-ink-faint">Grafik için henüz yeterli veri yok.</p>
@else
    <div class="relative">
        <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="h-48 w-full">
            @foreach ([0, 25, 50, 75, 100] as $line)
                <line x1="0" y1="{{ $line }}" x2="100" y2="{{ $line }}" stroke="#E7EAF0" stroke-width="0.4" vector-effect="non-scaling-stroke" />
            @endforeach

            <polyline points="{{ $path }}" fill="none" stroke="#16A34A" stroke-width="2"
                      vector-effect="non-scaling-stroke" stroke-linejoin="round" stroke-linecap="round" />

            @foreach ($coords as $point)
                <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="3" fill="#16A34A"
                        vector-effect="non-scaling-stroke">
                    <title>{{ $point['date'] }} · #{{ $point['rank'] }}</title>
                </circle>
            @endforeach
        </svg>

        <div class="pointer-events-none absolute inset-y-0 -left-1 flex flex-col justify-between text-[10px] text-ink-faint">
            <span>#1</span>
            <span>#{{ $max }}</span>
        </div>
    </div>

    <div class="mt-1 flex justify-between text-[11px] text-ink-faint">
        <span>{{ $coords->first()['date'] }}</span>
        <span>{{ $coords->last()['date'] }}</span>
    </div>
@endif
