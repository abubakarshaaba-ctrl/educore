<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Results — Parent Portal</title>

@include('parent.partials.base')
<style>
.nav{background:var(--midnight);padding:0 24px;height:58px;display:flex;align-items:center;justify-content:space-between}
.nav a{color:#94A3B8;text-decoration:none;font-size:13px;font-weight:600;padding:7px 12px;border-radius:7px}.nav a:hover{color:white;background:rgba(255,255,255,0.1)}
.nav-title{font-size:14px;font-weight:800;color:white}
.content{max-width:900px;margin:0 auto;padding:24px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.card-head{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:space-between}
table{width:100%;border-collapse:collapse;font-size:13px}th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94A3B8;text-align:left}td{padding:9px 14px;border-bottom:1px solid var(--border)}
select{padding:8px 12px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none}
</style>
@include('parent.partials.responsive')
{!! \App\Helpers\ThemeHelper::css() !!}
</head>
<body>
<nav class="nav">
    <span class="nav-title">Parent Portal</span>
    <div style="display:flex;gap:6px"><a href="{{ route('portal.parent.dashboard') }}">← Home</a><a href="{{ route('portal.parent.results') }}">Results</a><a href="{{ route('portal.parent.fees') }}">Fees</a><a href="{{ route('portal.parent.messages') }}">Messages</a></div>
</nav>
<div class="content">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;flex-wrap:wrap">
        @if($students->count() > 1)
        @foreach($students as $s)<a href="?student_id={{ $s->id }}&term_id={{ $termId }}" style="padding:7px 14px;font-size:13px;font-weight:600;background:{{ optional($student)->id==$s->id?'#2563EB':'white' }};color:{{ optional($student)->id==$s->id?'white':'#475569' }};border:1.5px solid {{ optional($student)->id==$s->id?'#2563EB':'#E2E8F0' }};border-radius:8px;text-decoration:none">{{ $s->first_name }}</a>@endforeach
        @endif
        <select onchange="location.href='?student_id={{ optional($student)->id }}&term_id='+this.value" style="margin-left:auto">
            @foreach($terms as $t)<option value="{{ $t->id }}" {{ $t->id==$termId?'selected':'' }}>{{ $t->name }} — {{ optional($t->session)->name }}</option>@endforeach
        </select>
    </div>
    @if($summary)
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px">
        @foreach(['final_average'=>['Average','%'],'position_in_class'=>['Position',''],'total_students_in_class'=>['Class Size',''],'subjects_offered'=>['Subjects','']] as $k=>[$l,$u])
        <div class="card" style="padding:16px;text-align:center"><div style="font-size:22px;font-weight:800">{{ $summary->$k ?? '—' }}{{ $u }}</div><div style="font-size:12px;color:#64748B;margin-top:3px">{{ $l }}</div></div>
        @endforeach
    </div>
    @if($summary->subject_breakdown)
    <div class="card"><div class="card-head">Subject Breakdown</div>
    <div class="tbl"><table><thead><tr><th>Subject</th><th>Score</th><th>Grade</th><th>Position</th></tr></thead>
    <tbody>
    @foreach($summary->subject_breakdown as $sub)
    <tr><td>{{ $sub['subject'] ?? '—' }}</td><td style="font-weight:700">{{ $sub['total'] ?? '—' }}</td><td>{{ $sub['grade'] ?? '—' }}</td><td>{{ $sub['position'] ?? '—' }}</td></tr>
    @endforeach
    </tbody></table></div></div>
    @endif
    @else
    <div style="text-align:center;padding:60px;background:white;border-radius:12px;border:1px solid var(--border);color:#94A3B8">No report card computed for this term yet.</div>
    @endif
</div>
</body></html>
