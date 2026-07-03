@php $staffUser = auth()->user(); @endphp

<div class="p-nav-section">
    <div class="p-nav-label">Self Service</div>
    <a href="{{ route('staff.portal.dashboard') }}" class="p-nav-item {{ request()->routeIs('staff.portal.dashboard') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
        <span>Dashboard</span>
    </a>
    <a href="{{ route('staff-attendance.my') }}" class="p-nav-item {{ request()->routeIs('staff-attendance.my') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 10 10A10.01 10.01 0 0 0 12 2zm1 11h-2V7h2zm0 4h-2v-2h2z"/></svg>
        <span>My Attendance</span>
    </a>
    <a href="{{ route('staff.portal.payroll') }}" class="p-nav-item {{ request()->routeIs('staff.portal.payroll') || request()->routeIs('staff.portal.payslip.*') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 14H4v-6h16zm0-10H4V6h16z"/></svg>
        <span>Payroll & Payslips</span>
    </a>
    <a href="{{ route('profile.edit') }}" class="p-nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-4.42 0-8 2.01-8 4.5V21h16v-2.5C20 16.01 16.42 14 12 14z"/></svg>
        <span>My Profile</span>
    </a>
</div>

@if(
    $staffUser->canAccessModule('scores') ||
    $staffUser->canAccessModule('attendance') ||
    $staffUser->canAccessModule('timetable') ||
    $staffUser->canAccessModule('lesson-planner') ||
    $staffUser->canAccessModule('cbt') ||
    $staffUser->canAccessModule('messages') ||
    $staffUser->canAccessModule('notifications') ||
    $staffUser->canAccessModule('calendar') ||
    $staffUser->canAccessModule('health') ||
    $staffUser->canAccessModule('library') ||
    $staffUser->canAccessModule('transport') ||
    $staffUser->canAccessModule('asc')
)
<div class="p-nav-section">
    <div class="p-nav-label">Work Tools</div>
    @if($staffUser->canAccessModule('scores'))
    <a href="{{ route('scores.index') }}" class="p-nav-item">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1.003 1.003 0 0 0 0-1.42l-2.34-2.34a1.003 1.003 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.84-1.82z"/></svg>
        <span>Scores</span>
    </a>
    @endif
    @if($staffUser->canAccessModule('attendance'))
    <a href="{{ route('attendance.index') }}" class="p-nav-item">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-1V1h-2v2H8V1H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm0 16H5V8h14zM7 10h5v5H7z"/></svg>
        <span>Student Attendance</span>
    </a>
    @endif
    @if($staffUser->canAccessModule('timetable'))
    <a href="{{ route('timetable.index') }}" class="p-nav-item">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 10 10A10.01 10.01 0 0 0 12 2zm1 11h5v-2h-4V7h-2v6z"/></svg>
        <span>Timetable</span>
    </a>
    @endif
    @if($staffUser->canAccessModule('lesson-planner'))
    <a href="{{ route('lesson-planner.index') }}" class="p-nav-item">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 4H3a2 2 0 0 0-2 2v13a1 1 0 0 0 1.45.89A9.72 9.72 0 0 1 7 19c2.13 0 3.82.54 5 1.59A7.24 7.24 0 0 1 17 19a9.72 9.72 0 0 1 4.55.89A1 1 0 0 0 23 19V6a2 2 0 0 0-2-2zm0 13.57A12.06 12.06 0 0 0 17 17a9.91 9.91 0 0 0-4 1V6.26A7.87 7.87 0 0 1 17 6a9.55 9.55 0 0 1 4 .74z"/></svg>
        <span>Lesson Planner</span>
    </a>
    @endif
    @if($staffUser->canAccessModule('cbt'))
    <a href="{{ route('cbt.banks') }}" class="p-nav-item">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 21h6v-1H9zm3-19a7 7 0 0 0-4 12.74V17a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-2.26A7 7 0 0 0 12 2z"/></svg>
        <span>CBT Exams</span>
    </a>
    @endif
    <a href="{{ route('staff.portal.messages') }}" class="p-nav-item {{ request()->routeIs('staff.portal.messages*') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4a2 2 0 0 0-2 2v18l4-4h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z"/></svg>
        <span>Messages</span>
    </a>
    @if($staffUser->canAccessModule('messages'))
    <a href="{{ route('messages.inbox') }}" class="p-nav-item {{ request()->routeIs('messages.*') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
        <span>Parent Messages</span>
    </a>
    @endif
    @if($staffUser->canAccessModule('notifications'))
    <a href="{{ route('notifications.index') }}" class="p-nav-item">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 22a2 2 0 0 0 2-2h-4a2 2 0 0 0 2 2zm6-6v-5a6 6 0 0 0-5-5.91V3h-2v2.09A6 6 0 0 0 6 11v5l-2 2v1h16v-1z"/></svg>
        <span>Notifications</span>
    </a>
    @endif
    @if($staffUser->canAccessModule('calendar'))
    <a href="{{ route('calendar.index') }}" class="p-nav-item">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 0 0-2 2v14h18V6a2 2 0 0 0-2-2zm0 14H5V9h14z"/></svg>
        <span>Calendar</span>
    </a>
    @endif
    @if($staffUser->canAccessModule('health'))
    <a href="{{ route('health.index') }}" class="p-nav-item">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 3h4v6h6v4h-6v6h-4v-6H4V9h6z"/></svg>
        <span>Health Records</span>
    </a>
    @endif
    @if($staffUser->canAccessModule('library'))
    <a href="{{ route('library.index') }}" class="p-nav-item">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zM6 4h5v8l-2.5-1.5L6 12z"/></svg>
        <span>Library</span>
    </a>
    @endif
    @if($staffUser->canAccessModule('transport'))
    <a href="{{ route('transport.routes') }}" class="p-nav-item">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 16a3 3 0 0 0 1 2.22V20a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1h8v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1.78A3 3 0 0 0 20 16V6c0-3.5-3.58-4-8-4S4 2.5 4 6zm2-10h12v5H6z"/></svg>
        <span>Transport</span>
    </a>
    @endif
    @if($staffUser->canAccessModule('asc'))
    <a href="{{ route('asc.report') }}" class="p-nav-item">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/></svg>
        <span>School Census (ASC)</span>
    </a>
    @endif
</div>
@endif

@if($staffUser->canAccessExactModule('students'))
<div class="p-nav-section">
    <div class="p-nav-label">Administration</div>
    <a href="{{ route('dashboard') }}" class="p-nav-item">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 3h8v8H3zm10 0h8v5h-8zM3 13h8v8H3zm10-3h8v11h-8z"/></svg>
        <span>School Dashboard</span>
    </a>
</div>
@endif
