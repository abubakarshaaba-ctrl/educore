@extends('layouts.app')
@section('title','Admission Portal Settings')
@section('page-title','Admission Portal Settings')
@push('styles')
<style>
.pg{display:grid;grid-template-columns:280px 1fr;gap:16px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;gap:10px}
.cb{padding:20px}
.fr{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%;transition:border 200ms}
.fc:focus{border-color:var(--indigo);background:white}
.toggle-row{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border)}
.toggle-row:last-child{border-bottom:none}
.toggle-label{font-size:13px;color:var(--midnight);font-weight:500}
.toggle-sub{font-size:11px;color:var(--slate-light);margin-top:2px}
.toggle{position:relative;display:inline-block;width:42px;height:24px;flex-shrink:0}
.toggle input{opacity:0;width:0;height:0}
.slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#CBD5E1;border-radius:24px;transition:200ms}
.slider::before{position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:white;border-radius:50%;transition:200ms}
input:checked + .slider{background:var(--indigo)}
input:checked + .slider::before{transform:translateX(18px)}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}
.portal-url{background:#F0FDF4;border:1px solid #A7F3D0;border-radius:10px;padding:14px 16px;margin-bottom:16px}
.portal-url .lbl{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#059669;margin-bottom:6px}
.portal-url .url{font-family:monospace;font-size:13px;color:#065F46;word-break:break-all}
.portal-url .copy-btn{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;font-size:11px;font-weight:600;background:white;border:1px solid #A7F3D0;border-radius:6px;cursor:pointer;font-family:inherit;margin-top:8px;color:#059669}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
@media(max-width:768px){.pg{grid-template-columns:1fr}.fr{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif

@php $tenant = auth()->user()->tenant; @endphp
<div class="portal-url">
    <div class="lbl">&#127760; Your Public Portal URL</div>
    <div class="url" id="portal-url">{{ route('portal.landing', $tenant->slug) }}</div>
    <button class="copy-btn" onclick="navigator.clipboard.writeText(document.getElementById('portal-url').innerText);this.innerText='Copied!'">&#128203; Copy Link</button>
</div>

<div class="pg">
  <div>
    <div class="card">
      <div class="ch">&#9881; Portal Navigation</div>
      <div style="padding:8px">
        <a href="{{ route('admissions.portal') }}" style="display:block;padding:9px 12px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:500;color:var(--slate);transition:all 150ms" onmouseover="this.style.background='#F1F5F9'" onmouseout="this.style.background=''">&#128203; Portal Applications</a>
        <a href="{{ route('admissions.index') }}" style="display:block;padding:9px 12px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:500;color:var(--slate)" onmouseover="this.style.background='#F1F5F9'" onmouseout="this.style.background=''">&#128196; All Applications</a>
        <a href="{{ route('portal.landing', $tenant->slug) }}" target="_blank" style="display:block;padding:9px 12px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:500;color:var(--indigo)" onmouseover="this.style.background='#EFF6FF'" onmouseout="this.style.background=''">&#127760; Preview Portal &#8599;</a>
      </div>
    </div>

    <div class="card">
      <div class="ch">&#127891; Portal Status</div>
      <div style="padding:16px;text-align:center">
        @if($settings->isCurrentlyOpen())
          <div style="font-size:36px;margin-bottom:8px">&#128994;</div>
          <div style="font-size:15px;font-weight:700;color:var(--emerald)">Portal is OPEN</div>
          <div style="font-size:12px;color:var(--slate-light);margin-top:4px">Parents can submit applications</div>
        @else
          <div style="font-size:36px;margin-bottom:8px">&#128308;</div>
          <div style="font-size:15px;font-weight:700;color:var(--crimson)">Portal is CLOSED</div>
          <div style="font-size:12px;color:var(--slate-light);margin-top:4px">Applications not accepted</div>
        @endif
      </div>
    </div>
  </div>

  <form method="POST" action="{{ route('admissions.portal.settings.save') }}">
  @csrf
  <div class="card">
    <div class="ch">&#128197; Intake Settings</div>
    <div class="cb">
      <div class="fr">
        <div class="fg"><label class="fl">Academic Year</label><input type="text" name="academic_year" class="fc" value="{{ $settings->academic_year }}" placeholder="e.g. 2025/2026"></div>
        <div class="fg"><label class="fl">Application Fee (₦)</label><input type="number" name="application_fee" class="fc" value="{{ $settings->application_fee }}" min="0" step="100"></div>
      </div>
      <div class="fr">
        <div class="fg"><label class="fl">Opens On</label><input type="date" name="opens_on" class="fc" value="{{ optional($settings->opens_on)->format('Y-m-d') }}"></div>
        <div class="fg"><label class="fl">Closes On</label><input type="date" name="closes_on" class="fc" value="{{ optional($settings->closes_on)->format('Y-m-d') }}"></div>
      </div>
      <div class="fg"><label class="fl">Welcome Message</label><textarea name="welcome_message" class="fc" rows="3" placeholder="Message shown on the portal homepage...">{{ $settings->welcome_message }}</textarea></div>
      <div class="fg"><label class="fl">Requirements (shown to applicants)</label><textarea name="requirements" class="fc" rows="3" placeholder="List what documents/requirements applicants need...">{{ $settings->requirements }}</textarea></div>
      <div class="fg"><label class="fl">Footer Note</label><input type="text" name="footer_note" class="fc" value="{{ $settings->footer_note }}" placeholder="e.g. For enquiries call 0801234567"></div>
    </div>
  </div>

  <div class="card">
    <div class="ch">&#9881; Portal Toggles</div>
    <div class="cb">
      <div class="toggle-row">
        <div><div class="toggle-label">Portal Open</div><div class="toggle-sub">Allow parents to submit new applications</div></div>
        <label class="toggle"><input type="checkbox" name="is_open" value="1" {{ $settings->is_open?'checked':'' }}><span class="slider"></span></label>
      </div>
      <div class="toggle-row">
        <div><div class="toggle-label">Auto-Shortlist</div><div class="toggle-sub">Automatically shortlist all new applications</div></div>
        <label class="toggle"><input type="checkbox" name="auto_shortlist" value="1" {{ $settings->auto_shortlist?'checked':'' }}><span class="slider"></span></label>
      </div>
      <div class="toggle-row">
        <div><div class="toggle-label">Require Passport Photo</div><div class="toggle-sub">Make passport photo upload mandatory</div></div>
        <label class="toggle"><input type="checkbox" name="require_passport" value="1" {{ $settings->require_passport?'checked':'' }}><span class="slider"></span></label>
      </div>
      <div class="toggle-row">
        <div><div class="toggle-label">Require Birth Certificate</div><div class="toggle-sub">Make birth certificate upload mandatory</div></div>
        <label class="toggle"><input type="checkbox" name="require_birth_cert" value="1" {{ $settings->require_birth_cert?'checked':'' }}><span class="slider"></span></label>
      </div>
      <div class="toggle-row">
        <div><div class="toggle-label">Require Last Report Card</div><div class="toggle-sub">Make previous report card mandatory</div></div>
        <label class="toggle"><input type="checkbox" name="require_report_card" value="1" {{ $settings->require_report_card?'checked':'' }}><span class="slider"></span></label>
      </div>
      <div class="toggle-row">
        <div><div class="toggle-label">SMS Confirmation</div><div class="toggle-sub">Send SMS to guardian when application is submitted</div></div>
        <label class="toggle"><input type="checkbox" name="notify_guardian_sms" value="1" {{ $settings->notify_guardian_sms?'checked':'' }}><span class="slider"></span></label>
      </div>
      <div class="toggle-row">
        <div><div class="toggle-label">Email Confirmation</div><div class="toggle-sub">Send email to guardian when application is submitted</div></div>
        <label class="toggle"><input type="checkbox" name="notify_guardian_email" value="1" {{ $settings->notify_guardian_email?'checked':'' }}><span class="slider"></span></label>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-p">&#128190; Save Portal Settings</button>
  </form>
</div>
@endsection
