@extends('layouts.app')
@section('title','Visitor Log')
@section('page-title','Visitor / Gate Pass Log')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:18px}
.fr{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;border-radius:8px;border:none;cursor:pointer;font-family:inherit}
.btn-p{background:var(--indigo);color:white}
.btn-g{background:#ECFDF5;color:#059669;border:1px solid #A7F3D0;padding:5px 10px;font-size:11px}
.stat{background:white;border:1px solid var(--border);border-radius:12px;padding:16px;margin-bottom:16px;display:inline-flex;gap:10px;align-items:center}
.stat b{font-size:22px;color:var(--indigo)}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94A3B8;text-align:left}
td{padding:10px 14px;border-bottom:1px solid var(--border)}
tr:last-child td{border:none}
.badge{display:inline-flex;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px}
.b-in{background:#ECFDF5;color:#059669}.b-out{background:#F1F5F9;color:#475569}
@media(max-width:900px){.fr{grid-template-columns:1fr 1fr}}
@media(max-width:600px){.fr{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<div class="stat">🚪 <span>Currently on premises: <b>{{ $checkedIn }}</b></span></div>

<div class="card">
    <div class="ch">Check In Visitor</div>
    <div class="cb">
        <form method="POST" action="{{ route('visitors.store') }}">
            @csrf
            <div class="fr">
                <div class="fg"><label class="fl">Visitor Name *</label><input type="text" name="visitor_name" class="fc" required></div>
                <div class="fg"><label class="fl">Phone</label><input type="text" name="phone" class="fc"></div>
                <div class="fg"><label class="fl">Purpose of Visit</label><input type="text" name="purpose" class="fc"></div>
                <div class="fg"><label class="fl">Person to See</label><input type="text" name="host_name" class="fc"></div>
                <div class="fg"><label class="fl">Badge Number</label><input type="text" name="badge_number" class="fc"></div>
            </div>
            <button type="submit" class="btn btn-p">✅ Check In</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="ch">Visitor Log</div>
    <div style="overflow-x:auto"><table>
        <thead><tr><th>Visitor</th><th>Purpose</th><th>Host</th><th>Check In</th><th>Check Out</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($visitors as $v)
        <tr>
            <td>
                <div style="font-weight:600">{{ $v->visitor_name }}</div>
                @if($v->phone)<div style="font-size:11px;color:#94A3B8">{{ $v->phone }}</div>@endif
            </td>
            <td>{{ $v->purpose ?? '—' }}</td>
            <td>{{ $v->host_name ?? '—' }}</td>
            <td>{{ $v->check_in_at?->format('d M Y, h:ia') }}</td>
            <td>{{ $v->check_out_at?->format('d M Y, h:ia') ?? '—' }}</td>
            <td><span class="badge {{ $v->isCheckedIn() ? 'b-in' : 'b-out' }}">{{ $v->isCheckedIn() ? 'On Premises' : 'Checked Out' }}</span></td>
            <td>
                @if($v->isCheckedIn())
                <form method="POST" action="{{ route('visitors.checkout', $v) }}">@csrf @method('PATCH')
                    <button class="btn btn-g">Check Out</button>
                </form>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:30px;color:#94A3B8">No visitors logged yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="padding:14px">{{ $visitors->links() }}</div>
</div>
@endsection
