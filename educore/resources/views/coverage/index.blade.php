@extends('layouts.app')
@section('title','Class Coverage')
@section('page-title','Substitute / Relief Teacher Coverage')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:18px}
.fr{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;border-radius:8px;border:none;cursor:pointer;font-family:inherit}
.btn-p{background:var(--indigo);color:white}
.btn-g{background:#ECFDF5;color:#059669;border:1px solid #A7F3D0;padding:5px 10px;font-size:11px}
.btn-danger{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;padding:5px 10px;font-size:11px}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94A3B8;text-align:left}
td{padding:10px 14px;border-bottom:1px solid var(--border)}
tr:last-child td{border:none}
.badge{display:inline-flex;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:capitalize}
.b-scheduled{background:#EFF6FF;color:#2563EB}.b-completed{background:#ECFDF5;color:#059669}.b-cancelled{background:#FEF2F2;color:#DC2626}
@media(max-width:700px){.fr{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<div class="card">
    <div class="ch">Assign Class Coverage</div>
    <div class="cb">
        <form method="POST" action="{{ route('coverage.store') }}">
            @csrf
            <div class="fr">
                <div class="fg"><label class="fl">Absent Teacher *</label>
                    <select name="absent_teacher_id" class="fc" required>
                        <option value="">Select teacher...</option>
                        @foreach($staff as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                    </select>
                </div>
                <div class="fg"><label class="fl">Covering Teacher *</label>
                    <select name="covering_teacher_id" class="fc" required>
                        <option value="">Select teacher...</option>
                        @foreach($staff as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                    </select>
                </div>
                <div class="fg"><label class="fl">Date *</label><input type="date" name="coverage_date" class="fc" value="{{ now()->toDateString() }}" required></div>
            </div>
            <div class="fg"><label class="fl">Notes</label><textarea name="notes" class="fc" rows="2"></textarea></div>
            <button type="submit" class="btn btn-p">Assign Coverage</button>
        </form>
        <p style="font-size:11.5px;color:#94A3B8;margin-top:10px">This automatically covers every period the absent teacher has on their timetable for that day of the week.</p>
    </div>
</div>

<div class="card">
    <div class="ch">Coverage Log</div>
    <div style="overflow-x:auto"><table>
        <thead><tr><th>Date</th><th>Class / Subject</th><th>Absent</th><th>Covering</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($assignments as $a)
        <tr>
            <td>{{ $a->coverage_date->format('d M Y') }}</td>
            <td>{{ optional(optional($a->classArm)->classLevel)->name }} {{ optional($a->classArm)->name }} @if($a->subject) — {{ $a->subject->name }} @endif</td>
            <td>{{ optional($a->absentTeacher)->name }}</td>
            <td>{{ optional($a->coveringTeacher)->name }}</td>
            <td><span class="badge b-{{ $a->status }}">{{ ucfirst($a->status) }}</span></td>
            <td>
                @if($a->status === 'scheduled')
                <div style="display:flex;gap:5px">
                    <form method="POST" action="{{ route('coverage.complete', $a) }}">@csrf @method('PATCH')<button class="btn btn-g">Done</button></form>
                    <form method="POST" action="{{ route('coverage.cancel', $a) }}">@csrf @method('PATCH')<button class="btn btn-danger">Cancel</button></form>
                </div>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;padding:30px;color:#94A3B8">No coverage assignments yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="padding:14px">{{ $assignments->links() }}</div>
</div>
@endsection
