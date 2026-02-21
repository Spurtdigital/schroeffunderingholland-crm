<x-filament-widgets::widget class="fi-wi-project-notes">
    <x-filament::section heading="Notities">
        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
            {{ $record?->notes ?: 'Geen notities.' }}
        </p>
    </x-filament::section>
</x-filament-widgets::widget>
