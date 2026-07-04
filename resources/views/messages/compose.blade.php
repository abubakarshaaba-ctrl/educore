@extends('layouts.app')
@section('title','New Message')
@section('page-title','Messages')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;width:100%}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:20px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:16px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fl span{color:var(--crimson)}
.fc{padding:10px 13px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:9px;background:#F8FAFC;outline:none;width:100%;transition:border 200ms}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;font-size:13px;font-weight:600;font-family:inherit;border-radius:9px;border:none;cursor:pointer;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}
.btn-ghost{background:white;border:1px solid var(--border);color:var(--midnight)}
.back{font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px}
.alert-e{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--crimson);margin-bottom:14px}
</style>
@endpush
@section('content')
@if($errors->any())<div class="alert-e">{{ $errors->first() }}</div>@endif
<a href="{{ route('messages.inbox') }}" class="back">← Inbox</a>
<div class="card">
    <div class="ch">New Message</div>
    <div class="cb">
        <form method="POST" action="{{ route('messages.store') }}">
        @csrf
        <div class="fg">
            <label class="fl">Regarding Student <span>*</span></label>
            <select name="student_id" class="fc" required>
                <option value="">Search student...</option>
                @foreach($students as $s)
                <option value="{{ $s->id }}" {{ old('student_id') == $s->id ? 'selected' : '' }}>
                    {{ $s->full_name }} — {{ $s->admission_number }} ({{ optional(optional($s->currentClassArm)->classLevel)->name }} {{ optional($s->currentClassArm)->name }})
                </option>
                @endforeach
            </select>
        </div>
        <div class="fg">
            <label class="fl">Subject <span>*</span></label>
            <input type="text" name="subject" class="fc" required value="{{ old('subject') }}" placeholder="e.g. Regarding academic performance">
        </div>
        <div class="fg">
            <label class="fl">Message <span>*</span></label>
            <textarea name="body" class="fc" rows="6" required placeholder="Type your message here...">{{ old('body') }}</textarea>
        </div>
        <div style="display:flex;gap:10px">
            <button type="submit" class="btn btn-p">Send Message</button>
            <a href="{{ route('messages.inbox') }}" class="btn btn-ghost">Cancel</a>
        </div>
        </form>
    </div>
</div>
@endsection