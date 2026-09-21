<x-mail::layout>
<x-slot:header>
<x-mail::header
    :url="$mailBrand['home_url'] ?? config('app.url')"
    :brand="$mailBrand ?? []"
/>
</x-slot:header>

{!! $slot !!}

@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

<x-slot:footer>
<x-mail::footer :brand="$mailBrand ?? []"></x-mail::footer>
</x-slot:footer>
</x-mail::layout>
