<select name="role" class="form-control" required id="roleSelect" onchange="updateRoleHint(this.value)">
    <option value="">— Select Role —</option>

    <optgroup label="🏛 Administration">
        <option value="admin"             {{ $selected === 'admin'             ? 'selected':'' }}>Administrator — Full school access</option>
        <option value="principal"         {{ $selected === 'principal'         ? 'selected':'' }}>Principal — Academic & staff oversight</option>
        <option value="head"              {{ $selected === 'head'              ? 'selected':'' }}>Head — School administration continuity</option>
        <option value="head_teacher"      {{ $selected === 'head_teacher'      ? 'selected':'' }}>Head Teacher — School administration continuity</option>
        <option value="vice_principal"    {{ $selected === 'vice_principal'    ? 'selected':'' }}>Vice Principal — Academic support</option>
        <option value="academic_administrator" {{ $selected === 'academic_administrator' ? 'selected':'' }}>Academic Administrator — Academic operations</option>
        <option value="admission_officer" {{ $selected === 'admission_officer' ? 'selected':'' }}>Admission Officer — Applications & admissions only</option>
    </optgroup>

    <optgroup label="📚 Teaching">
        <option value="form_teacher"       {{ $selected === 'form_teacher'       ? 'selected':'' }}>Form Teacher — Attendance, remarks, skills, broadsheet</option>
        <option value="asst_form_teacher"  {{ $selected === 'asst_form_teacher'  ? 'selected':'' }}>Asst. Form Teacher — Supports form teacher duties</option>
        <option value="subject_teacher"    {{ $selected === 'subject_teacher'    ? 'selected':'' }}>Subject Teacher — Score entry for assigned subjects only</option>
        <option value="form_subject_teacher" {{ $selected === 'form_subject_teacher' ? 'selected':'' }}>Form & Subject Teacher — All teaching duties combined</option>
    </optgroup>

    <optgroup label="💰 Finance">
        <option value="accountant" {{ $selected === 'accountant' ? 'selected':'' }}>Accountant — Fees, payroll & financial reports</option>
    </optgroup>

    <optgroup label="🏥 Support Staff">
        <option value="health_officer"        {{ $selected === 'health_officer'        ? 'selected':'' }}>Health Officer — Student health records</option>
        <option value="librarian"             {{ $selected === 'librarian'             ? 'selected':'' }}>Librarian — Library management</option>
        <option value="transport_officer"     {{ $selected === 'transport_officer'     ? 'selected':'' }}>Transport Officer — Full transport management</option>
        <option value="communication_officer" {{ $selected === 'communication_officer' ? 'selected':'' }}>Communication Officer — SMS, notices, messages</option>
    </optgroup>

    <optgroup label="🚌 Transport">
        <option value="driver"        {{ $selected === 'driver'        ? 'selected':'' }}>Driver — Assigned to buses & routes</option>
        <option value="bus_assistant" {{ $selected === 'bus_assistant' ? 'selected':'' }}>Bus Assistant / Conductor</option>
    </optgroup>
</select>

{{-- Role hint box --}}
<div id="roleHint" style="display:none;margin-top:8px;padding:10px 14px;border-radius:8px;font-size:12px;line-height:1.6;background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE"></div>

@push('scripts')
<script>
const roleHints = {
    admin:                 '✅ Full access to all modules. Manages school settings, staff, and all records.',
    principal:             '🏛 Oversees all academic and staff modules. Can view financial reports.',
    head:                  'School administration continuity role with broad school oversight.',
    head_teacher:          'School administration continuity role with broad academic and staff oversight.',
    vice_principal:        '📋 Supports principal. Full academic access without finance.',
    academic_administrator:'Academic operations, classes, curriculum, attendance, reports, and timetable support.',
    form_teacher:          '📝 Can: mark attendance, enter remarks, rate skills, view report cards and broadsheet for their class only.',
    asst_form_teacher:     '📝 Same as Form Teacher. Assists the class form teacher.',
    subject_teacher:       '📊 Can: enter scores for their assigned subjects, view their subject timetable. Cannot access other classes.',
    form_subject_teacher:  '📝📊 Combined role: score entry + form teacher duties (attendance, remarks, skills, broadsheet).',
    accountant:            '💰 Fees, invoices, expenses, payroll, and financial reports only.',
    health_officer:        '🏥 Access to student health records only.',
    librarian:             '📚 Library module access only.',
    transport_officer:     '🚌 Full transport management: routes, buses, assignments.',
    communication_officer: '📢 Notifications, SMS campaigns, school calendar, and announcements.',
    driver:                '🚌 Can view their assigned route and manifest.',
    admission_officer:     '📋 Access admissions and applications module only. Can review, shortlist, admit or reject applicants. Cannot access finance, academics, or other modules.',
    bus_assistant:         '🚌 Can view their assigned route and manifest.',
};
function updateRoleHint(role) {
    const box = document.getElementById('roleHint');
    if (roleHints[role]) {
        box.textContent = roleHints[role];
        box.style.display = 'block';
    } else {
        box.style.display = 'none';
    }
}
// Show hint on page load if role is pre-selected
const sel = document.getElementById('roleSelect');
if (sel.value) updateRoleHint(sel.value);
</script>
@endpush
