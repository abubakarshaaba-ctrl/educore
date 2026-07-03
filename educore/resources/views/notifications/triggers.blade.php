@extends('layouts.app')
@section('title','Notification Triggers')
@section('page-title','Notifications')

@push('styles')
<style>
.tabs{display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content}
.tab{padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms}
.tab.active{background:var(--indigo);color:white}.tab:hover:not(.active){background:#F1F5F9}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:18px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
.trigger-row{display:flex;align-items:flex-start;gap:16px;padding:16px 18px;border-bottom:1px solid var(--border)}
.trigger-row:last-child{border:none}
.trigger-toggle{flex-shrink:0;margin-top:2px}
.trigger-body{flex:1}
.trigger-name{font-size:13px;font-weight:700;color:var(--midnight)}
.trigger-desc{font-size:12px;color:var(--slate-light);margin-top:2px}
.trigger-opts{display:grid;grid-template-columns:140px 1fr;gap:10px;margin-top:10px}
.fl{font-size:10px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:4px}
.fc{padding:7px 10px;font-size:12px;font-family:inherit;border:1.5px solid var(--border);border-radius:7px;background:#F8FAFC;outline:none;width:100%}
.fc:focus{border-color:var(--indigo)}
.toggle{position:relative;display:inline-block;width:44px;height:24px}
.toggle input{opacity:0;width:0;height:0}
.slider{position:absolute;cursor:pointer;inset:0;background:#CBD5E1;border-radius:24px;transition:.3s}
.slider::before{content:"";position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:white;border-radius:50%;transition:.3s}
input:checked+.slider{background:var(--indigo)}
input:checked+.slider::before{transform:translateX(20px)}
.ph-list{font-size:11px;color:var(--slate-light);margin-top:6px;display:flex;gap:6px;flex-wrap:wrap}
.ph{background:#F1F5F9;color:var(--slate);padding:2px 6px;border-radius:4px;font-size:11px;cursor:pointer;font-weight:600}
.ph:hover{background:var(--indigo);color:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none}
.btn-p{background:var(--indigo);color:white}
.btn-ghost{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:14px}
table{width:100%;border-collapse:collapse;font-size:12px}
th{padding:8px 12px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);text-align:left}
td{padding:8px 12px;border-bottom:1px solid var(--border);color:var(--midnight)}
.b-q{background:#EFF6FF;color:var(--indigo)}.b-s{background:#ECFDF5;color:#059669}.b-f{background:#FEF2F2;color:#DC2626}
.badge{display:inline-flex;font-size:10px;font-weight:600;padding:2px 7px;border-radius:20px}
</style>
@endpush

@section('content')
@if(!auth()->user()->canManage('notifications'))
<div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:40px;text-align:center;margin-top:20px">
    <div style="font-size:28px;margin-bottom:12px">🔒</div>
    <div style="font-size:15px;font-weight:700;color:#DC2626">Access Restricted</div>
    <div style="font-size:13px;color:#64748B;margin-top:6px">Auto Triggers management is for Administrators only.</div>
</div>
@else
<div class="tabs">
    <a href="{{ route('notifications.index') }}"  class="tab">📨 Send</a>
    <a href="{{ route('notifications.logs') }}"   class="tab">📋 Logs</a>
    <a href="{{ route('notifications.triggers') }}" class="tab active">⚡ Auto Triggers</a>
    <a href="{{ route('notifications.settings') }}" class="tab">⚙️ Settings</a>
</div>

@if(session('success'))<div class="alert-s">✓ {{ session('success') }}</div>@endif

<div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:10px;padding:12px 16px;font-size:13px;color:#1D4ED8;margin-bottom:18px">
    ⚡ <strong>Auto Triggers</strong> send SMS or email notifications automatically when specific events happen — fee payments, report cards published, absences, and more.
</div>

<form method="POST" action="{{ route('notifications.triggers.save') }}">
@csrf
@php
$eventDefs = [
    'fee_payment_received'      => ['💳 Fee Payment Received', 'Sent to guardian when a fee payment is recorded', ['{student_name}','{amount}','{balance}','{school_name}','{date}']],
    'report_card_published'     => ['📊 Report Card Published', 'Sent to parent when report cards are published for a term', ['{student_name}','{term}','{average}','{position}','{school_name}']],
    'student_absent'            => ['🏫 Student Absent', 'Sent to parent when student is marked absent', ['{student_name}','{date}','{school_name}']],
    'exam_scheduled'            => ['📝 CBT Exam Scheduled', 'Sent to students/parents when a CBT exam is published', ['{exam_title}','{date}','{subject}','{school_name}']],
    'admission_status_changed'  => ['🎓 Admission Status Changed', 'Sent when application status is updated (shortlisted/admitted/rejected)', ['{student_name}','{status}','{school_name}','{date}']],
    'fee_overdue'               => ['⚠️ Fee Overdue Reminder', 'Sent when invoice is past due date', ['{student_name}','{amount}','{due_date}','{school_name}']],
    'invoice_generated'         => ['🧾 Invoice Generated', 'Sent when a new fee invoice is created for a student', ['{student_name}','{amount}','{term}','{school_name}']],
];
$defaultTemplates = [
    'fee_payment_received'     => 'Dear Parent, payment of ₦{amount} received for {student_name}. Outstanding balance: ₦{balance}. Thank you. — {school_name}',
    'report_card_published'    => 'Dear Parent, {student_name}\'s report card for {term} is now available. Average: {average}%, Position: {position}. Login to parent portal to view. — {school_name}',
    'student_absent'           => 'Dear Parent, {student_name} was absent from school on {date}. Please contact us if this was not planned. — {school_name}',
    'exam_scheduled'           => 'Reminder: {student_name} has a CBT exam ({exam_title}) scheduled. Please ensure they are prepared. — {school_name}',
    'admission_status_changed' => 'Dear {student_name}, your admission application status has been updated to: {status}. Contact {school_name} for details.',
    'fee_overdue'              => 'Dear Parent, the fee invoice of ₦{amount} for {student_name} was due on {due_date}. Please make payment to avoid disruption. — {school_name}',
    'invoice_generated'        => 'Dear Parent, a fee invoice of ₦{amount} has been generated for {student_name} for {term}. — {school_name}',
];
@endphp

<div class="card">
    <div class="ch">⚡ Event Triggers</div>
    @foreach($eventDefs as $event => [$label, $desc, $placeholders])
    @php $t = $triggers->get($event); @endphp
    <div class="trigger-row">
        <div class="trigger-toggle">
            <label class="toggle">
                <input type="checkbox" name="enabled_{{ $event }}" value="1" id="tgl_{{ $event }}"
                    {{ optional($t)->is_enabled ? 'checked':'' }}
                    onchange="toggleOpts('{{ $event }}', this.checked)">
                <span class="slider"></span>
            </label>
        </div>
        <div class="trigger-body">
            <div class="trigger-name">{{ $label }}</div>
            <div class="trigger-desc">{{ $desc }}</div>
            <div id="opts_{{ $event }}" style="{{ optional($t)->is_enabled ? '':'display:none' }}">
                <div class="trigger-opts" style="margin-top:12px">
                    <div>
                        <label class="fl">Channel</label>
                        <select name="channel_{{ $event }}" class="fc">
                            <option value="sms"   {{ optional($t)->channel === 'sms'   ? 'selected':'' }}>SMS</option>
                            <option value="email" {{ optional($t)->channel === 'email' ? 'selected':'' }}>Email</option>
                            <option value="both"  {{ optional($t)->channel === 'both'  ? 'selected':'' }}>SMS + Email</option>
                        </select>
                    </div>
                    <div>
                        <label class="fl">Message Template</label>
                        <textarea name="template_{{ $event }}" class="fc" rows="3" id="tmpl_{{ $event }}"
                            placeholder="Type your message...">{{ optional($t)->template ?: ($defaultTemplates[$event] ?? '') }}</textarea>
                        <div class="ph-list">
                            <span style="font-size:11px;color:var(--slate-light)">Placeholders:</span>
                            @foreach($placeholders as $ph)
                            <span class="ph" onclick="insertPh('tmpl_{{ $event }}','{{ $ph }}')">{{ $ph }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div style="display:flex;gap:10px;align-items:center">
    <button type="submit" class="btn btn-p">💾 Save All Triggers</button>
    <span style="font-size:12px;color:var(--slate-light)">Changes take effect immediately for new events</span>
</div>
</form>

{{-- Test trigger --}}
<div class="card" style="margin-top:20px">
    <div class="ch">🧪 Test a Trigger</div>
    <div style="padding:16px 18px">
    <form method="POST" action="{{ route('notifications.triggers.test') }}" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
        @csrf
        <div>
            <label class="fl" style="font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;display:block;margin-bottom:4px">Event</label>
            <select name="event" class="fc" style="min-width:200px">
                @foreach($eventDefs as $event => [$label])
                <option value="{{ $event }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="fl" style="font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;display:block;margin-bottom:4px">Test Phone Number</label>
            <input type="text" name="phone" class="fc" placeholder="08012345678" style="min-width:160px">
        </div>
        <button type="submit" class="btn btn-ghost">📤 Send Test</button>
    </form>
    </div>
</div>

{{-- Recent trigger logs --}}
<div class="card" style="margin-top:18px">
    <div class="ch">📋 Recent Trigger Activity</div>
    <div class="tbl"><table>
        <thead><tr><th>Event</th><th>Channel</th><th>Recipient</th><th>Status</th><th>Time</th></tr></thead>
        <tbody>
        @forelse($logs as $log)
        <tr>
            <td style="font-weight:600">{{ str_replace('_',' ',ucfirst($log->event)) }}</td>
            <td>{{ ucfirst($log->channel) }}</td>
            <td>{{ $log->recipient }}</td>
            <td><span class="badge b-{{ substr($log->status,0,1) === 'q' ? 'q':($log->status==='sent'?'s':'f') }}">{{ ucfirst($log->status) }}</span></td>
            <td style="font-size:11px;color:var(--slate-light)">{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--slate-light)">No trigger activity yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
@endif
@endsection

@push('scripts')
<script>
function toggleOpts(event, show) {
    document.getElementById('opts_'+event).style.display = show ? '' : 'none';
}
function insertPh(id, ph) {
    const ta = document.getElementById(id);
    const s = ta.selectionStart, e = ta.selectionEnd;
    ta.value = ta.value.slice(0,s) + ph + ta.value.slice(e);
    ta.selectionStart = ta.selectionEnd = s + ph.length;
    ta.focus();
}
</script>
@endpush
