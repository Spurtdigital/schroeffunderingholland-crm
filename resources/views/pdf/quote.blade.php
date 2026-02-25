@php
    // Achtergrond base64
    $bgPath = public_path('images/achtergrond-pdf.jpg');
    $bgBase64 = '';
    if (file_exists($bgPath)) {
        $bgBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($bgPath));
    }

    // Logo base64 — uit settings of uit public/images/logo.jpg
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
    <title>Offerte {{ $quote->quote_number }}</title>
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

        /* Offerte titel balk */
        .doc-title-bar {
            background: linear-gradient(to right, #2563a8 0%, #4a8fd9 100%);
            color: white;
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
            color: #000;
        }

        /* Items tabel */
        .items-section {
            margin-bottom: 14px;
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
            border-top: 2px solid #000;
            border-bottom: 1px solid #000;
            font-size: 8pt;
        }
        .items-table td {
            padding: 9px 8px;
            border-bottom: 1px solid #ccc;
            font-size: 8pt;
        }
        .items-table tbody tr:last-child td {
            border-bottom: none;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }

        /* Totalen */
        .totals-container {
            overflow: hidden;
            margin-bottom: 14px;
            border-top: 2px solid #000;
            padding-top: 8px;
        }
        .totals-table {
            float: right;
            width: 55%;
        }
        .totals-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 4px 8px;
            font-size: 8.5pt;
        }
        .totals-table .total-final {
            background: linear-gradient(to right, #2563a8 0%, #4a8fd9 100%);
            color: white;
            font-weight: bold;
            font-size: 10pt;
        }
        .totals-table .total-final td {
            padding: 7px 8px;
        }

        /* Opmerkingen */
        .notes-section {
            padding: 10px 0;
            margin-bottom: 14px;
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

        /* Ondertekening */
        .signature-section {
            margin-top: 20px;
            overflow: hidden;
        }
        .signature-box {
            float: left;
            width: 45%;
            border-top: 1px solid #999;
            padding-top: 6px;
            font-size: 7.5pt;
            color: #555;
        }
        .signature-box:last-child {
            float: right;
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

    <!-- Offerte titel balk -->
    <div class="doc-title-bar clearfix">
        <div class="doc-title">OFFERTE</div>
        <div class="doc-meta">
            <div class="doc-number">{{ $quote->quote_number }}</div>
            <div>{{ $quote->created_at->format('d-m-Y') }}</div>
            @if($quote->valid_until)
            <div>Geldig tot: {{ $quote->valid_until->format('d-m-Y') }}</div>
            @endif
        </div>
    </div>

    <!-- Info Boxen: klantgegevens + projectdetails -->
    <div class="info-container clearfix">
        <div class="info-box">
            <p><strong>{{ $quote->company->name }}</strong></p>
            @if($quote->company->contact_person)
            <p><span class="label">T.a.v.:</span> {{ $quote->company->contact_person }}</p>
            @endif
            @if($quote->company->street)
            <p><span class="label">Adres:</span> {{ trim(($quote->company->street ?? '') . ' ' . ($quote->company->house_number ?? '')) }}</p>
            @endif
            @if($quote->company->postal_code || $quote->company->city)
            <p><span class="label"></span> {{ trim(($quote->company->postal_code ?? '') . ' ' . ($quote->company->city ?? '')) }}</p>
            @endif
            @if($quote->company->phone)
            <p><span class="label">Telefoon:</span> {{ $quote->company->phone }}</p>
            @endif
            @if($quote->company->email)
            <p><span class="label">E-mail:</span> {{ $quote->company->email }}</p>
            @endif
        </div>

        <div class="info-box">
            @if($quote->lead?->project_description)
            <p><span class="label">Project:</span> {{ $quote->lead->project_description }}</p>
            @endif
            @php
                $werklocatie = $quote->workFullAddress();
            @endphp
            @if($werklocatie)
            <p><span class="label">Werklocatie:</span></p>
            <p style="padding-left: 105px;">{{ $werklocatie }}</p>
            @endif
        </div>
    </div>

    <!-- Items Tabel -->
    @if($quote->show_item_breakdown)
    <div class="items-section">
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Omschrijving</th>
                    <th style="width: 15%;" class="text-center">Aantal</th>
                    <th style="width: 20%;" class="text-right">Prijs p/st (excl. BTW)</th>
                    <th style="width: 15%;" class="text-right">Totaal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quote->items as $item)
                @php $isDiscount = $item->type instanceof \App\Enums\QuoteItemType && $item->type === \App\Enums\QuoteItemType::Discount; @endphp
                <tr @if($isDiscount) style="color: #c0392b;" @endif>
                    <td>{{ $item->description }}{{ $item->pile_length ? ' (lengte: ' . rtrim(rtrim(number_format((float)$item->pile_length, 2, ',', ''), '0'), ',') . ' m)' : '' }}</td>
                    <td class="text-center">{{ $isDiscount ? '—' : number_format($item->quantity, 0, ',', '.') }}</td>
                    <td class="text-right">{{ $isDiscount ? '—' : '€ ' . number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td class="text-right">
                        @if($isDiscount)
                            &minus;&nbsp;&euro;&nbsp;{{ number_format(abs($item->total), 2, ',', '.') }}
                        @else
                            &euro;&nbsp;{{ number_format($item->quantity * $item->unit_price, 2, ',', '.') }}
                        @endif
                    </td>
                </tr>
                @endforeach
                @if($quote->travel_distance && $quote->travel_total)
                <tr>
                    <td>Reiskosten ({{ number_format($quote->travel_distance, 0, ',', '.') }} km &times; &euro;&nbsp;{{ number_format($quote->travel_cost_per_km, 2, ',', '.') }})</td>
                    <td class="text-center">1</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($quote->travel_total, 2, ',', '.') }}</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($quote->travel_total, 2, ',', '.') }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    @endif

    <!-- Totalen -->
    <div class="totals-container clearfix">
        <div class="totals-table">
            <table>
                @if($quote->show_item_breakdown)
                <tr>
                    <td>Subtotaal producten:</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($quote->items->sum('total'), 2, ',', '.') }}</td>
                </tr>
                @if($quote->travel_total)
                <tr>
                    <td>Reiskosten:</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($quote->travel_total, 2, ',', '.') }}</td>
                </tr>
                @endif
                <tr>
                    <td><strong>Subtotaal excl. BTW:</strong></td>
                    <td class="text-right"><strong>&euro;&nbsp;{{ number_format($quote->items->sum('total') + ($quote->travel_total ?? 0), 2, ',', '.') }}</strong></td>
                </tr>
                <tr>
                    <td>BTW (21%):</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($quote->vat_amount, 2, ',', '.') }}</td>
                </tr>
                @endif
                <tr class="total-final">
                    <td>Totaal incl. BTW:</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($quote->total, 2, ',', '.') }}</td>
                </tr>
            </table>
        </div>
    </div>

    @if($quote->notes)
    <!-- Opmerkingen -->
    <div class="notes-section">
        <h4>Opmerkingen</h4>
        <p>{{ $quote->notes }}</p>
    </div>
    @endif

    <!-- Ondertekening -->
    <div class="signature-section clearfix">
        <div class="signature-box">
            <p>Handtekening opdrachtgever</p>
            <br><br><br>
            <p>Naam: ___________________________</p>
            <p style="margin-top:4px;">Datum: ___________________________</p>
        </div>
        <div class="signature-box">
            <p>Voor akkoord {{ $settings->company_name ?? 'Schroeffundering Holland' }}</p>
            <br><br><br>
            <p>Naam: ___________________________</p>
            <p style="margin-top:4px;">Datum: ___________________________</p>
        </div>
    </div>

</div><!-- /content -->

<!-- Footer -->
<div class="footer">
    @php
        $footerParts = [];
        if ($settings->company_kvk_number) $footerParts[] = 'KvK: ' . $settings->company_kvk_number;
        if ($settings->company_vat_number)  $footerParts[] = 'BTW: ' . $settings->company_vat_number;
        if ($settings->company_bank_account) $footerParts[] = 'IBAN: ' . $settings->company_bank_account;
    @endphp
    @if(count($footerParts))
    <p>{!! implode(' &nbsp;|&nbsp; ', $footerParts) !!}</p>
    @endif
</div>

</body>
</html>
