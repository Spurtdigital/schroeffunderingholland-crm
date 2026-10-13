@php
    // Helper: converteer **bold** markdown naar <strong>bold</strong>
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
    <title>Offerte {{ $quote->displayNumber() ?? 'Concept' }}</title>
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

        /* html/body */
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

        /* page-bg niet meer nodig */
        .page-bg {
            display: none;
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

        /* page-bg niet meer nodig */
        .page-bg {
            display: none;
        }

        /* ===================== HEADER ===================== */
        .header {
            margin-top: -15mm; /* Compenseer body padding-top op eerste pagina */
            padding-top: 10mm;
            padding-bottom: 8mm;
            padding-left: 20mm;
            padding-right: 15mm;
            overflow: hidden;
        }
        .header-logo {
            float: left;
            width: 120mm;
            padding-left: 0;
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
            line-height: 1.2;
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
            padding: 0 15mm 20mm 20mm;
        }

        /* Blokken bij elkaar houden op pagina */
        .doc-title-bar,
        .info-container,
        .totals-container,
        .notes-section,
        .pre-text-section,
        .post-text-section,
        .closing-section,
        .payment-term-section,
        .signature-section {
            page-break-inside: avoid;
        }

        /* Aannames mogen over pagina's lopen, maar elke regel blijft intact */
        .assumptions-section li {
            page-break-inside: avoid;
        }

        /* Tabelrijen bij elkaar houden */
        .items-table tr {
            page-break-inside: avoid;
        }

        /* Minimaal 3 rijen op een pagina voor page-break */
        .items-table tbody {
            orphans: 3;
        }

        /* Offerte titel balk */
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
            font-size: 8pt;
        }
        .info-box .label {
            display: inline-block;
            width: 105px;
            font-weight: bold;
            color: #000;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        .info-table td {
            padding: 1px 0;
            vertical-align: top;
        }
        .info-table td.lbl {
            font-weight: bold;
            white-space: nowrap;
            padding-right: 8px;
            width: 90px;
        }

        /* Items tabel */
        .items-section {
            margin-bottom: 14px;
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
        .items-table th {
            color: #000;
            padding: 7px 8px;
            text-align: left;
            font-weight: bold;
            border-top: 2px solid #000;
            border-bottom: 1px solid #000;
            font-size: 8pt;
        }
        .items-table th.text-right {
            text-align: right;
        }
        .items-table td {
            padding: 9px 8px;
            border-bottom: 1px solid #ccc;
            font-size: 8pt;
        }
        .items-section table:last-of-type tbody tr:last-child td {
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
            font-size: 8pt;
        }
        .totals-table .total-final {
            background-color: #fff;
            color: #000;
            font-weight: bold;
            font-size: 10pt;
        }
        .totals-table .total-final td {
            padding: 7px 8px;
            color: #000;
        }

        /* Opmerkingen */
        .notes-section {
            padding: 10px 0;
            margin-bottom: 14px;
        }
        .notes-section h4 {
            font-size: 8pt;
            font-weight: bold;
            color: #000;
            margin-bottom: 6px;
        }
        .notes-section p {
            font-size: 8pt;
            line-height: 1.5;
            color: #000;
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

        /* Footer — in @page bottom margin zone */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding-bottom: 4px;
            text-align: center;
            font-size: 6.5pt;
            color: #666;
        }
    </style>
</head>
<body>

<div class="page-bg-fixed"></div>

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
        @if($settings->company_name)
        <p><strong>{{ $settings->company_name }}</strong></p>
        @endif
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
        @if($settings->company_bank_account)
        <p>IBAN: {{ $settings->company_bank_account }}</p>
        @endif
        @if($settings->company_kvk_number)
        <p>KvK: {{ $settings->company_kvk_number }}</p>
        @endif
        @if($settings->company_vat_number)
        <p>BTW: {{ $settings->company_vat_number }}</p>
        @endif
    </div>
</div>

<!-- Content wrapper -->
<div class="content">

    <!-- Offerte titel balk -->
    <div class="doc-title-bar clearfix">
        @php $quoteNumberDisplay = !str_starts_with($quote->quote_number ?? '', 'CONCEPT-') ? ' ' . $quote->quote_number : ''; @endphp
        <div class="doc-title">OFFERTE{{ $quoteNumberDisplay }}</div>
        <div class="doc-meta">
            <div>{{ ($quote->created_at ?? now())->format('d-m-Y') }}</div>
            @if($quote->valid_until)
            <div>Geldig tot: {{ $quote->valid_until->format('d-m-Y') }}</div>
            @endif
        </div>
    </div>

    <!-- Info Boxen: klantgegevens + projectdetails -->
    <div class="info-container clearfix">
        <div class="info-box">
            <table class="info-table">
                <tr><td colspan="2"><strong>{{ $quote->company->name }}</strong></td></tr>
                @if($quote->company->contact_person)
                <tr><td class="lbl">T.a.v.:</td><td>{{ $quote->company->contact_person }}</td></tr>
                @endif
                @if($quote->company->street)
                <tr><td class="lbl">Adres:</td><td>{{ trim(($quote->company->street ?? '') . ' ' . ($quote->company->house_number ?? '')) }}<br>{{ $quote->company->postal_code }}<br>{{ $quote->company->city }}</td></tr>
                @endif
                @if($quote->company->phone)
                <tr><td class="lbl">Telefoon:</td><td>{{ $quote->company->phone }}</td></tr>
                @endif
                @if($quote->company->email)
                <tr><td class="lbl">E-mail:</td><td>{{ $quote->company->email }}</td></tr>
                @endif
            </table>
        </div>

        <div class="info-box">
            <table class="info-table">
                @if($quote->lead?->project_description)
                <tr><td class="lbl">Project:</td><td>{{ $quote->lead->project_description }}</td></tr>
                @endif
                @if($quote->machine)
                <tr><td class="lbl">Machine:</td><td>{{ $quote->machine->name }}</td></tr>
                @endif
                @php $werklocatie = $quote->workAddressLines(); @endphp
                @if($werklocatie)
                <tr><td class="lbl">Werklocatie:</td><td>{!! nl2br(e(implode("\n", $werklocatie))) !!}</td></tr>
                @endif
            </table>
        </div>
    </div>

    <!-- Aanhef + pre_text -->
    @php
        $aanhef = $quote->company->contact_person ?? null;
    @endphp
    @if($aanhef)
    <p style="margin-bottom: 10px; font-size: 8pt;">Beste {{ $aanhef }},</p>
    @endif

    @if($quote->pre_text)
    <div class="pre-text-section">
    <p style="margin-bottom: 14px; font-size: 8pt; line-height: 1.5;">{!! nl2br($mdBold(e($quote->pre_text))) !!}</p>
    </div>
    @endif

    @if($quote->show_assumptions !== false && $quote->assumptions->isNotEmpty())
    <div class="assumptions-section">
        <ul>
            @foreach($quote->assumptions->sortBy('sort_order') as $assumption)
            <li>{{ $assumption->resolveContent($assumption->pivot->pivot_value) }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @php
        // Begint de itemstabel op een nieuwe pagina? Alleen als er daadwerkelijk
        // inleidende inhoud boven staat: een intro-tekst en/of de voorwaarden.
        // Staat er niets boven, dan blijft de tabel gewoon op pagina 1 staan en
        // voorkomen we een vrijwel lege eerste pagina.
        // Zelfde conditie als in order-confirmation.blade.php — houd ze gelijk.
        $hasContentBeforeItems = filled(trim((string) $quote->pre_text))
            || ($quote->show_assumptions !== false && $quote->assumptions->isNotEmpty());
    @endphp

    @if($quote->show_item_breakdown)
    {{-- ── Volledige regelweergave (met prijzen) ──────────────────────── --}}
    @php
        // Bereken chunk sizes: eerste chunk = minimaal 50% van items (min 3)
        $allItems = $quote->items;
        $totalItems = $allItems->count();
        $firstChunkSize = max(3, (int) ceil($totalItems * 0.5));
        $firstChunk = $allItems->take($firstChunkSize);
        $remainingItems = $allItems->skip($firstChunkSize);
    @endphp
    <div class="items-section" @if($hasContentBeforeItems) style="page-break-before: always;" @endif>
        {{-- Wrapper tabel voor header + eerste 50% items - blijft samen --}}
        <table class="items-table" style="page-break-inside: avoid;">
            <thead>
                <tr>
                    <th style="width: 8%;">Aantal</th>
                    <th style="width: 18%;">Artikelnr.</th>
                    <th style="width: 39%;">Omschrijving</th>
                    <th style="width: 20%;" class="text-right">Prijs p/st (excl. BTW)</th>
                    <th style="width: 15%;" class="text-right">Totaal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($firstChunk as $item)
                @php $isDiscount = $item->type instanceof \App\Enums\QuoteItemType && $item->type === \App\Enums\QuoteItemType::Discount; @endphp
                <tr @if($item->is_optional) style="color: #888; font-style: italic;" @elseif($isDiscount) style="color: #c0392b;" @endif>
                    <td>{{ $isDiscount ? '—' : number_format($item->quantity, 0, ',', '.') }}</td>
                    <td>{{ $item->itemable?->sku ?? '' }}</td>
                    <td>{{ str_replace('⌀', 'Ø', $item->description) }}{{ $item->pile_length ? ' (lengte: ' . rtrim(rtrim(number_format((float)$item->pile_length, 2, ',', ''), '0'), ',') . ' m)' : '' }}{{ $item->machine ? ' — machine: ' . $item->machine->name : '' }}@if($item->is_optional) <em style="font-size:8pt;">(optioneel)</em>@endif</td>
                    <td class="text-right">
                        @if($isDiscount)—@else&euro;&nbsp;{{ number_format($item->unit_price, 2, ',', '.') }}@endif
                    </td>
                    <td class="text-right">
                        @if($item->is_optional)
                            <em>PM</em>
                        @elseif($isDiscount)
                            &minus;&nbsp;&euro;&nbsp;{{ number_format(abs($item->total), 2, ',', '.') }}
                        @else
                            &euro;&nbsp;{{ number_format($item->quantity * $item->unit_price, 2, ',', '.') }}
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{-- Overige items in aparte tabellen --}}
        @foreach($remainingItems->chunk(5) as $chunk)
        <table class="items-table" style="page-break-inside: avoid; border-top: none;">
            <tbody>
                @foreach($chunk as $item)
                @php $isDiscount = $item->type instanceof \App\Enums\QuoteItemType && $item->type === \App\Enums\QuoteItemType::Discount; @endphp
                <tr @if($item->is_optional) style="color: #888; font-style: italic;" @elseif($isDiscount) style="color: #c0392b;" @endif>
                    <td style="width: 8%;">{{ $isDiscount ? '—' : number_format($item->quantity, 0, ',', '.') }}</td>
                    <td style="width: 18%;">{{ $item->itemable?->sku ?? '' }}</td>
                    <td style="width: 39%;">{{ str_replace('⌀', 'Ø', $item->description) }}{{ $item->pile_length ? ' (lengte: ' . rtrim(rtrim(number_format((float)$item->pile_length, 2, ',', ''), '0'), ',') . ' m)' : '' }}{{ $item->machine ? ' — machine: ' . $item->machine->name : '' }}@if($item->is_optional) <em style="font-size:8pt;">(optioneel)</em>@endif</td>
                    <td style="width: 20%;" class="text-right">
                        @if($isDiscount)—@else&euro;&nbsp;{{ number_format($item->unit_price, 2, ',', '.') }}@endif
                    </td>
                    <td style="width: 15%;" class="text-right">
                        @if($item->is_optional)
                            <em>PM</em>
                        @elseif($isDiscount)
                            &minus;&nbsp;&euro;&nbsp;{{ number_format(abs($item->total), 2, ',', '.') }}
                        @else
                            &euro;&nbsp;{{ number_format($item->quantity * $item->unit_price, 2, ',', '.') }}
                        @endif
                    </td>
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
                    <td style="width: 8%;">1</td>
                    <td style="width: 18%;"></td>
                    <td style="width: 39%;">Voorrijkosten</td>
                    <td style="width: 20%;" class="text-right">&euro;&nbsp;{{ number_format($quote->travel_total, 2, ',', '.') }}</td>
                    <td style="width: 15%;" class="text-right">&euro;&nbsp;{{ number_format($quote->travel_total, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
        @endif
    </div>

    @else
    {{-- ── Vereenvoudigde weergave (zonder prijzen) ────────────────────── --}}
    @php
        // Filter kortingen en bereken chunk sizes
        $filteredItems = $quote->items->filter(fn($i) => !($i->type instanceof \App\Enums\QuoteItemType && $i->type === \App\Enums\QuoteItemType::Discount));
        $totalItems = $filteredItems->count();
        $firstChunkSize = max(3, (int) ceil($totalItems * 0.5));
        $firstChunk = $filteredItems->take($firstChunkSize);
        $remainingItems = $filteredItems->skip($firstChunkSize);
    @endphp
    <div class="items-section" @if($hasContentBeforeItems) style="page-break-before: always;" @endif>
        {{-- Wrapper tabel voor header + eerste 50% items - blijft samen --}}
        <table class="items-table" style="page-break-inside: avoid;">
            <thead>
                <tr>
                    <th style="width: 12%;">Aantal</th>
                    <th style="width: 18%;">Artikelnr.</th>
                    <th style="width: 70%;">Omschrijving</th>
                </tr>
            </thead>
            <tbody>
                @foreach($firstChunk as $item)
                <tr @if($item->is_optional) style="color: #888; font-style: italic;" @endif>
                    <td>{{ number_format($item->quantity, 0, ',', '.') }}</td>
                    <td>{{ $item->itemable?->sku ?? '' }}</td>
                    <td>{{ str_replace('⌀', 'Ø', $item->description) }}{{ $item->pile_length ? ' (lengte: ' . rtrim(rtrim(number_format((float)$item->pile_length, 2, ',', ''), '0'), ',') . ' m)' : '' }}{{ $item->machine ? ' — machine: ' . $item->machine->name : '' }}@if($item->is_optional) <em style="font-size:8pt;">(optioneel)</em>@endif</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{-- Overige items in aparte tabellen --}}
        @foreach($remainingItems->chunk(5) as $chunk)
        <table class="items-table" style="page-break-inside: avoid; border-top: none;">
            <tbody>
                @foreach($chunk as $item)
                <tr @if($item->is_optional) style="color: #888; font-style: italic;" @endif>
                    <td style="width: 12%;">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                    <td style="width: 18%;">{{ $item->itemable?->sku ?? '' }}</td>
                    <td style="width: 70%;">{{ str_replace('⌀', 'Ø', $item->description) }}{{ $item->pile_length ? ' (lengte: ' . rtrim(rtrim(number_format((float)$item->pile_length, 2, ',', ''), '0'), ',') . ' m)' : '' }}{{ $item->machine ? ' — machine: ' . $item->machine->name : '' }}@if($item->is_optional) <em style="font-size:8pt;">(optioneel)</em>@endif</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endforeach
    </div>
    @endif

    <!-- Totalen -->
    @php
        $discountItems  = $quote->items->where('is_optional', false)->filter(fn($i) => $i->type instanceof \App\Enums\QuoteItemType && $i->type === \App\Enums\QuoteItemType::Discount);
        $discountTotal  = $discountItems->sum(fn($i) => abs($i->total));
        $itemsSubtotal  = $quote->items->where('is_optional', false)->sum('total');
    @endphp
    <div class="totals-container clearfix">
        <div class="totals-table">
            <table>
                @if($quote->show_item_breakdown)
                <tr>
                    <td>Subtotaal producten:</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($itemsSubtotal, 2, ',', '.') }}</td>
                </tr>
                @if($quote->travel_total)
                <tr>
                    <td>Voorrijkosten:</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($quote->travel_total, 2, ',', '.') }}</td>
                </tr>
                @endif
                @else
                @if($quote->travel_total)
                <tr>
                    <td>Voorrijkosten:</td>
                    <td class="text-right">&euro;&nbsp;{{ number_format($quote->travel_total, 2, ',', '.') }}</td>
                </tr>
                @endif
                @endif
                <tr>
                    <td><strong>Subtotaal excl. BTW:</strong></td>
                    <td class="text-right"><strong>&euro;&nbsp;{{ number_format($itemsSubtotal + ($quote->travel_total ?? 0), 2, ',', '.') }}</strong></td>
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

    @if($quote->notes)
    <!-- Opmerkingen -->
    <div class="notes-section">
        <h4>Opmerkingen</h4>
        <p>{{ $quote->notes }}</p>
    </div>
    @endif

    @if($quote->post_text)
    <div class="post-text-section">
    <p style="margin-top: 14px; font-size: 8pt; line-height: 1.5;">{!! nl2br($mdBold(e($quote->post_text))) !!}</p>
    </div>
    @endif

    @if($quote->show_assumptions !== false && $settings->closing_text)
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
                if (empty($section)) continue;
                
                if (preg_match('/^\*\*[^*]+\*\*/', $section)) {
                    if (!empty($currentGroup)) {
                        $groupedSections[] = $currentGroup;
                    }
                    $currentGroup = $section;
                } else {
                    if (!empty($currentGroup)) {
                        $currentGroup .= "\n\n" . $section;
                    } else {
                        $currentGroup = $section;
                    }
                }
            }
            if (!empty($currentGroup)) {
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

    @if($quote->paymentTerm)
    <div class="payment-term-section" style="margin-top: 16px; font-size: 8pt; line-height: 1.5; color: #333;">
        <p style="font-weight: bold; margin-bottom: 4px;">Betalingsvoorwaarden</p>
        <p style="font-weight: bold; margin-bottom: 2px;">{{ $quote->paymentTerm->title }}</p>
        <p>{{ $quote->paymentTerm->content }}</p>
    </div>
    @endif

    @if($settings->email_signature)
    <div class="signature-section">
    <p style="margin-top: 14px; font-size: 8pt; line-height: 1.5;">{!! nl2br(e($settings->email_signature)) !!}</p>
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
