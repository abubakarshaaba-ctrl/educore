@extends('layouts.app')
@section('title', 'New CBT Question Bank')
@section('page-title', 'CBT Exams')

@push('styles')
<style>
.create-shell{max-width:940px;margin:0 auto}.crumbs{display:flex;align-items:center;gap:7px;margin-bottom:16px;font-size:11px;color:var(--slate)}.crumbs a{color:var(--indigo);text-decoration:none;font-weight:700}.create-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:18px}.eyebrow{font-size:9px;font-weight:800;letter-spacing:.15em;text-transform:uppercase;color:#A76B00;margin-bottom:6px}.create-head h1{margin:0;color:var(--midnight);font-size:24px}.create-head p{margin:6px 0 0;font-size:12px;color:var(--slate)}.back{display:inline-flex;align-items:center;padding:9px 12px;border:1px solid var(--border);border-radius:8px;background:#fff;color:var(--midnight);text-decoration:none;font-size:11px;font-weight:700}.card{background:#fff;border:1px solid var(--border);border-radius:15px;box-shadow:0 10px 30px rgba(15,23,42,.05);overflow:hidden}.card-top{padding:17px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:11px}.card-icon{width:34px;height:34px;border-radius:10px;background:#EEF4FC;color:#174F9E;display:grid;place-items:center;font-size:18px}.card-top strong{display:block;color:var(--midnight);font-size:14px}.card-top span{display:block;color:var(--slate);font-size:10px;margin-top:3px}.form{padding:20px}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px}.field.full{grid-column:1/-1}.label{display:block;margin-bottom:6px;color:var(--midnight);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.05em}.control{width:100%;min-height:42px;padding:10px 12px;border:1px solid var(--border);border-radius:9px;background:#F8FAFC;color:var(--midnight);font:inherit;font-size:12px;outline:none}.control:focus{background:#fff;border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,.08)}textarea.control{resize:vertical}.error{margin-top:5px;color:#B91C1C;font-size:10px}.form-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:18px;padding-top:17px;border-top:1px solid #EEF2F7}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:9px 15px;border-radius:8px;border:1px solid var(--border);background:#fff;color:var(--midnight);font:700 11px inherit;text-decoration:none;cursor:pointer}.btn-primary{background:var(--indigo);border-color:var(--indigo);color:#fff}.alert{border-radius:9px;padding:11px 14px;font-size:12px;margin-bottom:14px;background:#FEF2F2;border:1px solid #FECACA;color:#B91C1C}@media(max-width:640px){.create-head{align-items:flex-start;flex-direction:column}.form-grid{grid-template-columns:1fr}.field.full{grid-column:auto}.form{padding:16px}.create-head h1{font-size:21px}.form-actions{flex-direction:column-reverse}.btn{width:100%}}
</style>
@endpush

@section('content')
<div class="create-shell">
    <div class="crumbs"><a href="{{ route('cbt.banks') }}">Question banks</a><span>›</span><span>New bank</span></div>
    <header class="create-head"><div><div class="eyebrow">Assessment Studio</div><h1>New question bank</h1><p>Create a reusable question collection for one subject and class.</p></div><a class="back" href="{{ route('cbt.banks') }}">← Back to banks</a></header>
    @if($errors->any())<div class="alert">{{ $errors->first() }}</div>@endif
    <section class="card">
        <div class="card-top"><span class="card-icon">▣</span><div><strong>Bank details</strong><span>Required fields are marked.</span></div></div>
        <form class="form" method="POST" action="{{ route('cbt.banks.store') }}">@csrf
            <div class="form-grid">
                <div class="field full"><label class="label" for="name">Bank name *</label><input class="control" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. SS 3 Biology 2026" maxlength="150" required autofocus>@error('name')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="field"><label class="label" for="subject_id">Subject *</label><select class="control" id="subject_id" name="subject_id" required><option value="">Select subject</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}" @selected(old('subject_id') == $subject->id)>{{ $subject->name }}</option>@endforeach</select>@error('subject_id')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="field"><label class="label" for="class_level_id">Class level *</label><select class="control" id="class_level_id" name="class_level_id" required><option value="">Select class</option>@foreach($classLevels as $level)<option value="{{ $level->id }}" @selected(old('class_level_id') == $level->id)>{{ $level->name }}</option>@endforeach</select>@error('class_level_id')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="field full"><label class="label" for="description">Description</label><textarea class="control" id="description" name="description" rows="3" placeholder="Optional description">{{ old('description') }}</textarea>@error('description')<div class="error">{{ $message }}</div>@enderror</div>
            </div>
            <div class="form-actions"><a class="btn" href="{{ route('cbt.banks') }}">Cancel</a><button class="btn btn-primary" type="submit">Create question bank</button></div>
        </form>
    </section>
</div>
@endsection
