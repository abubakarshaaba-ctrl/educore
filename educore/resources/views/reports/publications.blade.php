@extends('layouts.app')
@section('title','Report Card Publications')
@section('page-title','Report Cards')

@push('styles')
<style>
    .page-tabs{display:flex;gap:6px;padding:5px;margin-bottom:20px;background:#fff;border:1px solid var(--border);border-radius:12px;flex-wrap:wrap}.page-tab{padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;color:var(--slate);text-decoration:none;white-space:nowrap}.page-tab:hover{background:#F1F5F9;color:var(--midnight)}.page-tab.active{background:var(--indigo);color:#fff}
    .alert-success,.alert-error{margin-bottom:16px;padding:12px 15px;border-radius:9px;font-size:13px}.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;color:#047857}.alert-error{background:#FEF2F2;border:1px solid #FECACA;color:#B91C1C}
    .term-selector{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px;padding:15px 18px;background:#fff;border:1px solid var(--border);border-radius:12px}.term-selector label{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}.term-selector select{min-width:240px;max-width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;font:inherit;font-size:13px;outline:none}.term-selector select:focus{border-color:var(--indigo)}.bulk-actions{display:flex;gap:8px;margin-left:auto;flex-wrap:wrap}.bulk-actions form{margin:0}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:8px 13px;border:0;border-radius:8px;font:inherit;font-size:12px;font-weight:700;text-decoration:none;cursor:pointer;min-height:38px}.btn-publish{background:#059669;color:#fff}.btn-unpublish{background:#F1F5F9;color:#475569;border:1px solid var(--border)}
    .stats-row{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:18px}.stat-pill{padding:9px 13px;border-radius:10px;font-size:12px;font-weight:700;text-align:center;min-width:0}.stat-pill-green{background:#ECFDF5;color:#047857}.stat-pill-amber{background:#FFFBEB;color:#B45309}.stat-pill-slate{background:#F1F5F9;color:#475569}
    .term-section{margin-bottom:28px}.term-title{margin-bottom:13px;padding-bottom:9px;border-bottom:1px solid var(--border);font-size:16px;font-weight:750;color:var(--midnight)}
    .pub-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px}.pub-card{padding:16px;background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:0 3px 12px rgba(15,23,42,.04);min-width:0}.pub-card.published{border-top:3px solid #059669}.pub-card.draft{border-top:3px solid #D97706}.pub-card.archived{border-top:3px solid #94A3B8}.pub-title{margin-bottom:4px;font-size:14px;font-weight:750;color:var(--midnight)}.pub-meta{margin-bottom:12px;font-size:11px;color:var(--slate);line-height:1.5}.status-badge{display:inline-flex;padding:3px 8px;border-radius:999px;font-size:10px;font-weight:700}.s-published{background:#ECFDF5;color:#047857}.s-draft{background:#FFFBEB;color:#B45309}.s-archived{background:#F1F5F9;color:#475569}.pub-actions{display:flex;gap:6px;align-items:stretch}.pub-actions>*{min-width:0}
    @media(max-width:768px){.page-tabs{flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch}.term-selector{align-items:stretch}.bulk-actions{width:100%;margin-left:0}.bulk-actions form{flex:1 1 180px}.bulk-actions .btn{width:100%}}
    @media(max-width:640px){.term-selector{padding:12px}.term-selector label{width:100%}.term-selector select{width:100%;min-width:0}.stats-row{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.stat-pill{font-size:10.5px;padding:8px}.pub-grid{grid-template-columns:1fr}.pub-card{padding:13px}.pub-actions{display:grid;grid-template-columns:1fr 1fr}.pub-actions .btn,.pub-actions form,.pub-actions form .btn{width:100%}.term-title{font-size:14px}}
    @media(max-width:380px){.bulk-actions{display:grid;grid-template-columns:1fr}.bulk-actions form{width:100%}.pub-actions{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="page-tabs">
    <a href="{{ route('reports.index') }}" class="page-tab">Generate</a>
    <a href="{{ route('reports.publications') }}" class="page-tab active">Publish / Unpublish</a>
    <a href="{{ route('reports.remarks') }}" class="page-tab">Remarks</a>
</div>

@if(session('success'))<div class="alert-success">✓ {{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

<div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:10px;padding:12px 16px;font-size:13px;color:#1D4ED8;margin-bottom:20px">
    📢 <strong>Publishing</strong> makes report cards visible to parents via the parent portal. Computed report cards must exist before publishing.
</div>

@php $selectedTermId = request('term_id', optional($terms->firstWhere('is_current', true))->id ?? optional($terms->first())->id); @endphp

<div class="term-selector">
    <label for="termSelect">📅 Select Term</label>
    <select id="termSelect" onchange="window.location.href='{{ route('reports.publications') }}?term_id='+this.value">
        @foreach($terms as $t)
        <option value="{{ $t->id }}" {{ $t->id == $selectedTermId ? 'selected' : '' }}>
            {{ $t->name }} — {{ optional($t->session)->name }}
        </option>
        @endforeach
    </select>

    @php
        $selectedTerm = $terms->firstWhere('id', $selectedTermId);
        $publishedCount = $classArms->filter(fn($arm) =>
            optional($pubs->get($arm->id . '_' . $selectedTermId))->status === 'published'
        )->count();
        $draftCount = $classArms->count() - $publishedCount;
    @endphp

    <div class="bulk-actions">
        <form method="POST" action="{{ route('reports.publish') }}" onsubmit="return confirm('Publish ALL computed classes for this term?')">
            @csrf
            <input type="hidden" name="term_id" value="{{ $selectedTermId }}">
            <input type="hidden" name="bulk" value="1">
            @foreach($classArms as $arm)
            @php $ck = $arm->id . '_' . $selectedTermId; $cc = optional($computed->get($ck))->count ?? 0; @endphp
            @if($cc > 0 && optional($pubs->get($ck))->status !== 'published')
            <input type="hidden" name="class_arm_ids[]" value="{{ $arm->id }}">
            @endif
            @endforeach
            <button type="submit" class="btn" style="background:#059669;color:white">📢 Publish All</button>
        </form>
        <form method="POST" action="{{ route('reports.unpublish') }}" onsubmit="return confirm('Unpublish ALL classes for this term?')">
            @csrf
            <input type="hidden" name="term_id" value="{{ $selectedTermId }}">
            <input type="hidden" name="bulk" value="1">
            @foreach($classArms as $arm)
            @if(optional($pubs->get($arm->id . '_' . $selectedTermId))->status === 'published')
            <input type="hidden" name="class_arm_ids[]" value="{{ $arm->id }}">
            @endif
            @endforeach
            <button type="submit" class="btn" style="background:#F1F5F9;color:#64748B;border:1px solid var(--border)">🔒 Unpublish All</button>
        </form>
    </div>
</div>

<div class="stats-row">
    <div class="stat-pill stat-pill-green">✅ {{ $publishedCount }} Published</div>
    <div class="stat-pill stat-pill-amber">📝 {{ $draftCount }} Draft / Unpublished</div>
    <div class="stat-pill stat-pill-slate">🏫 {{ $classArms->count() }} Total Classes</div>
</div>

@if($selectedTerm)
<div class="term-section">
    <div class="term-title">📋 {{ $selectedTerm->name }} — {{ optional($selectedTerm->session)->name }}</div>
    <div class="pub-grid">
        @foreach($classArms as $arm)
        @php
            $key = $arm->id . '_' . $selectedTermId;
            $pub = $pubs->get($key);
            $compCount = optional($computed->get($key))->count ?? 0;
            $status = $pub ? $pub->status : 'draft';
        @endphp
        <div class="pub-card {{ $status }}">
            <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px">
                <div style="min-width:0">
                    <div class="pub-title">{{ optional($arm->classLevel)->name }} {{ $arm->name }}</div>
                    <div class="pub-meta"><span class="status-badge s-{{ $status }}">{{ ucfirst($status) }}</span> · {{ $compCount }} report card{{ $compCount !== 1 ? 's' : '' }} computed</div>
                    @if($pub && $pub->published_at)
                    <div style="font-size:11px;color:var(--slate-light);overflow-wrap:anywhere">Published {{ $pub->published_at->diffForHumans() }} by {{ optional($pub->publishedBy)->name }}</div>
                    @endif
                </div>
            </div>

            @if($compCount > 0)
                @if($status !== 'published')
                <form method="POST" action="{{ route('reports.publish') }}" class="pub-actions">
                    @csrf
                    <input type="hidden" name="class_arm_id" value="{{ $arm->id }}">
                    <input type="hidden" name="term_id" value="{{ $selectedTermId }}">
                    <button type="submit" class="btn btn-publish">📢 Publish</button>
                    <a href="{{ route('reports.preview', ['class_arm_id'=>$arm->id,'term_id'=>$selectedTermId]) }}" class="btn" style="background:#EFF6FF;color:var(--indigo)">Preview</a>
                </form>
                @else
                <div class="pub-actions">
                    <form method="POST" action="{{ route('reports.unpublish') }}" style="flex:1">
                        @csrf
                        <input type="hidden" name="class_arm_id" value="{{ $arm->id }}">
                        <input type="hidden" name="term_id" value="{{ $selectedTermId }}">
                        <button type="submit" class="btn btn-unpublish" style="width:100%" onclick="return confirm('Unpublish {{ optional($arm->classLevel)->name }} {{ $arm->name }}?')">🔒 Unpublish</button>
                    </form>
                    <a href="{{ route('reports.preview', ['class_arm_id'=>$arm->id,'term_id'=>$selectedTermId]) }}" class="btn" style="background:#EFF6FF;color:var(--indigo)">Preview</a>
                </div>
                @endif
            @else
            <div style="font-size:12px;color:#D97706;margin-top:4px">⚠️ No report cards computed yet. <a href="{{ route('reports.index') }}" style="color:var(--indigo)">Compute first →</a></div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@else
<div style="text-align:center;padding:60px;color:var(--slate-light)">No terms found. Set up academic terms first.</div>
@endif
@endsection
