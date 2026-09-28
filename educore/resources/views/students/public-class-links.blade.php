@extends('layouts.app')

@section('title', 'Public Class Assignment Links')
@section('page-title', 'Public Class Assignment Links')

<style>
/* ============================================================
   Public Class Links — Page-scoped styles
   ============================================================ */

.pcl-page {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* ---------- Page header ---------- */
.pcl-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}

.pcl-header h1 {
    margin: 0 0 6px;
    font-size: 20px;
    font-weight: 800;
    letter-spacing: -0.01em;
    color: var(--midnight);
}

.pcl-header .pcl-sub {
    color: var(--slate-light);
    font-size: 13px;
    max-width: 640px;
    line-height: 1.5;
}

.pcl-header-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

/* ---------- Cards ---------- */
.pcl-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
}

.pcl-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 18px;
    border-bottom: 1px solid var(--border);
    background: #F8FAFC;
    flex-wrap: wrap;
}

.pcl-card-head h2 {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
    color: var(--midnight);
    display: flex;
    align-items: center;
    gap: 8px;
}

.pcl-card-head .pcl-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 7px;
    border-radius: 999px;
    background: var(--indigo);
    color: #fff;
    font-size: 11px;
    font-weight: 800;
}

.pcl-card-head .pcl-card-sub {
    margin: 4px 0 0;
    font-size: 12px;
    color: var(--slate-light);
    font-weight: 500;
}

.pcl-card-body {
    padding: 18px;
}

/* ---------- Alerts ---------- */
.pcl-alert {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 10px;
    font-size: 13px;
    line-height: 1.5;
    border: 1px solid transparent;
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
    width: 18px;
    height: 18px;
    margin-top: 1px;
}

.pcl-alert-body {
    flex: 1;
    min-width: 0;
}

.pcl-alert-body strong { display: block; margin-bottom: 4px; }

.pcl-alert-url {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-top: 6px;
    padding: 8px 10px;
    background: #fff;
    border: 1px solid #A7F3D0;
    border-radius: 8px;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 12px;
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
    gap: 14px;
    align-items: end;
}

.pcl-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
}

.pcl-field label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--slate);
}

.pcl-field select {
    width: 100%;
    min-height: 42px;
    padding: 9px 12px;
    border: 1px solid var(--border);
    border-radius: 9px;
    background: #F8FAFC;
    color: var(--midnight);
    font: inherit;
    font-size: 13.5px;
    outline: none;
    transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
}

.pcl-field select:focus {
    border-color: var(--indigo);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
}

.pcl-help {
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px dashed var(--border);
    font-size: 12px;
    color: var(--slate-light);
    line-height: 1.55;
    display: flex;
    gap: 8px;
    align-items: flex-start;
}

.pcl-help svg { flex-shrink: 0; margin-top: 2px; }

/* ---------- Buttons ---------- */
.pcl-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 42px;
    padding: 8px 18px;
    border: 1px solid transparent;
    border-radius: 9px;
    background: var(--indigo);
    color: #fff;
    font: inherit;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    transition: background 0.15s, transform 0.15s, box-shadow 0.15s;
}

.pcl-btn:hover { background: var(--indigo-dark); }
.pcl-btn:active { transform: translateY(1px); }

.pcl-btn.secondary {
    background: #fff;
    border-color: var(--border);
    color: var(--midnight);
}

.pcl-btn.secondary:hover {
    background: #F8FAFC;
    border-color: var(--slate-light);
}

.pcl-btn.ghost {
    background: transparent;
    border-color: transparent;
    color: var(--indigo);
    padding: 6px 10px;
    min-height: 32px;
    font-size: 12px;
}

.pcl-btn.ghost:hover { background: #EEF2FF; }

.pcl-btn.sm {
    min-height: 32px;
    padding: 5px 12px;
    font-size: 12px;
}

.pcl-btn.danger {
    background: #fff;
    border-color: #FECACA;
    color: #B91C1C;
    padding: 5px 12px;
    min-height: 32px;
    font-size: 12px;
}

.pcl-btn.danger:hover { background: #FEF2F2; }

/* ---------- Table ---------- */
.pcl-table-wrap {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.pcl-table {
    width: 100%;
    min-width: 720px;
    border-collapse: collapse;
}

.pcl-table th,
.pcl-table td {
    padding: 12px 16px;
    text-align: left;
    vertical-align: middle;
    border-bottom: 1px solid var(--border);
}

.pcl-table th {
    font-size: 11px;
    font-weight: 800;
    color: var(--slate);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: #F8FAFC;
    white-space: nowrap;
}

.pcl-table td {
    font-size: 13px;
    color: var(--midnight);
}

.pcl-table tr:last-child td { border-bottom: 0; }
.pcl-table tbody tr:hover { background: #FAFBFD; }

.pcl-table .pcl-class {
    font-weight: 700;
    color: var(--midnight);
}

.pcl-table .pcl-session {
    color: var(--slate);
    font-size: 12.5px;
}

.pcl-table .pcl-date {
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 12px;
    color: var(--slate);
    white-space: nowrap;
}

.pcl-table .pcl-actions {
    display: flex;
    gap: 6px;
    justify-content: flex-end;
}

/* ---------- Status badges ---------- */
.pcl-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.02em;
    border: 1px solid transparent;
    white-space: nowrap;
}

.pcl-badge::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

.pcl-badge.active {
    background: #ECFDF5;
    border-color: #A7F3D0;
    color: #065F46;
}

.pcl-badge.active::before { background: #10B981; }

.pcl-badge.expired {
    background: #FEF3C7;
    border-color: #FDE68A;
    color: #92400E;
}

.pcl-badge.expired::before { background: #F59E0B; }

.pcl-badge.revoked {
    background: #F1F5F9;
    border-color: #E2E8F0;
    color: #475569;
}

.pcl-badge.revoked::before { background: #94A3B8; }

/* ---------- Empty state ---------- */
.pcl-empty {
    padding: 48px 24px;
    text-align: center;
}

.pcl-empty-icon {
    width: 56px;
    height: 56px;
    margin: 0 auto 16px;
    border-radius: 16px;
    background: #EEF2FF;
    display: grid;
    place-items: center;
    color: var(--indigo);
}

.pcl-empty h3 {
    margin: 0 0 6px;
    font-size: 15px;
    font-weight: 700;
    color: var(--midnight);
}

.pcl-empty p {
    margin: 0 0 20px;
    font-size: 13px;
    color: var(--slate-light);
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.55;
}

/* ---------- Pagination ---------- */
.pcl-pagination {
    padding: 14px 18px;
    border-top: 1px solid var(--border);
    background: #F8FAFC;
}

/* ---------- Toast ---------- */
.pcl-toast {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: var(--midnight);
    color: #fff;
    padding: 12px 18px;
    border-radius: 10px;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.25);
    font-size: 13px;
    font-weight: 600;
    z-index: 100;
    opacity: 0;
    transform: translateY(12px);
    transition: opacity 0.2s, transform 0.2s;
    pointer-events: none;
}

.pcl-toast.show { opacity: 1; transform: translateY(0); }

/* ---------- Responsive ---------- */
@media (max-width: 980px) {
    .pcl-form { grid-template-columns: 1fr 1fr; }
    .pcl-form .pcl-btn { grid-column: 1 / -1; width: 100%; }
}

@media (max-width: 640px) {
    .pcl-form { grid-template-columns: 1fr; gap: 12px; }
    .pcl-form .pcl-btn { grid-column: auto; }
    .pcl-card-body { padding: 14px; }
    .pcl-card-head { padding: 12px 14px; }
    .pcl-table th { padding: 10px 12px; font-size: 10px; }
    .pcl-table td { padding: 11px 12px; font-size: 12.5px; }
    .pcl-table .pcl-actions { flex-direction: column; align-items: stretch; }
    .pcl-table .pcl-actions .pcl-btn { width: 100%; justify-content: center; }
}
</style>

<div class="pcl-page">

    {{-- ============================================================
         PAGE HEADER
         ============================================================ --}}
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
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Create new link
            </a>
        </div>
    </div>

    {{-- ============================================================
         FLASH ALERTS
         ============================================================ --}}
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
                        Copy
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

    {{-- ============================================================
         CREATE NEW LINK
         ============================================================ --}}
    <section class="pcl-card" id="pcl-create">
        <header class="pcl-card-head">
            <div>
                <h2>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Create New Link
                </h2>
                <p class="pcl-card-sub">Choose a class and expiry window. The link is generated instantly.</p>
            </div>
        </header>

        <div class="pcl-card-body">
            <form method="post"
                  action="{{ route('students.public-class-links.generate') }}"
                  class="pcl-form">
                @csrf

                <div class="pcl-field">
                    <label for="class_arm_id">Class</label>
                    <select id="class_arm_id" name="class_arm_id" required>
                        <option value="">Select class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="pcl-field">
                    <label for="expires_in_days">Link expiry</label>
                    <select id="expires_in_days" name="expires_in_days" required>
                        <option value="1">1 day</option>
                        <option value="3">3 days</option>
                        <option value="7" selected>7 days</option>
                        <option value="14">14 days</option>
                        <option value="30">30 days</option>
                    </select>
                </div>

                <button class="pcl-btn" type="submit">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Create link
                </button>
            </form>

            <div class="pcl-help">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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

    {{-- ============================================================
         ACTIVE LINKS
         ============================================================ --}}
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
                                <td class="pcl-class">{{ $link->classArm->full_name }}</td>
                                <td class="pcl-session">
                                    {{ $link->session->name }} · {{ $link->term->name }}
                                </td>
                                <td class="pcl-date">
                                    {{ $link->expires_at->format('d M Y, H:i') }}
                                </td>
                                <td>
                                    <span class="pcl-badge {{ $status }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="pcl-actions">
                                        @if($status === 'active')
                                            <form method="post"
                                                  action="{{ route('students.public-class-links.revoke', $link) }}"
                                                  onsubmit="return confirm('Revoke this link? Students will no longer be able to use it.');">
                                                @csrf
                                                @method('PATCH')
                                                <button class="pcl-btn danger" type="submit">
                                                    Revoke
                                                </button>
                                            </form>
                                        @else
                                            <span style="color: var(--slate-light); font-size: 11.5px;">
                                                {{ $status === 'revoked' ? 'No action' : 'Expired' }}
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
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
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
<div class="pcl-toast" id="pcl-toast">Copied to clipboard</div>

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
                }, 1800);
            }
            if (btn) {
                var original = btn.textContent;
                btn.textContent = 'Copied';
                setTimeout(function () { btn.textContent = original; }, 1500);
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
