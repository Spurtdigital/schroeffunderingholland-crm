<x-filament-widgets::widget class="fi-wi-project-activity">
    <x-filament::section heading="Historie">
        @php
            $activities = $record?->activities()->latest()->take(25)->get() ?? collect();

            $fieldLabels = [
                'status'         => 'Status',
                'project_number' => 'Projectnummer',
                'project_value'  => 'Projectwaarde',
                'name'           => 'Naam',
                'description'    => 'Omschrijving',
                'address'        => 'Adres',
                'city'           => 'Plaats',
                'postal_code'    => 'Postcode',
                'phone'          => 'Telefoon',
                'email'          => 'E-mail',
                'notes'          => 'Notities',
                'company_id'     => 'Bedrijf',
                'company.name'   => 'Bedrijf',
                'planned_at'     => 'Gepland op',
                'completed_at'   => 'Afgerond op',
            ];

            $statusLabels = [
                'active'    => 'Actief',
                'on_hold'   => 'In de wacht',
                'completed' => 'Afgerond',
                'cancelled' => 'Geannuleerd',
            ];
        @endphp

        <div class="space-y-2">
            @forelse($activities as $activity)
                <div class="flex gap-2 text-sm">
                    <div class="mt-0.5 shrink-0 text-gray-400 dark:text-gray-500">
                        <x-heroicon-o-clock class="w-3 h-3" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-gray-700 dark:text-gray-300 leading-snug">
                            @if($activity->description && $activity->description !== $activity->event)
                                <span class="font-medium">{{ $activity->description }}</span>
                            @elseif($activity->event === 'created')
                                <span class="font-medium text-success-600 dark:text-success-400">Aangemaakt</span>
                            @elseif($activity->event === 'updated')
                                <span class="font-medium text-info-600 dark:text-info-400">Bijgewerkt</span>
                            @elseif($activity->event === 'deleted')
                                <span class="font-medium text-danger-600 dark:text-danger-400">Verwijderd</span>
                            @else
                                <span class="font-medium">{{ ucfirst($activity->event ?? 'Gewijzigd') }}</span>
                            @endif
                            @if($activity->causer)
                                <span class="text-gray-400 dark:text-gray-500 font-normal"> door {{ $activity->causer->name }}</span>
                            @endif
                        </p>

                        @if($activity->event !== 'created' && $activity->properties->has('old'))
                            @php
                                $changes = $activity->properties->get('attributes', []);
                                $old = $activity->properties->get('old', []);
                            @endphp
                            <ul class="mt-0.5 space-y-0 text-xs text-gray-500 dark:text-gray-400">
                                @foreach($old as $key => $oldVal)
                                    @php
                                        $newVal = $changes[$key] ?? null;
                                        $label = $fieldLabels[$key] ?? ucfirst(str_replace(['.', '_'], ' ', $key));
                                        $fmt = function($k, $v) use ($statusLabels) {
                                            if ($v === null) return '—';
                                            if ($k === 'status') return $statusLabels[$v] ?? $v;
                                            return is_array($v) ? json_encode($v) : $v;
                                        };
                                    @endphp
                                    <li>
                                        <span class="font-medium text-gray-600 dark:text-gray-300">{{ $label }}:</span>
                                        <span class="line-through text-gray-400 dark:text-gray-600">{{ $fmt($key, $oldVal) }}</span>
                                        <span class="text-gray-400 dark:text-gray-600"> → </span>
                                        <span>{{ $fmt($key, $newVal) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                            {{ $activity->created_at->diffForHumans() }}
                            <span class="mx-1 text-gray-300 dark:text-gray-600">·</span>
                            {{ $activity->created_at->format('d-m-Y H:i') }}
                        </p>
                    </div>
                </div>
                @if(!$loop->last)
                    <div class="border-t border-gray-100 dark:border-white/5"></div>
                @endif
            @empty
                <p class="text-sm italic text-gray-400 dark:text-gray-500">Geen activiteit geregistreerd.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
