@extends('layouts.app')
@section('title','Notification Settings')
@section('page-title','Notification & SMS Settings')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px;max-width:520px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;gap:10px}
.cb{padding:18px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%;transition:border 200ms}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-g{background:var(--emerald);color:white}
.info-box{background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:12px 16px;font-size:12.5px;color:var(--indigo);margin-bottom:16px;line-height:1.6;max-width:520px}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
<div class="info-box">
    💡 Email and SMS delivery (payment receipts, admission updates, results, fee reminders) are handled automatically by EduCore's messaging system — there's nothing to configure here. If a parent or staff member isn't receiving messages, contact EduCore support.
</div>
<div class="card">
  <div class="ch"><span>🔔</span> Push Notifications</div>
  <div class="cb">
    <p style="font-size:13px;color:var(--slate);margin-bottom:16px">Browser push notifications let you send instant alerts to your school's staff and parents even when they're not on the site.</p>
    <form method="POST" action="{{ route('push.broadcast') }}">
    @csrf
    <div class="fg"><label class="fl">Title</label><input type="text" name="title" class="fc" placeholder="e.g. School closed tomorrow" required></div>
    <div class="fg"><label class="fl">Message</label><textarea name="body" class="fc" rows="3" placeholder="Push notification body..." required></textarea></div>
    <button type="submit" class="btn btn-p">🔔 Send Push to All Devices</button>
    </form>
    <div style="margin-top:12px">
        <form method="POST" action="{{ route('push.test') }}">@csrf
        <button type="submit" class="btn btn-g" style="width:100%;justify-content:center">Test Push on This Device</button>
        </form>
    </div>
  </div>
</div>
@endsection
