@extends('layouts.app')
@section('title','Bulk Upload Questions')
@section('page-title','Bulk Upload Questions')

@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;width:100%}
.card-head{padding:13px 20px;border-bottom:1px solid var(--border);background:#F8FAFC}
.card-title{font-size:13px;font-weight:700}
.card-body{padding:24px}
.drop-zone{border:2px dashed #CBD5E1;border-radius:12px;padding:40px 20px;text-align:center;cursor:pointer;transition:all 200ms;background:#F8FAFC}
.drop-zone:hover{border-color:var(--indigo);background:#EFF6FF}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;font-size:13px;font-weight:600;font-family:inherit;border:none;border-radius:8px;cursor:pointer;transition:all 150ms}
.btn-primary{background:var(--indigo);color:white}
.btn-ghost{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;padding:12px 16px;font-size:13px;color:#059669;margin-bottom:16px}
.alert-error{background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:12px 16px;font-size:13px;color:#DC2626;margin-bottom:16px}
</style>
@endpush

@section('content')
<div style="margin-bottom:16px;display:flex;gap:10px">
    <a href="{{ route('cbt.banks') }}" class="btn btn-ghost" style="padding:7px 14px;font-size:12px">← Question Banks</a>
    <a href="{{ route('cbt.bulk-template') }}" class="btn btn-ghost" style="padding:7px 14px;font-size:12px">⬇ Download Template</a>
</div>

@if(session('success'))<div class="alert-success">✓ {{ session('success') }}</div>@endif
@if(session('errors_list'))
<div class="alert-error">
    <strong>{{ count(session('errors_list')) }} errors:</strong>
    <ul style="margin:6px 0 0 16px">@foreach(session('errors_list') as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="card">
    <div class="card-head"><span class="card-title">📥 Bulk Upload Questions to: {{ $bank->name }}</span></div>
    <div class="card-body">
        <form method="POST" action="{{ route('cbt.bulk-import', $bank) }}" enctype="multipart/form-data">
            @csrf
            <div class="drop-zone" onclick="document.getElementById('qFile').click()">
                <div style="font-size:36px;margin-bottom:10px">📋</div>
                <div style="font-size:15px;font-weight:700" id="qFileLabel">Drop CSV file here or click to browse</div>
                <div style="font-size:13px;color:var(--slate-light);margin-top:4px">CSV format only &nbsp;·&nbsp; Max 5MB</div>
                <input type="file" id="qFile" name="file" accept=".csv,.txt" style="display:none" required
                       onchange="document.getElementById('qFileLabel').textContent = this.files[0]?.name">
            </div>

            <div style="margin:16px 0;padding:14px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;font-size:12px;color:#92400E">
                <strong>CSV columns:</strong> type (mcq/essay/short_answer/fill_blank/true_false), question_text,
                option_a, option_b, option_c, option_d, correct_option (a/b/c/d), explanation, difficulty (1-3), marks, model_answer (for essay)
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                ⚡ Import Questions
            </button>
        </form>
    </div>
</div>
@endsection
