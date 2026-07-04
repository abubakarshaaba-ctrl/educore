@extends('layouts.app')
@section('title','New SMS Campaign')
@section('page-title','SMS Campaigns')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;width:100%}
.card-head{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC}
.card-title{font-size:13px;font-weight:700}
.card-body{padding:22px}
.fg{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}
.fl{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fl span{color:var(--crimson)}
.fc{padding:10px 12px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms;width:100%}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:5px;padding:10px 18px;font-size:13px;font-weight:600;font-family:inherit;border:none;border-radius:8px;cursor:pointer;text-decoration:none}
.btn-primary{background:var(--indigo);color:white}.btn-ghost{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.char-count{font-size:11px;color:var(--slate-light);text-align:right;margin-top:3px}
</style>
@endpush
@section('content')
<div style="display:flex;align-items:center;gap:10px;margin-bottom:18px">
    <a href="{{ route('sms.index') }}" class="btn btn-ghost">← Back</a>
    <h2 style="font-size:15px;font-weight:700">New SMS Campaign</h2>
</div>
<div class="card">
    <div class="card-head"><span class="card-title">📱 Compose Message</span></div>
    <div class="card-body">
    <form method="POST" action="{{ route('sms.store') }}">@csrf
        <div class="fg"><label class="fl">Campaign Title <span>*</span></label><input name="title" class="fc" required placeholder="e.g. End of Term Fee Reminder"></div>
        <div class="fg">
            <label class="fl">Audience <span>*</span></label>
            <select name="audience" class="fc" id="audienceSel" required>
                <option value="all_parents">All Parents / Guardians</option>
                <option value="all_staff">All Staff</option>
                <option value="class_parents">Parents of a Specific Class</option>
                <option value="custom">Custom Phone Numbers</option>
            </select>
        </div>
        <div id="classRow" class="fg" style="display:none">
            <label class="fl">Class</label>
            <select name="class_arm_id" class="fc">
                <option value="">Select class...</option>
                @foreach($classArms as $arm)<option value="{{ $arm->id }}">{{ optional($arm->classLevel)->name }} {{ $arm->name }}</option>@endforeach
            </select>
        </div>
        <div id="customRow" class="fg" style="display:none">
            <label class="fl">Phone Numbers (comma-separated)</label>
            <textarea name="phones" class="fc" rows="3" placeholder="08012345678, 07098765432, ..."></textarea>
        </div>
        <div class="fg">
            <label class="fl">Message <span>*</span></label>
            <textarea name="message" class="fc" id="msgArea" rows="5" required maxlength="1600" placeholder="Type your message here..."></textarea>
            <div class="char-count"><span id="charCount">0</span> / 160 chars · <span id="smsCount">1</span> SMS</div>
        </div>
        <div class="fg">
            <label class="fl">Schedule (leave blank to save as draft)</label>
            <input type="datetime-local" name="schedule_at" class="fc">
        </div>
        <div style="display:flex;gap:10px;margin-top:6px">
            <button type="submit" class="btn btn-primary">📱 Save Campaign</button>
            <a href="{{ route('sms.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
const sel = document.getElementById('audienceSel');
sel.addEventListener('change', () => {
    document.getElementById('classRow').style.display = sel.value === 'class_parents' ? '' : 'none';
    document.getElementById('customRow').style.display = sel.value === 'custom' ? '' : 'none';
});
const msg = document.getElementById('msgArea');
msg.addEventListener('input', () => {
    const l = msg.value.length;
    document.getElementById('charCount').textContent = l;
    document.getElementById('smsCount').textContent = Math.max(1, Math.ceil(l/160));
});
</script>
@endpush
