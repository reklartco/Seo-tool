<div x-data="{
        items: [],
        push(detail) {
            const id = Date.now() + Math.random();
            this.items.push({ id, ...detail });
            setTimeout(() => this.items = this.items.filter(i => i.id !== id), 4000);
        },
     }"
     @toast.window="push($event.detail)"
     class="pointer-events-none fixed bottom-5 right-5 z-50 flex w-80 flex-col gap-2">
    <template x-for="item in items" :key="item.id">
        <div x-transition
             class="pointer-events-auto rounded-xl border border-line bg-surface px-4 py-3 text-sm shadow-pop"
             :class="item.type === 'error' ? 'text-down' : 'text-ink'">
            <span x-text="item.message"></span>
        </div>
    </template>
</div>
