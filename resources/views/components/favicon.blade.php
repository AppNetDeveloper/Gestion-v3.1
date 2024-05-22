{{-- Favicon --}}
@php
    $favicon = \Spatie\MediaLibrary\MediaCollections\Models\Media::where('collection_name', 'favicon')->first();
@endphp

@if ($favicon)
    <link rel="icon" href="{{ route('logo.show', $favicon->id) }}" type="image/png">
@endif
