@php
    // Achtergrond base64
    $bgPath = public_path('images/achtergrond-pdf.jpg');
    $bgBase64 = '';
    if (file_exists($bgPath)) {
        $bgBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($bgPath));
    }

    // Logo base64
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

    // Alle projectitems
    $items = $project->items;
    $grandTotal = $items->sum(fn($i) => (float) $i->total);
    $vatAmount = round($grandTotal * 0.21, 2);
    $totalIncl = $grandTotal + $vatAmount;

    // Foto's als base64
    $photos = $project->getMedia('project-photos');
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Opleverrapport {{ $project->project_number }}</title>
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
            @if($bgBase64)
            background-image: url("{{ $bgBase64 }}");
            background-size: 100% 100%;
            background-repeat: no-repeat;
            @endif
        }

        /* ===================== HEADER ===================== */
        .header {
            padding-top: 8.5mm;
            padding-bottom: 8mm;
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

        /* Titel balk */
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
            min-height: 100px;
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
        }

        /* Sectie headers */
        .section-title {
            font-size: 9.5pt;
            font-weight: bold;
            padding: 7px 0;
            border-bottom: 2px solid #000;
            margin-bottom: 0;
        }

        /* Items tabel */
        .items-section {
            margin-bottom: 16px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th {
            color: #000;
            padding: 7px 8px;
            text-align: left;
            font-weight: bold;
            border-bottom: 1px solid #000;
            font-size: 8pt;
        }
        .items-table th.text-right { text-align: right; }
        .items-table td {
            padding: 7px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 8pt;
            vertical-align: top;
        }
        .items-table td.text-right { text-align: right; }
        .items-table tbody tr:last-child td {
            border-bottom: none;
        }
        .items-table .totals-row td {
            border-top: 1px solid #000;
            border-bottom: none;
            font-weight: bold;
            padding-top: 8px;
        }
        .items-table .vat-row td,
        .items-table .total-incl-row td {
            border-bottom: none;
            padding-top: 3px;
        }
        .items-table .total-incl-row td {
            font-weight: bold;
            font-size: 9pt;
            border-top: 1px solid #000;
            padding-top: 6px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }

        /* Opmerkingen */
        .notes-section {
            padding: 10px 0;
            margin-bottom: 14px;
        }
        .notes-section p {
            font-size: 8pt;
            line-height: 1.5;
            margin-top: 6px;
        }
        .notes-placeholder {
            color: #888;
            font-style: italic;
        }

        /* Foto's */
        .photos-section {
            margin-bottom: 16px;
        }
        .photos-grid {
            overflow: hidden;
            margin-top: 8px;
        }
        .photo-item {
            float: left;
            width: 30%;
            margin-right: 3%;
            margin-bottom: 8px;
        }
        .photo-item:nth-child(3n) {
            margin-right: 0;
        }
        .photo-item img {
            width: 100%;
            height: 55mm;
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 3px;
        }

        /* Handtekeningen */
        .signatures-section {
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px solid #ddd;
            overflow: hidden;
            page-break-inside: avoid;
        }
        .signature-col {
            float: left;
            width: 46%;
            margin-right: 4%;
        }
        .signature-col:last-child {
            margin-right: 0;
        }
        .signature-col h4 {
            font-size: 8.5pt;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .signature-col img {
            max-width: 200px;
            max-height: 70px;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 4px;
            background: #fff;
        }
        .signature-col .sig-empty {
            width: 200px;
            height: 60px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .signature-col .sig-meta {
            margin-top: 6px;
            font-size: 7.5pt;
            color: #555;
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

<!-- Header -->
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
    </div>
</div>

<div class="content">

    <!-- Titel balk -->
    <div class="doc-title-bar clearfix">
        <div class="doc-title">OPLEVERRAPPORT</div>
        <div class="doc-meta">
            <div class="doc-number">{{ $project->project_number }}</div>
            <div>{{ now()->format('d-m-Y') }}</div>
        </div>
    </div>

    <!-- Info boxen -->
    <div class="info-container clearfix">
        <div class="info-box">
            <p><strong>{{ $project->company->name }}</strong></p>
            @if(!empty($project->company->contact_person))
            <p><span class="label">T.a.v.:</span> {{ $project->company->contact_person }}</p>
            @endif
            @if(!empty($clientName))
            <p><span class="label">Naam klant:</span> {{ $clientName }}</p>
            @endif
            <p><span class="label">Adres:</span> {{ $project->company->street ?? '' }} {{ $project->company->house_number ?? '' }}</p>
            <p><span class="label"></span> {{ $project->company->postal_code ?? '' }} {{ $project->company->city ?? '' }}</p>
            @if($project->company->phone)
            <p><span class="label">Telefoon:</span> {{ $project->company->phone }}</p>
            @endif
        </div>
        <div class="info-box">
            <p><span class="label">Projectnummer:</span> {{ $project->project_number }}</p>
            @if($project->quote)
            <p><span class="label">Offertenummer:</span> {{ $project->quote->quote_number }}</p>
            @endif
            @if($project->start_date)
            <p><span class="label">Startdatum:</span> {{ $project->start_date->format('d-m-Y') }}</p>
            @endif
            <p><span class="label">Opleverdatum:</span> {{ $project->completion_date?->format('d-m-Y') ?? now()->format('d-m-Y') }}</p>
            @if($project->quote?->lead?->full_address)
            <p><span class="label">Werklocatie:</span></p>
            <p style="padding-left: 105px;">{{ $project->quote->lead->full_address }}</p>
            @endif
        </div>
    </div>

    <!-- Items tabel -->
    <div class="items-section">
        <p class="section-title">Overzicht werkzaamheden &amp; materialen</p>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 18%;">Type</th>
                    <th style="width: 42%;">Omschrijving</th>
                    <th style="width: 10%;" class="text-right">Aantal</th>
                    <th style="width: 15%;" class="text-right">Stukprijs</th>
                    <th style="width: 15%;" class="text-right">Totaal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    <td>{{ $item->type instanceof \App\Enums\QuoteItemType ? $item->type->getLabel() : $item->type }}</td>
                    <td>{{ $item->description ?? '—' }}</td>
                    <td class="text-right">{{ rtrim(rtrim(number_format((float)$item->quantity, 2, ',', '.'), '0'), ',') }}</td>
                    <td class="text-right">€&nbsp;{{ number_format((float)$item->unit_price, 2, ',', '.') }}</td>
                    <td class="text-right">€&nbsp;{{ number_format((float)$item->total, 2, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 20px; color: #888; font-style: italic;">Geen items beschikbaar</td>
                </tr>
                @endforelse
            </tbody>
            @if($items->isNotEmpty())
            <tfoot>
                <tr class="totals-row">
                    <td colspan="4" class="text-right">Subtotaal excl. btw</td>
                    <td class="text-right">€&nbsp;{{ number_format($grandTotal, 2, ',', '.') }}</td>
                </tr>
                <tr class="vat-row">
                    <td colspan="4" class="text-right">BTW 21%</td>
                    <td class="text-right">€&nbsp;{{ number_format($vatAmount, 2, ',', '.') }}</td>
                </tr>
                <tr class="total-incl-row">
                    <td colspan="4" class="text-right">Totaal incl. btw</td>
                    <td class="text-right">€&nbsp;{{ number_format($totalIncl, 2, ',', '.') }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    <!-- Opmerkingen -->
    @if($project->notes)
    <div class="notes-section">
        <p class="section-title">Opmerkingen</p>
        <p>{{ $project->notes }}</p>
    </div>
    @endif

    <!-- Foto's -->
    @if($photos->isNotEmpty())
    <div class="photos-section">
        <p class="section-title">Foto's oplevering</p>
        <div class="photos-grid clearfix">
            @foreach($photos as $photo)
                @php
                    $photoPath = $photo->getPath();
                    $photoBase64 = null;
                    if (file_exists($photoPath)) {
                        $mime = $photo->mime_type ?? 'image/jpeg';
                        $photoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($photoPath));
                    }
                @endphp
                @if($photoBase64)
                <div class="photo-item">
                    <img src="{{ $photoBase64 }}" alt="Foto oplevering">
                </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    <!-- Handtekeningen -->
    <div class="signatures-section clearfix">
        <div class="signature-col">
            <h4>Handtekening monteur</h4>
            @if($technicianSignatureBase64)
                <img src="{{ $technicianSignatureBase64 }}" alt="Handtekening monteur">
            @else
                <div class="sig-empty"></div>
            @endif
            @if(!empty($technicianName))
            <p class="sig-meta">Naam: {{ $technicianName }}</p>
            @else
            <p class="sig-meta">Naam: ___________________________</p>
            @endif
            <p class="sig-meta">Datum: {{ now()->format('d-m-Y') }}</p>
        </div>
        <div class="signature-col">
            <h4>Handtekening klant</h4>
            @if($clientSignatureBase64)
                <img src="{{ $clientSignatureBase64 }}" alt="Handtekening klant">
            @else
                <div class="sig-empty"></div>
            @endif
            @if(!empty($clientName))
            <p class="sig-meta">Naam: {{ $clientName }}</p>
            @else
            <p class="sig-meta">Naam: ___________________________</p>
            @endif
            <p class="sig-meta">Datum: {{ $project->completion_date?->format('d-m-Y') ?? now()->format('d-m-Y') }}</p>
        </div>
    </div>

</div><!-- /content -->

<!-- Footer -->
<div class="footer">
    @if($settings->company_kvk_number)
    <p>KvK: {{ $settings->company_kvk_number }}@if($settings->company_vat_number) &nbsp;|&nbsp; BTW: {{ $settings->company_vat_number }}@endif</p>
    @endif
</div>

</body>
</html>
