@extends('layouts.app')
@section('title','Alumni')
@section('page-title','Alumni')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94A3B8;text-align:left}
td{padding:10px 14px;border-bottom:1px solid var(--border);vertical-align:top}
tr:last-child td{border:none}
.badge{display:inline-flex;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px}
.b-graduated{background:#ECFDF5;color:#059669}.b-withdrawn{background:#F1F5F9;color:#475569}
.mini{font-size:12px;color:#94A3B8}
details summary{cursor:pointer;color:var(--indigo);font-size:12px;font-weight:600}
.pf-form{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:10px}
.pf-form input,.pf-form textarea{padding:7px 10px;font-size:12px;border:1px solid var(--border);border-radius:6px;width:100%}
.btn{padding:6px 12px;font-size:11px;font-weight:700;border-radius:6px;border:none;background:var(--indigo);color:white;cursor:pointer;margin-top:8px}
</style>
@endpush
@section('content')
<div class="card">
    <div class="ch" style="display:flex;justify-content:space-between;align-items:center">
        <span>Alumni ({{ $alumni->total() }})</span>
        <form method="GET"><input type="text" name="q" class="fc" placeholder="Search name or admission no..." value="{{ request('q') }}"></form>
    </div>
    <div style="overflow-x:auto"><table>
        <thead><tr><th>Student</th><th>Status</th><th>Left Date</th><th>Alumni Profile</th></tr></thead>
        <tbody>
        @forelse($alumni as $s)
        <tr>
            <td>
                <div style="font-weight:600">{{ $s->full_name }}</div>
                <div class="mini">{{ $s->admission_number }}</div>
            </td>
            <td><span class="badge b-{{ $s->status }}">{{ ucfirst($s->status) }}</span></td>
            <td>{{ $s->graduation_date?->format('d M Y') ?? '—' }}</td>
            <td style="min-width:280px">
                @if($s->alumniProfile)
                    <div class="mini">
                        @if($s->alumniProfile->further_institution)Studying at: <b>{{ $s->alumniProfile->further_institution }}</b><br>@endif
                        @if($s->alumniProfile->occupation)Occupation: <b>{{ $s->alumniProfile->occupation }}</b> @if($s->alumniProfile->employer) at {{ $s->alumniProfile->employer }} @endif<br>@endif
                        @if($s->alumniProfile->contact_email || $s->alumniProfile->contact_phone)Contact: {{ $s->alumniProfile->contact_email }} {{ $s->alumniProfile->contact_phone }}@endif
                    </div>
                @else
                    <div class="mini">No profile recorded yet.</div>
                @endif
                <details>
                    <summary>{{ $s->alumniProfile ? 'Edit' : 'Add' }} profile</summary>
                    <form method="POST" action="{{ route('alumni.update', $s) }}">
                        @csrf @method('PUT')
                        <div class="pf-form">
                            <input type="text" name="graduation_year" placeholder="Graduation year" value="{{ $s->alumniProfile->graduation_year ?? '' }}">
                            <input type="text" name="further_institution" placeholder="Further institution" value="{{ $s->alumniProfile->further_institution ?? '' }}">
                            <input type="text" name="occupation" placeholder="Occupation" value="{{ $s->alumniProfile->occupation ?? '' }}">
                            <input type="text" name="employer" placeholder="Employer" value="{{ $s->alumniProfile->employer ?? '' }}">
                            <input type="email" name="contact_email" placeholder="Contact email" value="{{ $s->alumniProfile->contact_email ?? '' }}">
                            <input type="text" name="contact_phone" placeholder="Contact phone" value="{{ $s->alumniProfile->contact_phone ?? '' }}">
                        </div>
                        <textarea name="notes" placeholder="Notes" style="margin-top:8px">{{ $s->alumniProfile->notes ?? '' }}</textarea>
                        <button type="submit" class="btn">Save</button>
                    </form>
                </details>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" style="text-align:center;padding:30px;color:#94A3B8">No alumni yet — students appear here once graduated or withdrawn.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="padding:14px">{{ $alumni->links() }}</div>
</div>
@endsection
