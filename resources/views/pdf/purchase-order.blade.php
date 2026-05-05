@php
    // ============================================================
    // Meertalige labels: nl / en / de
    // ============================================================
    $lang = $order->language ?? 'nl';

    $labels = [
        'nl' => [
            'title'            => 'INKOOPORDER',
            'supplier'         => 'Leverancier',
            'buyer'            => 'Koper',
            'order_date'       => 'Besteldatum',
            'delivery_date'    => 'Uiterste leverdatum',
            'reference'        => 'Referentie',
            'products_heading' => 'Te leveren producten',
            'qty'              => 'STUK',
            'article'          => 'ARTIKEL',
            'description'      => 'OMSCHRIJVING',
            'unit_price'       => 'EENHEIDSPRIJS',
            'total'            => 'TOTAAL',
            'subtotal'         => 'Totaalbedrag excl. BTW',
            'subtotal_no_vat'  => 'Totaalbedrag',
            'vat'              => 'BTW (21%)',
            'total_incl'       => 'Totaal incl. BTW',
            'notes'            => 'Opmerkingen',
            'delivery_address' => 'Leveringsadres',
            'date_place'       => 'Datum en plaats',
            'buyer_name'       => 'Koper',
        ],
        'en' => [
            'title'            => 'PURCHASE ORDER',
            'supplier'         => 'Supplier',
            'buyer'            => 'Buyer',
            'order_date'       => 'Order date',
            'delivery_date'    => 'Last delivery date',
            'reference'        => 'Reference',
            'products_heading' => 'Products to be delivered',
            'qty'              => 'QTY',
            'article'          => 'ARTICLE',
            'description'      => 'DESCRIPTION',
            'unit_price'       => 'UNIT PRICE',
            'total'            => 'TOTAL',
            'subtotal'         => 'Total amount excl. VAT',
            'subtotal_no_vat'  => 'Total amount',
            'vat'              => 'VAT (21%)',
            'total_incl'       => 'Total incl. VAT',
            'notes'            => 'Remarks',
            'delivery_address' => 'Delivery address',
            'date_place'       => 'Date and place',
            'buyer_name'       => 'Buyer',
        ],
        'de' => [
            'title'            => 'BESTELLUNG',
            'supplier'         => 'Lieferant',
            'buyer'            => 'Kunden',
            'order_date'       => 'Kaufdatum',
            'delivery_date'    => 'Letzter Liefertermin',
            'reference'        => 'Referenz',
            'products_heading' => 'Zu liefernde Produkte',
            'qty'              => 'STÜCK',
            'article'          => 'ARTIKEL',
            'description'      => 'BESCHREIBUNG',
            'unit_price'       => 'EINHEITSPREIS',
            'total'            => 'GESAMT',
            'subtotal'         => 'Gesamtbetrag ohne MwSt.',
            'subtotal_no_vat'  => 'Gesamtbetrag',
            'vat'              => 'MwSt. (21%)',
            'total_incl'       => 'Gesamt inkl. MwSt.',
            'notes'            => 'Anmerkungen',
            'delivery_address' => 'Lieferadresse',
            'date_place'       => 'Datum und Ort',
            'buyer_name'       => 'Käufer',
        ],
    ];

    $t = $labels[$lang] ?? $labels['nl'];

    // ============================================================
    // Achtergrond + logo base64
    // ============================================================
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
            $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png';
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
<html lang="{{ $lang }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $t['title'] }} {{ $order->order_number }}</title>
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
            color: #000;
        }
        .doc-title-bar .doc-meta {
            float: right;
            text-align: right;
            font-size: 8pt;
            line-height: 1.6;
            color: #000;
        }
        .doc-title-bar .doc-meta .doc-number {
            font-size: 11pt;
            font-weight: bold;
        }

        /* Info boxen: leverancier links / koper rechts */
        .info-container {
            margin-bottom: 14px;
            overflow: hidden;
        }
        .info-box {
            float: left;
            width: 48%;
            padding: 10px 12px;
            min-height: 90px;
        }
        .info-box:first-child {
            margin-right: 4%;
        }
        .info-box-label {
            font-size: 8pt;
            font-weight: bold;
            color: #2563a8;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-box p {
            margin: 2px 0;
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

        /* Meta rij: datum, leverdatum, referentie */
        .meta-row {
            margin-bottom: 14px;
            overflow: hidden;
            border-top: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
            padding: 8px 0;
        }
        .meta-item {
            float: left;
            width: 33%;
            padding: 0 8px;
        }
        .meta-item .meta-label {
            font-size: 7.5pt;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
        }
        .meta-item .meta-value {
            font-size: 9pt;
            margin-top: 2px;
        }

        /* Sectie heading */
        .section-heading {
            font-size: 9pt;
            font-weight: bold;
            color: #000;
            margin-bottom: 6px;
            padding-bottom: 3px;
        }

        /* Items tabel */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
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
            vertical-align: top;
        }
        .items-table tbody tr:last-child td {
            border-bottom: none;
        }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .text-bold   { font-weight: bold; }

        /* Totalen */
        .totals-container {
            overflow: hidden;
            margin-top: 0;
            border-top: 2px solid #000;
            padding-top: 8px;
            margin-bottom: 14px;
        }
        .totals-table {
            float: right;
            width: 50%;
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
            color: #fff;
        }

        /* Opmerkingen */
        .notes-section {
            padding: 10px 0;
            margin-bottom: 14px;
        }
        .notes-section h4 {
            font-size: 8.5pt;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .notes-section p {
            font-size: 8pt;
            line-height: 1.5;
        }

        /* Leveringsadres + ondertekening */
        .footer-info-container {
            overflow: hidden;
            margin-top: 14px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
        }
        .footer-info-box {
            float: left;
            width: 48%;
        }
        .footer-info-box:first-child {
            margin-right: 4%;
        }
        .footer-info-box .label {
            font-size: 8pt;
            font-weight: bold;
            color: #2563a8;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .footer-info-box p {
            font-size: 8pt;
            line-height: 1.6;
        }

        /* Page footer */
        .page-footer {
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

<!-- Content -->
<div class="content">

    <!-- Titel balk -->
    <div class="doc-title-bar clearfix">
        <div class="doc-title">{{ $t['title'] }}</div>
        <div class="doc-meta">
            <div class="doc-number">{{ $order->order_number }}</div>
        </div>
    </div>

    <!-- Leverancier / Koper info boxen -->
    <div class="info-container clearfix">
        <div class="info-box">
            <div class="info-box-label">{{ $t['supplier'] }}</div>
            <p><strong>{{ $order->supplier->name }}</strong></p>
            <table class="info-table">
                @if($order->supplier->contact_name)
                <tr><td class="lbl">T.a.v.:</td><td>{{ $order->supplier->contact_name }}</td></tr>
                @endif
                @if($order->supplier->address)
                <tr><td class="lbl">Adres:</td><td>{{ $order->supplier->address }}</td></tr>
                @endif
                @if($order->supplier->postal_code || $order->supplier->city)
                <tr><td class="lbl"></td><td>{{ trim(($order->supplier->postal_code ?? '') . ' ' . ($order->supplier->city ?? '')) }}</td></tr>
                @endif
                @if($order->supplier->country)
                <tr><td class="lbl">Land:</td><td>{{ $order->supplier->country }}</td></tr>
                @endif
            </table>
        </div>

        <div class="info-box">
            <div class="info-box-label">{{ $t['buyer'] }}</div>
            <p><strong>{{ $settings->company_name }}</strong></p>
            <table class="info-table">
                @if($settings->company_address)
                <tr><td class="lbl">Adres:</td><td>{{ $settings->company_address }}</td></tr>
                @endif
                @if($settings->company_postal_code || $settings->company_city)
                <tr><td class="lbl"></td><td>{{ trim(($settings->company_postal_code ?? '') . ' ' . ($settings->company_city ?? '')) }}</td></tr>
                @endif
                <tr><td class="lbl">Land:</td><td>Nederland</td></tr>
            </table>
        </div>
    </div>

    <!-- Meta: datum / leverdatum / referentie -->
    <div class="meta-row clearfix">
        <div class="meta-item">
            <div class="meta-label">{{ $t['order_date'] }}</div>
            <div class="meta-value">{{ $order->order_date->format('d-m-Y') }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">{{ $t['delivery_date'] }}</div>
            <div class="meta-value">{{ $order->expected_delivery_date ? $order->expected_delivery_date->format('d-m-Y') : '—' }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">{{ $t['reference'] }}</div>
            <div class="meta-value">{{ $order->reference ?: '—' }}</div>
        </div>
    </div>

    <!-- Producten -->
    <div class="section-heading">{{ $t['products_heading'] }}</div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 8%;" class="text-center">{{ $t['qty'] }}</th>
                <th style="width: 15%;">{{ $t['article'] }}</th>
                <th style="width: 42%;">{{ $t['description'] }}</th>
                <th style="width: 17%;" class="text-right">{{ $t['unit_price'] }}</th>
                <th style="width: 18%;" class="text-right">{{ $t['total'] }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td class="text-center">{{ rtrim(rtrim(number_format((float)$item->quantity, 2, ',', '.'), '0'), ',') }}</td>
                <td>{{ $item->supplier_article_number ?? '—' }}</td>
                <td>{{ $item->description }}</td>
                <td class="text-right">€&nbsp;{{ number_format((float)$item->unit_price, 2, ',', '.') }}</td>
                <td class="text-right">€&nbsp;{{ number_format((float)$item->total, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totalen -->
    <div class="totals-container clearfix">
        <div class="totals-table">
            <table>
                @if($order->vat_amount > 0)
                <tr>
                    <td>{{ $t['subtotal'] }}</td>
                    <td class="text-right">€&nbsp;{{ number_format((float)$order->subtotal, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>{{ $t['vat'] }}</td>
                    <td class="text-right">€&nbsp;{{ number_format((float)$order->vat_amount, 2, ',', '.') }}</td>
                </tr>
                <tr class="total-final">
                    <td>{{ $t['total_incl'] }}</td>
                    <td class="text-right">€&nbsp;{{ number_format((float)$order->total, 2, ',', '.') }}</td>
                </tr>
                @else
                <tr class="total-final">
                    <td>{{ $t['subtotal_no_vat'] }}</td>
                    <td class="text-right">€&nbsp;{{ number_format((float)$order->total, 2, ',', '.') }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    @if($order->notes)
    <!-- Opmerkingen -->
    <div class="notes-section">
        <h4>{{ $t['notes'] }}</h4>
        <p>{{ $order->notes }}</p>
    </div>
    @endif

    <!-- Leveringsadres + ondertekening -->
    <div class="footer-info-container clearfix">
        @php
            $hasDeliveryAddress = $order->delivery_name || $order->delivery_street || $order->delivery_city;
        @endphp
        @if($hasDeliveryAddress)
        <div class="footer-info-box">
            <div class="label">{{ $t['delivery_address'] }}</div>
            @if($order->delivery_name)  <p><strong>{{ $order->delivery_name }}</strong></p> @endif
            @if($order->delivery_street) <p>{{ $order->delivery_street }}</p> @endif
            @if($order->delivery_postal_code || $order->delivery_city)
            <p>{{ trim(($order->delivery_postal_code ?? '') . ' ' . ($order->delivery_city ?? '')) }}</p>
            @endif
            @if($order->delivery_country) <p>{{ $order->delivery_country }}</p> @endif
        </div>
        @endif

        <div class="footer-info-box">
            <p style="margin-bottom: 8px;">
                <strong>{{ $t['date_place'] }}:</strong>
                {{ $order->order_date->format('d-m-Y') }}, {{ $settings->company_city ?? '' }}
            </p>
            <p>
                <strong>{{ $t['buyer_name'] }}:</strong>
                {{ $settings->company_name ?? '' }}
            </p>
        </div>
    </div>

</div><!-- /content -->

<!-- Page footer -->
<div class="page-footer">
    @php
        $footerParts = [];
        if ($settings->company_kvk_number)  $footerParts[] = 'KvK: '  . $settings->company_kvk_number;
        if ($settings->company_vat_number)   $footerParts[] = 'BTW: '  . $settings->company_vat_number;
        if ($settings->company_bank_account) $footerParts[] = 'IBAN: ' . $settings->company_bank_account;
    @endphp
    @if(count($footerParts))
    <p>{!! implode(' &nbsp;|&nbsp; ', $footerParts) !!}</p>
    @endif
</div>

</body>
</html>
