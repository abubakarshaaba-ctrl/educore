@props(['url' => config('app.url'), 'brand' => []])

@php
    $context = $brand['context'] ?? 'platform';
    $isSchool = $context === 'school';
    $name = trim((string) ($brand['name'] ?? ($isSchool ? 'Your School' : 'EduCore')));
    $tagline = trim((string) ($brand['tagline'] ?? 'One Platform. Every School Operation.'));
    $motto = trim((string) ($brand['motto'] ?? ''));
    $logoUrl = $brand['logo_url'] ?? null;
    $initial = mb_strtoupper(mb_substr($name, 0, 1));
    $bg = $isSchool ? '#0E5A47' : '#082653';
    $soft = $isSchool ? '#D9F3EA' : '#DCE7F7';
@endphp

<tr>
<td class="{{ $isSchool ? 'header-school' : 'header-platform' }}" bgcolor="{{ $bg }}" style="background-color:{{ $bg }};padding:0;border-radius:12px 12px 0 0;">
    <a href="{{ $url }}" style="display:block;text-decoration:none;padding:15px 20px 13px;">
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;border-collapse:collapse;">
            <tr>
                <td valign="middle" style="width:46px;padding:0 11px 0 0;vertical-align:middle;">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $isSchool ? $name : 'EduCore' }}" width="42" height="42"
                             style="display:block;width:42px;height:42px;max-width:42px;border-radius:9px;background:#ffffff;{{ $isSchool ? 'border:1px solid #ffffff;' : '' }}object-fit:contain;">
                    @else
                        <table width="42" height="42" cellpadding="0" cellspacing="0" role="presentation"
                               style="width:42px;height:42px;border-collapse:separate;background:#F2C14E;border-radius:9px;">
                            <tr>
                                <td align="center" valign="middle"
                                    style="width:42px;height:42px;text-align:center;vertical-align:middle;font-family:Arial,Helvetica,sans-serif;font-size:22px;line-height:42px;font-weight:900;color:{{ $isSchool ? '#0E5A47' : '#082653' }};">
                                    {{ $isSchool ? $initial : 'E' }}
                                </td>
                            </tr>
                        </table>
                    @endif
                </td>
                <td valign="middle" style="padding:0;vertical-align:middle;">
                    @if($isSchool)
                        <div style="font-family:Arial,Helvetica,sans-serif;font-size:17px;line-height:1.2;font-weight:800;letter-spacing:-0.01em;color:#ffffff;">
                            {{ $name }}
                        </div>
                        <div style="font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:1.35;margin-top:3px;color:{{ $soft }};">
                            {{ $motto !== '' ? $motto : 'Official school communication' }}
                        </div>
                    @else
                        <div style="font-family:Arial,Helvetica,sans-serif;font-size:23px;line-height:1.05;font-weight:850;letter-spacing:-0.03em;color:#ffffff;">
                            <span style="color:#ffffff;font-weight:850;">Edu</span><span style="color:#F2C14E;font-weight:850;">Core</span>
                        </div>
                        <div style="font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:1.35;margin-top:3px;color:{{ $soft }};white-space:nowrap;">
                            {{ $tagline }}
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    </a>
    <div style="height:3px;line-height:3px;background:#F2C14E;font-size:0;">&nbsp;</div>
</td>
</tr>
