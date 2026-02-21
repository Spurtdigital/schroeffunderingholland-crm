<div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4 space-y-2">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Bedrijf</p>
            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $company->name ?? 'Onbekend' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Contact</p>
            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $lead->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Email</p>
            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $lead->email ?? $company->email ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Telefoon</p>
            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $lead->phone ?? $company->phone ?? '-' }}</p>
        </div>
    </div>
    
    @if($lead->notes)
        <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Projectomschrijving</p>
            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ Str::limit($lead->notes, 200) }}</p>
        </div>
    @endif
</div>
