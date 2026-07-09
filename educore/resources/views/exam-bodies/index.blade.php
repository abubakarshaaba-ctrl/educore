@extends('layouts.app')
@section('title','External Exam Registration')
@section('page-title','WAEC / NECO / NABTEB / JAMB Registration')
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
.btn-danger{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;padding:5px 10px;font-size:11px}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94A3B8;text-align:left}
td{padding:10px 14px;border-bottom:1px solid var(--border)}
tr:last-child td{border:none}
.badge{display:inline-flex;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:capitalize}
.b-pending{background:#FFFBEB;color:#D97706}.b-registered{background:#EFF6FF;color:#2563EB}.b-completed{background:#ECFDF5;color:#059669}
select.inline{padding:4px 8px;font-size:11px;border:1px solid var(--border);border-radius:6px}
@media(max-width:900px){.fr{grid-template-columns:1fr 1fr}}
@media(max-width:600px){.fr{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<div class="card">
    <div class="ch">Register Candidate</div>
    <div class="cb">
        <form method="POST" action="{{ route('exam-bodies.store') }}">
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
                <div class="fg"><label class="fl">Exam Body *</label>
                    <select name="exam_body" class="fc" required>
                        <option value="WAEC">WAEC</option><option value="NECO">NECO</option>
                        <option value="NABTEB">NABTEB</option><option value="JAMB">JAMB</option>
                    </select>
                </div>
                <div class="fg"><label class="fl">Exam Year *</label><input type="text" name="exam_year" class="fc" placeholder="e.g. 2026" required></div>
                <div class="fg"><label class="fl">Registration Number</label><input type="text" name="registration_number" class="fc"></div>
            </div>
            <div class="fg"><label class="fl">Subjects (comma-separated)</label>
                <input type="text" name="subjects" class="fc" placeholder="Mathematics, English, Biology, ...">
            </div>
            <button type="submit" class="btn btn-p">Register Candidate</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="ch">Registrations</div>
    <div style="overflow-x:auto"><table>
        <thead><tr><th>Student</th><th>Exam Body</th><th>Year</th><th>Reg. Number</th><th>Subjects</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($registrations as $r)
        <tr>
            <td>{{ optional($r->student)->full_name }}</td>
            <td>{{ $r->exam_body }}</td>
            <td>{{ $r->exam_year }}</td>
            <td style="font-family:monospace;font-size:12px">{{ $r->registration_number ?? '—' }}</td>
            <td style="font-size:12px">{{ $r->subjects ? implode(', ', $r->subjects) : '—' }}</td>
            <td><span class="badge b-{{ $r->status }}">{{ ucfirst($r->status) }}</span></td>
            <td>
                <form method="POST" action="{{ route('exam-bodies.destroy', $r) }}" onsubmit="return confirm('Remove this registration?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger">Remove</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:30px;color:#94A3B8">No candidate registrations yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="padding:14px">{{ $registrations->links() }}</div>
</div>
@endsection
