@extends('layouts.app')
@section('title','Application Documents')
@section('page-title','Application Documents')
@section('content')
<a href="{{ route('admissions.show',$admission) }}" style="font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px">← Back to Application</a>
<div style="background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden">
    <div style="padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700">
        Documents for {{ $admission->first_name }} {{ $admission->last_name }} — {{ $admission->application_number }}
    </div>
    <div style="padding:20px">
        @forelse($docs as $doc)
        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border:1px solid var(--border);border-radius:10px;margin-bottom:10px">
            <div style="display:flex;align-items:center;gap:12px">
                <div style="font-size:28px">&#128196;</div>
                <div>
                    <div style="font-size:13px;font-weight:600;color:var(--midnight)">{{ ucwords(str_replace('_',' ',$doc->document_type)) }}</div>
                    <div style="font-size:11px;color:var(--slate-light)">{{ $doc->original_name }}</div>
                </div>
            </div>
            <a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank" style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;font-size:12px;font-weight:600;background:var(--indigo);color:white;border-radius:8px;text-decoration:none">
                &#128065; View
            </a>
        </div>
        @empty
        <div style="text-align:center;padding:40px;color:var(--slate-light)">No documents uploaded for this application.</div>
        @endforelse
    </div>
</div>
@endsection
