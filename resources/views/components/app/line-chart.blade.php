@props(['series', 'labels' => [], 'color' => '#F2551B', 'height' => 'h-56'])

@php
    // Simple area chart: values are scaled to a 0-100 viewbox.
    $values = collect($series)->values();
    $max = max(1, (float) $values->max());
    $count = max(1, $values->count() - 1);

    $points = $values->map(fn ($value, $index) => [
        round($index / $count * 100, 2),
        round(100 - ($value / $max * 100), 2),
    ]);

    $line = $points->map(fn ($p) => $p[0].','.$p[1])->implode(' ');
    $area = '0,100 '.$line.' 100,100';
@endphp

@if ($values->count() < 2)
    <p class="py-10 text-center text-sm text-ink-faint">Grafik için henüz yeterli veri yok.</p>
@else
    <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="{{ $height }} w-full">
        @foreach ([0, 25, 50, 75, 100] as $line_y)
            <line x1="0" y1="{{ $line_y }}" x2="100" y2="{{ $line_y }}" stroke="#E7EAF0" stroke-width="0.4" vector-effect="non-scaling-stroke" />
        @endforeach

        <polygon points="{{ $area }}" fill="{{ $color }}" opacity="0.08" />
        <polyline points="{{ $line }}" fill="none" stroke="{{ $color }}" stroke-width="2"
                  vector-effect="non-scaling-stroke" stroke-linejoin="round" stroke-linecap="round" />
    </svg>

    @if ($labels)
        <div class="mt-1 flex justify-between text-[11px] text-ink-faint">
            <span>{{ $labels[0] }}</span>
            <span>{{ $labels[count($labels) - 1] }}</span>
        </div>
    @endif
@endif
