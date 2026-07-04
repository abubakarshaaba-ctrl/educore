@extends('layouts.app')
@section('title','Subject Analysis')
@section('page-title','Subject Analysis')
@push('styles')
<style>
.filter-card{background:white;border:1px solid var(--border);border-radius:10px;padding:14px 18px;margin-bottom:16px;display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap}
.fg{display:flex;flex-direction:column;gap:5px}
.fl{font-size:10px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;min-width:200px}
.btn{display:inline-flex;align-items:center;gap:5px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:11px 14px;border-bottom:1px solid var(--border);font-size:13px}
tbody tr:last-child td{border-bottom:none}tbody tr:hover td{background:#F8FAFC}
.badge{display:inline-flex;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px}
.bg{background:#ECFDF5;color:var(--emerald)}.ba{background:#FFFBEB;color:var(--amber)}.br{background:#FEF2F2;color:var(--crimson)}
.bar-wrap{background:#F1F5F9;border-radius:4px;height:8px;width:120px;overflow:hidden;display:inline-block;vertical-align:middle}
.bar-fill{height:8px;border-radius:4px;background:var(--indigo)}
</style>
@endpush
@section('content')
<form method="GET">
<div class="filter-card">
    <div class="fg"><span class="fl">Term</span>
        <select name="term_id" class="fc">
            <option value="">Select term</option>
            @foreach($terms as $term)<option value="{{ $term->id }}" {{ request('term_id')==$term->id?'selected':'' }}>{{ $term->name }} — {{ $term->session->name }}</option>@endforeach
        </select>
    </div>
    <button type="submit" class="btn btn-p">Analyse</button>
</div>
</form>
@if($analysis)
<div class="card">
    <div class="ch">Subject Performance — {{ $terms->firstWhere('id', request('term_id'))?->name }}</div>
    <div class="tbl"><table>
        <thead><tr><th>Subject</th><th>Students</th><th>Average</th><th>Min Score</th><th>Max Score</th><th>Failing</th><th>Performance</th></tr></thead>
        <tbody>
        @foreach($analysis as $sub)
        @php $a=round($sub->avg,1); $failPct=$sub->students>0?round(($sub->failing/$sub->students)*100):0; @endphp
        <tr>
            <td><strong>{{ $sub->subject }}</strong></td>
            <td>{{ $sub->students }}</td>
            <td><span class="badge {{ $a>=70?'bg':($a>=50?'ba':'br') }}">{{ $a }}</span></td>
            <td style="color:var(--crimson)">{{ round($sub->min,1) }}</td>
            <td style="color:var(--emerald)">{{ round($sub->max,1) }}</td>
            <td style="color:{{ $failPct>20?'var(--crimson)':'' }}">{{ $sub->failing }} ({{ $failPct }}%)</td>
            <td><div class="bar-wrap"><div class="bar-fill" style="width:{{ min($a,100) }}%"></div></div></td>
        </tr>
        @endforeach
        </tbody>
    </table></div>
</div>
@else
<div style="background:white;border:1px solid var(--border);border-radius:12px;padding:50px;text-align:center;color:var(--slate-light)">Select a term above to generate analysis</div>
@endif
@endsection
