@php
    $logoUrl = null;
    if (!empty($settings->company_logo)) {
        $logoPath = storage_path('app/public/' . $settings->company_logo);
        if (file_exists($logoPath)) {
            $logoUrl = asset('storage/' . $settings->company_logo);
        }
    }
    if (!$logoUrl) {
        $fallbackLogo = public_path('images/logo.jpg');
        if (file_exists($fallbackLogo)) {
            $logoUrl = asset('images/logo.jpg');
        }
    }

    $signatureUrl = null;
    if ($signatureBase64) {
        $signatureUrl = $signatureBase64;
    }

    $hasItems = false;
    $totalWeight = 0;
    $hasWeight = false;
    foreach ($project->items as $item) {
        if ($item->itemable_type === 'App\\Models\\Product' && $item->itemable) {
            $hasItems = true;
            if ($item->itemable->weight) {
                $hasWeight = true;
                $totalWeight += $item->quantity * $item->itemable->weight;
            }
        }
    }
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Pakbon {{ $project->project_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Montserrat', sans-serif;
            font-size: 9pt;
            color: #000;
            line-height: 1.3;
            background: #fff;
            padding: 20px;
        }
        .no-print { display: block; }
        .print-toolbar {
            text-align: center;
            padding: 16px 0;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .print-toolbar button {
            padding: 10px 32px;
            font-size: 14px;
            font-family: 'Montserrat', sans-serif;
            font-weight: bold;
            background: #2563a8;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .print-toolbar button:hover { background: #1d4e8a; }
        .print-toolbar .hint { font-size: 10px; color: #888; margin-top: 6px; }

        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; padding: 0; }
        .header-logo img { max-width: 180px; max-height: 60px; }
        .header-logo .logo-text { font-size: 14pt; font-weight: bold; color: #2563a8; }
        .header-naw { text-align: right; }
        .header-naw p { font-size: 7.5pt; color: #444; line-height: 1.5; }

        .content { padding: 10px 0; }

        .doc-title-bar { padding: 14px 0; margin-bottom: 14px; overflow: hidden; border-bottom: 2px solid #000; }
        .doc-title-bar .doc-title { float: left; font-size: 16pt; font-weight: bold; letter-spacing: 2px; }
        .doc-title-bar .doc-meta { float: right; text-align: right; font-size: 8pt; line-height: 1.6; }
        .doc-title-bar .doc-meta .doc-number { font-size: 11pt; font-weight: bold; }

        .info-container { margin-bottom: 14px; overflow: hidden; }
        .info-box { float: left; width: 48%; padding: 10px 12px; }
        .info-box:first-child { margin-right: 4%; }
        .info-box p { margin: 3px 0; font-size: 8.5pt; }
        .info-table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        .info-table td { padding: 2px 0; vertical-align: top; }
        .info-table td.lbl { font-weight: bold; white-space: nowrap; width: 110px; padding-right: 6px; }

        .materials-section { margin-bottom: 14px; }
        .materials-section h3 { font-size: 9.5pt; font-weight: bold; padding: 7px 0; }
        .materials-table { width: 100%; border-collapse: collapse; }
        .materials-table th {
            padding: 7px 8px; text-align: left; font-weight: bold;
            border-top: 2px solid #000; border-bottom: 1px solid #000; font-size: 8pt;
        }
        .materials-table td { padding: 9px 8px; border-bottom: 1px solid #ccc; font-size: 8pt; }
        .materials-table tfoot td { padding: 8px; font-size: 8pt; font-weight: bold; border-top: 2px solid #000; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }

        .notes-section { padding: 10px 0; margin-bottom: 14px; }
        .notes-section h4 { font-size: 8.5pt; font-weight: bold; margin-bottom: 6px; }
        .notes-section p { font-size: 8pt; line-height: 1.5; }
        .notes-placeholder { color: #888; font-style: italic; }

        .signature-section { margin-top: 20px; padding-top: 12px; border-top: 1px solid #ddd; overflow: hidden; }
        .signature-box { float: left; width: 55%; }
        .signature-box h4 { font-size: 8.5pt; font-weight: bold; margin-bottom: 6px; }
        .signature-box img { max-width: 220px; max-height: 80px; border: 1px solid #ccc; border-radius: 4px; padding: 4px; background: #fff; }
        .signature-line { float: left; width: 45%; padding-top: 60px; text-align: right; }
        .signature-line p { font-size: 7.5pt; color: #555; border-top: 1px solid #555; display: inline-block; padding-top: 3px; }

        .footer { padding: 10px 0; text-align: center; font-size: 6.5pt; color: #666; }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            @page { margin: 15mm; size: A4 portrait; }
        }
    </style>
</head>
<body>

<div class="no-print print-toolbar">
    <button onclick="window.print()">Pakbon afdrukken</button>
    <div class="hint">Druk op Ctrl+P (Cmd+P op Mac) of klik op de knop hierboven</div>
</div>

<table class="header-table">
    <tr>
        <td class="header-logo">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $settings->company_name ?? 'Logo' }}">
            @else
                <span class="logo-text">{{ $settings->company_name ?? 'Schroeffundering Holland' }}</span>
            @endif
        </td>
        <td class="header-naw">
            @if($settings->company_address)
            <p>{{ $settings->company_address }}</p>
            @endif
            @if($settings->company_postal_code || $settings->company_city)
            <p>{{ trim(($settings->company_postal_code ?? '') . ' ' . ($settings->company_city ?? '')) }}</p>
            @endif
            @if($settings->company_phone)
            <p>T: {{ $settings->company_phone }}</p>
            @endif
            @if($settings->company_email)
            <p>E: {{ $settings->company_email }}</p>
            @endif
            @if($settings->company_kvk_number)
            <p>KvK: {{ $settings->company_kvk_number }}</p>
            @endif
        </td>
    </tr>
</table>

<div class="content">
    <div class="doc-title-bar">
        <div class="doc-title">PAKBON</div>
        <div class="doc-meta">
            <div class="doc-number">{{ $project->project_number }}</div>
            <div>{{ now()->format('d-m-Y') }} &nbsp; {{ now()->format('H:i') }} uur</div>
        </div>
    </div>

    <div class="info-container">
        <div class="info-box">
            <p><strong>{{ $project->company->name }}</strong></p>
            <table class="info-table">
                @if($project->company->contact_person)
                <tr><td class="lbl">T.a.v.:</td><td>{{ $project->company->contact_person }}</td></tr>
                @endif
                @if($project->company->street)
                <tr><td class="lbl">Adres:</td><td>{{ trim(($project->company->street ?? '') . ' ' . ($project->company->house_number ?? '')) }}</td></tr>
                <tr><td class="lbl"></td><td>{{ trim(($project->company->postal_code ?? '') . ' ' . ($project->company->city ?? '')) }}</td></tr>
                @endif
                @if($project->company->phone)
                <tr><td class="lbl">Telefoon:</td><td>{{ $project->company->phone }}</td></tr>
                @endif
                @if($project->company->email)
                <tr><td class="lbl">E-mail:</td><td>{{ $project->company->email }}</td></tr>
                @endif
            </table>
        </div>

        <div class="info-box">
            <table class="info-table">
                <tr><td class="lbl">Projectnummer:</td><td>{{ $project->project_number }}</td></tr>
                @if($project->quote && !str_starts_with($project->quote->quote_number ?? '', 'CONCEPT-'))
                <tr><td class="lbl">Offertenummer:</td><td>{{ $project->quote->quote_number }}</td></tr>
                @endif
                @if($project->start_date)
                <tr><td class="lbl">Montagedatum:</td><td>{{ $project->start_date->format('d-m-Y') }}</td></tr>
                @endif
                @if($project->quote?->machine)
                <tr><td class="lbl">Machine:</td><td>{{ $project->quote->machine->name }}</td></tr>
                @endif
                @php $werklocatie = $project->quote?->workFullAddress(); @endphp
                @if($werklocatie)
                <tr><td class="lbl">Werklocatie:</td><td>{!! nl2br(e($werklocatie)) !!}</td></tr>
                @endif
            </table>
        </div>
    </div>

    <div class="materials-section">
        <h3>Te pakken materialen</h3>
        @php $descWidth = $hasWeight ? '47%' : '58%'; @endphp
        <table class="materials-table">
            <thead>
                <tr>
                    <th style="width: 9%;">Aantal</th>
                    <th style="width: 16%;">Artikelnummer</th>
                    <th style="width: {{ $descWidth }};">Omschrijving</th>
                    @if($hasWeight)
                    <th style="width: 13%; text-align: right; padding-right: 10px;">Gewicht</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($project->items as $item)
                    @if($item->itemable_type === 'App\\Models\\Product' && $item->itemable)
                        @php $lineWeight = $item->itemable->weight ? $item->quantity * $item->itemable->weight : null; @endphp
                        <tr>
                            <td class="text-bold">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                            <td>{{ $item->itemable->sku ?? '-' }}</td>
                            <td>{{ str_replace('⌀', 'Ø', $item->itemable->name ?? $item->description) }}</td>
                            @if($hasWeight)
                            <td class="text-right" style="padding-right: 10px;">{{ $lineWeight ? number_format($lineWeight, 1, ',', '.') . ' kg' : '-' }}</td>
                            @endif
                        </tr>
                    @endif
                @endforeach
                @if(!$hasItems)
                <tr>
                    <td colspan="{{ $hasWeight ? 4 : 3 }}" class="text-center" style="padding: 25px;">Geen materialen beschikbaar</td>
                </tr>
                @endif
            </tbody>
            @if($hasWeight)
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right;">Totaalgewicht:</td>
                    <td class="text-right" style="padding-right: 10px;">{{ number_format($totalWeight, 1, ',', '.') }} kg</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    <div class="notes-section">
        <h4>Opmerkingen / Montage-instructies</h4>
        @if($project->notes)
        <p>{{ $project->notes }}</p>
        @else
        <p class="notes-placeholder">Ruimte voor aanvullende opmerkingen...</p>
        @endif
    </div>

    @if($signatureUrl)
    <div class="signature-section">
        <div class="signature-box">
            <h4>Handtekening klant</h4>
            <img src="{{ $signatureUrl }}" alt="Handtekening">
        </div>
        <div class="signature-line">
            <p>Datum: {{ $project->completion_date?->format('d-m-Y') ?? '___________________________' }}</p>
        </div>
    </div>
    @else
    <div class="signature-section">
        <div class="signature-box">
            <h4>Handtekening klant</h4>
            <div style="width: 200px; height: 60px; border: 1px solid #ccc; border-radius: 4px;"></div>
        </div>
        <div class="signature-line">
            <p>Datum: {{ $project->completion_date?->format('d-m-Y') ?? '___________________________' }}</p>
        </div>
    </div>
    @endif
</div>

<div class="footer">
    @if($settings->company_kvk_number)
    <p>KvK: {{ $settings->company_kvk_number }}@if($settings->company_vat_number) &nbsp;|&nbsp; BTW: {{ $settings->company_vat_number }}@endif</p>
    @endif
</div>

<script>
    (function() {
        if (window.location.search.includes('print=1')) {
            window.onload = function() {
                setTimeout(function() { window.print(); }, 500);
            };
        }
    })();
</script>

</body>
</html>
