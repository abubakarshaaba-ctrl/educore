@extends('layouts.app')
@section('title','New Application')
@section('page-title','Admissions')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:14px 20px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:14px;font-weight:700;color:var(--midnight)}
.cb{padding:20px}
.fr{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.fr3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fl span{color:var(--crimson)}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%;transition:border 200ms}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-ghost{background:white;color:var(--midnight);border:1px solid var(--border)}
.back{font-size:13px;color:var(--indigo);text-decoration:none;font-weight:500;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px}
@media(max-width:768px){.fr,.fr3{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<a href="{{ route('admissions.index') }}" class="back">← Back to Admissions</a>
<form method="POST" action="{{ route('admissions.store') }}">
@csrf
<div class="card">
    <div class="ch">Student Information</div>
    <div class="cb">
        <div class="fr3">
            <div class="fg"><label class="fl">First Name <span>*</span></label><input type="text" name="first_name" class="fc" required></div>
            <div class="fg"><label class="fl">Last Name <span>*</span></label><input type="text" name="last_name" class="fc" required></div>
            <div class="fg"><label class="fl">Other Names</label><input type="text" name="other_names" class="fc"></div>
        </div>
        <div class="fr">
            <div class="fg"><label class="fl">Date of Birth <span>*</span></label><input type="date" name="date_of_birth" class="fc" required></div>
            <div class="fg"><label class="fl">Gender <span>*</span></label>
                <select name="gender" class="fc" required><option value="male">Male</option><option value="female">Female</option></select>
            </div>
            <div class="fg"><label class="fl">Religion</label><select name="religion" class="fc"><option value="">Select</option><option>Islam</option><option>Christianity</option><option>Other</option></select></div>
            @include('partials.nigeria-location',['uid'=>'admission_create','stateField'=>'state_of_origin','showLga'=>false,'showDistrict'=>false,'selectedState'=>old('state_of_origin',''),'labelClass'=>'fl','inputClass'=>'fc','wrapClass'=>'fg','stateLabel'=>'State of Origin'])
        </div>
        <div class="fr">
            <div class="fg"><label class="fl">Applying for Class</label>
                <select name="applying_for_class_level_id" class="fc">
                    <option value="">Select</option>
                    @foreach($classLevels as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
                </select>
            </div>
            <div class="fg"><label class="fl">Previous School</label><input type="text" name="previous_school" class="fc"></div>
        </div>
        <div class="fg"><label class="fl">Home Address</label><input type="text" name="address" class="fc"></div>
    </div>
</div>
<div class="card">
    <div class="ch">Guardian / Parent Information</div>
    <div class="cb">
        <div class="fr">
            <div class="fg"><label class="fl">Full Name <span>*</span></label><input type="text" name="guardian_name" class="fc" required></div>
            <div class="fg"><label class="fl">Phone <span>*</span></label><input type="tel" name="guardian_phone" class="fc" required></div>
            <div class="fg"><label class="fl">Email</label><input type="email" name="guardian_email" class="fc"></div>
            <div class="fg"><label class="fl">Relationship <span>*</span></label>
                <select name="guardian_relationship" class="fc" required>
                    <option value="parent">Parent</option><option value="guardian">Guardian</option><option value="sibling">Sibling</option><option value="other">Other</option>
                </select>
            </div>
        </div>
        <div class="fr">
            <div class="fg"><label class="fl">Occupation</label><input type="text" name="guardian_occupation" class="fc"></div>
            <div class="fg"><label class="fl">Address</label><input type="text" name="guardian_address" class="fc"></div>
        </div>
    </div>
</div>
<div class="fg"><label class="fl">Additional Notes</label><textarea name="notes" class="fc" rows="3"></textarea></div>
<div style="display:flex;gap:10px">
    <button type="submit" class="btn btn-p">Submit Application</button>
    <a href="{{ route('admissions.index') }}" class="btn btn-ghost">Cancel</a>
</div>
</form>
@endsection