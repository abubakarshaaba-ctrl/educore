@props(['url' => config('app.url'), 'brand' => []])

@php
    $context = $brand['context'] ?? 'platform';
    $isSchool = $context === 'school';
    $name = trim((string) ($brand['name'] ?? ($isSchool ? 'Your School' : 'EduCore')));
    $tagline = trim((string) ($brand['tagline'] ?? 'One Platform. Every School Operation.'));
    $motto = trim((string) ($brand['motto'] ?? ''));
    $logoUrl = $brand['logo_url'] ?? null;
    $initial = mb_strtoupper(mb_substr($name, 0, 1));
@endphp

<tr>
<td class="header {{ $isSchool ? 'header-school' : 'header-platform' }}">
    <a href="{{ $url }}" class="brand-link">
        <table class="brand-table" width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td class="brand-logo-cell" valign="middle">
                    @if($isSchool && $logoUrl)
                        <img src="{{ $logoUrl }}" class="school-mail-logo" alt="{{ $name }}" width="58" height="58">
                    @elseif($isSchool)
                        <table class="school-mail-mark" cellpadding="0" cellspacing="0" role="presentation">
                            <tr><td>{{ $initial }}</td></tr>
                        </table>
                    @else
                        <table class="platform-mail-mark" cellpadding="0" cellspacing="0" role="presentation">
                            <tr><td>E</td></tr>
                        </table>
                    @endif
                </td>
                <td class="brand-copy-cell" valign="middle">
                    @if($isSchool)
                        <div class="school-mail-name">{{ $name }}</div>
                        <div class="school-mail-motto">{{ $motto !== '' ? $motto : 'School communication' }}</div>
                    @else
                        <div class="platform-mail-wordmark"><span class="brand-edu">Edu</span><span class="brand-core">Core</span></div>
                        <div class="platform-mail-tagline">{{ $tagline }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </a>
    <div class="{{ $isSchool ? 'school-gold-line' : 'platform-gold-line' }}"></div>
</td>
</tr>
