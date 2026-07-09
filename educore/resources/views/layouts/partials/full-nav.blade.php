{{-- Full feature navigation — shared by the main admin/staff app shell
     (layouts/app.blade.php) and the staff portal sidebar, so portal
     accounts see the exact same module links as the main app, scoped
     by the same canAccessModule() checks per role. --}}
    {{-- CORE — always visible --}}
    <div class="nav-section">
        <div class="nav-section-label">Core</div>
        {{-- Non-admin-tier staff land on the portal dashboard (see
             DashboardController::index() / LoginRedirector), which already
             carries this same "Dashboard" nav item lower down — skip it here
             to avoid a duplicate link pointing at the same page. --}}
        @unless(!auth()->user()->canAccessExactModule('students'))
        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" data-tip="Dashboard">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
            <span class="nav-label">Dashboard</span>
        </a>
        @endunless
        {{-- Self-service links for portal (non-admin-dashboard) staff --}}
        @if(!auth()->user()->canAccessExactModule('students'))
        <a href="{{ route('staff.portal.dashboard') }}" class="nav-item {{ request()->routeIs('staff.portal.dashboard') ? 'active' : '' }}" data-tip="Dashboard">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
            <span class="nav-label">Dashboard</span>
        </a>
        <a href="{{ route('staff.portal.payroll') }}" class="nav-item {{ request()->routeIs('staff.portal.payroll') || request()->routeIs('staff.portal.payslip.*') ? 'active' : '' }}" data-tip="Payroll & Payslips">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 14H4v-6h16zm0-10H4V6h16z"/></svg>
            <span class="nav-label">Payroll & Payslips</span>
        </a>
        <a href="{{ route('staff.portal.messages') }}" class="nav-item {{ request()->routeIs('staff.portal.messages*') ? 'active' : '' }}" data-tip="Messages">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
            <span class="nav-label">Messages</span>
        </a>
        @endif
    </div>

    {{-- ACADEMICS --}}
    @php $u = auth()->user(); @endphp
    @if($u->canAccessModule('students') || $u->canAccessModule('staff') || $u->canAccessModule('classes') || $u->canAccessModule('academic-cycle') || $u->canAccessModule('subjects') || $u->canAccessModule('curriculum') || $u->canAccessModule('attendance') || $u->canAccessModule('timetable') || $u->canAccessModule('skills'))
    <div class="nav-section">
        <div class="nav-section-label">Academics</div>
        @if($u->canAccessModule('students'))
        <a href="{{ route('students.index') }}" class="nav-item {{ request()->routeIs('students.*') && !request()->routeIs('students.transfers.*') && !request()->routeIs('students.class-transfers.*') && !request()->routeIs('students.archive.*') && !request()->routeIs('students.status.*') && !request()->routeIs('students.reactivate*') && !request()->routeIs('students.readmit*') && !request()->routeIs('students.graduation-correction*') ? 'active' : '' }}" data-tip="Students">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
            <span class="nav-label">Students</span>
        </a>
        @endif
        @can('student.transfer.view')
        <a href="{{ route('students.class-transfers.index') }}" class="nav-item {{ request()->routeIs('students.class-transfers.*') ? 'active' : '' }}" data-tip="Student Transfers">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 7h11l-3-3 1.41-1.41L21.83 8l-5.42 5.41L15 12l3-3H7V7zm10 10H6l3 3-1.41 1.41L2.17 16l5.42-5.41L9 12l-3 3h11v2z"/></svg>
            <span class="nav-label">Student Transfers</span>
        </a>
        @endcan
        @if($u->canAccessModule('classes'))
        <a href="{{ route('classes.levels') }}" class="nav-item {{ request()->routeIs('classes.*') && !request()->routeIs('classes.promotion*') && !request()->routeIs('classes.bulk-promote*') && !request()->routeIs('classes.grading') ? 'active' : '' }}" data-tip="Classes">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 6h-8l-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2z"/></svg>
            <span class="nav-label">Classes</span>
        </a>
        <a href="{{ route('classes.promotion.preview') }}" class="nav-item {{ request()->routeIs('classes.promotion*') || request()->routeIs('classes.bulk-promote*') || request()->routeIs('classes.grading') ? 'active' : '' }}" data-tip="Promotion Engine">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
            <span class="nav-label">Promotion Engine</span>
        </a>
        @endif
        @if($u->canAccessModule('academic-cycle'))
        <a href="{{ route('academic-cycle.index') }}" class="nav-item {{ request()->routeIs('academic-cycle.*') ? 'active' : '' }}" data-tip="Academic Session">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 2v2H5c-1.1 0-2 .9-2 2v13c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-2V2h-2v2H9V2H7zm12 8H5V7h14v3zm-9 4H7v-2h3v2zm5 0h-3v-2h3v2zm4 0h-2v-2h2v2zm-9 4H7v-2h3v2zm5 0h-3v-2h3v2z"/></svg>
            <span class="nav-label">Academic Session</span>
        </a>
        @endif
        @if($u->canAccessModule('curriculum'))
        <a href="{{ route('curriculum.tracks') }}" class="nav-item {{ request()->routeIs('curriculum.*') ? 'active' : '' }}" data-tip="Curriculum">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>
            <span class="nav-label">Curriculum</span>
        </a>
        @endif
        @if($u->canAccessModule('subjects'))
        <a href="{{ route('subjects.index') }}" class="nav-item {{ request()->routeIs('subjects.*') ? 'active' : '' }}" data-tip="Subjects">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1z"/></svg>
            <span class="nav-label">Subjects</span>
        </a>
        @endif
        @if($u->canAccessModule('attendance'))
        <a href="{{ route('attendance.index') }}" class="nav-item {{ request()->routeIs('attendance.*') && !request()->routeIs('staff-attendance.*') ? 'active' : '' }}" data-tip="Student Attendance">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
            <span class="nav-label">Student Attendance</span>
        </a>
        @endif
        @if($u->canAccessModule('timetable'))
        <a href="{{ route('timetable.index') }}" class="nav-item {{ request()->routeIs('timetable.*') ? 'active' : '' }}" data-tip="Timetable">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg>
            <span class="nav-label">Timetable</span>
        </a>
        @endif
        @if($u->canAccessModule('lesson-planner'))
        <a href="{{ route('lesson-planner.index') }}" class="nav-item {{ request()->routeIs('lesson-planner.*') ? 'active' : '' }}" data-tip="Lesson Planner">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1zm0 13.5c-1.1-.35-2.3-.5-3.5-.5-1.7 0-4.15.65-5.5 1.5V8c1.35-.85 3.8-1.5 5.5-1.5 1.2 0 2.4.15 3.5.5v11.5z"/></svg>
            <span class="nav-label">Lesson Planner</span>
            <span style="font-size:9px;background:linear-gradient(135deg,#7C3AED,#4F46E5);color:white;padding:1px 5px;border-radius:8px;margin-left:4px;font-weight:700">AI</span>
        </a>
        @endif
        @if($u->canAccessModule('skills'))
        <a href="{{ route('skills.index') }}" class="nav-item {{ request()->routeIs('skills.*') ? 'active' : '' }}" data-tip="Skill Ratings">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
            <span class="nav-label">Skill Ratings</span>
        </a>
        @endif
        @if($u->canAccessModule('students'))
        <a href="{{ route('discipline.index') }}" class="nav-item {{ request()->routeIs('discipline.*') ? 'active' : '' }}" data-tip="Discipline & Conduct">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2 4 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-8-3zm-1 14-4-4 1.41-1.41L11 13.17l4.59-4.58L17 10l-6 6z"/></svg>
            <span class="nav-label">Discipline & Conduct</span>
        </a>
        <a href="{{ route('certificates.index') }}" class="nav-item {{ request()->routeIs('certificates.*') ? 'active' : '' }}" data-tip="Certificates">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2 4 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-8-3zm0 4.5c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 11c-1.86 0-3.5-.94-4.5-2.37.06-1.5 3-2.33 4.5-2.33s4.44.83 4.5 2.33c-1 1.43-2.64 2.37-4.5 2.37z"/></svg>
            <span class="nav-label">Certificates</span>
        </a>
        <a href="{{ route('alumni.index') }}" class="nav-item {{ request()->routeIs('alumni.*') ? 'active' : '' }}" data-tip="Alumni">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3 1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>
            <span class="nav-label">Alumni</span>
        </a>
        <a href="{{ route('hostels.index') }}" class="nav-item {{ request()->routeIs('hostels.*') ? 'active' : '' }}" data-tip="Boarding / Hostel">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
            <span class="nav-label">Boarding / Hostel</span>
        </a>
        @endif
    </div>
    @endif

    {{-- HUMAN RESOURCE — "Staff Leave" is a self-service link everyone gets
         (own leave requests), so this section always renders; the rest of
         the items are gated per-module as usual. --}}
    <div class="nav-section">
        <div class="nav-section-label">Human Resource</div>
        @if($u->canAccessModule('staff'))
        <a href="{{ route('staff.index') }}" class="nav-item {{ request()->routeIs('staff.*') && !request()->routeIs('staff.archive.*') && !request()->routeIs('staff.reinstate*') ? 'active' : '' }}" data-tip="Staff">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            <span class="nav-label">Staff</span>
        </a>
        @endif
        @if($u->canAccessModule('staff-attendance'))
        <a href="{{ route('staff-attendance.my') }}"
           class="nav-item {{ request()->routeIs('staff-attendance.*') ? 'active' : '' }}"
           data-tip="Staff Attendance">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
            <span class="nav-label">Staff Attendance</span>
        </a>
        @endif
        {{-- Ungated: every staff member (not just admin-tier) needs to file their own leave --}}
        <a href="{{ route('leave.index') }}" class="nav-item {{ request()->routeIs('leave.*') ? 'active' : '' }}" data-tip="Staff Leave">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
            <span class="nav-label">Staff Leave</span>
        </a>
        @if($u->canAccessModule('staff'))
        <a href="{{ route('coverage.index') }}" class="nav-item {{ request()->routeIs('coverage.*') ? 'active' : '' }}" data-tip="Class Coverage">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4zm7-6 1.41-1.41L18.83 5l1.59-1.59L19 2l-3 3 1.41 1.41z"/></svg>
            <span class="nav-label">Class Coverage</span>
        </a>
        <a href="{{ route('recruitment.index') }}" class="nav-item {{ request()->routeIs('recruitment.*') ? 'active' : '' }}" data-tip="Recruitment">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 6h-2.18c.11-.31.18-.65.18-1a2.996 2.996 0 0 0-5.5-1.65l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.1 0 2-.89 2-2V8c0-1.11-.9-2-2-2zM15 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm11 15H4v-2h16v2zm0-5H4V8h5.08L7 10.83 8.62 12 11 8.76l1-1.36 1 1.36L15.38 12 17 10.83 14.92 8H20v6z"/></svg>
            <span class="nav-label">Recruitment</span>
        </a>
        <a href="{{ route('staff-discipline.index') }}" class="nav-item {{ request()->routeIs('staff-discipline.*') ? 'active' : '' }}" data-tip="Staff Disciplinary Actions">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
            <span class="nav-label">Staff Disciplinary Actions</span>
        </a>
        @endif
    </div>

    {{-- ASSESSMENTS --}}
    @if($u->canAccessModule('scores') || $u->canAccessModule('reports') || $u->canAccessModule('cbt') || $u->canAccessModule('gradebook'))
    <div class="nav-section">
        <div class="nav-section-label">Assessments</div>
        @if($u->canAccessModule('scores'))
        <a href="{{ route('scores.index') }}" class="nav-item {{ request()->routeIs('scores.*') ? 'active' : '' }}" data-tip="Scores">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
            <span class="nav-label">Scores</span>
        </a>
        @endif
        @if($u->canManage('reports'))
        <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}" data-tip="Report Cards">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.89 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
            <span class="nav-label">Report Cards</span>
        </a>
        @endif
        @if($u->canManage('exams'))
        <a href="{{ route('exams.index') }}" class="nav-item {{ request()->routeIs('exams.*') ? 'active' : '' }}" data-tip="Exam Timetables">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
            <span class="nav-label">Exam Timetables</span>
        </a>
        @endif
        {{-- Transcript: admin, principal, vice principal only --}}
        @if($u->canAccessModule('transcript'))
        <a href="{{ route('students.transcript.index') }}" class="nav-item {{ request()->routeIs('students.transcript*') ? 'active' : '' }}" data-tip="Transcripts">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.89 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13zm-2 8H7v-2h4v2zm6-4H7v-2h10v2zm0-4H7V9h4.5l1.5 1.5V11h4v2z"/></svg>
            <span class="nav-label">Transcripts</span>
        </a>
        @endif

        @if($u->canAccessModule('cbt'))
        <a href="{{ route('cbt.banks') }}" class="nav-item {{ request()->routeIs('cbt.*') ? 'active' : '' }}" data-tip="CBT Exams">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 21c0 .55.45 1 1 1h4c.55 0 1-.45 1-1v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7z"/></svg>
            <span class="nav-label">CBT Exams</span>
        </a>
        <a href="{{ route('exam-bodies.index') }}" class="nav-item {{ request()->routeIs('exam-bodies.*') ? 'active' : '' }}" data-tip="WAEC/NECO/JAMB Registration">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 14H7v-2h10v2zm0-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
            <span class="nav-label">Exam Body Registration</span>
        </a>
        @endif
    </div>
    @endif

    {{-- ADMISSIONS --}}
    @if($u->canAccessModule('admissions'))
    <div class="nav-section">
        <div class="nav-section-label">Admissions</div>
        <a href="{{ route('admissions.index') }}" class="nav-item {{ request()->routeIs('admissions.index') || request()->routeIs('admissions.show') || request()->routeIs('admissions.create') ? 'active' : '' }}" data-tip="Applications">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
            <span class="nav-label">Applications</span>
        </a>
        <a href="{{ route('admissions.portal') }}" class="nav-item {{ request()->routeIs('admissions.portal') ? 'active' : '' }}" data-tip="Online Portal">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
            <span class="nav-label">Online Portal</span>
        </a>
        <a href="{{ route('admissions.portal.settings') }}" class="nav-item {{ request()->routeIs('admissions.portal.settings') ? 'active' : '' }}" data-tip="Portal Settings">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12s0.02,0.64,0.07,0.94l-2.03,1.58c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/></svg>
            <span class="nav-label">Portal Settings</span>
        </a>
        @if($u->canAccessModule('transfers'))
        <a href="{{ route('students.transfers.index') }}" class="nav-item {{ request()->routeIs('students.transfers.*') ? 'active' : '' }}" data-tip="Transfers">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 3L5 6.99h3V14h2V6.99h3L9 3zm7 14.01V10h-2v7.01h-3L15 21l4-3.99h-3z"/></svg>
            <span class="nav-label">Transfers</span>
        </a>
        @endif
    </div>
    @endif

    {{-- FINANCE --}}
    @if($u->canAccessModule('fees') || $u->canAccessModule('expenses') || $u->canAccessModule('payroll'))
    <div class="nav-section">
        <div class="nav-section-label">Finance</div>
        @if($u->canAccessModule('fees'))
        <a href="{{ route('fees.subaccounts') }}" class="nav-item {{ request()->routeIs('fees.subaccounts') || request()->routeIs('fees.categories') || request()->routeIs('fees.structures') ? 'active' : '' }}" data-tip="Fee Setup">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
            <span class="nav-label">Fee Setup</span>
        </a>
        <a href="{{ route('fees.invoices') }}" class="nav-item {{ request()->routeIs('fees.invoices*') || request()->routeIs('fees.payment*') ? 'active' : '' }}" data-tip="Invoices">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
            <span class="nav-label">Invoices & Payments</span>
        </a>
        <a href="{{ route('fees.generate.index') }}" class="nav-item {{ request()->routeIs('fees.generate.*') ? 'active' : '' }}" data-tip="Generate Invoices">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
            <span class="nav-label">Generate Invoices</span>
        </a>
        <a href="{{ route('fees.plans.index') }}" class="nav-item {{ request()->routeIs('fees.plans.*') ? 'active' : '' }}" data-tip="Payment Plans">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
            <span class="nav-label">Payment Plans</span>
        </a>
        <a href="{{ route('fees.reminders.index') }}" class="nav-item {{ request()->routeIs('fees.reminders.*') ? 'active' : '' }}" data-tip="Fee Reminders">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
            <span class="nav-label">Fee Reminders</span>
        </a>
        <a href="{{ route('fees.gateway.settings') }}" class="nav-item {{ request()->routeIs('fees.gateway.*') ? 'active' : '' }}" data-tip="Online Payments">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
            <span class="nav-label">Online Payments</span>
        </a>
        @endif
        @if($u->canAccessModule('expenses'))
        <a href="{{ route('expenses.index') }}" class="nav-item {{ request()->routeIs('expenses.*') ? 'active' : '' }}" data-tip="Expenses">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
            <span class="nav-label">Expenses</span>
        </a>
        <a href="{{ route('procurement.index') }}" class="nav-item {{ request()->routeIs('procurement.*') ? 'active' : '' }}" data-tip="Procurement">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 7h-3V6a4 4 0 0 0-8 0v1H5a1 1 0 0 0-1 1v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8a1 1 0 0 0-1-1zM10 6a2 2 0 0 1 4 0v1h-4z"/></svg>
            <span class="nav-label">Procurement</span>
        </a>
        <a href="{{ route('inventory.index') }}" class="nav-item {{ request()->routeIs('inventory.*') ? 'active' : '' }}" data-tip="Asset & Inventory">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 6h-8l-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2z"/></svg>
            <span class="nav-label">Asset & Inventory</span>
        </a>
        <a href="{{ route('scholarships.index') }}" class="nav-item {{ request()->routeIs('scholarships.*') ? 'active' : '' }}" data-tip="Scholarships">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3 1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>
            <span class="nav-label">Scholarships</span>
        </a>
        @endif
        @if($u->canAccessModule('payroll'))
        <a href="{{ route('payroll.index') }}" class="nav-item {{ request()->routeIs('payroll.*') ? 'active' : '' }}" data-tip="Payroll">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
            <span class="nav-label">Payroll</span>
        </a>
        @endif
    </div>
    @endif

    {{-- OPERATIONS --}}
    @if($u->canAccessModule('health') || $u->canAccessModule('library') || $u->canAccessModule('transport') || $u->canAccessModule('announcements') || $u->canAccessModule('calendar') || $u->canAccessModule('messages') || $u->canAccessModule('notifications') || $u->canAccessModule('sms'))
    <div class="nav-section">
        <div class="nav-section-label">Operations</div>
        @if($u->canAccessModule('health'))
        <a href="{{ route('health.index') }}" class="nav-item {{ request()->routeIs('health.*') ? 'active' : '' }}" data-tip="Health Records">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10.5 13H8v-3h2.5V7.5h3V10H16v3h-2.5v2.5h-3V13zM12 2L4 5v6.09c0 5.05 3.41 9.76 8 10.91 4.59-1.15 8-5.86 8-10.91V5l-8-3z"/></svg>
            <span class="nav-label">Health Records</span>
        </a>
        @endif
        <a href="{{ route('visitors.index') }}" class="nav-item {{ request()->routeIs('visitors.*') ? 'active' : '' }}" data-tip="Visitor Log">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            <span class="nav-label">Visitor Log</span>
        </a>
        @if($u->canAccessModule('library'))
        <a href="{{ route('library.index') }}" class="nav-item {{ request()->routeIs('library.*') ? 'active' : '' }}" data-tip="Library">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 4h5v8l-2.5-1.5L6 12V4z"/></svg>
            <span class="nav-label">Library</span>
        </a>
        @endif
        @if($u->canAccessModule('transport'))
        <a href="{{ route('transport.routes') }}" class="nav-item {{ request()->routeIs('transport.*') ? 'active' : '' }}" data-tip="Transport">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 16c0 .88.39 1.67 1 2.22V20c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h8v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1.78c.61-.55 1-1.34 1-2.22V6c0-3.5-3.58-4-8-4s-8 .5-8 4v10zm3.5 1c-.83 0-1.5-.67-1.5-1.5S6.67 14 7.5 14s1.5.67 1.5 1.5S8.33 17 7.5 17zm9 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm1.5-6H6V6h12v5z"/></svg>
            <span class="nav-label">Transport</span>
        </a>
        @endif
        @if($u->canAccessModule('announcements'))
        <a href="{{ route('announcements.index') }}" class="nav-item {{ request()->routeIs('announcements.*') ? 'active' : '' }}" data-tip="Announcements">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
            <span class="nav-label">Announcements</span>
        </a>
        @endif
        @if($u->canAccessModule('calendar'))
        <a href="{{ route('calendar.index') }}" class="nav-item {{ request()->routeIs('calendar.*') ? 'active' : '' }}" data-tip="Calendar">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
            <span class="nav-label">Calendar</span>
        </a>
        @endif
        @if($u->canAccessModule('messages'))
        <a href="{{ route('messages.inbox') }}" class="nav-item {{ request()->routeIs('messages.*') ? 'active' : '' }}" data-tip="Messages">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg>
            <span class="nav-label">Messages</span>
        </a>
        @endif
        <a href="{{ route('platform.notices') }}" class="nav-item {{ request()->routeIs('platform.notices') ? 'active' : '' }}" data-tip="Platform Notices">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
            <span class="nav-label">Notices</span>
        </a>
        <a href="{{ route('support.index') }}" class="nav-item {{ request()->routeIs('support.*') ? 'active' : '' }}" data-tip="Platform Support">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/></svg>
            <span class="nav-label">Support</span>
        </a>
        @if($u->canAccessModule('notifications'))
        <a href="{{ route('notifications.index') }}" class="nav-item {{ request()->routeIs('notifications.*') && !request()->routeIs('notifications.triggers*') ? 'active' : '' }}" data-tip="Notifications">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
            <span class="nav-label">Notifications</span>
        </a>
        @endif
        @if($u->canManage('notifications'))
        <a href="{{ route('notifications.triggers') }}" class="nav-item {{ request()->routeIs('notifications.triggers*') ? 'active' : '' }}" data-tip="Auto Triggers">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 2v11h3v9l7-12h-4l4-8z"/></svg>
            <span class="nav-label">Auto Triggers</span>
        </a>
        @endif
        @if($u->canAccessModule('sms'))
        <a href="{{ route('sms.index') }}" class="nav-item {{ request()->routeIs('sms.*') ? 'active' : '' }}" data-tip="SMS Campaigns">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17L4 17.17V4h16v12zM7 9h2v2H7V9zm4 0h2v2h-2V9zm4 0h2v2h-2V9z"/></svg>
            <span class="nav-label">SMS Campaigns</span>
        </a>
        @endif
    </div>
    @endif

    {{-- REPORTING --}}
    @if($u->canAccessModule('analytics') || $u->canAccessModule('risk') || $u->canAccessModule('exports') || $u->canAccessModule('asc'))
    <div class="nav-section">
        <div class="nav-section-label">Reporting</div>
        @if($u->canAccessModule('analytics'))
        <a href="{{ route('analytics.index') }}" class="nav-item {{ request()->routeIs('analytics.*') && !request()->routeIs('analytics.financial') ? 'active' : '' }}" data-tip="Analytics">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
            <span class="nav-label">Analytics</span>
        </a>
        <a href="{{ route('analytics.financial') }}" class="nav-item {{ request()->routeIs('analytics.financial') ? 'active' : '' }}" data-tip="Financial Report">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
            <span class="nav-label">Financial Report</span>
        </a>
        @endif
        @if($u->canAccessModule('risk'))
        <a href="{{ route('risk.index') }}" class="nav-item {{ request()->routeIs('risk.*') ? 'active' : '' }}" data-tip="Risk Flags">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
            <span class="nav-label">Risk Flags</span>
        </a>
        @endif
        @if($u->canAccessModule('exports'))
        <a href="{{ route('exports.index') }}" class="nav-item {{ request()->routeIs('exports.*') ? 'active' : '' }}" data-tip="Export Data">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
            <span class="nav-label">Export Data</span>
        </a>
        @endif
        @if($u->canAccessModule('asc'))
        <a href="{{ route('asc.report') }}" class="nav-item {{ request()->routeIs('asc.*') ? 'active' : '' }}" data-tip="School Census (ASC)">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/></svg>
            <span class="nav-label">School Census (ASC)</span>
        </a>
        @endif
    </div>
    @endif

    {{-- SETTINGS --}}
    <div class="nav-section">
        <div class="nav-section-label">Settings</div>
        <a href="{{ route('profile.edit') }}" class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}" data-tip="My Profile">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            <span class="nav-label">My Profile</span>
        </a>
    @if($u->canAccessModule('settings') || $u->canAccessModule('portal-accounts'))
        @if($u->canAccessModule('portal-accounts'))
        <a href="{{ route('portal-accounts.index') }}" class="nav-item {{ request()->routeIs('portal-accounts.*') ? 'active' : '' }}" data-tip="Portal Accounts">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
            <span class="nav-label">Portal Accounts</span>
        </a>
        @endif
        {{-- Subscription billing — admin only --}}
        @if($u->isAdmin())
        <a href="{{ route('billing.subscription') }}" class="nav-item {{ request()->routeIs('billing.*') ? 'active' : '' }}" data-tip="Subscription">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
            <span class="nav-label">Subscription</span>
        </a>
        @endif

        @if($u->canAccessModule('settings'))
        <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings.*') && !request()->routeIs('settings.letter-templates.*') ? 'active' : '' }}" data-tip="School Settings">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12s0.02,0.64,0.07,0.94l-2.03,1.58c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/></svg>
            <span class="nav-label">School Settings</span>
        </a>
        <a href="{{ route('settings.letter-templates.edit') }}" class="nav-item {{ request()->routeIs('settings.letter-templates.*') ? 'active' : '' }}" data-tip="Offer Letter Templates">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M14,2H6C4.9,2,4,2.9,4,4v16c0,1.1,0.9,2,2,2h12c1.1,0,2-0.9,2-2V8L14,2z M13,9V3.5L18.5,9H13z M8,12h8v2H8V12z M8,16h8v2H8V16z M8,8h4v2H8V8z"/></svg>
            <span class="nav-label">Offer Letter Templates</span>
        </a>
        @endif
    @endif
    </div>

    {{-- SUPER ADMIN --}}
    @if(auth()->user()?->is_super_admin)
    <div class="nav-section">
        <div class="nav-section-label">Super Admin</div>
        <a href="{{ route('super.dashboard') }}" class="nav-item {{ request()->routeIs('super.dashboard') ? 'active' : '' }}" data-tip="Admin Panel">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
            <span class="nav-label">Admin Panel</span>
        </a>
        <a href="{{ route('super.tenants') }}" class="nav-item {{ request()->routeIs('super.tenants*') || request()->routeIs('super.tenant*') ? 'active' : '' }}" data-tip="All Schools">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
            <span class="nav-label">All Schools</span>
        </a>
    </div>
    @endif
    {{-- STOP IMPERSONATING --}}
    @if(session('impersonating'))
    <div style="margin-top:auto;padding:12px 10px;border-top:1px solid rgba(255,255,255,0.1)">
        <form method="POST" action="{{ route('super.stop-impersonating') }}">
            @csrf
            <button type="submit" style="width:100%;padding:9px 12px;background:rgba(239,68,68,0.2);color:#FCA5A5;border:1px solid rgba(239,68,68,0.3);border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;text-align:left;display:flex;align-items:center;gap:8px">
                <svg viewBox="0 0 24 24" fill="currentColor" style="width:15px;height:15px"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
                <span class="nav-label">Exit School View</span>
            </button>
        </form>
    </div>
    @endif
