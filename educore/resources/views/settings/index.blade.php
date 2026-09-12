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
.signature-preview{width:210px;height:82px;border:1px dashed var(--border);border-radius:10px;background:#fff;display:flex;align-items:center;justify-content:center;padding:8px;overflow:hidden}
.signature-preview img{max-width:100%;max-height:100%;object-fit:contain}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
.alert-e{background:#FEF2F2;border:1px solid #FCA5A5;border-radius:8px;padding:12px 16px;font-size:13px;color:#991B1B;margin-bottom:14px}
.school-days{display:grid;grid-template-columns:repeat(7,minmax(92px,1fr));gap:8px}
.school-day{display:flex;align-items:center;gap:8px;padding:10px 11px;border:1px solid var(--border);border-radius:9px;background:#F8FAFC;font-size:12px;font-weight:600;color:var(--midnight);cursor:pointer}
.school-day:has(input:checked){border-color:var(--brand-gold);background:#FEF9EC}
.school-day input{width:16px;height:16px;accent-color:var(--brand-gold)}
.hint{font-size:11px;color:var(--slate-light);line-height:1.5;margin-top:5px}
@media(max-width:1100px){.school-days{grid-template-columns:repeat(4,1fr)}}
@media(max-width:768px){.sg{grid-template-columns:1fr}.snav{position:relative}.fr{grid-template-columns:1fr}.school-days{grid-template-columns:repeat(2,1fr)}}
</style>
@endpush

@section('content')
<div class="page-tabs" style="margin-bottom:20px">
    <a href="{{ route('settings.index') }}" class="page-tab {{ request()->routeIs('settings.index') ? 'active' : '' }}">School Info</a>
    <a href="{{ route('settings.grading') }}" class="page-tab {{ request()->routeIs('settings.grading') ? 'active' : '' }}">Grading Scale</a>
    <a href="{{ route('settings.promotion') }}" class="page-tab {{ request()->routeIs('settings.promotion') ? 'active' : '' }}">Promotion Rules</a>
</div>

@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
@if($errors->any())
<div class="alert-e">
    <strong>Please correct the following:</strong>
    <ul style="margin:6px 0 0 18px">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

@if($tenant->slug)
@php
    $_scheme     = config('tenancy.scheme', 'https');
    $_baseDomain = config('tenancy.base_domain', 'educoreng.online');
    $_portalBase = $_scheme . '://' . $tenant->slug . '.' . $_baseDomain;
@endphp
<div class="card">
    <div class="ch">Public School Links</div>
    <div class="cb" style="display:grid;gap:8px;font-size:13px">
        <div><strong>School Portal:</strong> <a href="{{ $_portalBase }}" target="_blank" rel="noopener">{{ $_portalBase }}</a></div>
        <div><strong>Staff Login:</strong> <a href="{{ $_portalBase.'/login' }}" target="_blank" rel="noopener">{{ $_portalBase.'/login' }}</a></div>
        <div><strong>Admissions:</strong> <a href="{{ $_portalBase.'/apply' }}" target="_blank" rel="noopener">{{ $_portalBase.'/apply' }}</a></div>
    </div>
</div>
@endif

<div class="sg">
    <div class="snav">
        <a href="#gen" class="sn on">General Info</a>
        <a href="#school-week" class="sn">School Week</a>
        <a href="#logo" class="sn">Logo</a>
        <a href="#signature" class="sn">Authorized Signature</a>
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
                <div class="fg">
                    <label class="fl">School Name *</label>
                    <input type="text" name="name" class="fc" value="{{ old('name',$tenant->name) }}" required>
                </div>
                <div class="fr">
                    <div class="fg">
                        <label class="fl">Motto</label>
                        <input type="text" name="motto" class="fc" value="{{ old('motto',$tenant->motto ?? optional($settings->get('motto'))->value) }}">
                    </div>
                    <div class="fg">
                        <label class="fl">Proprietor</label>
                        <input type="text" name="proprietor" class="fc" value="{{ old('proprietor',optional($settings->get('proprietor'))->value) }}">
                    </div>
                </div>
                <div class="fr">
                    <div class="fg">
                        <label class="fl">Year Est.</label>
                        <input type="number" name="established_year" class="fc" value="{{ old('established_year',optional($settings->get('established_year'))->value) }}" min="1800" max="{{ date('Y') }}">
                    </div>
                    <div class="fg">
                        <label class="fl">Website</label>
                        <input type="url" name="website" class="fc" value="{{ old('website',optional($settings->get('website'))->value) }}" placeholder="https://">
                    </div>
                </div>
            </div>
        </div>

        @php
            $selectedSchoolDays = collect(old('school_open_days', $schoolOpenDays ?? [1,2,3,4,5]))
                ->map(fn($day) => (int) $day)->all();
        @endphp
        <div class="card" id="school-week">
            <div class="ch">School Week</div>
            <div class="cb">
                <div style="font-size:13px;color:var(--slate);margin-bottom:12px">
                    Select every day on which the school normally opens. At least one day must be selected.
                </div>
                <div class="school-days">
                    @foreach(($schoolDayLabels ?? \App\Services\SchoolWeekService::DAY_LABELS) as $dayNumber => $dayLabel)
                        <label class="school-day">
                            <input type="checkbox" name="school_open_days[]" value="{{ $dayNumber }}"
                                {{ in_array((int)$dayNumber,$selectedSchoolDays,true) ? 'checked' : '' }}>
                            <span>{{ $dayLabel }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="hint">
                    This school-wide calendar is used to determine whether normal staff attendance clock-in is open on a given day. Closed days do not permit routine web clock-in; administrators can still make a manual correction when necessary.
                </div>
            </div>
        </div>

        <div class="card" id="logo">
            <div class="ch">School Logo</div>
            <div class="cb" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
                <div class="logo-circle" id="logoPreviewWrap">
                    @if($tenant->logo_path)
                        <img id="logoPreviewImg" src="{{ asset('storage/' . preg_replace('#^storage/#','',ltrim($tenant->logo_path,'/'))) }}" alt="School Logo">
                    @else
                        <span id="logoInitial">{{ strtoupper(substr($tenant->name,0,1)) }}</span>
                    @endif
                </div>
                <div style="flex:1;min-width:240px">
                    <div class="fg" style="margin:0">
                        <label class="fl">Upload Logo</label>
                        <input type="file" name="logo" accept="image/*" class="fc" style="padding:5px" id="logoFileInput">
                    </div>
                    <div class="hint">PNG or JPG. Maximum 2MB. Displays on report cards and portal.</div>
                </div>
            </div>
        </div>

        <div class="card" id="signature">
            <div class="ch">Authorized Signature</div>
            <div class="cb" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
                <div class="signature-preview" id="signaturePreviewWrap">
                    @if($tenant->authorized_signature_path)
                        <img id="signaturePreviewImg" src="{{ asset('storage/' . preg_replace('#^storage/#','',ltrim($tenant->authorized_signature_path,'/'))) }}" alt="Authorized signature">
                    @else
                        <span id="signaturePlaceholder" style="font-size:11px;color:var(--slate-light)">No signature uploaded</span>
                    @endif
                </div>
                <div style="flex:1;min-width:240px">
                    <div class="fg" style="margin:0">
                        <label class="fl">Upload Principal / Authorized Signature</label>
                        <input type="file" name="authorized_signature" accept="image/png,image/jpeg,image/webp" class="fc" style="padding:5px" id="signatureFileInput">
                    </div>
                    <div class="hint">Use a tightly cropped PNG with a transparent or white background. Maximum 2MB. This appears on staff ID cards and report cards.</div>
                </div>
            </div>
        </div>

        <div class="card" id="contact">
            <div class="ch">Contact Details</div>
            <div class="cb">
                <div class="fg">
                    <label class="fl">Address</label>
                    <textarea name="address" class="fc" rows="2">{{ old('address',$tenant->address) }}</textarea>
                </div>
                <div class="fr">
                    <div class="fg"><label class="fl">Phone</label><input type="text" name="phone" class="fc" value="{{ old('phone',$tenant->phone) }}"></div>
                    <div class="fg"><label class="fl">Email</label><input type="email" name="email" class="fc" value="{{ old('email',$tenant->email) }}"></div>
                </div>
                <div class="fr">
                    @include('partials.nigeria-location',[
                        'uid'=>'school','stateField'=>'school_state','lgaField'=>'school_lga','districtField'=>'school_senatorial_district',
                        'selectedState'=>$tenant->school_state??'','selectedLga'=>$tenant->school_lga??'','selectedDistrict'=>$tenant->school_senatorial_district??'',
                        'labelClass'=>'fl','inputClass'=>'fc','wrapClass'=>'fg','stateLabel'=>'State','lgaLabel'=>'LGA','districtLabel'=>'Senatorial District'
                    ])
                    <div class="fg">
                        <label class="fl">EMIS / Reg. No.</label>
                        <input type="text" name="emis_code" class="fc" value="{{ old('emis_code',$tenant->emis_code) }}" placeholder="e.g. LA/KSF/001">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-p">Save Settings</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function previewImage(inputId, imgId, wrapId, placeholderId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(ev) {
            const placeholder = placeholderId ? document.getElementById(placeholderId) : null;
            if (placeholder) placeholder.remove();
            let img = document.getElementById(imgId);
            if (!img) {
                img = document.createElement('img');
                img.id = imgId;
                document.getElementById(wrapId).appendChild(img);
            }
            img.src = ev.target.result;
        };
        reader.readAsDataURL(file);
    });
}
previewImage('logoFileInput','logoPreviewImg','logoPreviewWrap','logoInitial');
previewImage('signatureFileInput','signaturePreviewImg','signaturePreviewWrap','signaturePlaceholder');
</script>
@endpush
