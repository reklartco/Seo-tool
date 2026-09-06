<div class="mx-auto max-w-2xl">
    <div class="mb-5">
        <h1 class="text-xl font-semibold tracking-tight">Yeni Proje</h1>
        <p class="mt-1 text-sm text-ink-muted">Alan adını ekle, sonraki adımlarda anahtar kelime ve entegrasyonları bağlarız.</p>
    </div>

    <form wire:submit="save" class="card card-pad space-y-5">
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
                <span wire:loading.remove wire:target="save">Projeyi oluştur</span>
                <span wire:loading wire:target="save">Oluşturuluyor…</span>
            </button>
        </div>
    </form>
</div>
