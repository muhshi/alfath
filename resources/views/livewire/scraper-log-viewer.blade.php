<x-filament-widgets::widget>
    <x-filament::section>
        <div wire:poll.2s>
            <div class="bg-black text-green-400 p-4 rounded-md font-mono text-xs md:text-sm overflow-y-auto" style="height: 250px; white-space: pre-wrap;">
                {{ $logs ?: 'Memuat log...' }}
            </div>
            <div class="text-xs text-gray-400 mt-2 text-right">
                Live Scraper Progress | Otomatis memperbarui setiap 2 detik
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
