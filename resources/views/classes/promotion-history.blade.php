@extends('layouts.app')
@section('title','Promotion History')
@section('page-title','Promotion Engine')

@push('styles')
<style>
.tabs{display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content}
.tab{padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms}
.tab.active{background:var(--indigo);color:white}.tab:hover:not(.active){background:#F1F5F9}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.ch{padding:12px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);text-align:left}
td{padding:9px 14px;border-bottom:1px solid var(--border)}
.badge{display:inline-flex;font-size:11px;font-weight:600;padding:2px 9px;border-radius:20px}
.b-promoted{background:#ECFDF5;color:#059669}.b-repeat{background:#FEF2F2;color:#DC2626}
.fg{display:flex;flex-direction:column;gap:4px}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none}
</style>
@endpush

@section('content')
<div class="tabs">
    <a href="{{ route('settings.promotion') }}"         class="tab">⚙️ Rules</a>
    <a href="{{ route('classes.promotion.preview') }}" class="tab">🚀 Run Promotion</a>
    <a href="{{ route('classes.promotion.history') }}" class="tab active">📋 History</a>
    <a href="{{ route('classes.bulk-promote.page') }}" class="tab">👥 Manual Bulk</a>
</div>

<div style="margin-bottom:16px">
    <form method="GET">
        <div class="fg" style="flex-direction:row;align-items:flex-end;gap:10px">
            <div>
                <label style="font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:4px">Term</label>
                <select name="term_id" class="fc" onchange="this.form.submit()">
                    @foreach($terms as $t)
                    <option value="{{ $t->id }}" {{ $t->id==$termId ? 'selected':'' }}>
                        {{ $t->name }} — {{ optional($t->session)->name }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="ch">
        <span>Promotion Records</span>
        <span style="font-size:12px;color:var(--slate-light)">{{ $history->total() }} records</span>
    </div>
    <div style="overflow-x:auto">
    <table>
        <thead><tr><th>Student</th><th>Class (Term)</th><th>Average</th><th>Position</th><th>Status</th><th>Term</th></tr></thead>
        <tbody>
        @forelse($history as $s)
        <tr>
            <td>
                <div style="font-weight:600">{{ optional($s->student)->full_name }}</div>
                <div style="font-size:11px;color:var(--slate-light)">{{ optional($s->student)->admission_number }}</div>
            </td>
            <td>{{ optional(optional($s->classArm)->classLevel)->name }} {{ optional($s->classArm)->name }}</td>
            <td style="font-weight:700;color:{{ ($s->final_average??0) >= 50 ? 'var(--emerald)':'var(--crimson)' }}">
                {{ number_format($s->final_average??0,1) }}%
            </td>
            <td>{{ $s->position_in_class ? $s->position_in_class.'/'.$s->total_students_in_class : '—' }}</td>
            <td><span class="badge b-{{ $s->promotion_status }}">{{ ucfirst($s->promotion_status) }}</span></td>
            <td style="font-size:12px;color:var(--slate-light)">{{ optional(optional($s->term)->session)->name }}</td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--slate-light)">No promotion records for this term.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div style="padding:14px">{{ $history->links() }}</div>
</div>
@endsection
