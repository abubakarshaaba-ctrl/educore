@extends('layouts.app')

@section('title', 'Public Class Assignment Links')
@section('page-title', 'Public Class Assignment Links')

<style>
/* ============================================================
   Public Class Links — Modern UI Overhaul
   ============================================================ */

:root {
    --pcl-primary: var(--indigo, #4F46E5); /* Fallback to indigo if not defined */
    --pcl-primary-hover: var(--indigo-dark, #4338CA);
    --pcl-bg: #F8FAFC;
    --pcl-card-bg: #FFFFFF;
    --pcl-text-main: #1E293B;
    --pcl-text-muted: #64748B;
    --pcl-border: #E2E8F0;
    --pcl-radius: 12px;
    --pcl-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --pcl-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
}

.pcl-page {
    display: flex;
    flex-direction: column;
    gap: 24px;
    max-width: 1000px;
    margin: 0 auto;
}

/* ---------- Page header ---------- */
.pcl-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 8px;
}

.pcl-header h1 {
    margin: 0 0 8px;
    font-size: 24px;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: var(--pcl-text-main);
}

.pcl-header .pcl-sub {
    color: var(--pcl-text-muted);
    font-size: 14px;
    max-width: 600px;
    line-height: 1.6;
}

/* ---------- Cards ---------- */
.pcl-card {
    background: var(--pcl-card-bg);
    border: 1px solid var(--pcl-border);
    border-radius: var(--pcl-radius);
    box-shadow: var(--pcl-shadow-md);
    overflow: hidden;
    transition: box-shadow 0.2s ease;
}

.pcl-card:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
}

.pcl-card-head {
    padding: 20px 24px;
    border-bottom: 1px solid var(--pcl-border);
    background: #FFFFFF;
}

.pcl-card-head h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: var(--pcl-text-main);
    display: flex;
    align-items: center;
    gap: 8px;
}

.pcl-card-head .pcl-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 24px;
    padding: 0 8px;
    border-radius: 999px;
    background: var(--pcl-primary);
    color: #fff;
    font-size: 12px;
    font-weight: 700;
}

.pcl-card-head .pcl-card-sub {
    margin: 6px 0 0;
    font-size: 13px;
    color: var(--pcl-text-muted);
    font-weight: 400;
}

.pcl-card-body {
    padding: 24px;
}

/* ---------- Alerts ---------- */
.pcl-alert {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px;
    border-radius: var(--pcl-radius);
    font-size: 14px;
    line-height: 1.5;
    border: 1px solid transparent;
    box-shadow: var(--pcl-shadow-sm);
}

.pcl-alert.success {
    background: #ECFDF5;
    border-color: #A7F3D0;
    color: #065F46;
}

.pcl-alert.info {
    background: #EFF6FF;
    border-color: #BFDBFE;
    color: #1E40AF;
}

.pcl-alert-icon {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    margin-top: 2px;
}

.pcl-alert-body strong { display: block; margin-bottom: 6px; font-size: 14px; }

.pcl-alert-url {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-top: 10px;
    padding: 10px 12px;
    background: #fff;
    border: 1px solid #A7F3D0;
    border-radius: 8px;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 13px;
}

.pcl-alert-url code {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    background: transparent;
    color: inherit;
}

/* ---------- Create form ---------- */
.pcl-form {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(160px, 1fr) auto;
    gap: 20px;
    align-items: end;
}

.pcl-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 0;
}

.pcl-field label {
    font-size: 13px;
    font-weight: 600;
    color: var(--pcl-text-main);
}

/* Custom Select Styling */
.pcl-field select {
    width: 100%;
    min-height: 44px;
    padding: 10px 16px;
    padding-right: 40px; /* Space for custom arrow */
    border: 1px solid var(--pcl-border);
    border-radius: 10px;
    background-color: #F8FAFC;
    color: var(--pcl-text-main);
    font: inherit;
    font-size: 14px;
    outline: none;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    transition: all 0.2s ease;
}

.pcl-field select:focus {
    border-color: var(--pcl-primary);
    background-color: #fff;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
}

.pcl-help {
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px dashed var(--pcl-border);
    font-size: 13px;
    color: var(--pcl-text-muted);
    line-height: 1.6;
    display: flex;
    gap: 10px;
    align-items: flex-start;
}

.pcl-help svg { flex-shrink: 0; margin-top: 2px; color: var(--pcl-text-muted); }

/* ---------- Buttons ---------- */
.pcl-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    padding: 10px 20px;
    border: 1px solid transparent;
    border-radius: 10px;
    background: var(--pcl-primary);
    color: #fff;
    font: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    box-shadow: var(--pcl-shadow-sm);
    transition: all 0.2s ease;
}

.pcl-btn:hover { 
    background: var(--pcl-primary-hover); 
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
.pcl-btn:active { transform: translateY(0); box-shadow: none; }

.pcl-btn.secondary {
    background: #fff;
    border-color: var(--pcl-border);
    color: var(--pcl-text-main);
    box-shadow: none;
}

.pcl-btn.secondary:hover {
    background: #F8FAFC;
    border-color: #CBD5E1;
}

.pcl-btn.danger {
    background: #fff;
    border-color: #FECACA;
    color: #B91C1C;
    box-shadow: none;
}

.pcl-btn.danger:hover { background: #FEF2F2; border-color: #FCA5A5; }

.pcl-btn.sm {
    min-height: 36px;
    padding: 6px 14px;
    font-size: 13px;
    border-radius: 8px;
}

/* ---------- Table ---------- */
.pcl-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.pcl-table {
    width: 100%;
    min-width: 720px;
    border-collapse: collapse;
}

.pcl-table th,
.pcl-table td {
    padding: 16px 24px;
    text-align: left;
    vertical-align: middle;
    border-bottom: 1px solid var(--pcl-border);
}

.pcl-table th {
    font-size: 12px;
    font-weight: 600;
    color: var(--pcl-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: #F8FAFC;
    white-space: nowrap;
}

.pcl-table td {
    font-size: 14px;
    color: var(--pcl-text-main);
}

.pcl-table tr:last-child td { border-bottom: 0; }
.pcl-table tbody tr:hover { background: #F8FAFC; }

/* ---------- Status badges ---------- */
.pcl-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid transparent;
    white-space: nowrap;
}

.pcl-badge::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

.pcl-badge.active { background: #ECFDF5; border-color: #A7F3D0; color: #065F46; }
.pcl-badge.active::before { background: #10B981; }

.pcl-badge.expired { background: #FEF3C7; border-color: #FDE68A; color: #92400E; }
.pcl-badge.expired::before { background: #F59E0B; }

.pcl-badge.revoked { background: #F1F5F9; border-color: #E2E8F0; color: #475569; }
.pcl-badge.revoked::before { background: #94A3B8; }

/* ---------- Empty state (Redesigned) ---------- */
.pcl-empty {
    padding: 64px 24px;
    text-align: center;
    background: #F8FAFC;
    border: 2px dashed #CBD5E1;
    border-radius: var(--pcl-radius);
    margin: 24px;
}

.pcl-empty-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 20px;
    border-radius: 20px;
    background: #EEF2FF;
    display: grid;
    place-items: center;
    color: var(--pcl-primary);
    box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.1);
}

.pcl-empty h3 {
    margin: 0 0 8px;
    font-size: 18px;
    font-weight: 700;
    color: var(--pcl-text-main);
}

.pcl-empty p {
    margin: 0 auto 24px;
    font-size: 14px;
    color: var(--pcl-text-muted);
    max-width: 420px;
    line-height: 1.6;
}

/* ---------- Pagination ---------- */
.pcl-pagination {
    padding: 16px 24px;
    border-top: 1px solid var(--pcl-border);
    background: #F8FAFC;
}

/* ---------- Toast ---------- */
.pcl-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: #1E293B;
    color: #fff;
    padding: 14px 20px;
    border-radius: 10px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    font-size: 14px;
    font-weight: 500;
    z-index: 100;
    opacity: 0;
    transform: translateY(16px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: none;
    display: flex;
    align-items: center;
    gap: 10px;
}

.pcl-toast.show { opacity: 1; transform: translateY(0); }

/* ---------- Responsive ---------- */
@media (max-width: 980px) {
    .pcl-form { grid-template-columns: 1fr 1fr; }
    .pcl-form .pcl-btn { grid-column: 1 / -1; width: 100%; }
}

@media (max-width: 640px) {
    .pcl-form { grid-template-columns: 1fr; gap: 16px; }
    .pcl-card-body { padding: 20px; }
    .pcl-card-head { padding: 16px 20px; }
    .pcl-empty { margin: 16px; padding: 40px 16px; }
    .pcl-table th, .pcl-table td { padding: 12px 16px; }
}
</style>

<div class="pcl-page">

    {{-- PAGE HEADER --}}
    <div class="pcl-header">
        <div>
            <h1>Public Class Assignment Links</h1>
            <p class="pcl-sub">
                Create secure, time-limited links so active students who don't yet
                have a class can select one during registration or first login.
            </p>
        </div>
        <div class="pcl-header-actions">
            <a href="#pcl-create" class="pcl-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Create new link
            </a>
        </div>
    </div>

    {{-- FLASH ALERTS --}}
    @if(session('public_link'))
        <div class="pcl-alert success" id="pcl-created-alert">
            <svg class="pcl-alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <div class="pcl-alert-body">
                <strong>Link created successfully</strong>
                <span>Copy the URL below and share it with students. It will remain active until the expiry date.</span>
                <div class="pcl-alert-url">
                    <code id="pcl-new-link">{{ session('public_link') }}</code>
                    <button type="button" class="pcl-btn sm secondary" onclick="pclCopy('pcl-new-link', this)">
                        Copy Link
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if(session('success'))
        <div class="pcl-alert success">
            <svg class="pcl-alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <div class="pcl-alert-body">
                <strong>{{ session('success') }}</strong>
            </div>
        </div>
    @endif

    {{-- CREATE NEW LINK --}}
    <section class="pcl-card" id="pcl-create">
        <header class="pcl-card-head">
            <div>
                <h2>Create New Link</h2>
                <p class="pcl-card-sub">Choose a class and expiry window. The link is generated instantly.</p>
            </div>
        </header>

        <div class="pcl-card-body">
            <form method="post"
                  action="{{ route('students.public-class-links.generate') }}"
                  class="pcl-form">
                @csrf

                <div class="pcl-field">
                    <label for="class_arm_id">Select Class</label>
                    <select id="class_arm_id" name="class_arm_id" required>
                        <option value="">Choose a class...</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="pcl-field">
                    <label for="expires_in_days">Link Expiry</label>
                    <select id="expires_in_days" name="expires_in_days" required>
                        <option value="1">1 day</option>
                        <option value="3">3 days</option>
                        <option value="7" selected>7 days</option>
                        <option value="14">14 days</option>
                        <option value="30">30 days</option>
                    </select>
                </div>

                <button class="pcl-btn" type="submit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Generate Link
                </button>
            </form>

            <div class="pcl-help">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                <span>
                    Only <strong>active students without a class</strong> will be able to use this link.
                    Revoking a link instantly stops new assignments but does not affect students already assigned.
                </span>
            </div>
        </div>
    </section>

    {{-- ACTIVE LINKS --}}
    <section class="pcl-card">
        <header class="pcl-card-head">
            <div>
                <h2>
                    Active Links
                    @if($links->count())
                        <span class="pcl-count">{{ $links->total() }}</span>
                    @endif
                </h2>
                <p class="pcl-card-sub">All public class assignment links you have generated.</p>
            </div>
        </header>

        @if($links->count())
            <div class="pcl-table-wrap">
                <table class="pcl-table">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Session / Term</th>
                            <th>Expires</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($links as $link)
                            @php
                                $status = $link->revoked_at
                                    ? 'revoked'
                                    : ($link->expires_at->isPast() ? 'expired' : 'active');
                            @endphp
                            <tr>
                                <td class="pcl-class" style="font-weight:600;">{{ $link->classArm->full_name }}</td>
                                <td class="pcl-session" style="color: var(--pcl-text-muted);">
                                    {{ $link->session->name }} · {{ $link->term->name }}
                                </td>
                                <td class="pcl-date" style="font-family: ui-monospace, monospace; font-size: 13px; color: var(--pcl-text-muted);">
                                    {{ $link->expires_at->format('d M Y, H:i') }}
                                </td>
                                <td>
                                    <span class="pcl-badge {{ $status }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="pcl-actions" style="display:flex; gap:8px; justify-content:flex-end;">
                                        @if($status === 'active')
                                            <form method="post"
                                                  action="{{ route('students.public-class-links.revoke', $link) }}"
                                                  onsubmit="return confirm('Revoke this link? Students will no longer be able to use it.');">
                                                @csrf
                                                @method('PATCH')
                                                <button class="pcl-btn danger sm" type="submit">
                                                    Revoke
                                                </button>
                                            </form>
                                        @else
                                            <span style="color: var(--pcl-text-muted); font-size: 12px; font-style: italic;">
                                                {{ $status === 'revoked' ? 'No action needed' : 'Expired' }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($links->hasPages())
                <div class="pcl-pagination">
                    {{ $links->links() }}
                </div>
            @endif

        @else
            {{-- Empty state --}}
            <div class="pcl-empty">
                <div class="pcl-empty-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                    </svg>
                </div>
                <h3>No public links yet</h3>
                <p>
                    Create your first link so unassigned students can select a class
                    during registration. You can revoke it any time.
                </p>
                <a href="#pcl-create" class="pcl-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Create your first link
                </a>
            </div>
        @endif
    </section>
</div>

{{-- Toast for copy feedback --}}
<div class="pcl-toast" id="pcl-toast">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="20 6 9 17 4 12"/>
    </svg>
    Copied to clipboard!
</div>

<script>
(function () {
    // Smooth-scroll for the "Create link" anchor links
    document.querySelectorAll('a[href="#pcl-create"]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            var target = document.getElementById('pcl-create');
            if (!target) return;
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            setTimeout(function () {
                var select = document.getElementById('class_arm_id');
                if (select) select.focus();
            }, 400);
        });
    });

    // Copy-to-clipboard helper
    window.pclCopy = function (sourceId, btn) {
        var el = document.getElementById(sourceId);
        if (!el) return;
        var text = (el.textContent || el.innerText || '').trim();
        if (!text) return;

        var done = function () {
            var toast = document.getElementById('pcl-toast');
            if (toast) {
                toast.classList.add('show');
                clearTimeout(window.__pclToastTimer);
                window.__pclToastTimer = setTimeout(function () {
                    toast.classList.remove('show');
                }, 2500);
            }
            if (btn) {
                var original = btn.innerHTML;
                btn.innerHTML = 'Copied!';
                setTimeout(function () { btn.innerHTML = original; }, 2000);
            }
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done).catch(function () {
                pclFallbackCopy(text, done);
            });
        } else {
            pclFallbackCopy(text, done);
        }
    };

    function pclFallbackCopy(text, done) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'absolute';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); done(); } catch (e) {}
        document.body.removeChild(ta);
    }
})();
</script>
