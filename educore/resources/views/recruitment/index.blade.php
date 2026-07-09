@extends('layouts.app')
@section('title','Recruitment')
@section('page-title','Recruitment & Applicant Tracking')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:18px}
.fr{display:grid;grid-template-columns:1fr 1fr;gap:12px}
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
.b-open{background:#ECFDF5;color:#059669}.b-closed{background:#F1F5F9;color:#475569}
@media(max-width:600px){.fr{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<div class="card">
    <div class="ch">Public Careers Page</div>
    <div class="cb" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <span style="font-size:13px;color:var(--slate)">Share this link with candidates:</span>
        <code style="background:#F8FAFC;border:1px solid var(--border);border-radius:6px;padding:6px 10px;font-size:12px">{{ $careersUrl }}</code>
        <a href="{{ $careersUrl }}" target="_blank" class="btn" style="background:#F1F5F9;color:#475569;padding:6px 12px;font-size:12px">Open ↗</a>
    </div>
</div>

<div class="card">
    <div class="ch">Post a Job Opening</div>
    <div class="cb">
        <form method="POST" action="{{ route('recruitment.postings.store') }}">
            @csrf
            <div class="fr">
                <div class="fg"><label class="fl">Title *</label><input type="text" name="title" class="fc" placeholder="e.g. Mathematics Teacher" required></div>
                <div class="fg"><label class="fl">Department</label><input type="text" name="department" class="fc"></div>
            </div>
            <div class="fg"><label class="fl">Description</label><textarea name="description" class="fc" rows="2"></textarea></div>
            <div class="fg"><label class="fl">Requirements</label><textarea name="requirements" class="fc" rows="2"></textarea></div>
            <div class="fg" style="max-width:220px"><label class="fl">Closes On</label><input type="date" name="closes_at" class="fc"></div>
            <button type="submit" class="btn btn-p">Post Job Opening</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="ch">Job Postings</div>
    <div style="overflow-x:auto"><table>
        <thead><tr><th>Title</th><th>Department</th><th>Applicants</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($postings as $p)
        <tr>
            <td><a href="{{ route('recruitment.show', $p) }}" style="color:var(--indigo);font-weight:600;text-decoration:none">{{ $p->title }}</a></td>
            <td>{{ $p->department ?? '—' }}</td>
            <td>{{ $p->applicants_count }}</td>
            <td><span class="badge b-{{ $p->status }}">{{ ucfirst($p->status) }}</span></td>
            <td>
                @if($p->status === 'open')
                <form method="POST" action="{{ route('recruitment.postings.close', $p) }}">@csrf @method('PATCH')<button class="btn btn-danger">Close</button></form>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;padding:30px;color:#94A3B8">No job postings yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
@endsection
