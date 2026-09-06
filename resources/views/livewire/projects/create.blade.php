<div class="mx-auto max-w-2xl">
    <div class="mb-5">
        <h1 class="text-xl font-semibold tracking-tight">Yeni Proje</h1>
        <p class="mt-1 text-sm text-ink-muted">Üç adımda kurulum: site, anahtar kelimeler, entegrasyonlar.</p>
    </div>

    <ol class="mb-5 flex items-center gap-2">
        @foreach (['Site', 'Anahtar kelimeler', 'Entegrasyonlar'] as $index => $label)
            @php $number = $index + 1; @endphp
            <li class="flex flex-1 items-center gap-2">
                <span @class([
                    'grid h-7 w-7 shrink-0 place-items-center rounded-full text-xs font-semibold',
                    'bg-brand-500 text-white' => $step >= $number,
                    'bg-canvas text-ink-faint ring-1 ring-line' => $step < $number,
                ])>{{ $number }}</span>
                <span class="truncate text-xs font-medium {{ $step >= $number ? 'text-ink' : 'text-ink-faint' }}">{{ $label }}</span>
                @if ($number < 3)
                    <span class="h-px flex-1 {{ $step > $number ? 'bg-brand-300' : 'bg-line' }}"></span>
                @endif
            </li>
        @endforeach
    </ol>

    @if ($step === 1)
    <form wire:submit="saveSite" class="card card-pad space-y-5">
        <div>
            <label for="domain" class="block text-sm font-medium">Alan adı</label>
            <div class="mt-1.5 flex gap-2">
                <select wire:model="protocol" class="field w-28">
                    <option value="https">https://</option>
                    <option value="http">http://</option>
                </select>
                <input id="domain" type="text" wire:model.blur="domain" placeholder="ornek.com" class="field flex-1">
            </div>
            @error('domain') <p class="mt-1.5 text-xs text-down">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="name" class="block text-sm font-medium">Proje adı</label>
            <input id="name" type="text" wire:model="name" class="field mt-1.5" placeholder="1etiket">
            @error('name') <p class="mt-1.5 text-xs text-down">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="language" class="block text-sm font-medium">Dil</label>
                <select id="language" wire:model="language" class="field mt-1.5">
                    <option value="tr">Türkçe</option>
                    <option value="en">İngilizce</option>
                    <option value="de">Almanca</option>
                </select>
            </div>

            <div>
                <label for="country" class="block text-sm font-medium">Ülke</label>
                <select id="country" wire:model="country" class="field mt-1.5">
                    <option value="TR">Türkiye</option>
                    <option value="DE">Almanya</option>
                    <option value="US">ABD</option>
                    <option value="GB">Birleşik Krallık</option>
                </select>
            </div>

            <div>
                <label for="cms" class="block text-sm font-medium">CMS</label>
                <select id="cms" wire:model="cms" class="field mt-1.5">
                    <option value="wordpress">WordPress</option>
                    <option value="woocommerce">WooCommerce</option>
                    <option value="custom">Diğer / özel</option>
                </select>
            </div>

            <div>
                <label for="crawl_frequency" class="block text-sm font-medium">Tarama sıklığı</label>
                <select id="crawl_frequency" wire:model="crawl_frequency" class="field mt-1.5">
                    <option value="daily">Günlük</option>
                    <option value="weekly">Haftalık</option>
                    <option value="manual">Manuel</option>
                </select>
            </div>
        </div>

        <div>
            <label for="max_pages" class="block text-sm font-medium">Taranacak azami sayfa</label>
            <input id="max_pages" type="number" wire:model="max_pages" class="field mt-1.5" min="10" max="100000">
            @error('max_pages') <p class="mt-1.5 text-xs text-down">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-line pt-4">
            <a href="{{ route('dashboard') }}" wire:navigate class="btn-ghost">Vazgeç</a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="saveSite">Devam et</span>
                <span wire:loading wire:target="saveSite">Kaydediliyor…</span>
            </button>
        </div>
    </form>

    @elseif ($step === 2)
        <form wire:submit="saveKeywords" class="card card-pad space-y-5">
            <div>
                <label for="keywords" class="block text-sm font-medium">Anahtar kelimeler</label>
                <p class="mt-0.5 text-xs text-ink-muted">
                    Her satıra bir kelime. Arama hacmi ve ilk sıra ölçümü arka planda çekilir. Bu adımı atlayabilirsin.
                </p>
                <textarea id="keywords" wire:model="keywords" rows="8" class="field mt-2 font-mono text-xs"
                          placeholder="etiket baskı&#10;sticker baskı&#10;ürün etiketi"></textarea>
            </div>

            <div class="flex items-center justify-between border-t border-line pt-4">
                <button type="button" wire:click="back" class="btn-ghost">Geri</button>
                <button type="submit" class="btn-primary">Devam et</button>
            </div>
        </form>

    @else
        <div class="card card-pad space-y-5">
            <div>
                <h2 class="text-sm font-semibold">Entegrasyonlar</h2>
                <p class="mt-0.5 text-xs text-ink-muted">Şimdi bağlayabilir ya da sonra Entegrasyonlar ekranından halledebilirsin.</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <a href="{{ $created ? route('search-console.connect', $created) : '#' }}"
                   class="card card-pad hover:border-brand-200">
                    <span class="flex items-center gap-2 text-sm font-medium">
                        <x-app.icon name="google" class="h-4 w-4 text-ink-muted" /> Search Console
                    </span>
                    <span class="mt-1 block text-xs text-ink-muted">Gerçek tıklama ve gösterim verisi.</span>
                </a>

                <a href="{{ route('integrations') }}" wire:navigate class="card card-pad hover:border-brand-200">
                    <span class="flex items-center gap-2 text-sm font-medium">
                        <x-app.icon name="plug" class="h-4 w-4 text-ink-muted" /> WordPress
                    </span>
                    <span class="mt-1 block text-xs text-ink-muted">Hataları doğrudan sitede düzeltmek için.</span>
                </a>
            </div>

            <label class="flex items-start gap-3 rounded-lg border border-line bg-canvas p-4">
                <input type="checkbox" wire:model="startCrawl"
                       class="mt-0.5 rounded border-line text-brand-500 focus:ring-brand-400">
                <span>
                    <span class="block text-sm font-medium">İlk taramayı hemen başlat</span>
                    <span class="block text-xs text-ink-muted">Panele döndüğünde ilerlemeyi üst barda görürsün.</span>
                </span>
            </label>

            <div class="flex items-center justify-between border-t border-line pt-4">
                <button type="button" wire:click="back" class="btn-ghost">Geri</button>
                <button wire:click="finish" class="btn-primary" wire:loading.attr="disabled">Kurulumu bitir</button>
            </div>
        </div>
    @endif
</div>
