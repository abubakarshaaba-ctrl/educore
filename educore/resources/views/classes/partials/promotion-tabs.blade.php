<style>
.promotion-tabs-wrap{margin-bottom:18px;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:thin}
.promotion-tabs{display:inline-flex;min-width:max-content;gap:5px;padding:5px;background:#fff;border:1px solid #E2E8F0;border-radius:12px;box-shadow:0 2px 10px rgba(15,23,42,.035)}
.promotion-tab{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:8px 14px;border-radius:8px;color:#526176;text-decoration:none;font-size:12px;font-weight:700;white-space:nowrap;transition:background .15s,color .15s,box-shadow .15s}
.promotion-tab:hover{background:#F5F7FA;color:#071E45}
.promotion-tab.active{background:#071E45;color:#fff;box-shadow:0 3px 8px rgba(7,30,69,.16)}
@media(max-width:640px){.promotion-tabs-wrap{margin-left:-2px;margin-right:-2px;padding-bottom:3px}.promotion-tab{padding:8px 12px;font-size:11px}}
</style>

<div class="promotion-tabs-wrap" aria-label="Promotion Engine sections">
    <nav class="promotion-tabs">
        <a href="{{ route('classes.promotion.preview') }}" class="promotion-tab {{ request()->routeIs('classes.promotion.preview') ? 'active' : '' }}">Run Promotion</a>
        <a href="{{ route('classes.grading') }}" class="promotion-tab {{ request()->routeIs('classes.grading') ? 'active' : '' }}">Grade Scales</a>
        <a href="{{ route('classes.promotion') }}" class="promotion-tab {{ request()->routeIs('classes.promotion') ? 'active' : '' }}">Promotion Rules</a>
        <a href="{{ route('classes.promotion.history') }}" class="promotion-tab {{ request()->routeIs('classes.promotion.history') ? 'active' : '' }}">History</a>
        <a href="{{ route('classes.bulk-promote.page') }}" class="promotion-tab {{ request()->routeIs('classes.bulk-promote.page') ? 'active' : '' }}">Manual Bulk</a>
    </nav>
</div>
