@extends('layouts.app')
@section('title','Public Class Assignment Links')
@section('page-title','Public Class Assignment Links')

<style>
.public-class-links-form {
    display:grid;
    grid-template-columns:minmax(0,2fr) minmax(160px,1fr) auto;
    gap:12px;
    align-items:end;
}

.public-class-links-field label {
    display:block;
    margin-bottom:4px;
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.04em;
    color:var(--slate-light);
}

.public-class-links-field select {
    width:100%;
    min-height:38px;
    padding:8px 10px;
    border:1px solid var(--border);
    border-radius:8px;
    background:#F8FAFC;
    color:var(--midnight);
    font:inherit;
    font-size:13px;
    outline:none;
}

.public-class-links-field select:focus {
    border-color:var(--indigo);
    background:#fff;
}

.public-class-links-table-wrap {
    width:100%;
    max-width:100%;
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
}

.public-class-links-table {
    width:100%;
    min-width:720px;
    border-collapse:collapse;
}

.public-class-links-table th,
.public-class-links-table td {
    padding:11px 10px;
    border-bottom:1px solid var(--border);
    text-align:left;
    vertical-align:middle;
}

.public-class-links-table th {
    font-size:11px;
    font-weight:800;
    color:var(--slate);
    text-transform:uppercase;
    letter-spacing:.04em;
    background:#F8FAFC;
}

.public-class-links-table td { font-size:12px; }

.public-class-links-alert {
    padding:12px 16px;
    border-radius:8px;
    margin-bottom:16px;
    border:1px solid #A7F3D0;
    background:#ECFDF5;
    color:#065F46;
    font-size:13px;
}

.public-class-links-alert a { color:inherit; }

.public-class-links-link {
    margin-top:4px;
    overflow-wrap:anywhere;
    font-size:12px;
}

.public-class-links-btn {
    min-height:40px;
    padding:8px 15px;
    border:0;
    border-radius:8px;
    background:var(--indigo);
    color:#fff;
    font:inherit;
    font-size:12px;
    font-weight:700;
    cursor:pointer;
    white-space:nowrap;
}

.public-class-links-btn:hover { background:var(--indigo-dark); }

.public-class-links-revoke {
    padding:6px 10px;
    border:1px solid #FECACA;
    border-radius:7px;
    background:#fff;
    color:#B91C1C;
    font:inherit;
    font-size:11px;
    font-weight:700;
    cursor:pointer;
}

.public-class-links-revoke:hover { background:#FEF2F2; }

@media (max-width:980px) {
    .public-class-links-form { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .public-class-links-btn { grid-column:1/-1; width:100%; }
}

@media (max-width:640px) {
    .public-class-links-form { grid-template-columns:1fr; gap:10px; }
    .public-class-links-btn { grid-column:auto; width:100%; }
    .public-class-links-table th { font-size:9px; padding:8px 10px; }
    .public-class-links-table td { font-size:11px; padding:9px 10px; }
}
</style>

<div>
    <div class="page-header">
        <div>
            <h1>Public Class Assignment Links</h1>
            <div class="hint">Create secure links for assigning active students who do not yet have a class.</div>
        </div>
    </div>
    @if(session('public_link'))
        <div class="public-class-links-alert">
            <strong>Link created.</strong>
            <div class="public-class-links-link">{{ session('public_link') }}</div>
        </div>
    @endif

    @if(session('success'))
        <div class="public-class-links-alert">{{ session('success') }}</div>
    @endif

    <section class="ec-card" style="padding:18px;">
        <form method="post" action="{{ route('students.public-class-links.generate') }}" class="public-class-links-form">
            @csrf
            <div class="public-class-links-field">
                <label for="class_arm_id">Class</label>
                <select id="class_arm_id" name="class_arm_id" required>
                    <option value="">Select class</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->full_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="public-class-links-field">
                <label for="expires_in_days">Link expiry</label>
                <select id="expires_in_days" name="expires_in_days" required>
                    <option value="1">1 day</option>
                    <option value="3">3 days</option>
                    <option value="7" selected>7 days</option>
                    <option value="14">14 days</option>
                    <option value="30">30 days</option>
                </select>
            </div>

            <button class="public-class-links-btn" type="submit">Create link</button>
        </form>

        <div class="public-class-links-table-wrap">
            <table class="public-class-links-table">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Session / Term</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($links as $link)
                        <tr>
                            <td>{{ $link->classArm->full_name }}</td>
                            <td>{{ $link->session->name }} / {{ $link->term->name }}</td>
                            <td>{{ $link->expires_at->format('d M Y H:i') }}</td>
                            <td>{{ $link->revoked_at ? 'Revoked' : ($link->expires_at->isPast() ? 'Expired' : 'Active') }}</td>
                            <td>
                                @if(!$link->revoked_at && $link->expires_at->isFuture())
                                    <form method="post" action="{{ route('students.public-class-links.revoke',$link) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="public-class-links-revoke" type="submit">Revoke</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No public class links yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:14px;">
            {{ $links->links() }}
        </div>
    </section>
</div>