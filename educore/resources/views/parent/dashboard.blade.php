<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Parent Portal</title>

@include('parent.partials.base')
<style>
.nav{background:var(--midnight);padding:0 24px;height:58px;display:flex;align-items:center;justify-content:space-between}
.nav-logo{display:flex;align-items:center;gap:9px;text-decoration:none;min-width:0}
.nav-icon{width:34px;height:34px;background:var(--indigo);border-radius:8px;display:flex;align-items:center;justify-content:center;flex:0 0 34px}
.nav-icon svg{width:18px;height:18px;fill:white}
.nav-title{font-size:13px;font-weight:800;color:white;overflow-wrap:anywhere}
.nav-links{display:flex;gap:4px}
.nav-link{padding:7px 14px;font-size:12px;font-weight:600;color:#94A3B8;text-decoration:none;border-radius:7px;transition:all 150ms;white-space:nowrap}
.nav-link:hover,.nav-link.active{background:rgba(255,255,255,0.1);color:white}
.nav-right{display:flex;align-items:center;gap:10px;min-width:0}
.nav-user{font-size:12px;color:#94A3B8;overflow-wrap:anywhere}
.logout-btn{padding:6px 12px;font-size:12px;font-weight:600;background:rgba(239,68,68,0.15);color:#FCA5A5;border:none;border-radius:7px;cursor:pointer;font-family:inherit;min-height:34px}
.content{max-width:1100px;margin:0 auto;padding:24px}
.student-tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.s-tab{padding:8px 16px;font-size:13px;font-weight:600;background:white;border:1.5px solid var(--border);border-radius:8px;text-decoration:none;color:var(--slate);transition:all 150ms}
.s-tab.active,.s-tab:hover{background:var(--indigo);border-color:var(--indigo);color:white}
.student-heading{margin-bottom:18px;min-width:0}
.student-heading h2,.student-heading p{overflow-wrap:anywhere}
.stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-bottom:20px}
.stat-card{background:white;border:1px solid var(--border);border-radius:12px;padding:16px 20px;min-width:0}
.stat-val{font-size:24px;font-weight:800;color:var(--midnight);margin-bottom:4px;overflow-wrap:anywhere}
.stat-lbl{font-size:12px;color:#64748B;overflow-wrap:anywhere}
.card{background:white;border:1px solid var(--border);border-radius:12px;margin-bottom:16px;overflow:hidden;min-width:0}
.card-head{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700}
.card-body{padding:16px 18px;min-width:0}
.dashboard-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;min-width:0}
.dashboard-grid>div{min-width:0}
.quick-link{display:block;padding:10px 14px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;overflow-wrap:anywhere}
.ann-item{padding:10px 0;border-bottom:1px solid var(--border)}
.ann-item:last-child{border:none}
.ann-title{font-size:13px;font-weight:600;color:var(--midnight);overflow-wrap:anywhere}
.ann-date{font-size:11px;color:#94A3B8;margin-top:2px}
.badge{display:inline-flex;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px}
.b-green{background:#ECFDF5;color:#059669}.b-red{background:#FEF2F2;color:#DC2626}.b-amber{background:#FFFBEB;color:#D97706}
.empty-linked{text-align:center;padding:60px;color:#94A3B8;overflow-wrap:anywhere}
@media(max-width:760px){.dashboard-grid{grid-template-columns:1fr}.card-body{padding:14px 15px}.card-head{padding:12px 15px}}
@media(max-width:420px){.student-heading h2{font-size:16px!important}.empty-linked{padding:36px 14px}}
</style>
@include('parent.partials.responsive')
{!! \App\Helpers\ThemeHelper::css() !!}
</head>
<body>
<nav class="nav">
    <a href="{{ route('portal.parent.dashboard') }}" class="nav-logo">
        <div class="nav-icon"><svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg></div>
        <span class="nav-title">Parent Portal</span>
    </a>
    <div class="nav-links">
        <a href="{{ route('portal.parent.dashboard') }}" class="nav-link active">Home</a>
        <a href="{{ route('portal.parent.results') }}" class="nav-link">Results</a>
        <a href="{{ route('portal.parent.fees') }}" class="nav-link">Fees</a>
        <a href="{{ route('portal.parent.messages') }}" class="nav-link">Messages</a>
    </div>
    <div class="nav-right">
        <span class="nav-user">{{ optional($account->guardian)->first_name }}</span>
        <form method="POST" action="{{ route('portal.parent.logout') }}">@csrf<button class="logout-btn">Sign Out</button></form>
    </div>
</nav>
<div class="content">
    @if($students->count() > 1)
    <div class="student-tabs">
        @foreach($students as $s)
        <a href="?student_id={{ $s->id }}" class="s-tab {{ optional($student)->id == $s->id ? 'active':'' }}">
            {{ $s->first_name }} {{ $s->last_name }}
        </a>
        @endforeach
    </div>
    @endif

    @if($student)
    <div class="student-heading">
        <h2 style="font-size:18px;font-weight:800">{{ $student->full_name }}</h2>
        <p style="font-size:13px;color:#64748B;margin-top:3px">
            {{ optional(optional($student->currentClassArm)->classLevel)->name }} {{ optional($student->currentClassArm)->name }}
            · Adm No: {{ $student->admission_number }}
        </p>
    </div>
    <div class="stats">
        <div class="stat-card">
            <div class="stat-val" style="color:{{ optional($summary)->final_average >= 50 ? 'var(--emerald)':'var(--crimson)' }}">
                {{ $summary ? number_format($summary->final_average,1).'%' : '—' }}
            </div>
            <div class="stat-lbl">{{ optional($currentTerm)->name ?? 'Current Term' }} Average</div>
        </div>
        <div class="stat-card">
            <div class="stat-val">{{ $summary ? $summary->position_in_class.'/'.$summary->total_students_in_class : '—' }}</div>
            <div class="stat-lbl">Class Position</div>
        </div>
        <div class="stat-card">
            @php $att = $attendance ? ($attendance->total > 0 ? round(($attendance->present/$attendance->total)*100) : 0) : null; @endphp
            <div class="stat-val" style="color:{{ $att && $att >= 75 ? 'var(--emerald)':'var(--crimson)' }}">{{ $att !== null ? $att.'%' : '—' }}</div>
            <div class="stat-lbl">Attendance Rate</div>
        </div>
        <div class="stat-card">
            <div class="stat-val" style="color:{{ $outstandingFees > 0 ? 'var(--crimson)':'var(--emerald)' }}">₦{{ number_format($outstandingFees) }}</div>
            <div class="stat-lbl">Outstanding Fees</div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div>
            <div class="card">
                <div class="card-head">📋 Quick Actions</div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
                    <a class="quick-link" href="{{ route('portal.parent.results', ['student_id' => $student->id]) }}" style="background:#EFF6FF;color:var(--indigo)">📊 View Report Card</a>
                    <a class="quick-link" href="{{ route('portal.parent.fees', ['student_id' => $student->id]) }}" style="background:#F0FDF4;color:#059669">💳 View Fee Balance</a>
                </div>
            </div>
        </div>
        <div>
            <div class="card">
                <div class="card-head">📢 Announcements</div>
                <div class="card-body">
                    @forelse($announcements as $ann)
                    <div class="ann-item">
                        <div class="ann-title">{{ $ann->title }}</div>
                        <div class="ann-date">{{ $ann->publish_date ? \Carbon\Carbon::parse($ann->publish_date)->format('d M Y') : '' }}</div>
                    </div>
                    @empty
                    <div style="color:#94A3B8;font-size:13px">No announcements.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="empty-linked">No children linked to your account.</div>
    @endif
</div>
</body>
</html>
