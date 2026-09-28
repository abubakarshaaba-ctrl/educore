<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>EduCore — Student Admission</title>
<style>
body{margin:0;background:#f4f6f8;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#17202a}.wrap{max-width:760px;margin:0 auto;padding:28px 16px}.card{background:#fff;border:1px solid #e1e6eb;border-radius:12px;padding:24px;box-shadow:0 8px 24px rgba(20,30,40,.06)}h1{font-size:24px;margin:0 0 8px}.muted{color:#66717d}.brand{font-weight:800;letter-spacing:.2px;margin-bottom:18px}.alert{padding:12px;border-radius:8px;margin:14px 0;background:#f1f5f9}.error{background:#fff1f2;color:#991b1b}.success{background:#ecfdf5;color:#065f46}.field{margin-top:16px}.field label{display:block;font-weight:700;margin-bottom:7px}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}input,select{width:100%;box-sizing:border-box;border:1px solid #ccd3da;border-radius:8px;padding:11px;font:inherit;background:#fff}.student-block{border-top:1px solid #e1e6eb;margin-top:22px;padding-top:18px}.student-heading{display:flex;justify-content:space-between;align-items:center;gap:10px;font-weight:800}.remove{border:0;background:none;color:#991b1b;font-weight:700;cursor:pointer}.add{margin-top:16px;border:1px solid #ccd3da;background:#fff;border-radius:8px;padding:10px 14px;font-weight:700;cursor:pointer}.btn{margin-top:18px;width:100%;border:0;border-radius:8px;padding:12px 16px;background:#1f2937;color:#fff;font-weight:700;cursor:pointer}.results{margin-top:12px}.result-row{padding:10px 0;border-bottom:1px solid #dfe5ea}.result-row:last-child{border-bottom:0}.small{font-size:13px;color:#66717d}@media(max-width:680px){.grid{grid-template-columns:1fr}}
</style>
</head>
<body><main class="wrap"><div class="brand">EduCore</div><section class="card">
<h1>Student Admission</h1>
<p class="muted">{{ $session->name }} · {{ $term->name }}</p>
<p>Register one or more students under the same parent/guardian. Each student can be assigned to the same or a different class/class arm. Admission numbers are generated automatically after successful submission.</p>
@if(session('success'))<div class="alert success"><strong>{{ session('success') }}</strong>@if(session('created_students'))<div class="results">@foreach(session('created_students') as $student)<div class="result-row"><strong>{{ $student['name'] }}</strong><br><span class="small">Admission No.: <strong>{{ $student['admission_number'] }}</strong> · {{ $student['class'] }}</span></div>@endforeach</div>@endif</div>@endif
@if($errors->any())<div class="alert error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<form method="post" id="admissionForm">@csrf
<div class="field"><label>Parent / Guardian Information</label><div class="grid">
<div><input type="text" name="guardian_first_name" placeholder="First name" value="{{ old('guardian_first_name') }}" required></div>
<div><input type="text" name="guardian_last_name" placeholder="Last name" value="{{ old('guardian_last_name') }}" required></div>
<div><input type="text" name="guardian_phone" placeholder="Phone number" value="{{ old('guardian_phone') }}" required></div>
<div><input type="email" name="guardian_email" placeholder="Email address (optional)" value="{{ old('guardian_email') }}"></div>
<div><select name="guardian_relationship" required><option value="">Relationship</option>@foreach(['father'=>'Father','mother'=>'Mother','guardian'=>'Guardian','other'=>'Other'] as $value=>$label)<option value="{{ $value }}" @selected(old('guardian_relationship')===$value)>{{ $label }}</option>@endforeach</select></div>
</div></div>
<div id="studentsContainer">@php $oldStudents=old('students',[[]]); $geo=\App\Data\NigeriaGeo::all(); @endphp
@foreach($oldStudents as $i=>$student)
@include('public.student-admission-fields',['index'=>$i,'student'=>$student,'classes'=>$classes,'geo'=>$geo])
@endforeach
</div>
<button type="button" class="add" id="addStudent">+ Add Another Student</button><button class="btn" type="submit">Submit Student Admission</button>
</form></section></main>
<script>
const classes=@json($classes->map(fn($c)=>['id'=>$c->id,'name'=>$c->full_name])->values()),geo=@json($geo);let studentIndex={{count($oldStudents)}};
function esc(v){return String(v??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m])}
function classOptions(){return '<option value="">Select class / class arm</option>'+classes.map(c=>'<option value="'+c.id+'">'+esc(c.name)+'</option>').join('')}
function stateOptions(){return '<option value="">Select State</option>'+Object.keys(geo).map(s=>'<option value="'+esc(s)+'">'+esc(s)+'</option>').join('')}
function studentFields(i){return '<div class="student-block"><div class="student-heading"><span>Student '+(i+1)+'</span><button type="button" class="remove" onclick="removeStudent(this)">Remove</button></div><div class="grid">'+
'<div class="field"><label>First Name *</label><input name="students['+i+'][first_name]" required></div><div class="field"><label>Last Name *</label><input name="students['+i+'][last_name]" required></div>'+
'<div class="field"><label>Middle Name</label><input name="students['+i+'][middle_name]"></div><div class="field"><label>Gender *</label><select name="students['+i+'][gender]" required><option value="">Select gender</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option></select></div>'+
'<div class="field"><label>Date of Birth *</label><input type="date" name="students['+i+'][date_of_birth]" required></div><div class="field"><label>Religion</label><select name="students['+i+'][religion]"><option value="">Select</option><option>Islam</option><option>Christianity</option><option>Other</option></select></div>'+
'<div class="field"><label>State of Origin</label><select name="students['+i+'][state_of_origin]" onchange="updateLga(this,'+i+')">'+stateOptions()+'</select></div><div class="field"><label>LGA of Origin</label><select name="students['+i+'][lga_of_origin]" id="lga_'+i+'"><option value="">Select LGA</option></select></div>'+
'<div class="field"><label>Blood Group</label><select name="students['+i+'][blood_group]"><option value="">Unknown</option><option>A+</option><option>A-</option><option>B+</option><option>B-</option><option>AB+</option><option>AB-</option><option>O+</option><option>O-</option></select></div>'+
'<div class="field"><label>Genotype</label><select name="students['+i+'][genotype]"><option value="">Unknown</option><option>AA</option><option>AS</option><option>AC</option><option>SS</option><option>SC</option></select></div>'+
'<div class="field"><label>Class / Class Arm *</label><select name="students['+i+'][current_class_arm_id]" required>'+classOptions()+'</select></div><div class="field"><label>Admission Date *</label><input type="date" name="students['+i+'][admission_date]" value="{{date('Y-m-d')}}" required></div></div></div>'}
function updateLga(el,i){const l=document.getElementById('lga_'+i),v=(geo[el.value]||{}).lgas||[];l.innerHTML='<option value="">Select LGA</option>'+v.map(x=>'<option value="'+esc(x)+'">'+esc(x)+'</option>').join('')}
function removeStudent(btn){const b=document.querySelectorAll('.student-block');if(b.length<=1)return;btn.closest('.student-block').remove();document.querySelectorAll('.student-block').forEach((x,i)=>x.querySelector('.student-heading span').textContent='Student '+(i+1))}
document.getElementById('addStudent').addEventListener('click',()=>document.getElementById('studentsContainer').insertAdjacentHTML('beforeend',studentFields(studentIndex++)));
</script></body></html>