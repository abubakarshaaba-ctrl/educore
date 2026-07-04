<div class="cycle-grid">
    <div class="cycle-card">
        <span class="cycle-label">Current Session</span>
        <strong>{{ optional($currentSession)->name ?? 'Not configured' }}</strong>
        @if($currentSession)
            <small>ID {{ $currentSession->id }}</small>
        @endif
    </div>
    <div class="cycle-card">
        <span class="cycle-label">Current Term</span>
        <strong>{{ optional($currentTerm)->name ?? 'Not configured or ambiguous' }}</strong>
        @if($currentTerm)
            <small>{{ optional($currentTerm->start_date)->format('M d, Y') }} to {{ optional($currentTerm->end_date)->format('M d, Y') }}</small>
        @endif
    </div>
</div>
