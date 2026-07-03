@extends('layouts.app')
@section('title','Notification Settings')
@section('page-title','Notification & SMS Settings')
@push('styles')
<style>
.pg{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;gap:10px}
.cb{padding:18px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%;transition:border 200ms}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-g{background:var(--emerald);color:white}
.gateway-badge{display:inline-flex;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px}
.gb-on{background:#ECFDF5;color:var(--emerald)}.gb-off{background:#F1F5F9;color:var(--slate)}
.info-box{background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:12px 16px;font-size:12.5px;color:var(--indigo);margin-bottom:16px;line-height:1.6}
.step{display:flex;gap:12px;margin-bottom:12px;font-size:13px;color:var(--slate)}
.step-num{min-width:22px;height:22px;border-radius:50%;background:var(--indigo);color:white;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
@media(max-width:768px){.pg{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
<div class="info-box">
    💡 SMS messages are sent via <strong>Termii</strong> (primary) or <strong>Africa's Talking</strong> (fallback). Configure your API keys in your <code>.env</code> file: <code>TERMII_API_KEY</code>, <code>TERMII_SENDER_ID</code>, <code>AT_API_KEY</code>, <code>AT_USERNAME</code>. Email is sent via the configured SMTP settings.
</div>
<div class="pg">
  <div>
    <div class="card">
      <div class="ch">
        <span>📱</span> Termii SMS Gateway
        <span class="gateway-badge {{ env('TERMII_API_KEY') ? 'gb-on' : 'gb-off' }}">{{ env('TERMII_API_KEY') ? 'Configured' : 'Not set' }}</span>
      </div>
      <div class="cb">
        <div class="step"><div class="step-num">1</div><div>Visit <a href="https://termii.com" target="_blank" style="color:var(--indigo);font-weight:600">termii.com</a> and create an account</div></div>
        <div class="step"><div class="step-num">2</div><div>Create a sender ID (e.g. your school name)</div></div>
        <div class="step"><div class="step-num">3</div><div>Add to <code>.env</code>:<br><code style="background:#F1F5F9;padding:2px 6px;border-radius:4px;font-size:11px">TERMII_API_KEY=your_key_here<br>TERMII_SENDER_ID=SCHOOLNAME</code></div></div>
        <div class="step"><div class="step-num">4</div><div>Fund your Termii wallet (₦2–4 per SMS)</div></div>
        <div style="background:#F8FAFC;border:1px solid var(--border);border-radius:8px;padding:12px;font-size:12px;color:var(--slate);margin-top:8px">
            Termii supports bulk SMS to Nigerian numbers and is NCC-approved.
        </div>
      </div>
    </div>
    <div class="card">
      <div class="ch">
        <span>📲</span> Africa's Talking (Fallback)
        <span class="gateway-badge {{ env('AT_API_KEY') ? 'gb-on' : 'gb-off' }}">{{ env('AT_API_KEY') ? 'Configured' : 'Not set' }}</span>
      </div>
      <div class="cb">
        <div class="step"><div class="step-num">1</div><div>Visit <a href="https://africastalking.com" target="_blank" style="color:var(--indigo);font-weight:600">africastalking.com</a></div></div>
        <div class="step"><div class="step-num">2</div><div>Create account and verify your business</div></div>
        <div class="step"><div class="step-num">3</div><div>Add to <code>.env</code>:<br><code style="background:#F1F5F9;padding:2px 6px;border-radius:4px;font-size:11px">AT_API_KEY=your_key<br>AT_USERNAME=your_username</code></div></div>
      </div>
    </div>
  </div>
  <div>
    <div class="card">
      <div class="ch"><span>📧</span> Email (SMTP)</div>
      <div class="cb">
        <p style="font-size:13px;color:var(--slate);margin-bottom:14px">Configure SMTP in your <code>.env</code> file. Recommended: Gmail, Zoho, or a transactional service like Mailgun.</p>
        <div style="background:#F8FAFC;border:1px solid var(--border);border-radius:8px;padding:12px;font-family:monospace;font-size:11.5px;line-height:1.8;color:var(--slate)">
            MAIL_MAILER=smtp<br>
            MAIL_HOST=smtp.gmail.com<br>
            MAIL_PORT=587<br>
            MAIL_USERNAME=you@school.ng<br>
            MAIL_PASSWORD=app_password<br>
            MAIL_ENCRYPTION=tls<br>
            MAIL_FROM_ADDRESS=noreply@school.ng<br>
            MAIL_FROM_NAME="${{ config('app.name') }}"
        </div>
      </div>
    </div>
    <div class="card">
      <div class="ch"><span>🔔</span> Push Notifications</div>
      <div class="cb">
        <p style="font-size:13px;color:var(--slate);margin-bottom:16px">Browser push notifications let you send instant alerts to staff and parents even when they're not on the site.</p>
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
  </div>
</div>
@endsection