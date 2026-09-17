<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Staff Profile — {{ $tenant->name }}</title>
<style>
*{box-sizing:border-box}
html,body{max-width:100%;overflow-x:hidden}
body{margin:0;font-family:Arial,sans-serif;background:#f8fafc;color:#0f172a}
.wrap{max-width:860px;margin:0 auto;padding:20px;width:100%}
.card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;min-width:0}
.head{background:#0b2a55;color:#fff;padding:20px;border-bottom:4px solid #d9a017}
.head h2,.head .school-name{color:#fff!important;overflow-wrap:anywhere}
.head h2{margin:0;font-size:24px;font-weight:800;line-height:1.2}
.school-name{margin-top:6px;font-size:17px;opacity:.95}
.body{padding:20px;min-width:0}
.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;min-width:0}
.fg{display:flex;flex-direction:column;gap:6px;min-width:0}
.full{grid-column:1/-1;min-width:0}
label{font-size:12px;font-weight:700;text-transform:uppercase;line-height:1.35}
input,select{width:100%;min-width:0;max-width:100%;box-sizing:border-box;padding:12px;border:1.5px solid #cbd5e1;border-radius:9px;background:#f8fafc;font-size:14px;color:#0f172a;font-family:inherit}
input:focus,select:focus{outline:none;border-color:#0b2a55;box-shadow:0 0 0 3px rgba(217,160,23,.28);background:#fff}
.btn{width:100%;min-height:46px;padding:13px;border:0;border-radius:9px;background:#d9a017;color:#0b2a55;font-weight:800;font-size:15px;font-family:inherit;cursor:pointer}
.ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;padding:12px;border-radius:9px;margin-bottom:14px;overflow-wrap:anywhere}
.err{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:12px;border-radius:9px;margin-bottom:14px;overflow-wrap:anywhere}
.note{font-size:12px;color:#64748b;line-height:1.5;margin-bottom:16px;overflow-wrap:anywhere}
.hint{font-size:11px;color:#64748b;line-height:1.45;overflow-wrap:anywhere}
@media(max-width:700px){
    .wrap{padding:10px}
    .head{padding:18px 16px}
    .head h2{font-size:21px}
    .school-name{font-size:16px}
    .body{padding:14px}
    .grid{grid-template-columns:1fr;gap:12px}
    .full{grid-column:auto}
    input,select{font-size:16px;padding:11px}
}
@media(max-width:420px){
    .wrap{padding:0}
    .card{border-radius:0;border-left:0;border-right:0}
    .head{padding:16px 14px}
    .head h2{font-size:19px}
    .school-name{font-size:14px}
    .body{padding:12px}
    .note{font-size:11px}
    .btn{font-size:14px}
}
</style>
</head>
<body>
<div class="wrap">
<div class="card">
<div class="head">
    <h2>Staff Profile Registration</h2>
    <div class="school-name">{{ $tenant->name }}</div>
</div>
<div class="body">
@if(session('success'))<div class="ok">{{ session('success') }}</div>@endif
@if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif
<div class="note">Complete your profile accurately. Submission does not activate access automatically; a school administrator will review and assign your system role.</div>
<form method="POST" action="{{ route('staff.join.store',$token) }}">
@csrf
<div class="grid">
    <div class="fg"><label>Full Name *</label><input name="name" value="{{ old('name') }}" placeholder="First name, Surname" required></div>
    <div class="fg"><label>Email *</label><input type="email" name="email" value="{{ old('email') }}" required></div>
    <div class="fg"><label>Phone</label><input name="phone" value="{{ old('phone') }}"></div>
    <div class="fg"><label>Date of Birth</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"></div>
    <div class="fg"><label>Gender *</label><select name="gender" required><option value="">Select</option><option value="male" @selected(old('gender')==='male')>Male</option><option value="female" @selected(old('gender')==='female')>Female</option></select></div>
    <div class="fg"><label>Highest Qualification *</label><select name="qualification" required><option value="">— Select Qualification —</option>@foreach($highestQualifications as $q)<option value="{{ $q }}" @selected(old('qualification')===$q)>{{ $q }}</option>@endforeach</select></div>
    <div class="fg full"><label>Home Address</label><input name="address" value="{{ old('address') }}"></div>
    <div class="fg"><label>Employment Start Date *</label><input type="date" name="employment_started_at" value="{{ old('employment_started_at') }}" required></div>
    <div class="fg"><label>Position Title *</label><input name="position_title" value="{{ old('position_title') }}" placeholder="e.g. Mathematics Teacher" required></div>
    <div class="fg"><label>Department</label><select name="department_name"><option value="">— Select Department —</option>@foreach($departments as $department)<option value="{{ $department }}" @selected(old('department_name')===$department)>{{ $department }}</option>@endforeach</select></div>
    <div class="fg"><label>Employment Type *</label><select name="employment_type" required><option value="">— Select Employment Type —</option>@foreach($employmentTypes as $employmentType)<option value="{{ $employmentType }}" @selected(old('employment_type')===$employmentType)>{{ $employmentType }}</option>@endforeach</select><div class="hint">Working-time arrangement: Full-time or Part-time.</div></div>
    <div class="fg full"><label>Appointment Type *</label><select name="appointment_type" required><option value="">— Select Appointment Type —</option>@foreach($appointmentTypes as $appointmentType)<option value="{{ $appointmentType }}" @selected(old('appointment_type')===$appointmentType)>{{ $appointmentType }}</option>@endforeach</select><div class="hint">Administrative basis of the appointment. Employment-history events are recorded separately by the school.</div></div>
    <div class="fg"><label>Create Password *</label><input type="password" name="password" required></div>
    <div class="fg"><label>Confirm Password *</label><input type="password" name="password_confirmation" required></div>
    <div class="full"><button class="btn" type="submit">Submit Staff Profile</button></div>
</div>
</form>
</div>
</div>
</div>
</body>
</html>
