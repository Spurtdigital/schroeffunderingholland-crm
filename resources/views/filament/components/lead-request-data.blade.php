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
                    @php
                        $isImageUrl = is_string($value)
                            && filter_var($value, FILTER_VALIDATE_URL)
                            && preg_match('/\.(jpg|jpeg|png|gif|webp)(\?.*)?$/i', $value);
                    @endphp
                    @if($isImageUrl)
                        <a href="{{ $value }}" target="_blank">
                            <img src="{{ $value }}" style="max-width:200px; max-height:150px; border-radius:4px; display:block;" alt="Bijlage" />
                        </a>
                    @else
                        {{ is_array($value) ? implode(', ', $value) : $value }}
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
@endif
