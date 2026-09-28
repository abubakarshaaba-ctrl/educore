@extends('layouts.app')

@section('title', 'Public Class Assignment Links')
@section('page-title', 'Public Class Assignment Links')

@section('content')

<style>
/* ============================================================
   Public Class Links — Streamlined & Expanded Layout
   ============================================================ */

/* 1. Page Container — Expanded to reduce side empty space */
.pcl-page {
    width: 100%;
    max-width: 1200px; /* Expanded from 900px to fill more space */
    margin: 0 auto;
    padding: 20px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* 2. Premium Cards */
.pcl-card {
    background: #fff;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
    overflow: hidden;
    transition: box-shadow 0.3s ease, transform 0.3s ease;
}

.pcl-card:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
    transform: translateY(-2px);
}

.pcl-card-head {
    padding: 20px 24px;
    border-bottom: 1px solid #E2E8F0;
    background: #F8FAFC;
}

.pcl-card-head h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #1E293B;
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
    padding: 0 8px;
    border-radius: 999px;
    background: #E2E8F0;
    color: #1E293B;
    font-size: 11px;
    font-weight: 700;
}

.pcl-card-head .pcl-card-sub {
    margin: 4px 0 0;
    font-size: 13px;
    color: #64748B;
}

.pcl-card-body {
    padding: 24px;
}

/* 3. Form Layout — Expanded grid to utilize wider space */
.pcl-form {
    display: grid;
    grid-template-columns: minmax(0, 400px) minmax(0, 250px) auto; /* Wider inputs */
    gap: 20px;
    align-items: end;
    max-width: 100%;
}

.pcl-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
}

.pcl-field label {
    font-size: 13px;
    font-weight: 600;
    color: #1E293B;
}

.pcl-field select {
    width: 100%;
    min-height: 44px;
    padding: 8px 12px;
    padding-right: 36px;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    background-color: #F8FAFC;
    color: #1E293B;
    font: inherit;
    font-size: 14px;
    outline: none;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    transition: all 0.2s ease;
}

.pcl-field select:focus {
    border-color: #F59E0B;
    background-color: #fff;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.15);
}

.pcl-help {
    margin-top: 24px;
    padding: 16px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    font-size: 13px;
    color: #64748B;
    line-height: 1.5;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.pcl-help svg { flex-shrink: 0; margin-top: 2px; color: #F59E0B; }

/* 4. Premium Buttons */
.pcl-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    padding: 8px 24px;
    border: 1px solid transparent;
    border-radius: 10px;
    background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
    color: #1E293B;
    font: inherit;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    box-shadow: 0 2px 4px rgba(245, 158, 11, 0.2);
    transition: all 0.2s ease;
}

.pcl-btn:hover { 
    background: linear-gradient(135deg, #D97706 0%, #B45309 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(245, 158, 11, 0.3);
}
.pcl-btn:active { transform: translateY(0); }

.pcl-btn.secondary {
    background: #fff;
    border-color: #E2E8F0;
    color: #1E293B;
    box-shadow: none;
}
.pcl-btn.secondary:hover { background: #F8FAFC; border-color: #CBD5E1; }

.pcl-btn.danger {
    background: #fff;
    border-color: #FECACA;
    color: #B91C1C;
    box-shadow: none;
}
.pcl-btn.danger:hover { background: #FEF2F2; border-color: #FCA5A5; }

/* 5. Table */
.pcl-table-wrap { width: 100%; overflow-x: auto; }
.pcl-table { width: 100%; min-width: 600px; border-collapse: collapse; }
.pcl-table th, .pcl-table td { padding: 16px 24px; text-align: left; border-bottom: 1px solid #E2E8F0; }
.pcl-table th { font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em; background: #F8FAFC; }
.pcl-table td { font-size: 14px; color: #1E293B; }
.pcl-table tr:last-child td { border-bottom: 0; }
.pcl-table tbody tr:hover { background: #F8FAFC; }

/* 6. Badges */
.pcl-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px; border-radius: 999px;
    font-size: 12px; font-weight: 600; border: 1px solid transparent;
}
.pcl-badge.active { background: #ECFDF5; border-color: #A7F3D0; color: #065F46; }
.pcl-badge.active::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #10B981; }
.pcl-badge.expired { background: #FEF3C7; border-color: #FDE68A; color: #92400E; }
.pcl-badge.expired::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #F59E0B; }
.pcl-badge.revoked { background: #F1F5F9; border-color: #E2E8F0; color: #475569; }
.pcl-badge.revoked::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #94A3B8; }

/* 7. Premium Empty State */
.pcl-empty {
    padding: 48px 24px;
    text-align: center;
    max-width: 500px;
    margin: 0 auto;
    background: #F8FAFC;
    border: 2px dashed #E2E8F0;
    border-radius: 16px;
}

.pcl-empty-icon {
    width: 64px; height: 64px;
    margin: 0 auto 16px;
    border-radius: 20px;
    background: #FEF3C7;
    display: grid; place-items: center;
    color: #D97706;
    box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.1);
}

.pcl-empty h3 { margin: 0 0 8px; font-size: 18px; font-weight: 700; color: #1E293B; }
.pcl-empty p { margin: 0 auto 24px; font-size: 14px; color: #64748B; line-height: 1.6; }

/* Responsive Breakpoints */
@media (max-width: 768px) {
    .pcl-form { grid-template-columns: 1fr; }
    .pcl-form .pcl-btn { width: 100%; }
    .pcl-page { padding: 16px; }
}
</style>

<div class="pcl-page">

    {{-- FLASH ALERTS --}}
    @if(session('public_link'))
        <div class="pcl-alert success" style="background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; padding: 16px; border-radius: 12px; display: flex; gap: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <div style="flex:1;">
                <strong style="display:block; margin-bottom:4px;">Link created successfully</strong>
                <span>Copy the URL below and share it with students. It will remain active until the expiry date.</span>
                <div style="display:flex; gap:8px; margin-top:10px; background:#fff; padding:8px 12px; border-radius:8px; border:1px solid #A7F3D0;">
                    <code id="pcl-new-link" style="flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:13px; line-height: 24px;">{{ session('public_link') }}</code>
                    <button type="button" class="pcl-btn" onclick="pclCopy('pcl-new-link', this)" style="min-height:32px; padding:0 16px; font-size:12px; box-shadow:none;">Copy</button>
                </div>
            </div>
        </div>
    @endif

    @if(session('success'))
        <div class="pcl-alert success" style="background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; padding: 16px; border-radius: 12px; display: flex; gap: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <div><strong>{{ session('success') }}</strong></div>
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
            <form method="post" action="{{ route('students.public-class-links.generate') }}" class="pcl-form">
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
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Generate Link
                </button>
            </form>

            <div class="pcl-help">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>Only <strong>active students without a class</strong> will be able to use this link. Revoking a link instantly stops new assignments but does not affect students already assigned.</span>
            </div>
        </div>
    </section>

    {{-- ACTIVE LINKS --}}
    <section class="pcl-card">
        <header class="pcl-card-head">
            <div>
                <h2>Active Links @if($links->count()) <span class="pcl-count">{{ $links->total() }}</span> @endif</h2>
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
                                $status = $link->revoked_at ? 'revoked' : ($link->expires_at->isPast() ? 'expired' : 'active');
                            @endphp
                            <tr>
                                <td style="font-weight:600;">{{ $link->classArm->full_name }}</td>
                                <td style="color: #64748B;">{{ $link->session->name }} · {{ $link->term->name }}</td>
                                <td style="font-family: monospace; font-size: 13px; color: #64748B;">{{ $link->expires_at->format('d M Y, H:i') }}</td>
                                <td><span class="pcl-badge {{ $status }}">{{ ucfirst($status) }}</span></td>
                                <td>
                                    <div style="display:flex; gap:8px; justify-content:flex-end;">
                                        @if($status === 'active')
                                            <form method="post" action="{{ route('students.public-class-links.revoke', $link) }}" onsubmit="return confirm('Revoke this link? Students will no longer be able to use it.');">
                                                @csrf @method('PATCH')
                                                <button class="pcl-btn danger" type="submit" style="min-height:32px; padding:4px 12px; font-size:12px;">Revoke</button>
                                            </form>
                                        @else
                                            <span style="color: #94A3B8; font-size: 12px; font-style: italic;">{{ $status === 'revoked' ? 'No action needed' : 'Expired' }}</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($links->hasPages())
                <div style="padding: 16px 24px; border-top: 1px solid #E2E8F0; background: #F8FAFC;">{{ $links->links() }}</div>
            @endif
        @else
            {{-- Premium Empty State --}}
            <div class="pcl-empty">
                <div class="pcl-empty-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                    </svg>
                </div>
                <h3>No public links yet</h3>
                <p>Create your first link so unassigned students can select a class during registration. You can revoke it any time.</p>
                <a href="#pcl-create" class="pcl-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Create your first link
                </a>
            </div>
        @endif
    </section>
</div>

<div id="pcl-toast" style="position: fixed; bottom: 24px; right: 24px; background: #1E293B; color: #fff; padding: 12px 20px; border-radius: 10px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); font-size: 14px; font-weight: 500; z-index: 100; opacity: 0; transform: translateY(16px); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); pointer-events: none; display: flex; align-items: center; gap: 8px;">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    Copied to clipboard!
</div>

<script>
window.pclCopy = function (sourceId, btn) {
    var el = document.getElementById(sourceId);
    if (!el) return;
    var text = (el.textContent || el.innerText || '').trim();
    if (!text) return;
    var done = function () {
        var toast = document.getElementById('pcl-toast');
        if (toast) {
            toast.style.opacity = '1'; toast.style.transform = 'translateY(0)';
            clearTimeout(window.__pclToastTimer);
            window.__pclToastTimer = setTimeout(function () {
                toast.style.opacity = '0'; toast.style.transform = 'translateY(16px)';
            }, 2500);
        }
        if (btn) { var o = btn.innerHTML; btn.innerHTML = 'Copied!'; setTimeout(function(){ btn.innerHTML = o; }, 2000); }
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(function(){ pclFallbackCopy(text, done); });
    } else { pclFallbackCopy(text, done); }
};
function pclFallbackCopy(text, done) {
    var ta = document.createElement('textarea'); ta.value = text; ta.setAttribute('readonly', ''); ta.style.position = 'absolute'; ta.style.left = '-9999px';
    document.body.appendChild(ta); ta.select(); try { document.execCommand('copy'); done(); } catch (e) {} document.body.removeChild(ta);
}
document.querySelectorAll('a[href="#pcl-create"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
        e.preventDefault();
        document.getElementById('pcl-create').scrollIntoView({ behavior: 'smooth', block: 'start' });
        setTimeout(function () { var s = document.getElementById('class_arm_id'); if (s) s.focus(); }, 400);
    });
});
</script>

@endsection
