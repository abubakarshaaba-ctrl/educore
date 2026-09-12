@extends('layouts.app')
@section('title','Admissions Dashboard')
@section('page-title','Admissions Dashboard')

@push('styles')
<style>
.role-hero{display:flex;justify-content:space-between;gap:16px;align-items:center;margin-bottom:18px;flex-wrap:wrap}
.role-hero h1{font-size:22px;margin:0;color:var(--midnight)}
.role-hero p{margin:4px 0 0;font-size:13px;color:var(--slate-light)}
.metric-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}
.metric{background:white;border:1px solid var(--border);border-radius:12px;padding:15px}
.metric .label{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light);font-weight:700}
.metric .value{font-size:25px;font-weight:700;color:var(--midnight);margin-top:5px}
.metric .sub{font-size:11px;color:var(--slate-light);margin-top:3px}
.two-col-dash{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(280px,.85fr);gap:16px;align-items:start}
.dash-card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.dash-head{padding:13px 16px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;justify-content:space-between;align-items:center;gap:10px}
.dash-head strong{font-size:13px;color:var(--midnight)}
.dash-body{padding:0}
.tbl-lite{width:100%;border-collapse:collapse;min-width:620px}
.tbl-lite th,.tbl-lite td{padding:11px 14px;border-bottom:1px solid #EEF2F7;text-align:left;font-size:12px}
.tbl-lite th{font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light);background:#FCFDFE}
.tbl-lite tr:last-child td{border-bottom:0}
.status{display:inline-flex;padding:3px 8px;border-radius:999px;font-size:10px;font-weight:700;text-transform:capitalize;background:#F1F5F9;color:var(--slate)}
.status.pending{background:#FFF7ED;color:#C2410C}.status.shortlisted{background:#EFF6FF;color:#1D4ED8}.status.admitted{background:#ECFDF5;color:#047857}.status.rejected{background:#FEF2F2;color:#B91C1C}
.list-row{display:flex;justify-content:space-between;gap:12px;padding:12px 16px;border-bottom:1px solid #EEF2F7;font-size:12px}
.list-row:last-child{border-bottom:0}.muted{color:var(--slate-light)}
.quick-actions{display:flex;gap:8px;flex-wrap:wrap}
@media(max-width:1024px){.metric-grid{grid-template-columns:repeat(2,1fr)}.two-col-dash{grid-template-columns:1fr}}
@media(max-width:520px){.metric-grid{grid-template-columns:1fr 1fr}.metric .value{font-size:21px}}
</style>
@endpush

@section('content')
<div class="role-hero">
    <div>
        <h1>Admissions Operations</h1>
        <p>Application pipeline, interviews, admission decisions and enrollment conversion.</p>
    </div>
    <div class="quick-actions">
        <a href="{{ route('admissions.index') }}" class="btn btn-secondary">View Applications</a>
        <a href="{{ route('admissions.create') }}" class="btn btn-primary">New Application</a>
    </div>
</div>

<div class="metric-grid">
    <div class="metric"><div class="label">Total Applications</div><div class="value">{{ number_format($stats['total']) }}</div><div class="sub">{{ number_format($stats['this_month']) }} received this month</div></div>
    <div class="metric"><div class="label">Pending Review</div><div class="value">{{ number_format($stats['pending']) }}</div><div class="sub">Applications awaiting action</div></div>
    <div class="metric"><div class="label">Shortlisted</div><div class="value">{{ number_format($stats['shortlisted']) }}</div><div class="sub">Ready for next-stage processing</div></div>
    <div class="metric"><div class="label">Admitted</div><div class="value">{{ number_format($stats['admitted']) }}</div><div class="sub">{{ number_format($stats['conversion_rate'],1) }}% conversion rate</div></div>
    <div class="metric"><div class="label">Upcoming Interviews</div><div class="value">{{ number_format($stats['upcoming_interviews']) }}</div><div class="sub">Scheduled from today onward</div></div>
    <div class="metric"><div class="label">Offers Pending</div><div class="value">{{ number_format($stats['offers_pending']) }}</div><div class="sub">Admitted applicants awaiting offer issue</div></div>
    <div class="metric"><div class="label">Rejected</div><div class="value">{{ number_format($stats['rejected']) }}</div><div class="sub">Applications not approved</div></div>
    <div class="metric"><div class="label">Withdrawn</div><div class="value">{{ number_format($stats['withdrawn']) }}</div><div class="sub">Withdrawn applications</div></div>
</div>

<div class="two-col-dash">
    <div>
        <div class="dash-card">
            <div class="dash-head"><strong>Recent Applications</strong><a href="{{ route('admissions.index') }}" style="font-size:11px;text-decoration:none">View all</a></div>
            <div class="responsive-table">
                <table class="tbl-lite">
                    <thead><tr><th>Applicant</th><th>Application No.</th><th>Class</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($recentApplications as $application)
                        <tr>
                            <td><a href="{{ route('admissions.show',$application) }}" style="font-weight:600;text-decoration:none;color:var(--midnight)">{{ $application->first_name }} {{ $application->last_name }}</a></td>
                            <td>{{ $application->application_number }}</td>
                            <td>{{ optional($application->applyingForClassLevel)->name ?? '—' }}</td>
                            <td>{{ $application->application_date ? \Carbon\Carbon::parse($application->application_date)->format('d M Y') : '—' }}</td>
                            <td><span class="status {{ $application->status }}">{{ $application->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">No applications recorded yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div>
        <div class="dash-card">
            <div class="dash-head"><strong>Upcoming Interviews</strong></div>
            @forelse($upcomingInterviews as $application)
                <div class="list-row">
                    <div><strong>{{ $application->first_name }} {{ $application->last_name }}</strong><div class="muted">{{ optional($application->applyingForClassLevel)->name ?? 'Class not specified' }}</div></div>
                    <div style="text-align:right"><strong>{{ \Carbon\Carbon::parse($application->interview_date)->format('d M') }}</strong><div class="muted">{{ \Carbon\Carbon::parse($application->interview_date)->format('Y') }}</div></div>
                </div>
            @empty
                <div class="list-row"><span class="muted">No upcoming interviews.</span></div>
            @endforelse
        </div>

        <div class="dash-card">
            <div class="dash-head"><strong>Applications by Class</strong></div>
            @forelse($applicationsByClass as $row)
                <div class="list-row"><span>{{ optional($row->applyingForClassLevel)->name ?? 'Unspecified' }}</span><strong>{{ number_format($row->total) }}</strong></div>
            @empty
                <div class="list-row"><span class="muted">No application distribution available.</span></div>
            @endforelse
        </div>
    </div>
</div>
@endsection
