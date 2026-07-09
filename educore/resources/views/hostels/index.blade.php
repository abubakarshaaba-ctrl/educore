@extends('layouts.app')
@section('title','Boarding & Hostel')
@section('page-title','Boarding / Hostel Management')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:18px}
.two{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.fr{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:10px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:8px 11px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;border-radius:8px;border:none;cursor:pointer;font-family:inherit}
.btn-p{background:var(--indigo);color:white}
.btn-g{background:#ECFDF5;color:#059669;border:1px solid #A7F3D0;padding:5px 10px;font-size:11px}
.btn-danger{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;padding:5px 10px;font-size:11px}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94A3B8;text-align:left}
td{padding:10px 14px;border-bottom:1px solid var(--border)}
tr:last-child td{border:none}
.badge{display:inline-flex;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:capitalize}
.b-paid{background:#ECFDF5;color:#059669}.b-unpaid{background:#FFFBEB;color:#D97706}
.hostel-card{border:1px solid var(--border);border-radius:10px;padding:12px 14px;margin-bottom:10px}
.mini{font-size:11px;color:#94A3B8}
.room-chip{display:inline-flex;padding:3px 9px;font-size:11px;border-radius:20px;background:#F1F5F9;color:#475569;margin:3px 4px 0 0}
@media(max-width:1000px){.two{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<div class="two">
    <div>
        <div class="card">
            <div class="ch">Add Hostel</div>
            <div class="cb">
                <form method="POST" action="{{ route('hostels.store') }}">
                    @csrf
                    <div class="fg"><label class="fl">Name *</label><input type="text" name="name" class="fc" placeholder="e.g. Block A" required></div>
                    <div class="fr">
                        <div class="fg"><label class="fl">Gender *</label>
                            <select name="gender" class="fc" required>
                                <option value="mixed">Mixed</option><option value="male">Male</option><option value="female">Female</option>
                            </select>
                        </div>
                        <div class="fg"><label class="fl">Capacity *</label><input type="number" name="capacity" class="fc" min="1" required></div>
                    </div>
                    <div class="fg"><label class="fl">Warden</label>
                        <select name="warden_id" class="fc">
                            <option value="">— None —</option>
                            @foreach($wardens as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-p" style="width:100%;justify-content:center">Add Hostel</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="ch">Hostels ({{ $hostels->count() }})</div>
            <div class="cb">
                @forelse($hostels as $h)
                <div class="hostel-card">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <div style="font-weight:700">{{ $h->name }}</div>
                            <div class="mini">{{ ucfirst($h->gender) }} · Warden: {{ optional($h->warden)->name ?? '—' }} · {{ $h->occupiedCount() }}/{{ $h->capacity }} occupied</div>
                        </div>
                    </div>
                    <div style="margin-top:8px">
                        @forelse($h->rooms as $r)
                        <span class="room-chip">{{ $r->room_number }} ({{ $r->occupiedCount() }}/{{ $r->capacity }})</span>
                        @empty
                        <span class="mini">No rooms yet.</span>
                        @endforelse
                    </div>
                    <form method="POST" action="{{ route('hostels.rooms.store', $h) }}" style="display:flex;gap:6px;margin-top:10px">
                        @csrf
                        <input type="text" name="room_number" class="fc" placeholder="Room no." style="max-width:100px" required>
                        <input type="number" name="capacity" class="fc" placeholder="Capacity" style="max-width:100px" min="1" value="4" required>
                        <button type="submit" class="btn" style="background:#F1F5F9;color:#475569;padding:6px 12px;font-size:11px">+ Room</button>
                    </form>
                </div>
                @empty
                <div style="text-align:center;color:#94A3B8;padding:20px">No hostels yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="ch">Allocate Student to Room</div>
            <div class="cb">
                <form method="POST" action="{{ route('hostels.allocate') }}">
                    @csrf
                    <div class="fg"><label class="fl">Student *</label>
                        <select name="student_id" class="fc" required>
                            <option value="">Select student...</option>
                            @foreach($students as $s)<option value="{{ $s->id }}">{{ $s->full_name }} ({{ $s->admission_number }})</option>@endforeach
                        </select>
                    </div>
                    <div class="fr">
                        <div class="fg"><label class="fl">Hostel *</label>
                            <select name="hostel_id" id="hostelSelect" class="fc" required onchange="loadRooms(this.value)">
                                <option value="">Select hostel...</option>
                                @foreach($hostels as $h)<option value="{{ $h->id }}">{{ $h->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="fg"><label class="fl">Room *</label>
                            <select name="room_id" id="roomSelect" class="fc" required>
                                <option value="">Select hostel first...</option>
                            </select>
                        </div>
                    </div>
                    <div class="fg"><label class="fl">Boarding Fee (₦)</label><input type="number" step="0.01" name="boarding_fee_amount" class="fc"></div>
                    <button type="submit" class="btn btn-p">Allocate Room</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="ch">Current Allocations</div>
            <div style="overflow-x:auto"><table>
                <thead><tr><th>Student</th><th>Hostel / Room</th><th>Fee</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($allocations as $a)
                <tr>
                    <td>{{ optional($a->student)->full_name }}</td>
                    <td>{{ optional($a->hostel)->name }} / {{ optional($a->room)->room_number }}</td>
                    <td>₦{{ number_format($a->boarding_fee_amount, 2) }}
                        <span class="badge b-{{ $a->boarding_fee_status }}">{{ ucfirst($a->boarding_fee_status) }}</span>
                    </td>
                    <td>{{ ucfirst($a->status) }}</td>
                    <td>
                        <div style="display:flex;gap:5px">
                        @if($a->boarding_fee_status === 'unpaid')
                        <form method="POST" action="{{ route('hostels.fee-paid', $a) }}">@csrf @method('PATCH')<button class="btn btn-g">Mark Paid</button></form>
                        @endif
                        <form method="POST" action="{{ route('hostels.vacate', $a) }}" onsubmit="return confirm('Vacate this student?')">@csrf @method('PATCH')<button class="btn btn-danger">Vacate</button></form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;padding:30px;color:#94A3B8">No active allocations.</td></tr>
                @endforelse
                </tbody>
            </table></div>
            <div style="padding:14px">{{ $allocations->links() }}</div>
        </div>
    </div>
</div>

<script>
function loadRooms(hostelId) {
    const roomSelect = document.getElementById('roomSelect');
    roomSelect.innerHTML = '<option value="">Loading...</option>';
    if (!hostelId) { roomSelect.innerHTML = '<option value="">Select hostel first...</option>'; return; }
    fetch(`/hostels/${hostelId}/rooms`).then(r => r.json()).then(rooms => {
        roomSelect.innerHTML = '<option value="">Select room...</option>' +
            rooms.map(r => `<option value="${r.id}" ${r.full ? 'disabled' : ''}>${r.label}${r.full ? ' — FULL' : ''}</option>`).join('');
    });
}
</script>
@endsection
