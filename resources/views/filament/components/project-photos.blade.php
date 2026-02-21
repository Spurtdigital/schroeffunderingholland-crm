@php
    $photos = $getRecord()->getMedia('project-photos');
@endphp

@if($photos->isEmpty())
    <p style="font-size:0.75rem; font-style:italic; color:#9ca3af;">Geen foto's opgeslagen bij dit project.</p>
@else
    <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:8px;">
        @foreach($photos as $photo)
            <a href="{{ $photo->getUrl() }}" target="_blank" title="{{ $photo->file_name }}"
               style="display:block; border-radius:6px; overflow:hidden; border:1px solid #e5e7eb;">
                <img src="{{ $photo->getUrl() }}"
                     alt="{{ $photo->file_name }}"
                     style="width:100%; height:90px; object-fit:cover; display:block;">
            </a>
        @endforeach
    </div>
    <p style="margin:6px 0 0; font-size:0.7rem; color:#9ca3af;">{{ $photos->count() }} foto('s) · klik om te vergroten</p>
@endif
