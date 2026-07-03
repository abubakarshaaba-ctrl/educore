@extends('layouts.app')
@section('title', 'Transcripts')
@section('page-title', 'Student Transcripts')

@push('styles')
<style>
.search-card{background:white;border:1px solid var(--border);border-radius:12px;padding:28px;box-shadow:0 1px 4px rgba(0,0,0,.05)}
.pg-split{display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start}
@media(max-width:900px){.pg-split{grid-template-columns:1fr}}
.fc{width:100%;padding:10px 14px;font-size:14px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms}
.fc:focus{border-color:var(--indigo);background:white;box-shadow:0 0 0 3px rgba(194,133,12,.12)}
.result-row{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--border);transition:background 100ms}
.result-row:last-child{border-bottom:none}
.result-row:hover{background:#F8FAFC}
.badge{display:inline-flex;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;background:var(--indigo-bg);color:var(--indigo)}
</style>
@endpush

@section('content')
<div class="pg-split">
<div class="search-card">
    <div style="font-size:18px;font-weight:800;color:var(--midnight);margin-bottom:6px;letter-spacing:-.02em">
        📑 Student Transcripts
    </div>
    <div style="font-size:13px;color:var(--slate);margin-bottom:22px;line-height:1.6">
        Official cumulative academic records. Search for a student to view or download their transcript.
        <br><span style="color:var(--crimson);font-size:11px;font-weight:600">🔒 Restricted — Admin, Principal & Vice Principal only</span>
    </div>

    <form method="GET" action="{{ route('students.transcript.search') }}">
        <div style="display:flex;gap:8px">
            <input type="text" name="q" class="fc" value="{{ $query ?? '' }}"
                   placeholder="Search by student name or admission number..." autofocus>
            <button type="submit" style="padding:10px 20px;background:var(--indigo);color:white;border:none;border-radius:8px;font-size:13px;font-weight:700;font-family:inherit;cursor:pointer;white-space:nowrap">
                Search
            </button>
        </div>
    </form>

    @if(isset($students))
        <div style="margin-top:20px;border:1px solid var(--border);border-radius:10px;overflow:hidden">
            @forelse($students as $s)
            <div class="result-row">
                <div>
                    <div style="font-size:13px;font-weight:700;color:var(--midnight)">{{ $s->full_name ?? $s->name }}</div>
                    <div style="font-size:11px;color:var(--slate-light);margin-top:2px">
                        {{ $s->admission_number ?? '—' }}
                        @if($s->currentClassArm)
                         · {{ $s->currentClassArm->classLevel->name }} {{ $s->currentClassArm->name }}
                        @endif
                    </div>
                </div>
                <a href="{{ route('students.transcript', $s) }}"
                   style="padding:6px 14px;background:var(--indigo);color:white;border-radius:7px;font-size:12px;font-weight:700;text-decoration:none">
                    View →
                </a>
            </div>
            @empty
            <div style="padding:24px;text-align:center;color:var(--slate-light);font-size:13px">
                No students found for "{{ $query }}"
            </div>
            @endforelse
        </div>
    @endif
</div>

<div>
    <div class="card" style="position:sticky;top:calc(var(--header-h) + 16px)">
        <div class="ch">About Transcripts</div>
        <div class="cb" style="font-size:13px;color:var(--slate);line-height:1.7">
            <p style="margin-bottom:10px">Transcripts show a student's cumulative academic record across all terms and sessions.</p>
            <p style="margin-bottom:10px">You can <strong style="color:var(--midnight)">download a PDF</strong> from the transcript view page.</p>
            <p style="color:#DC2626;font-size:12px;font-weight:600">🔒 Restricted to Admin, Principal, and Vice Principal only.</p>
        </div>
    </div>
</div>
</div>
@endsection
