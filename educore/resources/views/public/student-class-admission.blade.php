<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $tenant->name }} — Student Admission</title>
<style>
/* ============================================================
   Premium Student Admission Form
   ============================================================ */

/* 1. Base & Layout */
body {
    margin: 0;
    background: #F8FAFC;
    font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    color: #1E293B;
    line-height: 1.5;
}

.wrap {
    max-width: 900px;
    margin: 0 auto;
    padding: 40px 20px;
}

/* 2. Main Card Container */
.card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 20px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
    overflow: hidden;
}

/* 3. Header */
.adm-header {
    padding: 32px 40px;
    border-bottom: 1px solid #E2E8F0;
    background: #FFFFFF;
}

.school-brand {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 18px;
}
.school-logo {
    width: 64px;
    height: 64px;
    flex: 0 0 64px;
    object-fit: contain;
    border-radius: 10px;
    background: #FFFFFF;
}
.school-brand-copy { min-width: 0; }
.school-name {
    margin: 0;
    font-size: 21px;
    font-weight: 800;
    line-height: 1.25;
    color: #1E293B;
}
.school-address {
    margin: 5px 0 0;
    font-size: 13px;
    line-height: 1.45;
    color: #64748B;
    max-width: 680px;
}
.brand {
    font-weight: 800;
    font-size: 14px;
    color: #F59E0B;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    margin-bottom: 12px;
    display: inline-block;
}

.adm-header h1 {
    font-size: 28px;
    font-weight: 800;
    color: #1E293B;
    margin: 0 0 8px;
    letter-spacing: -0.02em;
}

.adm-header .session-badge {
    display: inline-block;
    background: #FEF3C7;
    color: #D97706;
    font-size: 13px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 999px;
    margin-bottom: 16px;
}

.adm-header p {
    margin: 0;
    font-size: 15px;
    color: #64748B;
    max-width: 700px;
    line-height: 1.6;
}

/* 4. Form Body */
.adm-body {
    padding: 40px;
}

/* Section Titles */
.section-title {
    font-size: 18px;
    font-weight: 700;
    color: #1E293B;
    margin: 0 0 24px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title::before {
    content: '';
    display: block;
    width: 4px;
    height: 20px;
    background: #F59E0B;
    border-radius: 2px;
}

/* 5. Grid & Fields */
.adm-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.adm-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.adm-field label {
    font-size: 13px;
    font-weight: 600;
    color: #1E293B;
}

.adm-field label .required {
    color: #EF4444;
    margin-left: 2px;
}

.adm-field input,
.adm-field select {
    width: 100%;
    box-sizing: border-box;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 12px 16px;
    font: inherit;
    font-size: 14px;
    background: #F8FAFC;
    color: #1E293B;
    outline: none;
    transition: all 0.2s ease;
}

/* Custom Select Arrow */
.adm-field select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 16px center;
    padding-right: 40px;
}

.adm-field input:focus,
.adm-field select:focus {
    border-color: #F59E0B;
    background: #FFFFFF;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.15);
}

/* 6. Student Blocks */
.student-block {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
    transition: box-shadow 0.3s ease;
}

.student-block:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
}

.student-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #F1F5F9;
}

.student-heading span {
    font-size: 16px;
    font-weight: 700;
    color: #1E293B;
}

.remove-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1px solid #FECACA;
    background: #FFFFFF;
    color: #EF4444;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.remove-btn:hover {
    background: #FEF2F2;
    border-color: #FCA5A5;
}

/* 7. Buttons */
.adm-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 10px;
    padding: 12px 24px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.adm-btn-primary {
    background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
    color: #1E293B;
    box-shadow: 0 4px 6px rgba(245, 158, 11, 0.2);
}

.adm-btn-primary:hover {
    background: linear-gradient(135deg, #D97706 0%, #B45309 100%);
    transform: translateY(-1px);
    box-shadow: 0 6px 8px rgba(245, 158, 11, 0.3);
}

.adm-btn-secondary {
    background: #FFFFFF;
    border-color: #E2E8F0;
    color: #1E293B;
}

.adm-btn-secondary:hover {
    background: #F8FAFC;
    border-color: #CBD5E1;
}

.add-student-wrapper {
    margin-bottom: 40px;
}

.adm-actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 24px;
    border-top: 1px solid #E2E8F0;
}

/* 8. Alerts */
.adm-alert {
    padding: 16px;
    border-radius: 12px;
    margin-bottom: 24px;
    font-size: 14px;
    line-height: 1.5;
    border: 1px solid transparent;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}

.adm-alert.success {
    background: #ECFDF5;
    border-color: #A7F3D0;
    color: #065F46;
}

.adm-alert.error {
    background: #FEF2F2;
    border-color: #FECACA;
    color: #991B1B;
}

.adm-alert strong { display: block; margin-bottom: 4px; }

.results {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px dashed #A7F3D0;
}

.result-row {
    padding: 8px 0;
    border-bottom: 1px solid #D1FAE5;
}
.result-row:last-child { border-bottom: 0; }

/* 9. Responsive */
@media (max-width: 768px) {
    .adm-grid {
        grid-template-columns: 1fr;
    }
    .adm-header, .adm-body {
        padding: 24px;
    }
    .school-brand {
        align-items: flex-start;
        gap: 12px;
    }
    .school-logo {
        width: 52px;
        height: 52px;
        flex-basis: 52px;
    }
    .school-name {
        font-size: 18px;
    }
    .adm-actions {
        flex-direction: column;
    }
    .adm-actions .adm-btn {
        width: 100%;
    }
}
</style>
</head>
<body>

<main class="wrap">
    <section class="card">
        
        {{-- HEADER --}}
        <div class="adm-header">
            <div class="school-brand">
                @if($tenant->logo_path)
                    <img class="school-logo" src="{{ \Illuminate\Support\Facades\Storage::url($tenant->logo_path) }}" alt="{{ $tenant->name }} logo">
                @else
                    <div class="school-logo" aria-hidden="true"></div>
                @endif
                <div class="school-brand-copy">
                    <h2 class="school-name">{{ $tenant->name }}</h2>
                    @if($tenant->address)
                        <p class="school-address">{{ $tenant->address }}</p>
                    @endif
                </div>
            </div>
            <div class="brand">EduCore</div>
            <div class="session-badge">{{ $session->name }} · {{ $term->name }}</div>
            <h1>Student Admission</h1>
            <p>Register one or more students under the same parent/guardian. Each student can be assigned to the same or a different class/class arm. Admission numbers are generated automatically after successful submission.</p>
        </div>

        <div class="adm-body">
            
            {{-- ALERTS --}}
            @if(session('success'))
                <div class="adm-alert success">
                    <strong>{{ session('success') }}</strong>
                    @if(session('created_students'))
                        <div class="results">
                            @foreach(session('created_students') as $student)
                                <div class="result-row">
                                    <strong>{{ $student['name'] }}</strong><br>
                                    <span style="font-size:13px; opacity:0.9;">Admission No.: <strong>{{ $student['admission_number'] }}</strong> · {{ $student['class'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            @if($errors->any())
                <div class="adm-alert error">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            {{-- FORM --}}
            <form method="post" id="admissionForm">
                @csrf

                {{-- PARENT / GUARDIAN SECTION --}}
                <h2 class="section-title">Parent / Guardian Information</h2>
                <div class="adm-grid">
                    <div class="adm-field">
                        <label>First Name <span class="required">*</span></label>
                        <input type="text" name="guardian_first_name" placeholder="Enter first name" value="{{ old('guardian_first_name') }}" required>
                    </div>
                    <div class="adm-field">
                        <label>Last Name <span class="required">*</span></label>
                        <input type="text" name="guardian_last_name" placeholder="Enter last name" value="{{ old('guardian_last_name') }}" required>
                    </div>
                    <div class="adm-field">
                        <label>Phone Number <span class="required">*</span></label>
                        <input type="text" name="guardian_phone" placeholder="e.g. 08012345678" value="{{ old('guardian_phone') }}" required>
                    </div>
                    <div class="adm-field">
                        <label>Email Address <span style="color:#94A3B8; font-weight:400;">(optional)</span></label>
                        <input type="email" name="guardian_email" placeholder="parent@example.com" value="{{ old('guardian_email') }}">
                    </div>
                    <div class="adm-field">
                        <label>Relationship to Student(s) <span class="required">*</span></label>
                        <select name="guardian_relationship" required>
                            <option value="">Select relationship...</option>
                            @foreach(['father'=>'Father','mother'=>'Mother','guardian'=>'Guardian','other'=>'Other'] as $value=>$label)
                                <option value="{{ $value }}" @selected(old('guardian_relationship')===$value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- STUDENTS SECTION --}}
                <h2 class="section-title" style="margin-top: 40px;">Students to Register</h2>
                
                <div id="studentsContainer">
                    @php 
                        $oldStudents = old('students', [[]]); 
                        $geo = \App\Data\NigeriaGeo::all(); 
                    @endphp
                    @foreach($oldStudents as $i => $student)
                        @include('public.student-admission-fields', [
                            'index' => $i,
                            'student' => $student,
                            'classes' => $classes,
                            'geo' => $geo
                        ])
                    @endforeach
                </div>

                <div class="add-student-wrapper">
                    <button type="button" class="adm-btn adm-btn-secondary" id="addStudent">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add Another Student
                    </button>
                </div>

                {{-- ACTIONS --}}
                <div class="adm-actions">
                    <button class="adm-btn adm-btn-primary" type="submit">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Submit Student Admission
                    </button>
                </div>

            </form>
        </div>
    </section>
</main>

<script>
const classes = @json($classes->map(fn($c) => ['id' => $c->id, 'name' => $c->full_name])->values());
const geo = @json($geo);
let studentIndex = {{ count($oldStudents) }};

function esc(v) {
    return String(v ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m]);
}

function classOptions() {
    return '<option value="">Select class / class arm</option>' + classes.map(c => '<option value="' + c.id + '">' + esc(c.name) + '</option>').join('');
}

function stateOptions() {
    return '<option value="">Select State</option>' + Object.keys(geo).map(s => '<option value="' + esc(s) + '">' + esc(s) + '</option>').join('');
}

function studentFields(i) {
    return `
    <div class="student-block" data-student-index="${i}">
        <div class="student-heading">
            <span>Student ${i + 1}</span>
            <button type="button" class="remove-btn" onclick="removeStudent(this)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Remove
            </button>
        </div>
        <div class="adm-grid">
            <div class="adm-field"><label>First Name <span class="required">*</span></label><input name="students[${i}][first_name]" placeholder="Enter first name" required></div>
            <div class="adm-field"><label>Last Name <span class="required">*</span></label><input name="students[${i}][last_name]" placeholder="Enter last name" required></div>
            <div class="adm-field"><label>Middle Name</label><input name="students[${i}][middle_name]" placeholder="Enter middle name"></div>
            <div class="adm-field"><label>Gender <span class="required">*</span></label><select name="students[${i}][gender]" required><option value="">Select gender</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option></select></div>
            <div class="adm-field"><label>Date of Birth <span class="required">*</span></label><input type="date" name="students[${i}][date_of_birth]" required></div>
            <div class="adm-field"><label>Religion</label><select name="students[${i}][religion]"><option value="">Select Religion</option><option>Islam</option><option>Christianity</option><option>Other</option></select></div>
            <div class="adm-field"><label>State of Origin</label><select name="students[${i}][state_of_origin]" onchange="updateLga(this,${i})">${stateOptions()}</select></div>
            <div class="adm-field"><label>LGA of Origin</label><select name="students[${i}][lga_of_origin]" id="lga_${i}"><option value="">Select LGA</option></select></div>
            <div class="adm-field"><label>Blood Group</label><select name="students[${i}][blood_group]"><option value="">Unknown</option><option>A+</option><option>A-</option><option>B+</option><option>B-</option><option>AB+</option><option>AB-</option><option>O+</option><option>O-</option></select></div>
            <div class="adm-field"><label>Genotype</label><select name="students[${i}][genotype]"><option value="">Unknown</option><option>AA</option><option>AS</option><option>AC</option><option>SS</option><option>SC</option></select></div>
            <div class="adm-field"><label>Class / Class Arm <span class="required">*</span></label><select name="students[${i}][current_class_arm_id]" required>${classOptions()}</select></div>
            <div class="adm-field"><label>Admission Date <span class="required">*</span></label><input type="date" name="students[${i}][admission_date]" value="{{ date('Y-m-d') }}" required></div>
        </div>
    </div>`;
}

function updateLga(el, i) {
    const l = document.getElementById('lga_' + i);
    const v = (geo[el.value] || {}).lgas || [];
    l.innerHTML = '<option value="">Select LGA</option>' + v.map(x => '<option value="' + esc(x) + '">' + esc(x) + '</option>').join('');
}

function removeStudent(btn) {
    const blocks = document.querySelectorAll('.student-block');
    if (blocks.length <= 1) return;
    btn.closest('.student-block').remove();
    // Re-number remaining students
    document.querySelectorAll('.student-block').forEach((x, i) => {
        x.querySelector('.student-heading span').textContent = 'Student ' + (i + 1);
    });
}

document.getElementById('addStudent').addEventListener('click', () => {
    document.getElementById('studentsContainer').insertAdjacentHTML('beforeend', studentFields(studentIndex++));
});
</script>
</body>
</html>
