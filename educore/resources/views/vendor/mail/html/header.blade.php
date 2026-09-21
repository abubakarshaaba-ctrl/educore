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
<td bgcolor="{{ $bg }}" style="background-color:{{ $bg }};padding:0;border-radius:16px 16px 0 0;">
    <a href="{{ $url }}" style="display:block;text-decoration:none;padding:24px 28px 20px;">
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;border-collapse:collapse;">
            <tr>
                <td valign="middle" style="width:64px;padding:0 14px 0 0;">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $isSchool ? $name : 'EduCore' }}" width="56" height="56"
                             style="display:block;width:56px;height:56px;max-width:56px;border-radius:12px;background:#ffffff;{{ $isSchool ? 'border:2px solid #ffffff;' : '' }}object-fit:contain;">
                    @else
                        <table width="56" height="56" cellpadding="0" cellspacing="0" role="presentation"
                               style="width:56px;height:56px;border-collapse:separate;background:#F2C14E;border-radius:12px;">
                            <tr>
                                <td align="center" valign="middle"
                                    style="width:56px;height:56px;text-align:center;vertical-align:middle;font-family:Arial,Helvetica,sans-serif;font-size:28px;line-height:56px;font-weight:900;color:{{ $isSchool ? '#0E5A47' : '#082653' }};">
                                    {{ $isSchool ? $initial : 'E' }}
                                </td>
                            </tr>
                        </table>
                    @endif
                </td>
                <td valign="middle" style="padding:0;vertical-align:middle;">
                    @if($isSchool)
                        <div style="font-family:Arial,Helvetica,sans-serif;font-size:21px;line-height:1.2;font-weight:800;letter-spacing:-0.01em;color:#ffffff;text-transform:uppercase;">
                            {{ $name }}
                        </div>
                        <div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;margin-top:5px;color:{{ $soft }};">
                            {{ $motto !== '' ? $motto : 'Official school communication' }}
                        </div>
                    @else
                        <div style="font-family:Arial,Helvetica,sans-serif;font-size:27px;line-height:1.1;font-weight:850;letter-spacing:-0.03em;color:#ffffff;">
                            <span style="color:#ffffff;font-weight:850;">Edu</span><span style="color:#F2C14E;font-weight:850;">Core</span>
                        </div>
                        <div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;margin-top:5px;color:{{ $soft }};">
                            {{ $tagline }}
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    </a>
    <div style="height:5px;line-height:5px;background:#F2C14E;font-size:0;">&nbsp;</div>
</td>
</tr>
