<x-mail::layout>
{{-- Header — brand name defaults to config('app.name') for platform-sent
     mail, but tenant-facing notifications share $mailBrandName (the
     school's own name) right before rendering so it shows the school,
     not "EduCore", here and in the footer below. --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ $mailBrandName ?? config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} {{ $mailBrandName ?? config('app.name') }}. {{ __('All rights reserved.') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
