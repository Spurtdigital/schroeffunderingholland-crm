@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo-v2.1.png" class="logo" alt="Laravel Logo">
@elseif (trim($slot) === 'Schroeffundering Holland CRM' || trim($slot) === config('app.name'))
<img src="{{ config('app.url') }}/schroeffunderingholland.svg" class="logo" alt="Schroeffundering Holland" style="height: 50px; max-height: 50px;">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
