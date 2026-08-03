@php
    $activities = $getRecord()->activities()->latest()->take(25)->get();
    $viewLogs = $getRecord()->viewLogs()->take(25)->get();

    // Combine activities and view logs into a single timeline
    $timeline = collect();

    foreach ($activities as $activity) {
        $timeline->push([
            'type' => 'activity',
            'data' => $activity,
            'created_at' => $activity->created_at,
        ]);
    }

    foreach ($viewLogs as $log) {
        $timeline->push([
            'type' => 'view_log',
            'data' => $log,
            'created_at' => $log->created_at,
        ]);
    }

    $timeline = $timeline->sortByDesc('created_at')->take(30)->values();

    $eventConfig = [
        'created'      => ['label' => 'Aangemaakt',      'color' => '#16a34a', 'dot' => '#22c55e'],
        'updated'      => ['label' => 'Bijgewerkt',       'color' => '#ea580c', 'dot' => '#f97316'],
        'deleted'      => ['label' => 'Verwijderd',       'color' => '#dc2626', 'dot' => '#ef4444'],
    ];

    $viewLogConfig = [
        'viewed' => ['label' => 'Bekeken door klant', 'color' => '#0284c7', 'dot' => '#0ea5e9'],
        'signed' => ['label' => 'Ondertekend door klant', 'color' => '#16a34a', 'dot' => '#22c55e'],
    ];

    $fieldLabels = [
        'status'        => 'Status',
        'quote_number'  => 'Offertenummer',
        'total'         => 'Totaal',
        'company.name'  => 'Bedrijf',
        'valid_until'   => 'Geldig tot',
        'notes'         => 'Opmerkingen',
    ];

    $statusLabels = [
        'draft'               => 'Concept',
        'sent'                => 'Verstuurd',
        'moneybird_prepared'  => 'Moneybird concept',
        'approved'            => 'Goedgekeurd',
        'converted'           => 'Geconverteerd',
        'rejected'            => 'Afgewezen',
    ];
@endphp

@forelse($timeline as $item)
    @if($item['type'] === 'activity')
        @php
            $activity = $item['data'];
            $cfg     = $eventConfig[$activity->event] ?? ['label' => ucfirst($activity->event ?? 'Gewijzigd'), 'color' => '#6b7280', 'dot' => '#9ca3af'];
            $old     = $activity->event !== 'created' ? $activity->properties->get('old', []) : [];
            $changes = $activity->properties->get('attributes', []);
            $isLast  = $loop->last;
            $fmt = function($k, $v) use ($statusLabels) {
                if ($v === null) return '—';
                if ($k === 'status') return $statusLabels[$v] ?? $v;
                return is_array($v) ? implode(', ', $v) : $v;
            };
        @endphp

        <div style="display:flex; flex-direction:row; gap:10px; position:relative; padding-bottom:12px;">
            {{-- Dot column --}}
            <div style="display:flex; flex-direction:column; align-items:center; flex-shrink:0; width:14px; padding-top:2px;">
                <span style="display:block; width:10px; height:10px; border-radius:50%; background:{{ $cfg['dot'] }}; flex-shrink:0;"></span>
                @if(!$isLast)
                    <span style="display:block; width:2px; flex:1; background:#e5e7eb; margin-top:4px;"></span>
                @endif
            </div>

            {{-- Content --}}
            <div style="flex:1; min-width:0; padding-bottom:4px;">
                <p style="margin:0; font-size:0.75rem; line-height:1.4;">
                    <span style="font-weight:600; color:{{ $cfg['color'] }};">{{ $cfg['label'] }}</span>
                    @if($activity->causer)
                        <span style="color:#6b7280;"> door {{ $activity->causer->name }}</span>
                    @endif
                </p>

                @if(!empty($old))
                    <ul style="margin:4px 0 0; padding:0; list-style:none;">
                        @foreach($old as $key => $oldVal)
                            @php
                                $newVal = $changes[$key] ?? null;
                                $label  = $fieldLabels[$key] ?? ucfirst(str_replace(['.', '_'], ' ', $key));
                            @endphp
                            <li style="font-size:0.75rem; color:#6b7280;">
                                <span style="font-weight:500; color:#374151;">{{ $label }}:</span>
                                <span style="text-decoration:line-through; opacity:0.5;">{{ $fmt($key, $oldVal) }}</span>
                                <span style="opacity:0.5;"> → </span>
                                <span>{{ $fmt($key, $newVal) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <p style="margin:2px 0 0; font-size:0.7rem; color:#9ca3af;">
                    {{ $activity->created_at->format('d-m-Y H:i') }}
                </p>
            </div>
        </div>
    @else
        @php
            $log = $item['data'];
            $cfg = $viewLogConfig[$log->event] ?? ['label' => 'Onbekend', 'color' => '#6b7280', 'dot' => '#9ca3af'];
            $isLast = $loop->last;
        @endphp

        <div style="display:flex; flex-direction:row; gap:10px; position:relative; padding-bottom:12px;">
            {{-- Dot column --}}
            <div style="display:flex; flex-direction:column; align-items:center; flex-shrink:0; width:14px; padding-top:2px;">
                <span style="display:block; width:10px; height:10px; border-radius:50%; background:{{ $cfg['dot'] }}; flex-shrink:0;"></span>
                @if(!$isLast)
                    <span style="display:block; width:2px; flex:1; background:#e5e7eb; margin-top:4px;"></span>
                @endif
            </div>

            {{-- Content --}}
            <div style="flex:1; min-width:0; padding-bottom:4px;">
                <p style="margin:0; font-size:0.75rem; line-height:1.4;">
                    <span style="font-weight:600; color:{{ $cfg['color'] }};">{{ $cfg['label'] }}</span>
                </p>

                <p style="margin:2px 0 0; font-size:0.7rem; color:#6b7280;">
                    IP: {{ $log->ip_address ?? 'Onbekend' }}
                </p>

                <p style="margin:2px 0 0; font-size:0.7rem; color:#9ca3af;">
                    {{ $log->created_at->format('d-m-Y H:i') }}
                </p>
            </div>
        </div>
    @endif
@empty
    <p style="font-size:0.75rem; font-style:italic; color:#9ca3af;">Geen activiteit geregistreerd.</p>
@endforelse
