@extends('layouts.super')

@section('title', 'Academic Content Repository')

@section('content')
<div class="repo-page">
    <header class="repo-header">
        <div>
            <span class="eyebrow">ACADEMIC REPOSITORY</span>
            <h1>Content library</h1>
            <p>Sources used by EduCore Lesson Planner.</p>
        </div>
        <a href="{{ route('super.curriculum-sources.create') }}" class="button button-primary">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5M5 14v5h14v-5"/></svg>
            Import archive
        </a>
    </header>

    @include('curriculum-sources._navigation')

    @if(session('success'))
        <div class="notice notice-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="notice notice-error">{{ $errors->first() }}</div>
    @endif

    <section class="repo-metrics" aria-label="Repository summary">
        @foreach([
            ['resources', 'Resources', 'library'],
            ['indexed', 'Indexed', 'check'],
            ['review', 'In review', 'review'],
            ['failed', 'Failed', 'alert'],
            ['imports', 'Imports', 'archive'],
        ] as [$key, $label, $icon])
            <article class="metric-card">
                <span class="metric-icon metric-icon-{{ $icon }}" aria-hidden="true"></span>
                <div><strong>{{ number_format($analytics[$key]) }}</strong><span>{{ $label }}</span></div>
            </article>
        @endforeach
    </section>

    <main class="repo-main">
            <section class="repo-panel resource-panel">
                <div class="panel-heading">
                    <div>
                        <h2>Resources</h2>
                        <span>{{ $sources->total() }} {{ \Illuminate\Support\Str::plural('item', $sources->total()) }}</span>
                    </div>
                    <form class="repo-search" method="GET" action="{{ route('super.curriculum-sources.index') }}" role="search">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                        <input name="search" value="{{ request('search') }}" placeholder="Search resources" aria-label="Search resources">
                        @if(request()->filled('search'))
                            <a href="{{ route('super.curriculum-sources.index') }}" aria-label="Clear search">Clear</a>
                        @endif
                    </form>
                </div>

                <div class="resource-list">
                    @forelse($sources as $source)
                        @php
                            $extension = strtoupper(pathinfo($source->original_filename ?? '', PATHINFO_EXTENSION) ?: $source->source_type);
                            $statusLabel = $source->is_active ? 'Active' : ($source->needs_review ? 'In review' : 'Inactive');
                            $statusClass = $source->is_active ? 'status-active' : ($source->needs_review ? 'status-review' : 'status-inactive');
                        @endphp
                        <article class="resource-row">
                            <div class="file-mark" aria-hidden="true">{{ substr($extension, 0, 4) }}</div>
                            <div class="resource-copy">
                                <div class="resource-meta">
                                    <span>{{ $source->authority }}</span>
                                    <i></i>
                                    <span>{{ str_replace('_', ' ', $source->source_type) }}</span>
                                </div>
                                <h3>{{ $source->title }}</h3>
                                <p>
                                    <span>{{ $source->original_filename }}</span>
                                    <span>{{ number_format($source->fragments_count) }} {{ \Illuminate\Support\Str::plural('section', $source->fragments_count) }}</span>
                                </p>
                            </div>
                            <div class="resource-controls">
                                <span class="status-pill {{ $statusClass }}"><i></i>{{ $statusLabel }}</span>
                                <div class="row-actions">
                                    @if(!$source->is_active && $source->extraction_status === 'extracted')
                                        <form method="POST" action="{{ route('super.curriculum-sources.activate', $source) }}">
                                            @csrf
                                            <button class="text-button text-button-primary">Activate</button>
                                        </form>
                                    @elseif($source->is_active)
                                        <form method="POST" action="{{ route('super.curriculum-sources.deactivate', $source) }}">
                                            @csrf
                                            <button class="text-button">Deactivate</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('super.curriculum-sources.destroy', $source) }}" onsubmit="return confirm('Remove this resource?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="icon-button danger" aria-label="Remove {{ $source->title }}" title="Remove">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3m3 0-1 13H7L6 7m4 4v5m4-5v5"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="empty-state">
                            <div class="empty-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h6l2 2h8v12H4z"/><path d="M9 13h6m-3-3v6"/></svg></div>
                            <h3>{{ request()->filled('search') ? 'No matches found' : 'Repository is empty' }}</h3>
                            <p>{{ request()->filled('search') ? 'Try a different search.' : 'Import your first archive.' }}</p>
                            @unless(request()->filled('search'))
                                <a href="{{ route('super.curriculum-sources.create') }}" class="button button-primary">Import archive</a>
                            @endunless
                        </div>
                    @endforelse
                </div>

                @if($sources->hasPages())
                    <div class="repo-pagination">{{ $sources->links() }}</div>
                @endif
            </section>

            <section class="repo-panel import-panel">
                <div class="panel-heading">
                    <div><h2>Import history</h2><span>Latest 10</span></div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Archive</th><th>Format</th><th>Imported</th><th>Duplicates</th><th>Failed</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($imports as $import)
                                <tr>
                                    <td><strong>{{ $import->filename }}</strong><span>#{{ $import->id }}</span></td>
                                    <td><span class="format-chip">{{ strtoupper($import->format) }}</span></td>
                                    <td>{{ number_format($import->imported) }}</td>
                                    <td>{{ number_format($import->duplicates) }}</td>
                                    <td>{{ number_format($import->failed) }}</td>
                                    <td><span class="history-status">{{ ucfirst(str_replace('_', ' ', $import->status)) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="table-empty">No imports yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
    </main>
</div>

<style>
:root{--repo-navy:#09244a;--repo-blue:#1756a9;--repo-gold:#e5a91b;--repo-ink:#12233d;--repo-muted:#697890;--repo-line:#e3e9f1;--repo-canvas:#f4f7fb}
.repo-page{max-width:1480px;margin:0 auto;padding:28px 30px 48px;color:var(--repo-ink)}
.repo-header{display:flex;align-items:center;justify-content:space-between;gap:24px;margin-bottom:22px}.eyebrow{display:block;margin-bottom:7px;color:var(--repo-blue);font-size:11px;font-weight:800;letter-spacing:.14em}.repo-header h1{margin:0;font-size:30px;line-height:1.15;letter-spacing:-.035em;color:#071b38}.repo-header p{margin:7px 0 0;color:var(--repo-muted);font-size:13px}
.button{min-height:42px;padding:0 17px;border:0;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;gap:9px;font-size:13px;font-weight:750;text-decoration:none;cursor:pointer;transition:transform .15s ease,box-shadow .15s ease,background .15s ease}.button:hover{transform:translateY(-1px)}.button svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round}.button-primary{background:var(--repo-blue);color:#fff;box-shadow:0 8px 20px rgba(23,86,169,.2)}.button-primary:hover{background:#11498f;color:#fff}.button-secondary{background:var(--repo-navy);color:#fff}.button-full{width:100%}
.notice{margin:0 0 18px;padding:12px 15px;border:1px solid;border-radius:10px;font-size:13px;font-weight:650}.notice-success{color:#17633b;background:#effbf4;border-color:#ccebd9}.notice-error{color:#9c2f2f;background:#fff5f5;border-color:#f1cccc}
.repo-metrics{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin-bottom:18px}.metric-card{min-height:82px;padding:15px 16px;display:flex;align-items:center;gap:12px;background:#fff;border:1px solid var(--repo-line);border-radius:14px;box-shadow:0 3px 12px rgba(22,43,75,.025)}.metric-icon{width:38px;height:38px;flex:0 0 38px;border-radius:11px;background:#edf4fd;position:relative}.metric-icon::before,.metric-icon::after{content:"";position:absolute}.metric-icon-library::before,.metric-icon-archive::before{inset:10px 11px;border:2px solid #3371b9;border-radius:2px}.metric-icon-library::after{left:15px;top:7px;width:8px;height:4px;border:2px solid #3371b9;border-bottom:0;border-radius:2px 2px 0 0}.metric-icon-check::before{left:10px;top:11px;width:15px;height:8px;border-left:2px solid #24905a;border-bottom:2px solid #24905a;transform:rotate(-45deg)}.metric-icon-review::before{left:11px;top:8px;width:14px;height:19px;border:2px solid #b47b0c;border-radius:2px}.metric-icon-review::after{left:14px;top:13px;width:8px;height:2px;background:#b47b0c;box-shadow:0 5px 0 #b47b0c}.metric-icon-alert::before{left:17px;top:9px;width:3px;height:13px;border-radius:2px;background:#c74646}.metric-icon-alert::after{left:17px;bottom:8px;width:3px;height:3px;border-radius:50%;background:#c74646}.metric-icon-archive::after{left:12px;top:16px;width:14px;height:2px;background:#3371b9}.metric-card strong{display:block;font-size:22px;line-height:1;color:#102c52}.metric-card span:last-child{display:block;margin-top:6px;color:var(--repo-muted);font-size:11px;font-weight:650}
.repo-panel{overflow:hidden;margin-bottom:18px;background:#fff;border:1px solid var(--repo-line);border-radius:15px;box-shadow:0 5px 20px rgba(15,39,72,.035)}.panel-heading{min-height:68px;padding:15px 18px;display:flex;align-items:center;justify-content:space-between;gap:18px;border-bottom:1px solid var(--repo-line)}.panel-heading h2{margin:0;font-size:16px;letter-spacing:-.01em;color:#102744}.panel-heading>div>span{display:block;margin-top:3px;color:var(--repo-muted);font-size:11px}
.repo-search{width:min(360px,55%);height:39px;padding:0 11px;display:flex;align-items:center;gap:8px;background:var(--repo-canvas);border:1px solid #dbe4ef;border-radius:9px}.repo-search svg{width:16px;flex:0 0 16px;fill:none;stroke:#73839a;stroke-width:2;stroke-linecap:round}.repo-search input{min-width:0;flex:1;border:0;outline:0;background:transparent;color:var(--repo-ink);font:inherit;font-size:12px}.repo-search a{color:var(--repo-blue);font-size:11px;font-weight:700;text-decoration:none}
.resource-row{min-height:100px;padding:16px 18px;display:grid;grid-template-columns:44px minmax(0,1fr) auto;align-items:center;gap:14px;border-bottom:1px solid #edf1f6;transition:background .15s ease}.resource-row:last-child{border-bottom:0}.resource-row:hover{background:#fbfcfe}.file-mark{width:44px;height:48px;display:grid;place-items:center;background:#eef4fb;border:1px solid #d8e4f1;border-radius:10px;color:#255f9f;font-size:9px;font-weight:850;letter-spacing:.04em}.resource-copy{min-width:0}.resource-meta{display:flex;align-items:center;gap:7px;color:#718096;font-size:9px;font-weight:800;letter-spacing:.06em;text-transform:uppercase}.resource-meta i{width:3px;height:3px;border-radius:50%;background:#b6c1ce}.resource-copy h3{margin:5px 0 6px;overflow:hidden;color:#132945;font-size:14px;font-weight:750;text-overflow:ellipsis;white-space:nowrap}.resource-copy p{margin:0;display:flex;gap:14px;color:#78869a;font-size:11px}.resource-copy p span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.resource-controls{display:flex;align-items:center;gap:15px}.status-pill{padding:6px 9px;display:inline-flex;align-items:center;gap:6px;border-radius:999px;font-size:10px;font-weight:750;white-space:nowrap}.status-pill i{width:6px;height:6px;border-radius:50%;background:currentColor}.status-active{color:#157044;background:#e8f8ef}.status-review{color:#94620a;background:#fff5d9}.status-inactive{color:#66758a;background:#edf1f5}.row-actions{display:flex;align-items:center;gap:8px}.row-actions form{margin:0}.text-button,.icon-button{border:0;background:transparent;cursor:pointer}.text-button{padding:7px 2px;color:#617086;font-size:11px;font-weight:750}.text-button-primary{color:var(--repo-blue)}.icon-button{width:30px;height:30px;padding:6px;display:grid;place-items:center;border-radius:7px;color:#7b889a}.icon-button:hover{background:#f1f4f8}.icon-button.danger:hover{color:#b73737;background:#fff0f0}.icon-button svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.empty-state{padding:58px 20px;text-align:center}.empty-icon{width:50px;height:50px;margin:0 auto 13px;display:grid;place-items:center;border-radius:14px;background:#eef4fb;color:#2f69aa}.empty-icon svg{width:25px;fill:none;stroke:currentColor;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}.empty-state h3{margin:0;color:#18304f;font-size:15px}.empty-state p{margin:6px 0 16px;color:var(--repo-muted);font-size:12px}.repo-pagination{padding:14px 18px;border-top:1px solid var(--repo-line)}
.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse}th{padding:11px 14px;background:#f8fafc;color:#748297;font-size:9px;letter-spacing:.07em;text-align:left;text-transform:uppercase;white-space:nowrap}td{padding:13px 14px;border-top:1px solid #edf1f6;color:#40516a;font-size:11px;white-space:nowrap}td:first-child{max-width:260px}td strong,td span{display:block}td strong{overflow:hidden;color:#253b59;font-size:11px;text-overflow:ellipsis}td>span{margin-top:3px;color:#8995a5;font-size:9px}.format-chip{display:inline-block!important;margin:0!important;padding:4px 6px;border-radius:5px;background:#eef4fb;color:#2e67a9;font-weight:800}.history-status{display:inline-block!important;margin:0!important;color:#365a88;font-weight:700}.table-empty{text-align:center!important;color:#7b899c!important;padding:30px!important}
@media(max-width:1120px){.repo-metrics{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:720px){.repo-page{padding:20px 14px 38px}.repo-header{align-items:flex-end}.repo-header h1{font-size:25px}.repo-header p{display:none}.repo-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.panel-heading{align-items:stretch;flex-direction:column}.repo-search{width:100%}.resource-row{grid-template-columns:40px minmax(0,1fr)}.file-mark{width:40px;height:44px}.resource-controls{grid-column:1/-1;justify-content:space-between;padding-left:54px}.resource-copy p span:first-child{max-width:190px}.import-panel .panel-heading{min-height:auto}}
@media(max-width:460px){.repo-header{display:block}.repo-header .button{width:100%;margin-top:15px}.repo-metrics{gap:8px}.metric-card{min-height:72px;padding:11px}.metric-icon{width:34px;height:34px;flex-basis:34px}.resource-row{padding:14px}.resource-controls{padding-left:0}.resource-copy p{display:block}.resource-copy p span{display:block;margin-top:3px}.row-actions{gap:4px}}
</style>
@endsection
