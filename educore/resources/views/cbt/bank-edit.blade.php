@extends('layouts.app')
@section('title','Edit Question Bank')
@section('page-title','CBT Exams')
@push('styles')
<style>
.form-page{width:100%}
.pg-split{display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start}
@media(max-width:900px){.pg-split{grid-template-columns:1fr}}
.breadcrumb{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--slate-light);margin-bottom:20px}
.breadcrumb a{color:var(--indigo);text-decoration:none;font-weight:500}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.ch{padding:14px 20px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:22px 20px}
.fg{display:flex;flex-direction:column;gap:6px;margin-bottom:16px}
.fl{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:10px 12px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.fc:focus{border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none}
.btn-p{background:var(--indigo);color:white}.btn-g{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.alert-e{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px 14px;font-size:13px;color:var(--crimson);margin-bottom:14px}
</style>
@endpush
@section('content')
<div class="form-page">
<div class="pg-split">
<div>
    <div class="breadcrumb">
        <a href="{{ route('cbt.banks') }}">Question Banks</a>
        <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        <a href="{{ route('cbt.questions', $bank) }}">{{ $bank->name }}</a>
        <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        Edit Bank
    </div>
    @if($errors->any())<div class="alert-e">{{ $errors->first() }}</div>@endif
    <div class="card">
        <div class="ch">✏️ Edit Question Bank</div>
        <div class="cb">
        <form method="POST" action="{{ route('cbt.banks.update', $bank) }}">
            @csrf @method('PUT')
            <div class="fg"><label class="fl">Bank Name *</label>
                <input name="name" class="fc" value="{{ old('name', $bank->name) }}" required></div>
            <div class="fg"><label class="fl">Subject *</label>
                <select name="subject_id" class="fc" required>
                    @foreach($subjects as $s)
                    <option value="{{ $s->id }}" {{ $bank->subject_id == $s->id ? 'selected':'' }}>{{ $s->name }}</option>
                    @endforeach
                </select></div>
            <div class="fg"><label class="fl">Class Level *</label>
                <select name="class_level_id" class="fc" required>
                    @foreach($classLevels as $cl)
                    <option value="{{ $cl->id }}" {{ $bank->class_level_id == $cl->id ? 'selected':'' }}>{{ $cl->name }}</option>
                    @endforeach
                </select></div>
            <div class="fg"><label class="fl">Description</label>
                <textarea name="description" class="fc" rows="2">{{ old('description', $bank->description) }}</textarea></div>
            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-p">✓ Save Changes</button>
                <a href="{{ route('cbt.questions', $bank) }}" class="btn btn-g">Cancel</a>
            </div>
        </form>
        </div>
    </div>
</div>

<div>
    <div class="card" style="position:sticky;top:calc(var(--header-h) + 16px)">
        <div class="ch">Question Banks</div>
        <div class="cb" style="font-size:13px;color:var(--slate);line-height:1.7">
            <p style="margin-bottom:10px">A question bank groups related questions by subject and class level for use in CBT exams.</p>
            <p style="margin-bottom:10px"><strong style="color:var(--midnight)">Class Level</strong> determines which exams can draw questions from this bank.</p>
            <p>After saving, you can add individual questions or bulk-upload them via CSV.</p>
        </div>
    </div>
</div>
</div>
</div>
@endsection
