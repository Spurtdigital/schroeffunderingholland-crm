@php
    $mdBold = fn(string $text): string => preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);

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
    <title>Opdrachtbevestiging {{ $quote->displayNumber() ?? $project->project_number }}</title>
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
        @@page {
            margin: 0;
            size: A4 portrait;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        html, body {
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Montserrat', sans-serif;
            font-size: 8pt;
            color: #000;
            line-height: 1.3;
            padding-top: 15mm;
        }

        /* Achtergrond via position:fixed — herhaalt op elke pagina, vult volledig papier */
        .page-bg-fixed {
            position: fixed;
            top: 0;
            left: 0;
            width: 210mm;
            height: 297mm;
            background-image: url("{{ $bgBase64 }}");
            background-size: 210mm 297mm;
            background-repeat: no-repeat;
            z-index: -1;
        }

        /* ===================== HEADER ===================== */
        .header {
            margin-top: -15mm; /* Compenseer body padding-top op eerste pagina */
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

        /* ===================== CONTENT ===================== */
        .content {
            padding: 0 15mm 10mm 20mm;
        }

        /* Titel balk */
        .doc-title-bar {
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

        /* Aanhef */
        .salutation {
            margin-bottom: 12px;
            font-size: 8pt;
        }

        /* Items tabel */
        .items-section {
            margin-bottom: 14px;
        }
        .items-section.page-break {
            page-break-before: always;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        .items-table + .items-table {
            margin-top: 0;
        }
        .items-table + .items-table th {
            border-top: none;
        }
        /* Tabelrijen bij elkaar houden */
        .items-table tr {
            page-break-inside: avoid;
        }
        /* Minimaal 3 rijen op een pagina voor page-break */
        .items-table tbody {
            orphans: 3;
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
            background: none;
            color: #000;
            font-weight: bold;
            font-size: 10pt;
            border-top: 2px solid #000;
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

        /* Blokken bij elkaar houden op pagina */
        .doc-title-bar,
        .info-container,
        .totals-container,
        .notes-section,
        .pre-text-section,
        .payment-section,
        .payment-term-section {
            page-break-inside: avoid;
        }

        /* Aannames mogen over pagina's lopen, maar elke regel blijft intact */
        .assumptions-section li {
            page-break-inside: avoid;
        }

        /* Slottekst: bewust GEEN page-break-inside op het geheel. Het blok is
           te lang voor één pagina; zou je het bij elkaar willen houden, dan
           schuift het in zijn geheel door en laat het een lege pagina achter.
           De losse kopje-met-tekst groepen hieronder blijven wel intact. */
        .closing-section {
            margin-top: 14px;
        }

        /* Aannames bullet-lijst */
        .assumptions-section {
            margin-bottom: 14px;
        }
        .assumptions-section ul {
            margin: 0;
            padding-left: 16px;
        }
        .assumptions-section ul li {
            font-size: 8pt;
            line-height: 1.6;
            color: #000;
        }

        /* Inleidende tekst */
        .pre-text-section p {
            margin-bottom: 14px;
            font-size: 8pt;
            line-height: 1.5;
        }

        /* Betalingsinfo */
        .payment-section {
            margin-top: 20px;
            padding: 10px 12px;
            border-top: 1px solid #ccc;
            font-size: 8pt;
        }

        /* Betalingsvoorwaarden */
        .payment-term-section {
            margin-top: 16px;
            font-size: 8.5pt;
            line-height: 1.5;
            color: #333;
        }
        .payment-term-section p {
            margin-bottom: 2px;
        }
        .payment-term-section .term-title {
            font-weight: bold;
        }
        .payment-term-section .term-heading {
            font-weight: bold;
            margin-bottom: 4px;
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

<div class="page-bg-fixed"></div>

<!-- Header: logo links, bedrijfs-NAW rechts boven -->
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

<!-- Content wrapper -->
<div class="content">

    <!-- Opdrachtbevestiging titel balk -->
    <div class="doc-title-bar clearfix">
        <div class="doc-title">OPDRACHTBEVESTIGING</div>
        <div class="doc-meta">
            @if($quote && !str_starts_with($quote->quote_number ?? '', 'CONCEPT-'))
            <div class="doc-number">{{ $quote->quote_number }}</div>
            @endif
            <div>{{ ($quote?->created_at ?? now())->format('d-m-Y') }}</div>
        </div>
    </div>

    <!-- Info Boxen: klantgegevens + projectdetails -->
    <div class="info-container clearfix">
        <div class="info-box">
            <p><strong>{{ $company->name }}</strong></p>
            <table class="info-table">
                @if($company->contact_person)
                <tr><td class="lbl">T.a.v.:</td><td>{{ $company->contact_person }}</td></tr>
                @endif
                @if($company->street)
                <tr><td class="lbl">Adres:</td><td>{{ trim(($company->street ?? '') . ' ' . ($company->house_number ?? '')) }}</td></tr>
                <tr><td class="lbl"></td><td>{{ trim(($company->postal_code ?? '') . ' ' . ($company->city ?? '')) }}</td></tr>
                @endif
                @if($company->phone)
                <tr><td class="lbl">Telefoon:</td><td>{{ $company->phone }}</td></tr>
                @endif
                @if($company->email)
                <tr><td class="lbl">E-mail:</td><td>{{ $company->email }}</td></tr>
                @endif
            </table>
        </div>

        <div class="info-box">
            <table class="info-table">
                <tr><td class="lbl">Projectnummer:</td><td>{{ $project->project_number }}</td></tr>
                @if($quote && !str_starts_with($quote->quote_number ?? '', 'CONCEPT-'))
                <tr><td class="lbl">Offertenummer:</td><td>{{ $quote->quote_number }}</td></tr>
                @endif
                @if($project->start_date)
                <tr><td class="lbl">Montagedatum:</td><td>{{ $project->start_date->format('d-m-Y') }}</td></tr>
                @endif
                @if($quote?->machine)
                <tr><td class="lbl">Machine:</td><td>{{ $quote->machine->name }}</td></tr>
                @endif
                @php $werklocatie = $quote?->workAddressLines(); @endphp
                @if($werklocatie)
                <tr><td class="lbl">Werklocatie:</td><td>{!! nl2br(e(implode("\n", $werklocatie))) !!}</td></tr>
                @elseif($quote?->lead?->project_description)
                <tr><td class="lbl">Project:</td><td>{{ $quote->lead->project_description }}</td></tr>
                @endif
            </table>
        </div>
    </div>

    <!-- Aanhef -->
    @php $contactPersoon = $company->contact_person ?? null; @endphp
    @if($contactPersoon)
    <div class="salutation">
        <p>Beste {{ $contactPersoon }},</p>
    </div>
    @endif

    <!-- Inleidende tekst (pre_text) -->
    @if($quote && $quote->pre_text)
    <div class="pre-text-section">
        <p>{!! nl2br($mdBold(e($quote->pre_text))) !!}</p>
    </div>
    @endif

    <!-- Voorwaarden (aannames van de offerte) -->
    @if($quote && $quote->show_assumptions !== false && $quote->assumptions->isNotEmpty())
    <div class="assumptions-section">
        <ul>
            @foreach($quote->assumptions->sortBy('sort_order') as $assumption)
            <li>{{ $assumption->resolveContent($assumption->pivot->pivot_value) }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Items Tabel -->
    @if($quote && $quote->items->count())
    @php
        $hasContentBeforeItems = ($quote->pre_text) || ($quote->show_assumptions !== false && $quote->assumptions->isNotEmpty());
        $filteredItems = $quote->items->filter(fn($i) => !($i->type === \App\Enums\QuoteItemType::Discount || $i->type === \App\Enums\QuoteItemType::Adjustment));
        $totalItems = $filteredItems->count();
        $firstChunkSize = max(3, (int) ceil($totalItems * 0.5));
        $firstChunk = $filteredItems->take($firstChunkSize);
        $remainingItems = $filteredItems->skip($firstChunkSize);
    @endphp
    <div class="items-section{{ $hasContentBeforeItems ? ' page-break' : '' }}">
        {{-- Wrapper tabel voor header + eerste 50% items - blijft samen --}}
        <table class="items-table" style="page-break-inside: avoid;">
            <thead>
                <tr>
                    <th style="width: {{ $quote->show_item_breakdown ? '50%' : '75%' }};">Omschrijving</th>
                    <th style="width: 15%;" class="text-center">Aantal</th>
                    @if($quote->show_item_breakdown)
                    <th style="width: 20%;" class="text-right">Prijs p/st (excl. BTW)</th>
                    <th style="width: 15%;" class="text-right">Totaal</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($firstChunk as $item)
                <tr>
                    <td>{{ str_replace('⌀', 'Ø', $item->description) }}{{ $item->pile_length ? ' (lengte: ' . rtrim(rtrim(number_format((float)$item->pile_length, 2, ',', ''), '0'), ',') . ' m)' : '' }}{{ $item->machine ? ' — machine: ' . $item->machine->name : '' }}</td>
                    <td class="text-center">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                    @if($quote->show_item_breakdown)
                    <td class="text-right">&euro;&nbsp;{{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($item->total, 2, ',', '.') }}</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
        {{-- Overige items in aparte tabellen --}}
        @foreach($remainingItems->chunk(5) as $chunk)
        <table class="items-table" style="page-break-inside: avoid; border-top: none;">
            <tbody>
                @foreach($chunk as $item)
                <tr>
                    <td style="width: {{ $quote->show_item_breakdown ? '50%' : '75%' }};">{{ str_replace('⌀', 'Ø', $item->description) }}{{ $item->pile_length ? ' (lengte: ' . rtrim(rtrim(number_format((float)$item->pile_length, 2, ',', ''), '0'), ',') . ' m)' : '' }}{{ $item->machine ? ' — machine: ' . $item->machine->name : '' }}</td>
                    <td style="width: 15%;" class="text-center">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                    @if($quote->show_item_breakdown)
                    <td style="width: 20%;" class="text-right">&euro;&nbsp;{{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td style="width: 15%;" class="text-right">&euro;&nbsp;{{ number_format($item->total, 2, ',', '.') }}</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
        @endforeach
        {{-- Voorrijkosten als aparte tabel --}}
        @if($quote->travel_total)
        <table class="items-table" style="border-top: none;">
            <tbody>
                <tr>
                    <td style="width: {{ $quote->show_item_breakdown ? '50%' : '75%' }};">Voorrijkosten</td>
                    <td style="width: 15%;" class="text-center">1</td>
                    @if($quote->show_item_breakdown)
                    <td style="width: 20%;" class="text-right">&euro;&nbsp;{{ number_format($quote->travel_total, 2, ',', '.') }}</td>
                    <td style="width: 15%;" class="text-right">&euro;&nbsp;{{ number_format($quote->travel_total, 2, ',', '.') }}</td>
                    @endif
                </tr>
            </tbody>
        </table>
        @endif
    </div>
    @endif

    <!-- Totalen -->
    @if($quote)
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
                    <td>Voorrijkosten:</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($quote->travel_total, 2, ',', '.') }}</td>
                </tr>
                @endif
                @endif
                <tr>
                    <td><strong>Subtotaal excl. BTW:</strong></td>
                    <td class="text-right"><strong>&euro;&nbsp;{{ number_format($quote->subtotal, 2, ',', '.') }}</strong></td>
                </tr>
                <tr>
                    <td>BTW (21%):</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($quote->vat_amount, 2, ',', '.') }}</td>
                </tr>
                <tr class="total-final">
                    <td>Totaal incl. BTW:</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($quote->total, 2, ',', '.') }}</td>
                </tr>
            </table>
        </div>
    </div>
    @endif

    @if($quote?->notes)
    <!-- Opmerkingen -->
    <div class="notes-section">
        <h4>Opmerkingen</h4>
        <p>{{ $quote->notes }}</p>
    </div>
    @endif

    <!-- Slottekst: de eisen aan de klant (stroom, vrij van obstakels, Klic,
         wachttijd). Zelfde bron en opmaak als op de offerte. Bewust niet
         gekoppeld aan show_assumptions: dit is het document dat de klant
         tekent, dus deze voorwaarden horen er altijd op te staan. -->
    @if($settings->closing_text)
    <div class="closing-section">
        @php
            // Split closing text op secties die beginnen met **titel**
            $closingText = trim($settings->closing_text);
            $sections = preg_split('/\n{2,}/', $closingText);

            // Groepeer secties: als een sectie begint met ** is het een nieuwe groep
            $groupedSections = [];
            $currentGroup = '';

            foreach ($sections as $section) {
                $section = trim($section);
                if (empty($section)) {
                    continue;
                }

                if (preg_match('/^\*\*[^*]+\*\*/', $section)) {
                    if (! empty($currentGroup)) {
                        $groupedSections[] = $currentGroup;
                    }
                    $currentGroup = $section;
                } else {
                    if (! empty($currentGroup)) {
                        $currentGroup .= "\n\n".$section;
                    } else {
                        $currentGroup = $section;
                    }
                }
            }
            if (! empty($currentGroup)) {
                $groupedSections[] = $currentGroup;
            }
        @endphp
        @foreach($groupedSections as $section)
        <table style="width: 100%; margin-bottom: 10px;"><tbody style="page-break-inside: avoid;"><tr><td style="padding: 0;">
            <p style="font-size: 8pt; line-height: 1.5; margin: 0;">{!! nl2br($mdBold(e(trim($section)))) !!}</p>
        </td></tr></tbody></table>
        @endforeach
    </div>
    @endif

    <!-- Betalingsinformatie -->
    @if($settings->company_bank_account)
    <div class="payment-section">
        <p><strong>Betaling:</strong> {{ $settings->company_bank_account }}
        @if($settings->company_name) t.n.v. {{ $settings->company_name }}@endif</p>
    </div>
    @endif

    <!-- Betalingsvoorwaarden -->
    @if($quote && $quote->paymentTerm)
    <div class="payment-term-section">
        <p class="term-heading">Betalingsvoorwaarden</p>
        <p class="term-title">{{ $quote->paymentTerm->title }}</p>
        <p>{{ $quote->paymentTerm->content }}</p>
    </div>
    @endif

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
