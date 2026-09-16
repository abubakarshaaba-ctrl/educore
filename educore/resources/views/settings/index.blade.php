@extends('layouts.app')
@section('title','School Settings')
@section('page-title','School Settings')

@push('styles')
<style>
.settings-shell{max-width:1180px;margin:0 auto}
.settings-hero{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:20px 22px;margin-bottom:16px;background:linear-gradient(135deg,#071E45 0%,#0B2D63 100%);border-radius:16px;color:#fff;box-shadow:0 10px 28px rgba(7,30,69,.12)}
.settings-hero-copy{min-width:0}.settings-hero h2{margin:0 0 5px;font-size:20px;line-height:1.2;color:#fff}.settings-hero p{margin:0;color:#DCE5F2;font-size:12px;line-height:1.55;max-width:690px}
.settings-school-mark{width:68px;height:68px;flex:0 0 68px;border-radius:14px;background:#fff;border:2px solid rgba(242,195,91,.55);display:flex;align-items:center;justify-content:center;overflow:hidden;color:#071E45;font-size:24px;font-weight:800}.settings-school-mark img{width:100%;height:100%;object-fit:contain;padding:5px}
.settings-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px}
.settings-card{background:#fff;border:1px solid #E2E8F0;border-radius:14px;overflow:hidden;box-shadow:0 3px 14px rgba(15,23,42,.035)}
.settings-card.wide{grid-column:1/-1}.settings-card-head{padding:15px 18px;border-bottom:1px solid #E8EDF4;background:#FAFBFD}.settings-card-head h3{margin:0;color:#071E45;font-size:14px;font-weight:800}.settings-card-head p{margin:4px 0 0;color:#718096;font-size:11px;line-height:1.45}.settings-card-body{padding:18px}
.field-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px 16px}.field-grid.three{grid-template-columns:repeat(3,minmax(0,1fr))}.field-full{grid-column:1/-1}.fg{display:flex;flex-direction:column;gap:6px;min-width:0}.fl{font-size:11px;font-weight:700;color:#344054;letter-spacing:.015em}.fl .req{color:#B42318}.fc{width:100%;min-height:40px;padding:9px 11px;border:1px solid #D6DDE8;border-radius:9px;background:#fff;color:#1E293B;font:500 13px/1.35 inherit;outline:none;transition:border-color .15s,box-shadow .15s,background .15s}.fc:hover{border-color:#B8C3D3}.fc:focus{border-color:#D79A21;box-shadow:0 0 0 3px rgba(215,154,33,.12);background:#fff}.fc::placeholder{color:#98A2B3}textarea.fc{resize:vertical;min-height:76px}
.hint{font-size:10.5px;line-height:1.45;color:#7C899B}.section-note{padding:10px 12px;border:1px solid #E7ECF3;border-radius:9px;background:#F8FAFC;color:#64748B;font-size:11px;line-height:1.5}
.asset-row{display:grid;grid-template-columns:112px minmax(0,1fr);gap:18px;align-items:center}.logo-preview{width:96px;height:96px;border-radius:16px;border:1px solid #DDE3EC;background:#F8FAFC;display:flex;align-items:center;justify-content:center;overflow:hidden;color:#071E45;font-size:30px;font-weight:800}.logo-preview img{width:100%;height:100%;object-fit:contain;padding:6px}.signature-preview{width:108px;height:76px;border:1px dashed #CBD5E1;border-radius:10px;background:#FAFBFC;display:flex;align-items:center;justify-content:center;padding:7px;overflow:hidden;text-align:center;color:#94A3B8;font-size:10px}.signature-preview img{max-width:100%;max-height:100%;object-fit:contain}
.school-days{display:grid;grid-template-columns:repeat(7,minmax(84px,1fr));gap:8px}.school-day{position:relative;display:flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:9px 8px;border:1px solid #DDE3EC;border-radius:9px;background:#fff;color:#334155;font-size:11px;font-weight:700;cursor:pointer}.school-day input{width:15px;height:15px;accent-color:#D79A21}.school-day:has(input:checked){border-color:#D79A21;background:#FFF9EA;color:#071E45}
.portal-links{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.portal-link{display:block;padding:11px 12px;border:1px solid #DFE5EE;border-radius:10px;background:#fff;color:#071E45;text-decoration:none;min-width:0}.portal-link span{display:block;color:#64748B;font-size:10px;margin-bottom:3px}.portal-link strong{display:block;font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.portal-link:hover{border-color:#D79A21;background:#FFFCF4}
.alert-s,.alert-e{border-radius:10px;padding:12px 15px;font-size:12px;margin-bottom:14px}.alert-s{background:#ECFDF3;border:1px solid #ABEFC6;color:#067647}.alert-e{background:#FEF3F2;border:1px solid #FECDCA;color:#B42318}.alert-e ul{margin:6px 0 0 18px;padding:0}
.save-bar{position:sticky;bottom:10px;z-index:20;margin-top:16px;padding:12px 14px;border:1px solid #D9E0EA;border-radius:12px;background:rgba(255,255,255,.96);box-shadow:0 10px 26px rgba(7,30,69,.11);display:flex;align-items:center;justify-content:space-between;gap:14px;backdrop-filter:blur(8px)}.save-bar-copy strong{display:block;color:#071E45;font-size:12px}.save-bar-copy span{display:block;margin-top:2px;color:#7A8699;font-size:10px}.save-btn{display:inline-flex;align-items:center;justify-content:center;min-width:128px;padding:10px 18px;border:0;border-radius:9px;background:#071E45;color:#fff;font:700 12px inherit;cursor:pointer;box-shadow:0 4px 10px rgba(7,30,69,.16)}.save-btn:hover{background:#0B2D63}
.location-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px 16px}.location-grid>.fg{margin:0}
@media(max-width:1050px){.school-days{grid-template-columns:repeat(4,1fr)}.field-grid.three,.location-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:720px){.settings-hero{padding:17px}.settings-school-mark{width:58px;height:58px;flex-basis:58px}.settings-grid,.field-grid,.field-grid.three,.location-grid,.portal-links{grid-template-columns:1fr}.settings-card.wide,.field-full{grid-column:auto}.asset-row{grid-template-columns:90px minmax(0,1fr);gap:13px}.logo-preview{width:82px;height:82px}.signature-preview{width:88px;height:68px}.school-days{grid-template-columns:repeat(2,1fr)}.save-bar{bottom:6px}.save-bar-copy{display:none}.save-btn{width:100%}}
</style>
@endpush

@section('content')
@php
    $settingValue = fn (string $key, $default = null) => optional($settings->get($key))->value ?? $default;
    $selectedSchoolDays = collect(old('school_open_days', $schoolOpenDays ?? [1,2,3,4,5]))->map(fn($day)=>(int)$day)->all();
    $_scheme = config('tenancy.scheme','https');
    $_baseDomain = config('tenancy.base_domain','educoreng.online');
    $_portalBase = $tenant->slug ? $_scheme.'://'.$tenant->slug.'.'.$_baseDomain : null;
@endphp

<div class="settings-shell">
    <div class="page-tabs" style="margin-bottom:16px">
        <a href="{{ route('settings.index') }}" class="page-tab {{ request()->routeIs('settings.index') ? 'active' : '' }}">School Info</a>
        <a href="{{ route('settings.grading') }}" class="page-tab {{ request()->routeIs('settings.grading') ? 'active' : '' }}">Grading Scale</a>
        <a href="{{ route('settings.promotion') }}" class="page-tab {{ request()->routeIs('settings.promotion') ? 'active' : '' }}">Promotion Rules</a>
    </div>

    @if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert-e"><strong>Some settings could not be saved.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="settings-hero">
        <div class="settings-hero-copy">
            <h2>{{ $tenant->name }}</h2>
            <p>Maintain the school identity, official contact information, location, brand assets and operating week used throughout EduCore.</p>
        </div>
        <div class="settings-school-mark" id="heroLogoWrap">
            @if($tenant->logo_path)
                <img id="heroLogoImg" src="{{ asset('storage/'.preg_replace('#^storage/#','',ltrim($tenant->logo_path,'/'))) }}" alt="{{ $tenant->name }} logo">
            @else
                <span id="heroLogoInitial">{{ strtoupper(substr($tenant->name,0,1)) }}</span>
            @endif
        </div>
    </div>

    @if($_portalBase)
        <div class="settings-card wide" style="margin-bottom:16px">
            <div class="settings-card-head"><h3>Public school links</h3><p>Quick access to the school-facing pages generated from this tenant.</p></div>
            <div class="settings-card-body">
                <div class="portal-links">
                    <a class="portal-link" href="{{ $_portalBase }}" target="_blank" rel="noopener"><span>School portal</span><strong>{{ $_portalBase }}</strong></a>
                    <a class="portal-link" href="{{ $_portalBase.'/login' }}" target="_blank" rel="noopener"><span>Staff login</span><strong>{{ $_portalBase.'/login' }}</strong></a>
                    <a class="portal-link" href="{{ $_portalBase.'/apply' }}" target="_blank" rel="noopener"><span>Admissions</span><strong>{{ $_portalBase.'/apply' }}</strong></a>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
        @csrf

        <div class="settings-grid">
            <section class="settings-card wide">
                <div class="settings-card-head"><h3>School identity & registration</h3><p>Official information used on school documents, reports and public-facing pages.</p></div>
                <div class="settings-card-body">
                    <div class="field-grid">
                        <div class="fg field-full">
                            <label class="fl" for="school-name">School name <span class="req">*</span></label>
                            <input id="school-name" type="text" name="name" class="fc" value="{{ old('name',$tenant->name) }}" maxlength="150" required>
                        </div>
                        <div class="fg">
                            <label class="fl" for="school-motto">Motto</label>
                            <input id="school-motto" type="text" name="motto" class="fc" value="{{ old('motto',$tenant->motto) }}" maxlength="200" placeholder="School motto">
                        </div>
                        <div class="fg">
                            <label class="fl" for="school-proprietor">Proprietor / owner</label>
                            <input id="school-proprietor" type="text" name="proprietor" class="fc" value="{{ old('proprietor',$settingValue('proprietor')) }}" maxlength="180" placeholder="Name of proprietor or organization">
                        </div>
                        <div class="fg">
                            <label class="fl" for="school-year">Year established</label>
                            <input id="school-year" type="number" name="established_year" class="fc" value="{{ old('established_year',$settingValue('established_year')) }}" min="1800" max="{{ date('Y') }}" placeholder="e.g. 2014">
                        </div>
                        <div class="fg">
                            <label class="fl" for="school-emis">EMIS / registration number</label>
                            <input id="school-emis" type="text" name="emis_code" class="fc" value="{{ old('emis_code',$settingValue('emis_code')) }}" maxlength="100" placeholder="e.g. LA/KSF/001">
                        </div>
                    </div>
                </div>
            </section>

            <section class="settings-card wide">
                <div class="settings-card-head"><h3>Location & contact</h3><p>Address and communication details used on official documents and school correspondence.</p></div>
                <div class="settings-card-body">
                    <div class="location-grid">
                        @include('partials.nigeria-location',[
                            'uid'=>'school',
                            'stateField'=>'school_state','lgaField'=>'school_lga','districtField'=>'school_senatorial_district',
                            'selectedState'=>old('school_state',$settingValue('school_state','')),
                            'selectedLga'=>old('school_lga',$settingValue('school_lga','')),
                            'selectedDistrict'=>old('school_senatorial_district',$settingValue('school_senatorial_district','')),
                            'labelClass'=>'fl','inputClass'=>'fc','wrapClass'=>'fg',
                            'stateLabel'=>'State','lgaLabel'=>'LGA','districtLabel'=>'Senatorial District'
                        ])
                    </div>
                    <div class="field-grid" style="margin-top:14px">
                        <div class="fg field-full">
                            <label class="fl" for="school-address">School address</label>
                            <textarea id="school-address" name="address" class="fc" maxlength="300" rows="2" placeholder="Full school address">{{ old('address',$tenant->address) }}</textarea>
                        </div>
                        <div class="fg">
                            <label class="fl" for="school-phone">Phone</label>
                            <input id="school-phone" type="text" name="phone" class="fc" value="{{ old('phone',$tenant->phone) }}" maxlength="40" placeholder="e.g. +234 800 000 0000">
                        </div>
                        <div class="fg">
                            <label class="fl" for="school-email">Official email</label>
                            <input id="school-email" type="email" name="email" class="fc" value="{{ old('email',$tenant->email) }}" maxlength="255" placeholder="school@example.com">
                        </div>
                        <div class="fg field-full">
                            <label class="fl" for="school-website">Website</label>
                            <input id="school-website" type="url" name="website" class="fc" value="{{ old('website',$settingValue('website')) }}" maxlength="255" placeholder="https://www.example.com">
                        </div>
                    </div>
                </div>
            </section>

            <section class="settings-card">
                <div class="settings-card-head"><h3>School logo</h3><p>Used on report cards, ID cards and school-facing pages.</p></div>
                <div class="settings-card-body">
                    <div class="asset-row">
                        <div class="logo-preview" id="logoPreviewWrap">
                            @if($tenant->logo_path)
                                <img id="logoPreviewImg" src="{{ asset('storage/'.preg_replace('#^storage/#','',ltrim($tenant->logo_path,'/'))) }}" alt="School logo">
                            @else
                                <span id="logoInitial">{{ strtoupper(substr($tenant->name,0,1)) }}</span>
                            @endif
                        </div>
                        <div class="fg">
                            <label class="fl" for="logoFileInput">Replace logo</label>
                            <input id="logoFileInput" type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="fc" style="padding:6px">
                            <div class="hint">PNG, JPG or WebP. Maximum 2MB. A square or transparent logo works best.</div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="settings-card">
                <div class="settings-card-head"><h3>Authorized signature</h3><p>Signature asset used where an authorized school sign-off is required.</p></div>
                <div class="settings-card-body">
                    <div class="asset-row">
                        <div class="signature-preview" id="signaturePreviewWrap">
                            @if($tenant->authorized_signature_path)
                                <img id="signaturePreviewImg" src="{{ asset('storage/'.preg_replace('#^storage/#','',ltrim($tenant->authorized_signature_path,'/'))) }}" alt="Authorized signature">
                            @else
                                <span id="signaturePlaceholder">No signature uploaded</span>
                            @endif
                        </div>
                        <div class="fg">
                            <label class="fl" for="signatureFileInput">Replace signature</label>
                            <input id="signatureFileInput" type="file" name="authorized_signature" accept="image/png,image/jpeg,image/webp" class="fc" style="padding:6px">
                            <div class="hint">Use a tightly cropped signature on a transparent or white background. Maximum 2MB.</div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="settings-card wide">
                <div class="settings-card-head"><h3>School week</h3><p>Select the normal operating days used by attendance and school-calendar logic.</p></div>
                <div class="settings-card-body">
                    <div class="school-days">
                        @foreach(($schoolDayLabels ?? \App\Services\SchoolWeekService::DAY_LABELS) as $dayNumber=>$dayLabel)
                            <label class="school-day">
                                <input type="checkbox" name="school_open_days[]" value="{{ $dayNumber }}" {{ in_array((int)$dayNumber,$selectedSchoolDays,true) ? 'checked' : '' }}>
                                <span>{{ $dayLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="section-note" style="margin-top:12px">At least one day must remain selected. Closed days do not permit routine attendance clock-in; administrators can still make manual corrections when necessary.</div>
                </div>
            </section>
        </div>

        <div class="save-bar">
            <div class="save-bar-copy"><strong>Save school settings</strong><span>Changes apply to this school only and are used across EduCore.</span></div>
            <button type="submit" class="save-btn">Save changes</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function previewImage(inputId,imgId,wrapId,placeholderId,mirrorImgId,mirrorPlaceholderId){
    const input=document.getElementById(inputId);
    if(!input)return;
    input.addEventListener('change',function(e){
        const file=e.target.files&&e.target.files[0];
        if(!file)return;
        const reader=new FileReader();
        reader.onload=function(ev){
            const src=ev.target.result;
            const placeholder=placeholderId?document.getElementById(placeholderId):null;
            if(placeholder)placeholder.remove();
            let img=document.getElementById(imgId);
            if(!img){img=document.createElement('img');img.id=imgId;document.getElementById(wrapId).appendChild(img);}
            img.src=src;
            if(mirrorImgId){
                const mirrorPlaceholder=mirrorPlaceholderId?document.getElementById(mirrorPlaceholderId):null;
                if(mirrorPlaceholder)mirrorPlaceholder.remove();
                let mirror=document.getElementById(mirrorImgId);
                if(!mirror){mirror=document.createElement('img');mirror.id=mirrorImgId;document.getElementById('heroLogoWrap').appendChild(mirror);}
                mirror.src=src;
            }
        };
        reader.readAsDataURL(file);
    });
}
previewImage('logoFileInput','logoPreviewImg','logoPreviewWrap','logoInitial','heroLogoImg','heroLogoInitial');
previewImage('signatureFileInput','signaturePreviewImg','signaturePreviewWrap','signaturePlaceholder');
</script>
@endpush
