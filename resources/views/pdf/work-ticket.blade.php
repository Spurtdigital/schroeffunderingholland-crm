@php
    $bgPath = public_path('images/achtergrond-pdf.jpg');
    $bgBase64 = '';
    if (file_exists($bgPath)) {
        $bgBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($bgPath));
    }

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
    <title>Werkbon {{ $project->project_number }}</title>
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
            font-size: 8pt;
            color: #000;
            line-height: 1.3;
            background-color: #fff;
            background-image: url("{{ $bgBase64 }}");
            background-size: 100% 100%;
            background-repeat: no-repeat;
        }

        .header {
            padding: 8.5mm 15mm 8mm 20mm;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
            padding: 0;
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
            text-align: right;
        }
        .header-naw p {
            font-size: 7.5pt;
            color: #444;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }

        .content {
            padding: 0 15mm 10mm 20mm;
        }

        .doc-title-bar {
            background: none;
            color: #000;
            padding: 0 0 14px 0;
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
        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
        }
        .info-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .info-table td.lbl {
            font-weight: bold;
            white-space: nowrap;
            width: 110px;
            padding-right: 6px;
        }

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
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }

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

        .footer {
            padding: 7px 15mm 0 15mm;
            text-align: center;
            font-size: 6.5pt;
            color: #666;
        }
    </style>
</head>
<body>

<div class="header">
    <table class="header-table">
        <tr>
            <td class="header-logo">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="{{ $settings->company_name ?? 'Logo' }}">
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
</div>

<div class="content">

    <div class="doc-title-bar clearfix">
        <div class="doc-title">WERKBON</div>
        <div class="doc-meta">
            <div class="doc-number">{{ $project->project_number }}</div>
            <div>{{ now()->format('d-m-Y') }} &nbsp; {{ now()->format('H:i') }} uur</div>
        </div>
    </div>

    <div class="info-container clearfix">
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
                @php $werklocatie = $project->quote?->workAddressLines(); @endphp
                @if($werklocatie)
                <tr><td class="lbl">Werklocatie:</td><td>{!! nl2br(e(implode("\n", $werklocatie))) !!}</td></tr>
                @endif
            </table>
        </div>
    </div>

    <div class="materials-section">
        <h3>Te leveren en monteren producten</h3>
        @php
            $allProductItems = $project->items->filter(fn ($item) => $item->itemable_type === 'App\\Models\\Product' && $item->itemable);
        @endphp
        <table class="materials-table">
            <thead>
                <tr>
                    <th style="width: 8%;">Aantal</th>
                    <th style="width: 22%;">Artikelnummer</th>
                    <th style="width: 55%;">Omschrijving</th>
                    <th style="width: 15%;" class="text-center">Geleverd / Gereed</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allProductItems as $item)
                    <tr @if($item->is_optional) style="color: #888; font-style: italic;" @endif>
                        <td class="text-bold">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                        <td>{{ $item->itemable->sku ?? '-' }}</td>
                        <td>{{ str_replace('⌀', 'Ø', $item->itemable->name ?? $item->description) }}@if($item->is_optional) <em style="font-size:8pt;">(optioneel)</em>@endif</td>
                        <td class="text-center">&nbsp;</td>
                    </tr>
                @endforeach
                @if($allProductItems->isEmpty())
                <tr>
                    <td colspan="4" class="text-center" style="padding: 25px;">Geen materialen beschikbaar</td>
                </tr>
                @endif
            </tbody>
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

</div>

<div class="footer">
    @if($settings->company_kvk_number)
    <p>KvK: {{ $settings->company_kvk_number }}@if($settings->company_vat_number) &nbsp;|&nbsp; BTW: {{ $settings->company_vat_number }}@endif</p>
    @endif
</div>

</body>
</html>
