@php
    // Achtergrond base64
    $bgPath = public_path('images/achtergrond-pdf.jpg');
    $bgBase64 = '';
    if (file_exists($bgPath)) {
        $bgBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($bgPath));
    }

    // Logo base64 — uit settings (opgeslagen pad) of uit public/images/logo.png
    $logoBase64 = null;
    if (!empty($settings->company_logo)) {
        $logoPath = storage_path('app/public/' . $settings->company_logo);
        if (file_exists($logoPath)) {
            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $mime = $ext === 'jpg' || $ext === 'jpeg' ? 'image/jpeg' : 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }
    }
    if (!$logoBase64) {
        $fallbackLogo = public_path('images/logo.jpg');
        if (file_exists($fallbackLogo)) {
            $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($fallbackLogo));
        }
    }
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Pakbon {{ $project->project_number }}</title>
    <style>
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: normal;
            src: url("{{ public_path('fonts/montserrat/Montserrat-Regular.ttf') }}") format('truetype');
        }
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: bold;
            src: url("{{ public_path('fonts/montserrat/Montserrat-Bold.ttf') }}") format('truetype');
        }
        @page {
            margin: 0;
            size: A4 portrait;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Montserrat', sans-serif;
            font-size: 9pt;
            color: #000;
            line-height: 1.3;
            background-color: #fff;
            background-image: url("{{ $bgBase64 }}");
            background-size: 100% 100%;
            background-repeat: no-repeat;
        }

        /* ===================== HEADER ===================== */
        .header {
            padding-top: 8.5mm;
            padding-bottom: 8mm;
            padding-left: 0;
            padding-right: 0;
            overflow: hidden;
        }
        .header-logo {
            float: left;
            width: 120mm;
            padding-left: 20mm;
        }
        .header-logo img {
            max-width: 180px;
            max-height: 60px;
        }
        .header-logo .logo-text {
            font-size: 14pt;
            font-weight: bold;
            color: #2563a8;
        }
        .header-naw {
            float: left;
            width: 66mm;
            text-align: left;
        }
        .header-naw p {
            font-size: 7.5pt;
            color: #444;
            line-height: 1.5;
        }
        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }

        /* ===================== CONTENT ===================== */
        .content {
            padding: 0 15mm 10mm 20mm;
        }

        /* Pakbon titel balk */
        .doc-title-bar {
            background: none;
            color: #000;
            padding: 10px 14px;
            margin-bottom: 14px;
            overflow: hidden;
        }
        .doc-title-bar .doc-title {
            float: left;
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 2px;
        }
        .doc-title-bar .doc-meta {
            float: right;
            text-align: right;
            font-size: 8pt;
            line-height: 1.6;
        }
        .doc-title-bar .doc-meta .doc-number {
            font-size: 11pt;
            font-weight: bold;
        }

        /* Info boxen */
        .info-container {
            margin-bottom: 14px;
            overflow: hidden;
        }
        .info-box {
            float: left;
            width: 48%;
            padding: 10px 12px;
            min-height: 120px;
        }
        .info-box:first-child {
            margin-right: 4%;
        }
        .info-box p {
            margin: 3px 0;
            font-size: 8.5pt;
        }
        .info-box .label {
            display: inline-block;
            width: 105px;
            font-weight: bold;
            color: #000;
        }

        /* Materialen sectie */
        .materials-section {
            margin-bottom: 14px;
        }
        .materials-section h3 {
            font-size: 9.5pt;
            font-weight: bold;
            color: #000;
            padding: 7px 0;
            margin-bottom: 0;
        }
        .materials-table {
            width: 100%;
            border-collapse: collapse;
        }
        .materials-table th {
            color: #000;
            padding: 7px 8px;
            text-align: left;
            font-weight: bold;
            border-top: 2px solid #000;
            border-bottom: 1px solid #000;
            font-size: 8pt;
        }
        .materials-table td {
            padding: 9px 8px;
            border-bottom: 1px solid #ccc;
            font-size: 8pt;
        }
        .materials-table tbody tr:last-child td {
            border-bottom: none;
        }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .materials-table tbody tr:last-child td {
            border-bottom: none;
        }
        .materials-table tfoot td {
            padding: 8px 8px;
            font-size: 8pt;
            font-weight: bold;
            border-top: 2px solid #000;
        }

        /* Opmerkingen sectie */
        .notes-section {
            padding: 10px 0;
            margin-bottom: 14px;
            min-height: 70px;
        }
        .notes-section h4 {
            font-size: 8.5pt;
            font-weight: bold;
            color: #000;
            margin-bottom: 6px;
        }
        .notes-section p {
            font-size: 8pt;
            line-height: 1.5;
            color: #000;
        }
        .notes-placeholder {
            color: #888;
            font-style: italic;
        }

        /* Handtekening sectie */
        .signature-section {
            margin-top: 20px;
            padding-top: 12px;
            border-top: 1px solid #ddd;
            overflow: hidden;
        }
        .signature-box {
            float: left;
            width: 55%;
        }
        .signature-box h4 {
            font-size: 8.5pt;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .signature-box img {
            max-width: 220px;
            max-height: 80px;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 4px;
            background: #fff;
        }
        .signature-line {
            float: left;
            width: 45%;
            padding-top: 60px;
            text-align: right;
        }
        .signature-line p {
            font-size: 7.5pt;
            color: #555;
            border-top: 1px solid #555;
            display: inline-block;
            padding-top: 3px;
        }

        /* Footer */
        .footer {
            padding: 7px 15mm 0 15mm;
            text-align: center;
            font-size: 6.5pt;
            color: #666;
        }
    </style>
</head>
<body>


<!-- Header: logo links, bedrijfs-NAW rechts boven -->
<div class="header clearfix">
    <div class="header-logo">
        @if($logoBase64)
            <img src="{{ $logoBase64 }}" alt="{{ $settings->company_name ?? 'Logo' }}">
        @else
            <span class="logo-text">{{ $settings->company_name ?? 'Schroeffundering Holland' }}</span>
        @endif
    </div>
    <div class="header-naw">
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
    </div>
</div>

<!-- Content wrapper -->
<div class="content">

    <!-- Pakbon titel balk -->
    <div class="doc-title-bar clearfix">
        <div class="doc-title">PAKBON</div>
        <div class="doc-meta">
            <div class="doc-number">{{ $project->project_number }}</div>
            <div>{{ now()->format('d-m-Y') }} &nbsp; {{ now()->format('H:i') }} uur</div>
        </div>
    </div>

    <!-- Info Boxen -->
    <div class="info-container clearfix">
        <div class="info-box">
            <p><strong>{{ $project->company->name }}</strong></p>
            @if($project->company->contact_person)
            <p><span class="label">T.a.v.:</span> {{ $project->company->contact_person }}</p>
            @endif
            @if($project->company->street)
            <p><span class="label">Adres:</span> {{ trim(($project->company->street ?? '') . ' ' . ($project->company->house_number ?? '')) }}</p>
            <p><span class="label"></span> {{ trim(($project->company->postal_code ?? '') . ' ' . ($project->company->city ?? '')) }}</p>
            @endif
            @if($project->company->phone)
            <p><span class="label">Telefoon:</span> {{ $project->company->phone }}</p>
            @endif
            @if($project->company->email)
            <p><span class="label">E-mail:</span> {{ $project->company->email }}</p>
            @endif
        </div>

        <div class="info-box">
            <p><span class="label">Projectnummer:</span> {{ $project->project_number }}</p>
            @if($project->quote && !str_starts_with($project->quote->quote_number ?? '', 'CONCEPT-'))
            <p><span class="label">Offertenummer:</span> {{ $project->quote->quote_number }}</p>
            @endif
            @if($project->start_date)
            <p><span class="label">Montagedatum:</span> {{ $project->start_date->format('d-m-Y') }}</p>
            @endif
            @if($project->quote?->machine)
            <p><span class="label">Machine:</span> {{ $project->quote->machine->name }}</p>
            @endif
            @php
                $werklocatie = $project->quote?->workFullAddress();
            @endphp
            @if($werklocatie)
            <p><span class="label">Werklocatie:</span></p>
            <p style="padding-left: 105px; word-wrap: break-word; overflow-wrap: break-word;">{!! nl2br(e($werklocatie)) !!}</p>
            @endif
        </div>
    </div>

    <!-- Materialen Tabel -->
    <div class="materials-section">
        <h3>Te pakken materialen</h3>
        @php
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
        @php $descWidth = $hasWeight ? '42%' : '53%'; @endphp
        <table class="materials-table">
            <thead>
                <tr>
                    <th style="width: 12%;" class="text-center">Aantal</th>
                    <th style="width: 23%;">Artikelnummer</th>
                    <th style="width: {{ $descWidth }};">Omschrijving</th>
                    @if($hasWeight)
                    <th style="width: 15%;" class="text-center">Gewicht</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($project->items as $item)
                    @if($item->itemable_type === 'App\\Models\\Product' && $item->itemable)
                        @php $lineWeight = $item->itemable->weight ? $item->quantity * $item->itemable->weight : null; @endphp
                        <tr>
                            <td class="text-center text-bold">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                            <td>{{ $item->itemable->sku ?? '-' }}</td>
                            <td>{{ str_replace('⌀', 'Ø', $item->itemable->name ?? $item->description) }}</td>
                            @if($hasWeight)
                            <td class="text-center">{{ $lineWeight ? number_format($lineWeight, 1, ',', '.') . ' kg' : '-' }}</td>
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
                    <td class="text-center">{{ number_format($totalWeight, 1, ',', '.') }} kg</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    <!-- Opmerkingen -->
    <div class="notes-section">
        <h4>Opmerkingen / Montage-instructies</h4>
        @if($project->notes)
        <p>{{ $project->notes }}</p>
        @else
        <p class="notes-placeholder">Ruimte voor aanvullende opmerkingen...</p>
        @endif
    </div>

    {{-- Handtekening --}}
    @if($signatureBase64)
    <div class="signature-section clearfix">
        <div class="signature-box">
            <h4>Handtekening klant</h4>
            <img src="{{ $signatureBase64 }}" alt="Handtekening">
        </div>
        <div class="signature-line">
            <p>Datum: {{ $project->completion_date?->format('d-m-Y') ?? '___________________________' }}</p>
        </div>
    </div>
    @else
    <div class="signature-section clearfix">
        <div class="signature-box">
            <h4>Handtekening klant</h4>
            <div style="width: 200px; height: 60px; border: 1px solid #ccc; border-radius: 4px;"></div>
        </div>
        <div class="signature-line">
            <p>Datum: {{ $project->completion_date?->format('d-m-Y') ?? '___________________________' }}</p>
        </div>
    </div>
    @endif

</div><!-- /content -->

<!-- Footer -->
<div class="footer">
    @if($settings->company_kvk_number)
    <p>KvK: {{ $settings->company_kvk_number }}@if($settings->company_vat_number) &nbsp;|&nbsp; BTW: {{ $settings->company_vat_number }}@endif</p>
    @endif
</div>

</body>
</html>
