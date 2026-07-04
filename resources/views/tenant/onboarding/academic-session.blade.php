@extends('tenant.onboarding.index')
@section('page-title', 'Academic Session')

@push('styles')
<style>
.as-wrap{max-width:1100px}
.as-card{background:#fff;border:1px solid var(--border);border-radius:14px;padding:24px 26px;margin-bottom:16px;box-shadow:0 1px 2px rgba(7,30,69,0.04)}
.as-head{display:flex;align-items:flex-start;gap:14px;margin-bottom:4px}
.as-head .as-mark{flex:0 0 auto;width:42px;height:42px;border-radius:11px;background:rgba(215,154,33,0.12);color:var(--indigo);display:flex;align-items:center;justify-content:center;font-size:20px}
.as-title{font-size:18px;font-weight:800;color:var(--midnight);letter-spacing:-0.02em;margin:0}
.as-sub{font-size:13px;color:var(--slate);line-height:1.55;margin:4px 0 0}
.as-note{background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;padding:11px 14px;font-size:12.5px;color:#92400E;margin:16px 0 4px;line-height:1.5}
.as-form{margin-top:20px}
.as-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:16px}
.as-field{display:flex;flex-direction:column;gap:6px}
.as-label{font-size:12px;font-weight:700;color:var(--midnight);text-transform:uppercase;letter-spacing:.04em}
.as-input{width:100%;padding:11px 13px;font-size:14px;font-family:inherit;color:var(--midnight);background:#F8FAFC;border:1.5px solid var(--border);border-radius:9px;outline:none;transition:border-color 140ms,box-shadow 140ms}
.as-input:focus{border-color:var(--indigo);background:#fff;box-shadow:0 0 0 3px rgba(215,154,33,0.15)}
.as-hint{font-size:11.5px;color:#94A3B8}
.as-error{display:flex;align-items:center;gap:8px;background:#FEF2F2;border:1px solid #FECACA;color:#B91C1C;font-size:12.5px;font-weight:600;border-radius:9px;padding:10px 13px;margin-top:16px}
.as-actions{display:flex;align-items:center;gap:12px;margin-top:20px}
.as-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 20px;border-radius:9px;border:none;background:var(--indigo);color:#fff;font-weight:700;font-size:13.5px;font-family:inherit;cursor:pointer;transition:filter 140ms}
.as-btn:hover{filter:brightness(1.06)}
.as-session{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 16px;border:1px solid var(--border);border-radius:10px;margin-bottom:10px;background:#fff}
.as-session:last-child{margin-bottom:0}
.as-session.is-current{border-color:var(--indigo);background:rgba(215,154,33,0.05)}
.as-session-name{font-size:14px;font-weight:700;color:var(--midnight)}
.as-terms{font-size:12px;color:var(--slate);margin-top:3px}
.as-badge{flex:0 0 auto;font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;padding:4px 10px;border-radius:999px;background:#ECFDF5;color:#059669}
.as-empty{font-size:13px;color:#94A3B8;padding:6px 0}
@media(max-width:620px){.as-card{padding:18px}.as-session{flex-direction:column;align-items:flex-start}}
</style>
@endpush

@section('content')
<div class="as-wrap">
@include('tenant.onboarding.partials.progress')

<div class="as-card">
    <div class="as-head">
        <div class="as-mark">🗓️</div>
        <div>
            <h2 class="as-title">Current Academic Session &amp; Term</h2>
            <p class="as-sub">Your school runs on an active session and term. Set the current one here &mdash; results, attendance, report cards and promotions all depend on it. Saving makes this the school's current session and marks the term as active.</p>
        </div>
    </div>

    <div class="as-note">A current academic session <strong>and</strong> a current term are both required before the school can operate. If a term has just ended, set the next one here to keep things running.</div>

    <form method="POST" action="{{ route('tenant.onboarding.session') }}" class="as-form">
        @csrf
        <div class="as-grid">
            <div class="as-field">
                <label class="as-label" for="session_name">Session Name</label>
                <input class="as-input" id="session_name" name="session_name" value="{{ old('session_name') }}" placeholder="2026/2027" required>
                <span class="as-hint">The academic year, e.g. 2026/2027.</span>
            </div>
            <div class="as-field">
                <label class="as-label" for="term_name">Term Name</label>
                <input class="as-input" id="term_name" name="term_name" value="{{ old('term_name') }}" placeholder="First Term" required>
                <span class="as-hint">e.g. First Term, Second Term, Third Term.</span>
            </div>
            <div class="as-field">
                <label class="as-label" for="term_start_date">Term Start</label>
                <input class="as-input" id="term_start_date" type="date" name="term_start_date" value="{{ old('term_start_date') }}" required>
            </div>
            <div class="as-field">
                <label class="as-label" for="term_end_date">Term End</label>
                <input class="as-input" id="term_end_date" type="date" name="term_end_date" value="{{ old('term_end_date') }}" required>
                <span class="as-hint">Must be on or after the start date.</span>
            </div>
        </div>

        @if($errors->any())
            <div class="as-error">⚠️ {{ $errors->first() }}</div>
        @endif

        <div class="as-actions">
            <button class="as-btn" type="submit">Save &amp; Continue →</button>
        </div>
    </form>
</div>

<div class="as-card">
    <h3 class="as-title" style="font-size:15px;margin-bottom:14px">Existing Sessions</h3>
    @forelse($sessions as $session)
        <div class="as-session {{ $session->is_current ? 'is-current' : '' }}">
            <div>
                <div class="as-session-name">{{ $session->name }}</div>
                @if($session->relationLoaded('terms') && $session->terms->count())
                    <div class="as-terms">
                        @foreach($session->terms as $term){{ $term->name }}@if($term->is_current) (current)@endif@if(!$loop->last) &middot; @endif@endforeach
                    </div>
                @else
                    <div class="as-terms">No terms recorded yet.</div>
                @endif
            </div>
            @if($session->is_current)<span class="as-badge">Current</span>@endif
        </div>
    @empty
        <p class="as-empty">No sessions yet. Add your current session and term above to get started.</p>
    @endforelse
</div>
</div>
@endsection
