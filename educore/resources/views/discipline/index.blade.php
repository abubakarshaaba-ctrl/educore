@extends('layouts.app')
@section('title','Discipline & Conduct')
@section('page-title','Discipline & Conduct Records')
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
.btn-danger{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94A3B8;text-align:left}
td{padding:10px 14px;border-bottom:1px solid var(--border)}
tr:last-child td{border:none}
.badge{display:inline-flex;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:capitalize}
.b-merit{background:#ECFDF5;color:#059669}.b-demerit{background:#FFFBEB;color:#D97706}
.b-incident{background:#FEF2F2;color:#DC2626}.b-suspension{background:#F1F5F9;color:#475569}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:#059669;margin-bottom:14px}
@media(max-width:900px){.fr{grid-template-columns:1fr 1fr}}
@media(max-width:600px){.fr{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">✓ {{ session('success') }}</div>@endif

<div class="card">
    <div class="ch">Record New Entry</div>
    <div class="cb">
        <form method="POST" action="{{ route('discipline.store') }}">
            @csrf
            <div class="fr">
                <div class="fg"><label class="fl">Student *</label>
                    <select name="student_id" class="fc" required>
                        <option value="">Select student...</option>
                        @foreach($students as $s)
                        <option value="{{ $s->id }}">{{ $s->full_name }} ({{ $s->admission_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="fg"><label class="fl">Type *</label>
                    <select name="type" class="fc" required>
                        <option value="merit">Merit</option>
                        <option value="demerit">Demerit</option>
                        <option value="incident">Incident</option>
                        <option value="suspension">Suspension</option>
                    </select>
                </div>
                <div class="fg"><label class="fl">Category *</label>
                    <input type="text" name="category" class="fc" placeholder="e.g. Late to class" required>
                </div>
                <div class="fg"><label class="fl">Date *</label>
                    <input type="date" name="occurred_at" class="fc" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="fg"><label class="fl">Points</label>
                    <input type="number" name="points" class="fc" placeholder="Auto if blank">
                </div>
                <div class="fg"><label class="fl">Suspension Start</label>
                    <input type="date" name="suspension_start" class="fc">
                </div>
                <div class="fg"><label class="fl">Suspension End</label>
                    <input type="date" name="suspension_end" class="fc">
                </div>
            </div>
            <div class="fg"><label class="fl">Description</label>
                <textarea name="description" class="fc" rows="2"></textarea>
            </div>
            <div class="fg"><label class="fl">Action Taken</label>
                <textarea name="action_taken" class="fc" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-p">Save Record</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="ch">All Records</div>
    <div style="overflow-x:auto"><table>
        <thead><tr><th>Student</th><th>Type</th><th>Category</th><th>Points</th><th>Date</th><th>Status</th><th>Recorded By</th><th></th></tr></thead>
        <tbody>
        @forelse($records as $r)
        <tr>
            <td>{{ optional($r->student)->full_name }}</td>
            <td><span class="badge b-{{ $r->type }}">{{ $r->type }}</span></td>
            <td>{{ $r->category }}</td>
            <td style="color:{{ $r->points < 0 ? '#DC2626' : ($r->points > 0 ? '#059669' : '#64748B') }}">{{ $r->points > 0 ? '+' : '' }}{{ $r->points }}</td>
            <td>{{ $r->occurred_at?->format('d M Y') }}</td>
            <td>{{ ucfirst($r->status) }}</td>
            <td>{{ optional($r->recorder)->name ?? '—' }}</td>
            <td>
                <div style="display:flex;gap:6px">
                    @if($r->status === 'open')
                    <form method="POST" action="{{ route('discipline.resolve', $r) }}">@csrf
                        <button class="btn" style="background:#F1F5F9;color:#475569;padding:5px 10px;font-size:11px">Resolve</button>
                    </form>
                    @endif
                    <form method="POST" action="{{ route('discipline.destroy', $r) }}" onsubmit="return confirm('Delete this record?')">@csrf @method('DELETE')
                        <button class="btn btn-danger" style="padding:5px 10px;font-size:11px">Delete</button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:30px;color:#94A3B8">No discipline records yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="padding:14px">{{ $records->links() }}</div>
</div>
@endsection
