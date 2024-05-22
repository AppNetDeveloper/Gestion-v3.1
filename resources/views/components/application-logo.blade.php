<a class="flex items-center" href="{{ url('/') }}">
    @php
        $logo = \Spatie\MediaLibrary\MediaCollections\Models\Media::where('collection_name', 'logo')->first();
        $darkLogo = \Spatie\MediaLibrary\MediaCollections\Models\Media::where('collection_name', 'dark_logo')->first();
    @endphp

    @if ($logo)
        <img src="{{ route('logo.show', $logo->id) }}" class="black_logo" alt="logo">
    @endif

    @if ($darkLogo)
        <img src="{{ route('logo.show', $darkLogo->id) }}" class="white_logo" alt="logo">
    @endif

    <span class="ltr:ml-3 rtl:mr-3 text-xl font-Inter font-bold text-slate-900 dark:text-white hidden xl:inline-block">AppNetDev</span>
</a>
