@php
$student = $student ?? [];
$index = $index ?? 0;
$geo = $geo ?? [];
@endphp
<div class="student-block" data-student-index="{{ $index }}">
<div class="student-heading"><span>Student {{ $index + 1 }}</span><button type="button" class="remove" onclick="removeStudent(this)">Remove</button></div>
<div class="grid">
<div class="field"><label>First Name *</label><input name="students[{{ $index }}][first_name]" value="{{ $student['first_name'] ?? '' }}" required></div>
<div class="field"><label>Last Name *</label><input name="students[{{ $index }}][last_name]" value="{{ $student['last_name'] ?? '' }}" required></div>
<div class="field"><label>Middle Name</label><input name="students[{{ $index }}][middle_name]" value="{{ $student['middle_name'] ?? '' }}"></div>
<div class="field"><label>Gender *</label><select name="students[{{ $index }}][gender]" required><option value="">Select gender</option><option value="male" @selected(($student['gender'] ?? '')==='male')>Male</option><option value="female" @selected(($student['gender'] ?? '')==='female')>Female</option><option value="other" @selected(($student['gender'] ?? '')==='other')>Other</option></select></div>
<div class="field"><label>Date of Birth *</label><input type="date" name="students[{{ $index }}][date_of_birth]" value="{{ $student['date_of_birth'] ?? '' }}" required></div>
<div class="field"><label>Religion</label><select name="students[{{ $index }}][religion]"><option value="">Select</option><option value="Islam" @selected(($student['religion'] ?? '')==='Islam')>Islam</option><option value="Christianity" @selected(($student['religion'] ?? '')==='Christianity')>Christianity</option><option value="Other" @selected(($student['religion'] ?? '')==='Other')>Other</option></select></div>
<div class="field"><label>State of Origin</label><select name="students[{{ $index }}][state_of_origin]" onchange="updateLga(this,{{ $index }})"><option value="">Select State</option>@foreach(array_keys($geo) as $state)<option value="{{ $state }}" @selected(($student['state_of_origin'] ?? '')===$state)>{{ $state }}</option>@endforeach</select></div>
<div class="field"><label>LGA of Origin</label><select name="students[{{ $index }}][lga_of_origin]" id="lga_{{ $index }}"><option value="">Select LGA</option>@if(!empty($student['state_of_origin']) && isset($geo[$student['state_of_origin']]))@foreach($geo[$student['state_of_origin']]['lgas'] as $lga)<option value="{{ $lga }}" @selected(($student['lga_of_origin'] ?? '')===$lga)>{{ $lga }}</option>@endforeach@endif</select></div>
<div class="field"><label>Blood Group</label><select name="students[{{ $index }}][blood_group]"><option value="">Unknown</option>@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)<option value="{{ $bg }}" @selected(($student['blood_group'] ?? '')===$bg)>{{ $bg }}</option>@endforeach</select></div>
<div class="field"><label>Genotype</label><select name="students[{{ $index }}][genotype]"><option value="">Unknown</option>@foreach(['AA','AS','AC','SS','SC'] as $gt)<option value="{{ $gt }}" @selected(($student['genotype'] ?? '')===$gt)>{{ $gt }}</option>@endforeach</select></div>
<div class="field"><label>Class / Class Arm *</label><select name="students[{{ $index }}][current_class_arm_id]" required><option value="">Select class / class arm</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected((string)($student['current_class_arm_id'] ?? '')===(string)$class->id)>{{ $class->full_name }}</option>@endforeach</select></div>
<div class="field"><label>Admission Date *</label><input type="date" name="students[{{ $index }}][admission_date]" value="{{ $student['admission_date'] ?? date('Y-m-d') }}" required></div>
</div></div>