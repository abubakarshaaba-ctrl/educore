@extends('layouts.app')
@section('title','Application Documents')
@section('page-title','Application Documents')
@push('styles')
<style>
.docs-card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;min-width:0}
.docs-head{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;overflow-wrap:anywhere}
.docs-body{padding:20px}
.doc-row{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 16px;border:1px solid var(--border);border-radius:10px;margin-bottom:10px;min-width:0}
.doc-main{display:flex;align-items:center;gap:12px;min-width:0;flex:1}
.doc-copy{min-width:0}
.doc-name{font-size:13px;font-weight:600;color:var(--midnight);overflow-wrap:anywhere}
.doc-file{font-size:11px;color:var(--slate-light);overflow-wrap:anywhere;word-break:break-word}
.doc-view{display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:7px 14px;font-size:12px;font-weight:600;background:var(--indigo);color:white;border-radius:8px;text-decoration:none;white-space:nowrap;flex-shrink:0}
@media(max-width:640px){
    .docs-body{padding:14px}
    .doc-row{align-items:flex-start;flex-direction:column;padding:12px}
    .doc-main{width:100%}
    .doc-view{width:100%;min-height:40px}
}
</style>
@endpush
@section('content')
<a href="{{ route('admissions.show',$admission) }}" style="font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px">← Back to Application</a>
<div class="docs-card">
    <div class="docs-head">
        Documents for {{ $admission->first_name }} {{ $admission->last_name }} — {{ $admission->application_number }}
    </div>
    <div class="docs-body">
        @forelse($docs as $doc)
        <div class="doc-row">
            <div class="doc-main">
                <div style="font-size:28px;flex-shrink:0">&#128196;</div>
                <div class="doc-copy">
                    <div class="doc-name">{{ ucwords(str_replace('_',' ',$doc->document_type)) }}</div>
                    <div class="doc-file">{{ $doc->original_name }}</div>
                </div>
            </div>
            <a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank" class="doc-view">
                &#128065; View
            </a>
        </div>
        @empty
        <div style="text-align:center;padding:40px 16px;color:var(--slate-light)">No documents uploaded for this application.</div>
        @endforelse
    </div>
</div>
@endsection
