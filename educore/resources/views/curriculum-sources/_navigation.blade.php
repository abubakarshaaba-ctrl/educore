<nav class="repository-tabs" aria-label="Academic repository sections">
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

<style>
.repository-tabs{margin:0 0 20px;padding:5px;display:inline-flex;align-items:center;gap:4px;border:1px solid #dfe7f0;border-radius:12px;background:#fff;box-shadow:0 4px 14px rgba(15,39,72,.035)}
.repository-tabs a{min-height:38px;padding:0 13px;display:inline-flex;align-items:center;gap:7px;border-radius:8px;color:#64748a;font-size:11px;font-weight:750;text-decoration:none;transition:color .15s ease,background .15s ease,box-shadow .15s ease}
.repository-tabs a:hover{color:#163d6c;background:#f4f7fb}.repository-tabs a.active{color:#fff;background:#123f75;box-shadow:0 5px 14px rgba(18,63,117,.18)}
.repository-tabs svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
@media(max-width:560px){.repository-tabs{width:100%;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));overflow:hidden}.repository-tabs a{padding:7px 5px;justify-content:center;flex-direction:column;gap:4px;text-align:center;font-size:9px}.repository-tabs svg{width:15px;height:15px}}
</style>
