@php
    $activities = $getRecord()->activities()->latest()->take(25)->get();

    $eventConfig = [
        'created'      => ['label' => 'Aangemaakt',      'color' => '#16a34a', 'dot' => '#22c55e'],
        'updated'      => ['label' => 'Bijgewerkt',       'color' => '#ea580c', 'dot' => '#f97316'],
        'deleted'      => ['label' => 'Verwijderd',       'color' => '#dc2626', 'dot' => '#ef4444'],
        'item_added'   => ['label' => 'Item toegevoegd',  'color' => '#16a34a', 'dot' => '#22c55e'],
        'item_updated' => ['label' => 'Item bijgewerkt',  'color' => '#ea580c', 'dot' => '#f97316'],
        'item_deleted' => ['label' => 'Item verwijderd',  'color' => '#dc2626', 'dot' => '#ef4444'],
    ];

    $fieldLabels = [
        'status'          => 'Status',
        'project_number'  => 'Projectnummer',
        'project_value'   => 'Projectwaarde',
        'quote_number'    => 'Offertenummer',
        'total'           => 'Totaal',
        'description'     => 'Omschrijving',
        'notes'           => 'Notities',
        'start_date'      => 'Montagedatum',
        'completion_date' => 'Opleverdatum',
        'end_date'        => 'Einddatum',
        'company_id'      => 'Bedrijf',
        'company.name'    => 'Bedrijf',
        'contact_id'      => 'Contactpersoon',
        'user_id'         => 'Medewerker',
        'address'         => 'Adres',
        'city'            => 'Plaats',
        'postal_code'     => 'Postcode',
    ];
@endphp

@forelse($activities as $activity)
    @php
        $cfg      = $eventConfig[$activity->event] ?? ['label' => ucfirst($activity->event ?? 'Gewijzigd'), 'color' => '#6b7280', 'dot' => '#9ca3af'];
        $isCreate = in_array($activity->event, ['created', 'item_added', 'item_deleted']);
        $old      = $isCreate ? [] : $activity->properties->get('old', []);
        $changes  = $isCreate ? [] : $activity->properties->get('attributes', []);
        $itemLabel = in_array($activity->event, ['item_added', 'item_updated', 'item_deleted'])
            ? ($activity->properties->get('item_description') ?? $activity->description)
            : null;
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
                @if($itemLabel)
                    <span style="color:#374151;"> · {{ $itemLabel }}</span>
                @endif
                @if($activity->causer)
                    <span style="color:#6b7280;"> door {{ $activity->causer->name }}</span>
                @endif
            </p>

            {{-- item_added / item_deleted: qty + prijs --}}
            @if(in_array($activity->event, ['item_added', 'item_deleted']))
                @php
                    $qty   = $activity->properties->get('item_quantity');
                    $price = $activity->properties->get('item_unit_price');
                    $total = $activity->properties->get('item_total');
                @endphp
                @if($qty !== null && $price !== null)
                    <p style="margin:2px 0 0; font-size:0.75rem; color:#6b7280;">
                        {{ $qty }}x &euro;{{ number_format($price, 2, ',', '.') }}
                        @if($total !== null)
                            &nbsp;=&nbsp;<span style="font-weight:500;">&euro;{{ number_format($total, 2, ',', '.') }}</span>
                        @endif
                    </p>
                @endif
            @endif

            {{-- updated: oud → nieuw --}}
            @if(!empty($old))
                <ul style="margin:4px 0 0; padding:0; list-style:none;">
                    @foreach($old as $key => $oldVal)
                        @php
                            $newVal = $changes[$key] ?? null;
                            $label  = $fieldLabels[$key] ?? ucfirst(str_replace(['.', '_'], ' ', $key));
                        @endphp
                        <li style="font-size:0.75rem; color:#6b7280;">
                            <span style="font-weight:500; color:#374151;">{{ $label }}:</span>
                            <span style="text-decoration:line-through; opacity:0.5;">{{ is_array($oldVal) ? implode(', ', $oldVal) : $oldVal }}</span>
                            <span style="opacity:0.5;"> → </span>
                            <span>{{ is_array($newVal) ? implode(', ', $newVal) : $newVal }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <p style="margin:2px 0 0; font-size:0.7rem; color:#9ca3af;">
                {{ $activity->created_at->format('d-m-Y H:i') }}
            </p>
        </div>
    </div>
@empty
    <p style="font-size:0.75rem; font-style:italic; color:#9ca3af;">Geen activiteit geregistreerd.</p>
@endforelse
