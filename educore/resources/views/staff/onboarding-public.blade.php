<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Staff Profile — {{ $tenant->name }}</title>
<style>
body{margin:0;font-family:Arial,sans-serif;background:#f8fafc;color:#0f172a}
.wrap{max-width:860px;margin:0 auto;padding:20px}
.card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden}
.head{background:#0b2a55;color:#fff;padding:20px;border-bottom:4px solid #d9a017}
.head h2,.head .school-name{color:#fff!important}
.head h2{margin:0;font-size:24px;font-weight:800;line-height:1.2}
.school-name{margin-top:6px;font-size:17px;opacity:.95}
.body{padding:20px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.fg{display:flex;flex-direction:column;gap:6px}
.full{grid-column:1/-1}
label{font-size:12px;font-weight:700;text-transform:uppercase}
input,select{width:100%;box-sizing:border-box;padding:12px;border:1.5px solid #cbd5e1;border-radius:9px;background:#f8fafc;font-size:14px;color:#0f172a}
input:focus,select:focus{outline:none;border-color:#0b2a55;box-shadow:0 0 0 3px rgba(217,160,23,.28);background:#fff}
.btn{width:100%;padding:13px;border:0;border-radius:9px;background:#d9a017;color:#0b2a55;font-weight:800;font-size:15px}
.ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;padding:12px;border-radius:9px;margin-bottom:14px}
.err{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:12px;border-radius:9px;margin-bottom:14px}
.note{font-size:12px;color:#64748b;line-height:1.5;margin-bottom:16px}
@media(max-width:700px){.wrap{padding:10px}.head{padding:18px}.head h2{font-size:21px}.school-name{font-size:16px}.body{padding:14px}.grid{grid-template-columns:1fr}.full{grid-column:auto}}
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
    <div class="fg"><label>Full Name *</label><input name="name" value="{{ old('name') }}" required></div>
    <div class="fg"><label>Email *</label><input type="email" name="email" value="{{ old('email') }}" required></div>
    <div class="fg"><label>Phone</label><input name="phone" value="{{ old('phone') }}"></div>
    <div class="fg"><label>Date of Birth</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"></div>
    <div class="fg"><label>Gender *</label><select name="gender" required><option value="">Select</option><option value="male" @selected(old('gender')==='male')>Male</option><option value="female" @selected(old('gender')==='female')>Female</option></select></div>
    <div class="fg"><label>Highest Qualification *</label><select name="qualification" required><option value="">— Select Qualification —</option>@foreach($highestQualifications as $q)<option value="{{ $q }}" @selected(old('qualification')===$q)>{{ $q }}</option>@endforeach</select></div>
    <div class="fg full"><label>Home Address</label><input name="address" value="{{ old('address') }}"></div>
    <div class="fg"><label>Employment Start Date</label><input type="date" name="employment_started_at" value="{{ old('employment_started_at') }}"></div>
    <div class="fg"><label>Position Title *</label><input name="position_title" value="{{ old('position_title') }}" placeholder="e.g. Mathematics Teacher" required></div>
    <div class="fg"><label>Department</label><select name="department_name"><option value="">— Select Department —</option>@foreach($departments as $department)<option value="{{ $department }}" @selected(old('department_name')===$department)>{{ $department }}</option>@endforeach</select></div>
    <div class="fg"><label>Employment Type</label><select name="employment_type"><option value="">— Select Employment Type —</option>@foreach($employmentTypes as $employmentType)<option value="{{ $employmentType }}" @selected(old('employment_type')===$employmentType)>{{ $employmentType }}</option>@endforeach</select></div>
    <div class="fg full"><label>Appointment Type</label><select name="appointment_type"><option value="">— Select Appointment Type —</option>@foreach($appointmentTypes as $appointmentType)<option value="{{ $appointmentType }}" @selected(old('appointment_type')===$appointmentType)>{{ $appointmentType }}</option>@endforeach</select></div>
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
