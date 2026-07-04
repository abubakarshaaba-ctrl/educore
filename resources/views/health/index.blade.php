@extends('layouts.app')
@section('title','Health Records')
@section('page-title','Student Health Records')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:11px 14px;border-bottom:1px solid var(--border);font-size:13px}
tbody tr:last-child td{border-bottom:none}tbody tr:hover td{background:#F8FAFC}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12.5px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-sm{padding:4px 10px;font-size:11px}
.has-record{color:var(--emerald);font-size:11px;font-weight:600}.no-record{color:var(--slate-light);font-size:11px}
</style>
@endpush
@section('content')
<div class="card">
<div class="ch">Students — Health Records</div>
<div class="tbl"><table>
    <thead><tr><th>Student</th><th>Adm No</th><th>Class</th><th>Blood Group</th><th>Allergies</th><th>Emergency Contact</th><th></th></tr></thead>
    <tbody>
    @forelse($students as $student)
    <tr>
        <td><strong>{{ $student->full_name }}</strong></td>
        <td style="font-size:11px">{{ $student->admission_number }}</td>
        <td style="font-size:11px">{{ optional(optional($student->currentClassArm)->classLevel)->name }} {{ optional($student->currentClassArm)->name }}</td>
        <td>{{ optional($student->healthRecord)->blood_group ?? '—' }}</td>
        <td style="font-size:11px">{{ optional($student->healthRecord)->allergies ? Str::limit($student->healthRecord->allergies,30) : 'None recorded' }}</td>
        <td style="font-size:11px">{{ optional($student->healthRecord)->emergency_contact_name ?? '—' }}<br>{{ optional($student->healthRecord)->emergency_contact_phone }}</td>
        <td><a href="{{ route('health.show',$student) }}" class="btn btn-p btn-sm">{{ $student->healthRecord ? 'Edit' : 'Add Record' }}</a></td>
    </tr>
    @empty
    <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--slate-light)">No students found</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $students->links() }}
</div>
@endsection