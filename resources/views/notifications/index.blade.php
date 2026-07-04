@extends('layouts.app')
@section('title', 'Send Message')
@section('page-title', 'Messaging')

@push('styles')
<style>
    .page-tabs { display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content; }
    .page-tab { padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms; }
    .page-tab.active { background:var(--indigo);color:white; }
    .page-tab:hover:not(.active) { background:#F1F5F9; }

    .stats-row { display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px; }
    .stat { background:white;border:1px solid var(--border);border-radius:10px;padding:14px;text-align:center; }
    .stat-val { font-size:22px;font-weight:700;color:var(--midnight);letter-spacing:-0.02em; }
    .stat-lbl { font-size:10px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:0.05em;margin-top:3px; }

    .compose-grid { display:grid;grid-template-columns:1fr 380px;gap:20px;align-items:start; }
    .card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden; }
    .card-header { padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; }
    .card-title { font-size:14px;font-weight:600;color:var(--midnight); }
    .card-body { padding:20px; }

    .form-group { margin-bottom:16px; }
    .form-label { display:block;font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px; }
    .form-label span { color:var(--crimson); }
    .form-control { width:100%;padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms; }
    .form-control:focus { border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1);background:white; }
    textarea.form-control { resize:vertical;min-height:100px; }
    .char-count { font-size:11px;color:var(--slate-light);text-align:right;margin-top:4px; }

    .channel-toggle { display:flex;gap:8px;margin-bottom:4px; }
    .channel-option { flex:1; }
    .channel-radio { display:none; }
    .channel-label { display:flex;align-items:center;justify-content:center;gap:6px;padding:9px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-weight:600;color:var(--slate);cursor:pointer;transition:all 150ms; }
    .channel-radio:checked + .channel-label { border-color:var(--indigo);background:var(--indigo-bg);color:var(--indigo); }

    .recipient-type { display:flex;gap:8px;margin-bottom:4px; }
    .recipient-option { flex:1; }
    .recipient-radio { display:none; }
    .recipient-label { display:flex;align-items:center;justify-content:center;padding:8px;border:1.5px solid var(--border);border-radius:7px;font-size:12px;font-weight:600;color:var(--slate);cursor:pointer;transition:all 150ms;text-align:center; }
    .recipient-radio:checked + .recipient-label { border-color:var(--indigo);background:var(--indigo-bg);color:var(--indigo); }

    .conditional-field { display:none; }
    .conditional-field.show { display:block; }

    .btn { display:inline-flex;align-items:center;gap:6px;padding:10px 20px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white;width:100%;justify-content:center; }
    .btn-primary:hover { background:#1D4ED8; }

    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }

    .log-item { padding:12px 0;border-bottom:1px solid var(--border);font-size:13px; }
    .log-item:last-child { border-bottom:none; }
    .log-meta { display:flex;align-items:center;justify-content:space-between;margin-bottom:4px; }
    .log-recipient { font-weight:600;color:var(--midnight); }
    .log-message { font-size:12px;color:var(--slate);white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
    .badge { display:inline-flex;font-size:10px;font-weight:600;padding:2px 7px;border-radius:20px; }
    .badge-success { background:#ECFDF5;color:var(--emerald); }
    .badge-warning { background:#FFFBEB;color:var(--amber); }
    .badge-error   { background:#FEF2F2;color:var(--crimson); }
    .badge-info    { background:var(--indigo-bg);color:var(--indigo); }
    .empty-state { text-align:center;padding:30px;color:var(--slate-light);font-size:13px; }
    @media(max-width:1024px) { .compose-grid { grid-template-columns:1fr; } .stats-row { grid-template-columns:repeat(2,1fr); } }
</style>
@endpush

@section('content')
{{-- NOTE: This file is notifications/compose.blade.php --}}
{{-- Route notifications.index points here --}}

<div class="page-tabs">
    <a href="{{ route('notifications.index') }}" class="page-tab active">Compose</a>
    <a href="{{ route('notifications.logs') }}" class="page-tab">Message Logs</a>
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

<div class="stats-row">
    <div class="stat"><div class="stat-val" style="color:var(--indigo)">{{ $stats['sent_today'] }}</div><div class="stat-lbl">Sent Today</div></div>
    <div class="stat"><div class="stat-val" style="color:var(--emerald)">{{ $stats['total_sent'] }}</div><div class="stat-lbl">Total Sent</div></div>
    <div class="stat"><div class="stat-val" style="color:var(--crimson)">{{ $stats['failed'] }}</div><div class="stat-lbl">Failed</div></div>
    <div class="stat"><div class="stat-val">&#8358;{{ number_format($stats['sms_cost']) }}</div><div class="stat-lbl">SMS Cost</div></div>
</div>

<div class="compose-grid">
    @if(auth()->user()->canAccessRoute('notifications.send'))
    <div class="card">
        <div class="card-header"><span class="card-title">Compose Message</span></div>
        <div class="card-body">
            <form method="POST" action="{{ route('notifications.send') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label">Channel <span>*</span></label>
                    <div class="channel-toggle">
                        <div class="channel-option">
                            <input type="radio" name="channel" id="ch_sms" value="sms" class="channel-radio" checked>
                            <label for="ch_sms" class="channel-label">📱 SMS</label>
                        </div>
                        <div class="channel-option">
                            <input type="radio" name="channel" id="ch_email" value="email" class="channel-radio">
                            <label for="ch_email" class="channel-label">✉️ Email</label>
                        </div>
                    </div>
                </div>

                <div class="form-group conditional-field" id="email-subject-field">
                    <label class="form-label">Email Subject</label>
                    <input type="text" name="subject" class="form-control" placeholder="Message subject" value="{{ old('subject') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Send To <span>*</span></label>
                    <div class="recipient-type">
                        <div class="recipient-option">
                            <input type="radio" name="recipient_type" id="rt_all" value="all" class="recipient-radio" checked>
                            <label for="rt_all" class="recipient-label">All Parents</label>
                        </div>
                        <div class="recipient-option">
                            <input type="radio" name="recipient_type" id="rt_class" value="class" class="recipient-radio">
                            <label for="rt_class" class="recipient-label">By Class</label>
                        </div>
                        <div class="recipient-option">
                            <input type="radio" name="recipient_type" id="rt_individual" value="individual" class="recipient-radio">
                            <label for="rt_individual" class="recipient-label">Individual</label>
                        </div>
                    </div>
                </div>

                <div class="form-group conditional-field" id="class-field">
                    <label class="form-label">Select Class</label>
                    <select name="class_arm_id" class="form-control">
                        <option value="">Select class</option>
                        @foreach($classArms as $arm)
                            <option value="{{ $arm->id }}">{{ $arm->classLevel->name }} {{ $arm->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group conditional-field" id="student-field">
                    <label class="form-label">Student Admission No.</label>
                    <input type="text" name="student_search" class="form-control" placeholder="Search by admission number...">
                    <input type="hidden" name="student_id">
                </div>

                <div class="form-group">
                    <label class="form-label">Message <span>*</span></label>
                    <textarea name="message" class="form-control" placeholder="Type your message here..." maxlength="480" oninput="updateCharCount(this)" required>{{ old('message') }}</textarea>
                    <div class="char-count" id="charCount">0/480 characters · 0 SMS units</div>
                </div>

                <button type="submit" class="btn btn-primary">
                    Send Message
                </button>
            </form>
        </div>
    </div>
    @else
    <div class="card">
        <div class="card-header"><span class="card-title">Compose Message</span></div>
        <div class="card-body" style="text-align:center;padding:40px 20px;color:var(--slate-light)">
            <div style="font-size:32px;margin-bottom:10px">🔒</div>
            <div style="font-size:13px;font-weight:600;color:var(--midnight);margin-bottom:4px">Read-only access</div>
            <div style="font-size:12px">You can view notification activity below, but sending messages is restricted to school administration.</div>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-header">
            <span class="card-title">Recent Messages</span>
            <a href="{{ route('notifications.logs') }}" style="font-size:12px;color:var(--indigo);text-decoration:none">View all →</a>
        </div>
        <div class="card-body">
            @forelse($recentLogs as $log)
            <div class="log-item">
                <div class="log-meta">
                    <span class="log-recipient">{{ $log->recipient }}</span>
                    <div style="display:flex;align-items:center;gap:6px">
                        <span class="badge badge-{{ in_array($log->status,['sent','delivered']) ? 'success' : ($log->status === 'queued' ? 'warning' : 'error') }}">
                            {{ ucfirst($log->status) }}
                        </span>
                        <span class="badge badge-info">{{ strtoupper($log->channel) }}</span>
                    </div>
                </div>
                <div class="log-message">{{ $log->message }}</div>
                <div style="font-size:11px;color:var(--slate-light);margin-top:3px">{{ optional($log->sent_at)->diffForHumans() ?? 'Pending' }}</div>
            </div>
            @empty
            <div class="empty-state">No messages sent yet.</div>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('[name="recipient_type"]').forEach(radio => {
    radio.addEventListener('change', () => {
        document.getElementById('class-field').classList.toggle('show', radio.value === 'class' && radio.checked);
        document.getElementById('student-field').classList.toggle('show', radio.value === 'individual' && radio.checked);
    });
});
document.querySelectorAll('[name="channel"]').forEach(radio => {
    radio.addEventListener('change', () => {
        document.getElementById('email-subject-field').classList.toggle('show', radio.value === 'email' && radio.checked);
    });
});
function updateCharCount(textarea) {
    const len = textarea.value.length;
    const units = Math.ceil(len / 160) || 0;
    document.getElementById('charCount').textContent = `${len}/480 characters · ${units} SMS unit${units !== 1 ? 's' : ''}`;
}
</script>
@endpush
@endsection
