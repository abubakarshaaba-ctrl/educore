<div class="p-nav-section">
    <div class="p-nav-label">Student Portal</div>
    <a href="{{ route('student.portal.dashboard') }}" class="p-nav-item {{ request()->routeIs('student.portal.dashboard') ? 'active':'' }}">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
        Dashboard
    </a>
    <a href="{{ route('student.portal.results') }}" class="p-nav-item {{ request()->routeIs('student.portal.results') ? 'active':'' }}">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
        My Results
    </a>
    <a href="{{ route('student.portal.attendance') }}" class="p-nav-item {{ request()->routeIs('student.portal.attendance') ? 'active':'' }}">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
        Attendance
    </a>
    <a href="{{ route('student.portal.timetable') }}" class="p-nav-item {{ request()->routeIs('student.portal.timetable') ? 'active':'' }}">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg>
        Timetable
    </a>
    <a href="{{ route('student.portal.exams') }}" class="p-nav-item {{ request()->routeIs('student.portal.exams') ? 'active':'' }}">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 21c0 .55.45 1 1 1h4c.55 0 1-.45 1-1v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7z"/></svg>
        CBT Exams
    </a>
    <a href="{{ route('student.portal.subjects') }}" class="p-nav-item {{ request()->routeIs('student.portal.subjects') ? 'active':'' }}">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1z"/></svg>
        My Subjects
    </a>
</div>
