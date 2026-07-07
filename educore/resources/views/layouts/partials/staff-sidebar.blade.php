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

{{-- Full feature navigation — identical module links to the main admin/staff
     app shell (layouts/app.blade.php), scoped by the same canAccessModule()
     checks per role, so staff portal accounts get full feature parity. --}}
@include('layouts.partials.full-nav')
