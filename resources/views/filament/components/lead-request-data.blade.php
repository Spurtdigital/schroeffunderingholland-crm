@php
    $data = $getRecord()->request_data ?? [];
@endphp

@if(empty($data))
    <p style="font-size:0.75rem; font-style:italic; color:#9ca3af;">Geen formuliergegevens beschikbaar.</p>
@else
    <table style="width:100%; border-collapse:collapse; font-size:0.75rem;">
        @foreach($data as $key => $value)
            <tr style="border-bottom:1px solid #f3f4f6;">
                <td style="padding:4px 8px 4px 0; color:#6b7280; white-space:nowrap; width:40%; vertical-align:top;">
                    {{ ucfirst(str_replace(['-', '_'], ' ', $key)) }}
                </td>
                <td style="padding:4px 0; color:#374151; word-break:break-word;">
                    {{ is_array($value) ? implode(', ', $value) : $value }}
                </td>
            </tr>
        @endforeach
    </table>
@endif
