<nav class="repo-tabs" aria-label="Academic repository sections">
    <a href="{{ route('super.curriculum-sources.index') }}" class="{{ request()->routeIs('super.curriculum-sources.index') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h6l2 2h8v12H4z"/></svg>
        Resources
    </a>
    <a href="{{ route('super.curriculum-sources.create') }}" class="{{ request()->routeIs('super.curriculum-sources.create') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5M5 14v5h14v-5"/></svg>
        Import archive
    </a>
    <a href="{{ route('super.curriculum-sources.topics.index') }}" class="{{ request()->routeIs('super.curriculum-sources.topics.*') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h10M4 17h7"/><circle cx="18" cy="15" r="3"/></svg>
        Topic mapping
    </a>
</nav>
