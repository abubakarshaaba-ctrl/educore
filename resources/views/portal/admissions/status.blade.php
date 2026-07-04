<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Application Status — {{ $tenant->name }}</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#F1F5F9;min-height:100vh;padding:24px 16px}
.container{max-width:600px;margin:0 auto}
.topbar{background:white;border-radius:12px;padding:14px 18px;margin-bottom:16px;display:flex;align-items:center;gap:12px;border:1px solid #E2E8F0}
.school-name{font-weight:700;color:#1E293B;font-size:15px}
.card{background:white;border-radius:16px;padding:28px;border:1px solid #E2E8F0;box-shadow:0 1px 3px rgba(0,0,0,0.06);margin-bottom:14px}
.status-banner{border-radius:12px;padding:20px;text-align:center;margin-bottom:20px}
.status-pending{background:#FFFBEB;border:1px solid #FDE68A}
.status-shortlisted{background:#EFF6FF;border:1px solid #BFDBFE}
.status-admitted{background:#ECFDF5;border:1px solid #A7F3D0}
.status-rejected{background:#FEF2F2;border:1px solid #FECACA}
.status-withdrawn{background:#F1F5F9;border:1px solid #E2E8F0}
.status-icon{font-size:40px;margin-bottom:10px}
.status-label{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px}
.status-pending .status-label{color:#92400E}
.status-shortlisted .status-label{color:#1E40AF}
.status-admitted .status-label{color:#065F46}
.status-rejected .status-label{color:#991B1B}
.status-msg{font-size:15px;font-weight:700;color:#1E293B}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.info-item .k{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94A3B8;margin-bottom:3px}
.info-item .v{font-size:14px;font-weight:600;color:#1E293B}
.doc-list{list-style:none}
.doc-item{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid #F1F5F9;font-size:13px;color:#475569}
.doc-item:last-child{border-bottom:none}
.doc-icon{font-size:18px}
.btn{display:block;text-align:center;padding:12px 20px;font-size:14px;font-weight:700;background:#2563EB;color:white;border-radius:10px;text-decoration:none;margin-bottom:8px;transition:all 200ms}
.btn-outline{background:white;color:#2563EB;border:2px solid #2563EB}
.app-num{font-family:monospace;font-size:13px;color:#64748B;background:#F8FAFC;padding:6px 12px;border-radius:6px;display:inline-block;margin-top:4px}
</style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <div style="font-size:18px">&#127981;</div>
        <div>
            <div class="school-name">{{ $tenant->name }}</div>
            <div style="font-size:12px;color:#94A3B8">Application Status</div>
        </div>
    </div>

    @php
        $statusIcons = ['pending'=>'⏳','shortlisted'=>'⭐','admitted'=>'🎉','rejected'=>'❌','withdrawn'=>'↩️'];
        $statusMessages = [
            'pending'     => 'Your application is being reviewed.',
            'shortlisted' => 'Congratulations! Your application has been shortlisted. We will contact you for the next step.',
            'admitted'    => 'Congratulations! Your ward has been offered admission to our school. Please contact us to complete enrollment.',
            'rejected'    => 'We regret to inform you that your application was not successful this time. Please contact the school for more information.',
            'withdrawn'   => 'This application has been withdrawn.',
        ];
    @endphp

    <div class="card">
        <div class="status-banner status-{{ $admission->status }}">
            <div class="status-icon">{{ $statusIcons[$admission->status] ?? '📋' }}</div>
            <div class="status-label">{{ ucfirst($admission->status) }}</div>
            <div class="status-msg">{{ $statusMessages[$admission->status] ?? '' }}</div>
        </div>

        <div class="info-grid">
            <div class="info-item"><div class="k">Applicant</div><div class="v">{{ $admission->first_name }} {{ $admission->last_name }}</div></div>
            <div class="info-item"><div class="k">Application No</div><div class="v"><span class="app-num">{{ $admission->application_number }}</span></div></div>
            <div class="info-item"><div class="k">Class Applied</div><div class="v">{{ optional($classLevel)->name ?? '—' }}</div></div>
            <div class="info-item"><div class="k">Date Applied</div><div class="v">{{ \Carbon\Carbon::parse($admission->application_date)->format('d M Y') }}</div></div>
            @if($admission->interview_date)
            <div class="info-item"><div class="k">Interview Date</div><div class="v">{{ \Carbon\Carbon::parse($admission->interview_date)->format('d M Y') }}</div></div>
            @endif
            @if($admission->decision_date)
            <div class="info-item"><div class="k">Decision Date</div><div class="v">{{ \Carbon\Carbon::parse($admission->decision_date)->format('d M Y') }}</div></div>
            @endif
        </div>
    </div>

    @if($documents->count())
    <div class="card">
        <div style="font-size:13px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px">Submitted Documents</div>
        <ul class="doc-list">
            @foreach($documents as $doc)
            <li class="doc-item">
                <span class="doc-icon">&#128196;</span>
                {{ ucwords(str_replace('_',' ',$doc->document_type)) }}
                <span style="margin-left:auto;font-size:11px;color:#94A3B8">Uploaded</span>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    <a href="{{ route('portal.landing', $tenant->slug) }}" class="btn btn-outline">Back to Portal</a>
    @if($tenant->phone)<p style="text-align:center;font-size:13px;color:#94A3B8;margin-top:12px">Questions? Call: <strong>{{ $tenant->phone }}</strong></p>@endif
</div>
</body>
</html>
