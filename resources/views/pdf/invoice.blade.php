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

    // Bereken totalen — altijd vanuit de factuur zelf (niet de offerte)
    $quote = $invoice->project->quote ?? null;
    $subtotal = $invoice->subtotal;
    $vatAmount = $invoice->vat_amount;
    $total = $invoice->total;
    $isDeposit = $invoice->invoice_type === \App\Enums\InvoiceType::Deposit;
    $isFinal   = $invoice->invoice_type === \App\Enums\InvoiceType::Final;
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Factuur {{ $invoice->invoice_number }}</title>
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

        /* Factuur titel balk */
        .doc-title-bar {
            color: #000;
            padding: 10px 0;
            margin-bottom: 14px;
            overflow: hidden;
            border-bottom: 2px solid #000;
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
            color: #000;
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
            background-color: #2563a8;
            color: #fff;
            font-weight: bold;
            font-size: 10pt;
        }
        .totals-table .total-final td {
            padding: 7px 8px;
            color: #fff;
        }

        /* Betaalgegevens */
        .payment-section {
            clear: both;
            margin-top: 6px;
            padding: 10px 12px;
            border-left: 3px solid #2563a8;
            margin-bottom: 14px;
        }
        .payment-section h4 {
            font-size: 8.5pt;
            font-weight: bold;
            color: #2563a8;
            margin-bottom: 6px;
        }
        .payment-section p {
            font-size: 8pt;
            margin: 3px 0;
            line-height: 1.5;
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

    <!-- Factuur titel balk -->
    <div class="doc-title-bar clearfix">
        <div class="doc-title">FACTUUR</div>
        <div class="doc-meta">
            <div class="doc-number">{{ $invoice->invoice_number }}</div>
            <div>Factuurdatum: {{ $invoice->invoice_date->format('d-m-Y') }}</div>
            @if($invoice->due_date)
            <div>Vervaldatum: {{ $invoice->due_date->format('d-m-Y') }}</div>
            @endif
        </div>
    </div>

    <!-- Info Boxen: factuuradres + projectdetails -->
    <div class="info-container clearfix">
        <div class="info-box">
            <p><strong>{{ $invoice->project->company->name }}</strong></p>
            @if($invoice->project->company->contact_person)
            <p><span class="label">T.a.v.:</span> {{ $invoice->project->company->contact_person }}</p>
            @endif
            @if($invoice->project->company->street)
            <p><span class="label">Adres:</span> {{ trim(($invoice->project->company->street ?? '') . ' ' . ($invoice->project->company->house_number ?? '')) }}</p>
            <p><span class="label"></span> {{ trim(($invoice->project->company->postal_code ?? '') . ' ' . ($invoice->project->company->city ?? '')) }}</p>
            @endif
            @if($invoice->project->company->email)
            <p><span class="label">E-mail:</span> {{ $invoice->project->company->email }}</p>
            @endif
        </div>

        <div class="info-box">
            <p><span class="label">Project:</span> {{ $invoice->project->name }}</p>
            <p><span class="label">Projectnummer:</span> {{ $invoice->project->project_number }}</p>
            @if($quote && $quote->displayNumber())
            <p><span class="label">Offertenummer:</span> {{ $quote->displayNumber() }}</p>
            @endif
            <p><span class="label">Type factuur:</span> {{ $invoice->invoice_type->getLabel() }}</p>
        </div>
    </div>

    <!-- Items Tabel -->
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
                @php
                    $orderRef = ($quote?->displayNumber()) ?? $invoice->project->project_number;
                    if ($isDeposit) {
                        $lineDesc = "Aanbetaling offerte {$orderRef}";
                    } elseif ($isFinal) {
                        $lineDesc = "Eindfactuur order {$orderRef}";
                    } else {
                        $lineDesc = "Opdracht {$orderRef}";
                    }

                    // Percentage toevoegen bij aanbetaling
                    if ($isDeposit && $invoice->deposit_percentage && $invoice->deposit_percentage != 100) {
                        $lineDesc .= " ({$invoice->deposit_percentage}%)";
                    }
                @endphp
                <tr>
                    <td>{{ $lineDesc }}</td>
                    <td class="text-center">1</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($subtotal, 2, ',', '.') }}</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($subtotal, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Totalen -->
    <div class="totals-container clearfix">
        <div class="totals-table">
            <table>
                @if($isDeposit && $quote && $quote->total)
                <tr>
                    <td>Totaal orderbedrag incl. BTW:</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($quote->total, 2, ',', '.') }}</td>
                </tr>
                @if($invoice->deposit_percentage && $invoice->deposit_percentage != 100)
                <tr>
                    <td>Aanbetaling ({{ number_format($invoice->deposit_percentage, 0) }}%):</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($total, 2, ',', '.') }}</td>
                </tr>
                @else
                <tr>
                    <td>Subtotaal excl. BTW:</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($subtotal, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>BTW (21%):</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($vatAmount, 2, ',', '.') }}</td>
                </tr>
                @endif
                @else
                <tr>
                    <td>Subtotaal excl. BTW:</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($subtotal, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>BTW (21%):</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($vatAmount, 2, ',', '.') }}</td>
                </tr>
                @endif
                <tr class="total-final">
                    <td>{{ $isDeposit ? 'Aanbetalingsbedrag incl. BTW:' : 'Totaal incl. BTW:' }}</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($total, 2, ',', '.') }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Betaalgegevens -->
    <div class="payment-section">
        <h4>Betalingsinformatie</h4>
        @if($settings->company_bank_account)
        <p><strong>IBAN:</strong> {{ $settings->company_bank_account }}</p>
        @endif
        @php
            $kenmerkRef = ($quote?->displayNumber()) ?? $invoice->project->project_number;
            $kenmerk = match ($invoice->invoice_type) {
                \App\Enums\InvoiceType::Deposit => "Aanbetaling order {$kenmerkRef}",
                \App\Enums\InvoiceType::Final => "Order {$kenmerkRef} | eindfactuur",
                \App\Enums\InvoiceType::Single => "Order {$kenmerkRef}",
            };
        @endphp
        <p><strong>Kenmerk:</strong> {{ $kenmerk }}</p>
        <p><strong>Factuurnummer:</strong> {{ $invoice->invoice_number }}</p>
        <p><strong>Betaaltermijn:</strong>
            @if($invoice->due_date)
                {{ $invoice->due_date->diffInDays($invoice->invoice_date) }} dagen (voor {{ $invoice->due_date->format('d-m-Y') }})
            @else
                Direct
            @endif
        </p>
        <p style="margin-top: 6px;">Gelieve &euro;&nbsp;{{ number_format($total, 2, ',', '.') }} over te maken o.v.v. kenmerk <strong>{{ $kenmerk }}</strong>.</p>
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
