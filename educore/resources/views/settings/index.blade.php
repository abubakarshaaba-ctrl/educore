@extends('layouts.app')
@section('title','School Settings')
@section('page-title','School Settings')
@push('styles')
<style>
.sg{display:grid;grid-template-columns:220px 1fr;gap:16px}
.snav{background:white;border:1px solid var(--border);border-radius:12px;padding:6px;position:sticky;top:76px}
.sn{display:block;padding:9px 13px;border-radius:8px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;margin-bottom:2px;transition:all 150ms}
.sn:hover{background:#F1F5F9;color:var(--midnight)}
.sn.on{background:var(--indigo-bg);color:var(--indigo)}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:18px}
.fr{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%;transition:border 200ms}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}
.logo-circle{width:76px;height:76px;border-radius:50%;background:var(--indigo);color:white;font-size:26px;font-weight:700;display:flex;align-items:center;justify-content:center;border:2px solid var(--border);overflow:hidden}
.logo-circle img{width:100%;height:100%;object-fit:cover}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
@media(max-width:768px){.sg{grid-template-columns:1fr}.snav{position:relative}.fr{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<div class="page-tabs" style="margin-bottom:20px">
    <a href="{{ route('settings.index') }}"   class="page-tab {{ request()->routeIs('settings.index')   ? 'active' : '' }}">School Info</a>
    <a href="{{ route('settings.grading') }}" class="page-tab {{ request()->routeIs('settings.grading') ? 'active' : '' }}">Grading Scale</a>
    <a href="{{ route('settings.promotion') }}" class="page-tab {{ request()->routeIs('settings.promotion') ? 'active' : '' }}">Promotion Rules</a>
</div>
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
@if($tenant->slug)
@php
    $_scheme     = config('tenancy.scheme', 'https');
    $_baseDomain = config('tenancy.base_domain', 'educoreng.online');
    $_portalBase = $_scheme . '://' . $tenant->slug . '.' . $_baseDomain;
    $_portalUrl  = $_portalBase;
    $_loginUrl   = $_portalBase . '/login';
    $_applyUrl   = $_portalBase . '/apply';
@endphp
<div class="card">
  <div class="ch">Public School Links</div>
  <div class="cb" style="display:grid;gap:8px;font-size:13px">
    <div><strong>School Portal:</strong> <a href="{{ $_portalUrl }}" target="_blank" rel="noopener">{{ $_portalUrl }}</a></div>
    <div><strong>Staff Login:</strong> <a href="{{ $_loginUrl }}" target="_blank" rel="noopener">{{ $_loginUrl }}</a></div>
    <div><strong>Admissions:</strong> <a href="{{ $_applyUrl }}" target="_blank" rel="noopener">{{ $_applyUrl }}</a></div>
  </div>
</div>
@endif
<div class="sg">
  <div class="snav">
    <a href="#gen" class="sn on">General Info</a>
    <a href="#logo" class="sn">Logo</a>
    <a href="#contact" class="sn">Contact</a>
    <div style="border-top:1px solid var(--border);margin:6px 0;padding-top:6px">
      <a href="{{ route('settings.grading') }}" class="sn">Grading System</a>
    </div>
  </div>
  <div>
    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
    @csrf
    <div class="card" id="gen">
      <div class="ch">General Information</div>
      <div class="cb">
        <div class="fg"><label class="fl">School Name *</label><input type="text" name="name" class="fc" value="{{ old('name',$tenant->name) }}" required></div>
        <div class="fr">
          <div class="fg"><label class="fl">Motto</label><input type="text" name="motto" class="fc" value="{{ optional($settings->get('motto'))->value ?? '' }}"></div>
          <div class="fg"><label class="fl">Proprietor</label><input type="text" name="proprietor" class="fc" value="{{ optional($settings->get('proprietor'))->value }}"></div>
        </div>
        <div class="fr">
          <div class="fg"><label class="fl">Year Est.</label><input type="number" name="established_year" class="fc" value="{{ optional($settings->get('established_year'))->value }}" min="1800" max="{{ date('Y') }}"></div>
          <div class="fg"><label class="fl">Website</label><input type="url" name="website" class="fc" value="{{ optional($settings->get('website'))->value }}" placeholder="https://"></div>
        </div>
      </div>
    </div>
    <div class="card" id="logo">
      <div class="ch">School Logo</div>
      <div class="cb" style="display:flex;align-items:center;gap:20px">
        <div class="logo-circle" id="logoPreviewWrap">
          @if($tenant->logo_path)
            <img id="logoPreviewImg" src="{{ asset('storage/' . ltrim($tenant->logo_path, 'storage/')) }}" alt="School Logo">
          @else
            <span id="logoInitial">{{ strtoupper(substr($tenant->name,0,1)) }}</span>
          @endif
        </div>
        <div>
          <div class="fg" style="margin:0">
            <label class="fl">Upload Logo</label>
            <input type="file" name="logo" accept="image/*" class="fc" style="padding:5px" id="logoFileInput">
          </div>
          <div style="font-size:11px;color:var(--slate-light);margin-top:3px">PNG or JPG. Max 2MB. Displays on report cards and portal.</div>
          @if($tenant->logo_path)
          <div style="font-size:11px;color:var(--emerald);margin-top:4px">✓ Logo uploaded</div>
          @endif
        </div>
      </div>
    </div>
    <div class="card" id="contact">
      <div class="ch">Contact Details</div>
      <div class="cb">
        <div class="fg"><label class="fl">Address</label><textarea name="address" class="fc" rows="2">{{ $tenant->address }}</textarea></div>
        <div class="fr">
          <div class="fg"><label class="fl">Phone</label><input type="text" name="phone" class="fc" value="{{ $tenant->phone }}"></div>
          <div class="fg"><label class="fl">Email</label><input type="email" name="email" class="fc" value="{{ $tenant->email }}"></div>
        </div>
        <div class="fr">
          @include('partials.nigeria-location',['uid'=>'school','stateField'=>'school_state','lgaField'=>'school_lga','districtField'=>'school_senatorial_district','selectedState'=>$tenant->school_state??'','selectedLga'=>$tenant->school_lga??'','selectedDistrict'=>$tenant->school_senatorial_district??'','labelClass'=>'fl','inputClass'=>'fc','wrapClass'=>'fg','stateLabel'=>'State','lgaLabel'=>'LGA','districtLabel'=>'Senatorial District'])
          <div class="fg"><label class="fl">EMIS / Reg. No.</label><input type="text" name="emis_code" class="fc" value="{{ $tenant->emis_code }}" placeholder="e.g. LA/KSF/001"></div>
        </div>
      </div>
    </div>
    <button type="submit" class="btn btn-p">Save Settings</button>
    </form>
  </div>
</div>

@push('scripts')
<script>
document.getElementById('logoFileInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(ev) {
        const wrap = document.getElementById('logoPreviewWrap');
        // Remove text initial if present
        const initial = document.getElementById('logoInitial');
        if (initial) initial.remove();
        // Update or create img
        let img = document.getElementById('logoPreviewImg');
        if (!img) {
            img = document.createElement('img');
            img.id = 'logoPreviewImg';
            wrap.appendChild(img);
        }
        img.src = ev.target.result;
    };
    reader.readAsDataURL(file);
});
</script>
@endpush

{{-- ── Brand & Theme ─────────────────────────────────────────────────── --}}
<div class="card" style="margin-top:24px">
    <div class="ch" style="display:flex;align-items:center;gap:8px">
        🎨 Brand Colours
        <span style="font-size:11px;font-weight:400;color:var(--slate-light)">Customise your school's sidebar and button colours</span>
    </div>
    <div style="padding:20px 24px">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:20px">

            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--midnight);margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em">
                    Sidebar / Nav Colour
                </label>
                <div style="display:flex;align-items:center;gap:10px">
                    <input type="color" name="theme_sidebar" form="settings-form"
                           value="{{ $tenant->theme_sidebar ?? '#071E45' }}"
                           style="width:48px;height:48px;padding:4px;border:2px solid var(--border);border-radius:10px;cursor:pointer;background:none">
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--midnight)">Background</div>
                        <div style="font-size:11px;color:var(--slate-light)">Sidebar background colour</div>
                    </div>
                </div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:10px">
                    @foreach(['#071E45','#0B2027','#1A1A2E','#2C1503','#1C0B2E','#0A1628'] as $c)
                    <div onclick="document.querySelector('[name=theme_sidebar]').value='{{ $c }}'"
                         style="width:24px;height:24px;border-radius:6px;background:{{ $c }};cursor:pointer;border:2px solid transparent;transition:border-color 100ms"
                         onmouseover="this.style.borderColor='var(--indigo)'" onmouseout="this.style.borderColor='transparent'"
                         title="{{ $c }}"></div>
                    @endforeach
                </div>
            </div>

            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--midnight);margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em">
                    Accent / Button Colour
                </label>
                <div style="display:flex;align-items:center;gap:10px">
                    <input type="color" name="theme_accent" form="settings-form"
                           value="{{ $tenant->theme_accent ?? '#D79A21' }}"
                           style="width:48px;height:48px;padding:4px;border:2px solid var(--border);border-radius:10px;cursor:pointer;background:none">
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--midnight)">Accent</div>
                        <div style="font-size:11px;color:var(--slate-light)">Buttons, active nav items</div>
                    </div>
                </div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:10px">
                    @foreach(['#D79A21','#2D9B72','#B5337E','#3F8AE0','#D4622E','#11A8BF'] as $c)
                    <div onclick="document.querySelector('[name=theme_accent]').value='{{ $c }}'"
                         style="width:24px;height:24px;border-radius:6px;background:{{ $c }};cursor:pointer;border:2px solid transparent;transition:border-color 100ms"
                         onmouseover="this.style.borderColor='var(--indigo)'" onmouseout="this.style.borderColor='transparent'"
                         title="{{ $c }}"></div>
                    @endforeach
                </div>
            </div>

            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--midnight);margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em">
                    Live Preview
                </label>
                <div id="themePreview" style="border-radius:10px;overflow:hidden;border:1px solid var(--border);width:130px">
                    <div id="previewSidebar" style="background:#071E45;padding:10px 12px">
                        <div style="display:flex;align-items:center;gap:6px;margin-bottom:8px">
                            <div style="width:16px;height:16px;border-radius:3px;background:var(--brand-gold)"></div>
                            <span style="color:white;font-size:9px;font-weight:800">EduCore</span>
                        </div>
                        <div id="previewActive" style="background:#D79A2130;color:#D79A21;border-left:2px solid #D79A21;padding:4px 7px;border-radius:4px;font-size:9px;font-weight:600;margin-bottom:4px">Dashboard</div>
                        <div style="color:rgba(255,255,255,.5);padding:4px 7px;font-size:9px">Students</div>
                        <div style="color:rgba(255,255,255,.5);padding:4px 7px;font-size:9px">Staff</div>
                    </div>
                    <div style="padding:8px;background:white">
                        <div id="previewBtn" style="background:#D79A21;color:#071E45;font-size:9px;font-weight:700;padding:4px 8px;border-radius:5px;text-align:center">Save Settings</div>
                    </div>
                </div>
            </div>
        </div>

        <div style="background:#FEF9EC;border:1px solid #F2C35B44;border-radius:8px;padding:10px 14px;font-size:12px;color:#92400E;margin-top:4px">
            💡 Save settings to apply your theme. Changes take effect immediately and appear for all users in your school.
        </div>
    </div>
</div>

<script>
// Live preview updater
document.querySelector('[name=theme_sidebar]').addEventListener('input', function() {
    document.getElementById('previewSidebar').style.background = this.value;
});
document.querySelector('[name=theme_accent]').addEventListener('input', function() {
    const c = this.value;
    document.getElementById('previewActive').style.background = c + '30';
    document.getElementById('previewActive').style.color = c;
    document.getElementById('previewActive').style.borderLeftColor = c;
    document.getElementById('previewBtn').style.background = c;
});
</script>

@endsection
