<div class="mx-auto max-w-2xl space-y-5">
    @if (! $project)
        <x-app.empty-state title="Önce bir proje oluştur" action="Yeni proje oluştur" :href="route('projects.create')" />
    @else
        <form wire:submit="save" class="card card-pad space-y-5">
            <div>
                <h2 class="text-base font-semibold">{{ $project->domain }}</h2>
                <p class="mt-0.5 text-sm text-ink-muted">Tarama ve düzeltme davranışını buradan yönet.</p>
            </div>

            <div>
                <label for="name" class="block text-sm font-medium">Proje adı</label>
                <input id="name" type="text" wire:model="name" class="field mt-1.5">
                @error('name') <p class="mt-1.5 text-xs text-down">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="protocol" class="block text-sm font-medium">Protokol</label>
                    <select id="protocol" wire:model="protocol" class="field mt-1.5">
                        <option value="https">https</option>
                        <option value="http">http</option>
                    </select>
                </div>
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
                <div>
                    <label for="max_pages" class="block text-sm font-medium">Azami sayfa</label>
                    <input id="max_pages" type="number" wire:model="max_pages" class="field mt-1.5" min="10" max="100000">
                </div>
            </div>

            <div class="rounded-lg border border-line bg-canvas p-4">
                <label class="flex items-start gap-3">
                    <input type="checkbox" wire:model="auto_apply_fixes"
                           class="mt-0.5 rounded border-line text-brand-500 focus:ring-brand-400"
                           @disabled($planFixLimit === 0)>
                    <span>
                        <span class="block text-sm font-medium">Onay beklemeden uygula</span>
                        <span class="block text-xs text-ink-muted">
                            AI düzeltmeleri taslakta beklemeden WordPress'e gider.
                            @if ($planFixLimit === 0)
                                Planın bu özelliği kapsamıyor.
                            @else
                                Planın günde en fazla {{ $planFixLimit }} sayfaya izin veriyor.
                            @endif
                        </span>
                    </span>
                </label>
                @error('auto_apply_fixes') <p class="mt-1.5 text-xs text-down">{{ $message }}</p> @enderror

                @if ($planFixLimit > 0)
                    <div class="mt-3">
                        <label for="daily_fix_limit" class="block text-sm font-medium">Günlük düzeltme sınırı</label>
                        <input id="daily_fix_limit" type="number" wire:model="daily_fix_limit"
                               class="field mt-1.5 w-32" min="0" max="{{ $planFixLimit }}">
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-between border-t border-line pt-4">
                <button type="button" wire:click="deleteProject"
                        wire:confirm="Proje ve tüm tarama verisi silinsin mi? Bu işlem geri alınamaz."
                        class="btn-ghost text-down">Projeyi sil</button>
                <button type="submit" class="btn-primary">Kaydet</button>
            </div>
        </form>
    @endif
</div>
