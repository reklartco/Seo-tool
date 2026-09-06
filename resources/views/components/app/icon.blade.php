@props(['name'])

@php
    $paths = [
        'home' => 'M3 10.5 12 3l9 7.5M5.25 9.75V20a1 1 0 0 0 1 1h3.5v-5.5h4.5V21h3.5a1 1 0 0 0 1-1V9.75',
        'alert' => 'M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z',
        'file' => 'M14 3v5h5M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z',
        'history' => 'M3 12a9 9 0 1 0 3-6.7M3 4v4h4m5 0v5l3.5 2',
        'trend' => 'M3 17l6-6 4 4 8-8m0 0h-5m5 0v5',
        'google' => 'M12 3a9 9 0 1 0 8.94 10H12V9.9h11v2.1A9 9 0 0 1 12 3Z',
        'wrench' => 'M14.7 6.3a4 4 0 0 1 5 5L18 10l-2 2-2-2 1.3-1.3ZM13 11 4.5 19.5a1.8 1.8 0 0 0 2.5 2.5L15.5 13',
        'plug' => 'M9 3v6m6-6v6M6 9h12v3a6 6 0 0 1-12 0V9Zm6 9v3',
        'cog' => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm8-3a8 8 0 0 0-.1-1.2l2-1.6-2-3.4-2.4 1a8 8 0 0 0-2-1.2L15 3H9l-.5 2.6a8 8 0 0 0-2 1.2l-2.4-1-2 3.4 2 1.6a8 8 0 0 0 0 2.4l-2 1.6 2 3.4 2.4-1a8 8 0 0 0 2 1.2L9 21h6l.5-2.6a8 8 0 0 0 2-1.2l2.4 1 2-3.4-2-1.6c.07-.4.1-.8.1-1.2Z',
        'refresh' => 'M20 11a8 8 0 1 0-.6 4M20 5v6h-6',
        'plus' => 'M12 5v14M5 12h14',
        'search' => 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm10 2-4.35-4.35',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'h-5 w-5']) }} viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="{{ $paths[$name] ?? $paths['home'] }}" />
</svg>
