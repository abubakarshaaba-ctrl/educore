<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Apply — {{ $tenant->name }}</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#F1F5F9;color:#1E293B}
.topbar{background:linear-gradient(135deg,#1E3A5F,#2563EB);color:white;padding:16px 20px;display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:50}
.topbar .logo{width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:16px;overflow:hidden;flex-shrink:0}
.topbar .logo img{width:100%;height:100%;object-fit:cover}
.topbar h1{font-size:16px;font-weight:700}
.topbar p{font-size:12px;opacity:.75}
.back{margin-left:auto;color:rgba(255,255,255,0.8);text-decoration:none;font-size:13px}
.container{max-width:800px;margin:0 auto;padding:24px 16px 60px}
.progress{display:flex;gap:0;margin-bottom:28px;background:white;border-radius:12px;overflow:hidden;border:1px solid #E2E8F0}
.step{flex:1;padding:12px 8px;text-align:center;font-size:12px;font-weight:600;color:#94A3B8;border-right:1px solid #E2E8F0;transition:all 200ms}
.step:last-child{border-right:none}
.step.active{background:#EFF6FF;color:#2563EB}
.step.done{background:#ECFDF5;color:#059669}
.step-num{width:22px;height:22px;border-radius:50%;background:currentColor;color:white;font-size:11px;display:inline-flex;align-items:center;justify-content:center;margin:0 auto 4px;opacity:.3}
.step.active .step-num{opacity:1;background:#2563EB}
.step.done .step-num{opacity:1;background:#059669}
.card{background:white;border-radius:14px;overflow:hidden;margin-bottom:18px;border:1px solid #E2E8F0;box-shadow:0 1px 3px rgba(0,0,0,0.05)}
.card-header{padding:16px 20px;border-bottom:1px solid #E2E8F0;background:#F8FAFC;display:flex;align-items:center;gap:10px}
.card-header .num{width:28px;height:28px;border-radius:50%;background:#2563EB;color:white;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.card-header h2{font-size:15px;font-weight:700;color:#1E293B}
.card-body{padding:20px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
.fg:last-child{margin-bottom:0}
label{font-size:12px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.04em}
label span{color:#EF4444}
input,select,textarea{padding:10px 13px;font-size:14px;font-family:inherit;border:1.5px solid #E2E8F0;border-radius:9px;background:#F8FAFC;outline:none;width:100%;transition:border 200ms,background 200ms;color:#1E293B}
input:focus,select:focus,textarea:focus{border-color:#2563EB;background:white;box-shadow:0 0 0 3px rgba(37,99,235,0.08)}
input[type="file"]{padding:7px 12px;font-size:13px}
.req{font-size:11px;color:#94A3B8;margin-top:3px}
.submit-bar{background:white;border-top:1px solid #E2E8F0;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:7px;padding:12px 28px;font-size:14px;font-weight:700;border-radius:10px;border:none;cursor:pointer;text-decoration:none;transition:all 200ms;font-family:inherit}
.btn-primary{background:#D79A21;color:white;box-shadow:0 2px 8px rgba(215,154,33,0.3)}
.btn-primary:hover{background:#1D4ED8}
.btn-ghost{background:#F1F5F9;color:#475569}
.error{background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:12px 16px;font-size:13px;color:#B91C1C;margin-bottom:16px}
.note{background:#EFF6FF;border:1px solid #BFDBFE;border-radius:10px;padding:12px 16px;font-size:13px;color:#1E40AF;margin-bottom:18px;line-height:1.5}
.divider{border:none;border-top:1px solid #F1F5F9;margin:14px 0}
@media(max-width:600px){.row,.row3{grid-template-columns:1fr}.submit-bar{flex-direction:column}.btn{width:100%;justify-content:center}}
</style>
</head>
<body>

<div class="topbar">
    <div class="logo">
        @if($tenant->logo_path)<img src="{{ asset($tenant->logo_path) }}">
        @else{{ strtoupper(substr($tenant->name,0,1)) }}@endif
    </div>
    <div>
        <h1>{{ $tenant->name }}</h1>
        <p>Online Application Form @if($settings->academic_year)— {{ $settings->academic_year }}@endif</p>
    </div>
    <a href="{{ route('portal.landing', $tenant->slug) }}" class="back">&#8592; Back</a>
</div>

<div class="container">

@if($errors->any())
<div class="error">
    <strong>Please fix the following errors:</strong><br>
    @foreach($errors->all() as $e)&bull; {{ $e }}<br>@endforeach
</div>
@endif

@if($settings->application_fee > 0)
<div class="note">
    &#128182; An application fee of <strong>&#8358;{{ number_format($settings->application_fee) }}</strong> is required. Payment details will be provided after submission.
</div>
@endif

<form method="POST" action="{{ route('portal.submit', $tenant->slug) }}" enctype="multipart/form-data">
@csrf

{{-- STUDENT INFO --}}
<div class="card">
    <div class="card-header">
        <div class="num">1</div>
        <h2>Student Information</h2>
    </div>
    <div class="card-body">
        <div class="row3">
            <div class="fg"><label>Last Name <span>*</span></label><input type="text" name="last_name" value="{{ old('last_name') }}" required placeholder="Surname"></div>
            <div class="fg"><label>First Name <span>*</span></label><input type="text" name="first_name" value="{{ old('first_name') }}" required placeholder="Given name"></div>
            <div class="fg"><label>Other Names</label><input type="text" name="other_names" value="{{ old('other_names') }}" placeholder="Middle name(s)"></div>
        </div>
        <div class="row">
            <div class="fg"><label>Date of Birth <span>*</span></label><input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required></div>
            <div class="fg"><label>Gender <span>*</span></label>
                <select name="gender" required>
                    <option value="">Select gender</option>
                    <option value="male" {{ old('gender')==='male'?'selected':'' }}>Male</option>
                    <option value="female" {{ old('gender')==='female'?'selected':'' }}>Female</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="fg"><label>Religion</label>
                <select name="religion">
                    <option value="">Select</option>
                    <option value="Islam" {{ old('religion')==='Islam'?'selected':'' }}>Islam</option>
                    <option value="Christianity" {{ old('religion')==='Christianity'?'selected':'' }}>Christianity</option>
                    <option value="Other" {{ old('religion')==='Other'?'selected':'' }}>Other</option>
                </select>
            </div>
            <div class="fg"><label>State of Origin</label>
                <select name="state_of_origin">
                    <option value="">Select state</option>
                    @foreach($nigerianStates as $state)
                    <option value="{{ $state }}" {{ old('state_of_origin')===$state?'selected':'' }}>{{ $state }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="fg"><label>Home Address <span>*</span></label><input type="text" name="address" value="{{ old('address') }}" required placeholder="Full residential address"></div>
        <hr class="divider">
        <div class="row">
            <div class="fg"><label>Class Applying For <span>*</span></label>
                <select name="applying_for_class_level_id" required>
                    <option value="">Select class</option>
                    @foreach($classLevels as $level)
                    <option value="{{ $level->id }}" {{ old('applying_for_class_level_id')==$level->id?'selected':'' }}>{{ $level->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fg"><label>Academic Year</label>
                <input type="text" name="academic_year" value="{{ old('academic_year', $settings->academic_year) }}" placeholder="e.g. 2025/2026">
            </div>
        </div>
        <div class="row">
            <div class="fg"><label>Previous School</label><input type="text" name="previous_school" value="{{ old('previous_school') }}" placeholder="Last school attended"></div>
            <div class="fg"><label>Previous Class</label><input type="text" name="previous_class" value="{{ old('previous_class') }}" placeholder="e.g. Primary 5, JSS2"></div>
        </div>
    </div>
</div>

{{-- GUARDIAN INFO --}}
<div class="card">
    <div class="card-header">
        <div class="num">2</div>
        <h2>Parent / Guardian Information</h2>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="fg"><label>Full Name <span>*</span></label><input type="text" name="guardian_name" value="{{ old('guardian_name') }}" required placeholder="Guardian full name"></div>
            <div class="fg"><label>Relationship <span>*</span></label>
                <select name="guardian_relationship" required>
                    <option value="">Select</option>
                    <option value="father" {{ old('guardian_relationship')==='father'?'selected':'' }}>Father</option>
                    <option value="mother" {{ old('guardian_relationship')==='mother'?'selected':'' }}>Mother</option>
                    <option value="guardian" {{ old('guardian_relationship')==='guardian'?'selected':'' }}>Guardian</option>
                    <option value="sibling" {{ old('guardian_relationship')==='sibling'?'selected':'' }}>Sibling</option>
                    <option value="other" {{ old('guardian_relationship')==='other'?'selected':'' }}>Other</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="fg">
                <label>Phone Number <span>*</span></label>
                <input type="tel" name="guardian_phone" value="{{ old('guardian_phone') }}" required placeholder="08012345678">
                <span class="req">&#x26A0;&#xFE0F; SMS confirmation will be sent to this number</span>
            </div>
            <div class="fg"><label>Email Address</label><input type="email" name="guardian_email" value="{{ old('guardian_email') }}" placeholder="parent@email.com"></div>
        </div>
        <div class="row">
            <div class="fg"><label>Occupation</label><input type="text" name="guardian_occupation" value="{{ old('guardian_occupation') }}" placeholder="e.g. Teacher, Trader"></div>
            <div class="fg"><label>Guardian Address</label><input type="text" name="guardian_address" value="{{ old('guardian_address') }}" placeholder="If different from student"></div>
        </div>
    </div>
</div>

{{-- DOCUMENTS --}}
@if($settings->require_passport || $settings->require_birth_cert || $settings->require_report_card)
<div class="card">
    <div class="card-header">
        <div class="num">3</div>
        <h2>Documents Upload</h2>
    </div>
    <div class="card-body">
        @if($settings->require_passport)
        <div class="fg">
            <label>Passport Photograph @if($settings->require_passport)<span>*</span>@endif</label>
            <input type="file" name="passport_photo" accept="image/*" @if($settings->require_passport) required @endif>
            <span class="req">JPG or PNG, max 2MB. Recent photo with white background.</span>
        </div>
        @endif
        @if($settings->require_birth_cert)
        <div class="fg">
            <label>Birth Certificate @if($settings->require_birth_cert)<span>*</span>@endif</label>
            <input type="file" name="birth_certificate" accept=".pdf,.jpg,.jpeg,.png" @if($settings->require_birth_cert) required @endif>
            <span class="req">PDF or image, max 4MB.</span>
        </div>
        @endif
        @if($settings->require_report_card)
        <div class="fg">
            <label>Last School Report Card @if($settings->require_report_card)<span>*</span>@endif</label>
            <input type="file" name="last_report_card" accept=".pdf,.jpg,.jpeg,.png" @if($settings->require_report_card) required @endif>
            <span class="req">PDF or image, max 4MB. Most recent term result.</span>
        </div>
        @endif
    </div>
</div>
@endif

<div class="submit-bar">
    <div style="font-size:13px;color:#64748B">
        &#x26A0;&#xFE0F; By submitting, you confirm all information is accurate.
    </div>
    <button type="submit" class="btn btn-primary">&#128228; Submit Application</button>
</div>
</form>

</div>
</body>
</html>
