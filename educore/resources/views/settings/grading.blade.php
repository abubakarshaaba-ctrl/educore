@extends('layouts.app')
@section('title','Grading System')
@section('page-title','School Settings')
@push('styles')
<style>
.page-grid{display:grid;grid-template-columns:220px minmax(0,1fr);gap:16px}.page-grid>*{min-width:0}
.snav{background:white;border:1px solid var(--border);border-radius:12px;padding:6px;position:sticky;top:76px}.sn{display:block;padding:9px 13px;border-radius:8px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;margin-bottom:2px;transition:all 150ms}.sn:hover{background:#F1F5F9;color:var(--midnight)}.sn.on{background:var(--indigo-bg);color:var(--indigo)}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
.grade-table-wrap{width:100%;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}.grade-table{width:100%;min-width:660px;border-collapse:collapse}thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border);white-space:nowrap}tbody td{padding:10px 14px;border-bottom:1px solid var(--border);font-size:13px;vertical-align:middle}tbody tr:last-child td{border-bottom:none}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px;min-width:0}.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%;min-width:0;transition:border 200ms}.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms}.btn-p{background:var(--indigo);color:white}.btn-r{background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA}.btn-sm{padding:4px 10px;font-size:11px}.grade-badge{display:inline-flex;font-size:11px;font-weight:700;padding:2px 8px;border-radius:6px;background:var(--indigo-bg);color:var(--indigo)}.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
.add-grade-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr)) minmax(100px,.75fr);gap:10px;align-items:end}.page-tabs{overflow-x:auto;-webkit-overflow-scrolling:touch;white-space:nowrap}.page-tab{display:inline-flex;white-space:nowrap}
@media(max-width:1050px){.add-grade-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:768px){.page-grid{grid-template-columns:1fr}.snav{position:relative;top:0;display:flex;gap:4px;overflow-x:auto;-webkit-overflow-scrolling:touch;white-space:nowrap}.sn{flex:0 0 auto;margin-bottom:0}.add-grade-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.card .btn-p{width:100%}}
@media(max-width:520px){.add-grade-grid{grid-template-columns:1fr}.grade-table{min-width:600px}.ch{padding:11px 13px}.grade-table th,.grade-table td{padding:8px 10px}.card>div[style*="padding:16px"]{padding:13px!important}}
</style>
@endpush
@section('content')
<div class="page-tabs" style="margin-bottom:20px">
    <a href="{{ route('settings.index') }}" class="page-tab {{ request()->routeIs('settings.index') ? 'active' : '' }}">School Info</a>
    <a href="{{ route('settings.grading') }}" class="page-tab {{ request()->routeIs('settings.grading') ? 'active' : '' }}">Grading Scale</a>
    <a href="{{ route('settings.promotion') }}" class="page-tab {{ request()->routeIs('settings.promotion') ? 'active' : '' }}">Promotion Rules</a>
</div>
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
<div class="page-grid">
  <div class="snav">
    <a href="{{ route('settings.index') }}" class="sn">General Info</a>
    <a href="{{ route('settings.grading') }}" class="sn on">Grading System</a>
  </div>
  <div>
    @foreach($levels as $level)
    @php $levelGrades = $grading->where('class_level_id',$level->id)->sortByDesc('min_score'); @endphp
    <div class="card">
        <div class="ch">{{ $level->name }}</div>
        @if($levelGrades->count())
        <div class="grade-table-wrap"><table class="grade-table">
            <thead><tr><th>Grade</th><th>Min Score</th><th>Max Score</th><th>Remark</th><th>Pass?</th><th></th></tr></thead>
            <tbody>
            @foreach($levelGrades as $g)
            <tr>
                <td><span class="grade-badge">{{ $g->grade_letter }}</span></td>
                <td>{{ $g->min_score }}</td>
                <td>{{ $g->max_score }}</td>
                <td>{{ $g->remark }}</td>
                <td>{{ $g->is_pass_grade ? '✅ Pass' : '❌ Fail' }}</td>
                <td>
                    <form method="POST" action="{{ route('settings.grading.destroy',$g) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-r btn-sm" onclick="return confirm('Remove this grade?')">Remove</button>
                    </form>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table></div>
        @else
        <div style="padding:16px;color:var(--slate-light);font-size:13px">No grades configured for {{ $level->name }}</div>
        @endif
    </div>
    @endforeach
    <div class="card">
        <div class="ch">Add Grade</div>
        <div style="padding:16px">
            <form method="POST" action="{{ route('settings.grading.store') }}">
            @csrf
            <div class="add-grade-grid">
                <div class="fg" style="margin:0"><label class="fl">Class Level *</label>
                    <select name="class_level_id" class="fc" required>
                        <option value="">Select</option>
                        @foreach($levels as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
                    </select>
                </div>
                <div class="fg" style="margin:0"><label class="fl">Grade *</label><input type="text" name="grade_letter" class="fc" required placeholder="A1" maxlength="5"></div>
                <div class="fg" style="margin:0"><label class="fl">Min *</label><input type="number" name="min_score" class="fc" required min="0" max="100"></div>
                <div class="fg" style="margin:0"><label class="fl">Max *</label><input type="number" name="max_score" class="fc" required min="0" max="100"></div>
                <div class="fg" style="margin:0"><label class="fl">Remark *</label><input type="text" name="remark" class="fc" required placeholder="Excellent"></div>
                <div>
                    <label class="fl" style="display:block;margin-bottom:5px">Pass?</label>
                    <select name="is_pass_grade" class="fc"><option value="1">Pass</option><option value="0">Fail</option></select>
                </div>
            </div>
            <button type="submit" class="btn btn-p" style="margin-top:12px">Add Grade</button>
            </form>
        </div>
    </div>
  </div>
</div>
@endsection