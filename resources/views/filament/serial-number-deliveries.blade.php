<div>
    @php
        $locations = $serial->deliveryLocations();
    @endphp

    @if (empty($locations))
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Dit serienummer is nog niet geleverd of gereserveerd.
        </p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-gray-500 dark:text-gray-400">
                        <th class="py-2 pr-4 font-medium">Project</th>
                        <th class="py-2 pr-4 font-medium">Klant</th>
                        <th class="py-2 pr-4 font-medium">Aantal</th>
                        <th class="py-2 pr-4 font-medium">Projectstatus</th>
                        <th class="py-2 font-medium">Opleverdatum</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($locations as $location)
                        <tr class="border-b dark:border-gray-700">
                            <td class="py-2 pr-4">
                                @if ($location['project_id'])
                                    <a
                                        href="{{ \App\Filament\Resources\Projects\ProjectResource::getUrl('view', ['record' => $location['project_id']]) }}"
                                        class="text-primary-600 hover:underline dark:text-primary-400"
                                    >
                                        {{ $location['project_number'] ?? '—' }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-2 pr-4">{{ $location['company'] ?? '—' }}</td>
                            <td class="py-2 pr-4">{{ $location['quantity'] }}x</td>
                            <td class="py-2 pr-4">
                                @if ($location['status'])
                                    <span class="fi-badge">{{ $location['status']->getLabel() }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-2">
                                {{ $location['completion_date'] ? \Illuminate\Support\Carbon::parse($location['completion_date'])->format('d-m-Y') : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
