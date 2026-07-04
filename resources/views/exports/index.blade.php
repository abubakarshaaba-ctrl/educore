@extends('layouts.app')
@section('title','Export Data')
@section('page-title','Export Data')
@push('styles')
<style>
.export-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.export-card{background:white;border:1px solid var(--border);border-radius:12px;padding:24px;display:flex;flex-direction:column;gap:12px}
.export-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px}
.export-title{font-size:15px;font-weight:700;color:var(--midnight)}
.export-desc{font-size:13px;color:var(--slate);line-height:1.5}
.fg{display:flex;flex-direction:column;gap:5px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:1px solid transparent;cursor:pointer;transition:all 150ms;text-decoration:none;justify-content:center;width:100%}
.btn-p{background:var(--indigo);color:white}.btn-g{background:var(--emerald);color:white}
@media(max-width:768px){.export-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<div class="export-grid">
    <div class="export-card">
        <div class="export-icon" style="background:#EFF6FF">📊</div>
        <div class="export-title">Broadsheet CSV</div>
        <div class="export-desc">Export full class broadsheet with all students, scores, averages and positions.</div>
        <form method="GET" action="{{ route('exports.broadsheet') }}">
            <div class="fg" style="margin-bottom:10px">
                <label class="fl">Class</label>
                <select name="class_arm_id" class="fc" required>
                    <option value="">Select class</option>
                    @foreach($classArms as $arm)
                        <option value="{{ $arm->id }}">{{ $arm->classLevel->name }} {{ $arm->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fg" style="margin-bottom:12px">
                <label class="fl">Term</label>
                <select name="term_id" class="fc" required>
                    <option value="">Select term</option>
                    @foreach($terms as $t)
                        <option value="{{ $t->id }}">{{ $t->name }} — {{ $t->session->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-p">⬇ Download Broadsheet</button>
        </form>
    </div>
    <div class="export-card">
        <div class="export-icon" style="background:#ECFDF5">👥</div>
        <div class="export-title">Student List CSV</div>
        <div class="export-desc">Export all active students with class assignments and personal details.</div>
        <div class="fg" style="margin-bottom:12px">
            <label class="fl">Filter by Class (optional)</label>
            <select id="scls" class="fc">
                <option value="">All Classes</option>
                @foreach($classArms as $arm)
                    <option value="{{ $arm->id }}">{{ $arm->classLevel->name }} {{ $arm->name }}</option>
                @endforeach
            </select>
        </div>
        <a href="{{ route('exports.students') }}" id="sbtn" class="btn btn-g">⬇ Download Students</a>
        <script>document.getElementById('scls').addEventListener('change',function(){document.getElementById('sbtn').href='{{ route("exports.students") }}'+(this.value?'?class_arm_id='+this.value:'');});</script>
    </div>
    <div class="export-card">
        <div class="export-icon" style="background:#FFFBEB">💰</div>
        <div class="export-title">Fee Report CSV</div>
        <div class="export-desc">Export fee collection data with billed, paid and outstanding balances.</div>
        <div class="fg" style="margin-bottom:12px">
            <label class="fl">Session</label>
            <select id="fsess" class="fc">
                <option value="">All Sessions</option>
                @foreach($sessions as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <a href="{{ route('exports.fees') }}" id="fbtn" class="btn" style="background:#D97706;color:white;border-color:#D97706">⬇ Download Fee Report</a>
        <script>document.getElementById('fsess').addEventListener('change',function(){document.getElementById('fbtn').href='{{ route("exports.fees") }}'+(this.value?'?session_id='+this.value:'');});</script>
    </div>
</div>
@endsection
