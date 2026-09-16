@extends('layouts.app')
@section('title', 'Transcripts')
@section('page-title', 'Student Transcripts')

@php
    $tenantId = auth()->user()->tenant_id;
    $query = trim((string) request('q', $query ?? ''));
    $graduationYear = request('graduation_year');
    $classArmId = request('class_arm_id');

    $graduationYears = \App\Models\Student::where('tenant_id', $tenantId)
        ->whereNotNull('graduation_date')
        ->selectRaw('YEAR(graduation_date) as graduation_year')
        ->distinct()
        ->orderByDesc('graduation_year')
        ->pluck('graduation_year')
        ->filter();

    $classArms = \App\Models\ClassArm::where('tenant_id', $tenantId)
        ->with('classLevel')
        ->get()
        ->sortBy(fn ($arm) => sprintf('%05d-%s', $arm->classLevel->order_index ?? 9999, $arm->name));

    $hasFilters = $query !== '' || filled($graduationYear) || filled($classArmId);

    if ($hasFilters) {
        $studentQuery = \App\Models\Student::where('tenant_id', $tenantId)
            ->with(['currentClassArm.classLevel', 'enrollments.classArm.classLevel']);

        if ($query !== '') {
            $studentQuery->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('middle_name', 'like', "%{$query}%")
                    ->orWhere('admission_number', 'like', "%{$query}%");
            });
        }

        if (filled($graduationYear)) {
            $studentQuery->whereNotNull('graduation_date')
                ->whereYear('graduation_date', (int) $graduationYear);
        }

        if (filled($classArmId)) {
            $studentQuery->where(function ($q) use ($classArmId) {
                $q->where('current_class_arm_id', (int) $classArmId)
                    ->orWhereHas('enrollments', fn ($enrolment) =>
                        $enrolment->where('class_arm_id', (int) $classArmId)
                    );
            });
        }

        $students = $studentQuery
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(50)
            ->get();
    } else {
        $students = null;
    }
@endphp

@push('styles')
<style>
.search-card{background:white;border:1px solid var(--border);border-radius:12px;padding:28px;box-shadow:0 1px 4px rgba(0,0,0,.05)}
.pg-split{display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:20px;align-items:start}
.transcript-filters{display:grid;grid-template-columns:minmax(240px,1.6fr) minmax(150px,.7fr) minmax(190px,.9fr) auto auto;gap:10px;align-items:end}
.filter-field{min-width:0}
.filter-label{display:block;font-size:10px;font-weight:800;color:var(--slate-light);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px}
.fc{width:100%;min-width:0;padding:10px 14px;min-height:42px;font-size:14px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms}
.fc:focus{border-color:var(--indigo);background:white;box-shadow:0 0 0 3px rgba(194,133,12,.12)}
.filter-btn{min-height:42px;padding:10px 20px;border:none;border-radius:8px;font-size:13px;font-weight:700;font-family:inherit;cursor:pointer;white-space:nowrap;text-decoration:none;display:inline-flex;align-items:center;justify-content:center}
.filter-btn-primary{background:var(--indigo);color:white}
.filter-btn-clear{background:#F8FAFC;color:var(--midnight);border:1px solid var(--border)}
.result-row{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 16px;border-bottom:1px solid var(--border);transition:background 100ms}
.result-row:last-child{border-bottom:none}
.result-row:hover{background:#F8FAFC}
.result-meta{font-size:11px;color:var(--slate-light);margin-top:3px;line-height:1.55}
.badge{display:inline-flex;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;background:var(--indigo-bg);color:var(--indigo)}
.active-filters{display:flex;gap:6px;flex-wrap:wrap;margin-top:12px}
.filter-chip{display:inline-flex;align-items:center;padding:4px 9px;border-radius:20px;background:#F8FAFC;border:1px solid var(--border);font-size:10px;font-weight:700;color:var(--slate)}
@media(max-width:1100px){.transcript-filters{grid-template-columns:1fr 1fr}.filter-search{grid-column:1/-1}.filter-actions{grid-column:auto}.filter-btn{width:100%}}
@media(max-width:900px){.pg-split{grid-template-columns:1fr}}
@media(max-width:640px){.search-card{padding:20px 16px}.transcript-filters{grid-template-columns:1fr;gap:12px}.filter-search,.filter-actions{grid-column:auto}.filter-btn{width:100%;min-height:44px}.result-row{align-items:flex-start;flex-direction:column}.result-row>a{width:100%;text-align:center}}
</style>
@endpush

@section('content')
<div class="pg-split">
<div class="search-card">
    <div style="font-size:18px;font-weight:800;color:var(--midnight);margin-bottom:6px;letter-spacing:-.02em">
        📑 Student Transcripts
    </div>
    <div style="font-size:13px;color:var(--slate);margin-bottom:22px;line-height:1.6">
        Official cumulative academic records. Search by student, graduation year, class arm, or any combination of filters.
        <br><span style="color:var(--crimson);font-size:11px;font-weight:600">🔒 Restricted — Admin, Principal &amp; Vice Principal only</span>
    </div>

    <form method="GET" action="{{ route('students.transcript.search') }}" class="transcript-filters">
        <div class="filter-field filter-search">
            <label class="filter-label" for="transcript-q">Student</label>
            <input id="transcript-q" type="text" name="q" class="fc" value="{{ $query }}"
                   placeholder="Name or admission number...">
        </div>

        <div class="filter-field">
            <label class="filter-label" for="graduation-year">Graduation Year</label>
            <select id="graduation-year" name="graduation_year" class="fc">
                <option value="">All years</option>
                @foreach($graduationYears as $year)
                    <option value="{{ $year }}" {{ (string) $graduationYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label class="filter-label" for="class-arm">Class Arm</label>
            <select id="class-arm" name="class_arm_id" class="fc">
                <option value="">All class arms</option>
                @foreach($classArms as $arm)
                    <option value="{{ $arm->id }}" {{ (string) $classArmId === (string) $arm->id ? 'selected' : '' }}>
                        {{ $arm->classLevel->name ?? 'Class' }} {{ $arm->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="filter-btn filter-btn-primary">Search</button>
        @if($hasFilters)
            <a href="{{ route('students.transcript.search') }}" class="filter-btn filter-btn-clear">Clear</a>
        @endif
    </form>

    @if($hasFilters)
        <div class="active-filters">
            @if($query !== '')<span class="filter-chip">Student: {{ $query }}</span>@endif
            @if(filled($graduationYear))<span class="filter-chip">Graduation: {{ $graduationYear }}</span>@endif
            @if(filled($classArmId) && ($selectedArm = $classArms->firstWhere('id', (int) $classArmId)))
                <span class="filter-chip">Class: {{ $selectedArm->classLevel->name ?? 'Class' }} {{ $selectedArm->name }}</span>
            @endif
        </div>
    @endif

    @if($students !== null)
        <div style="margin-top:20px;border:1px solid var(--border);border-radius:10px;overflow:hidden">
            @forelse($students as $s)
                @php
                    $matchedArm = null;
                    if (filled($classArmId)) {
                        if ((int) $s->current_class_arm_id === (int) $classArmId) {
                            $matchedArm = $s->currentClassArm;
                        } else {
                            $matchedArm = optional($s->enrollments->firstWhere('class_arm_id', (int) $classArmId))->classArm;
                        }
                    }
                    $displayArm = $matchedArm ?: $s->currentClassArm ?: optional($s->enrollments->sortByDesc('id')->first())->classArm;
                @endphp
                <div class="result-row">
                    <div style="min-width:0">
                        <div style="font-size:13px;font-weight:700;color:var(--midnight)">{{ $s->full_name ?? $s->name }}</div>
                        <div class="result-meta">
                            {{ $s->admission_number ?? '—' }}
                            @if($displayArm)
                                · {{ $displayArm->classLevel->name ?? '' }} {{ $displayArm->name }}
                            @endif
                            @if($s->graduation_date)
                                · Graduated {{ $s->graduation_date->format('Y') }}
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('students.transcript', $s) }}"
                       style="padding:7px 14px;background:var(--indigo);color:white;border-radius:7px;font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap">
                        View →
                    </a>
                </div>
            @empty
                <div style="padding:24px;text-align:center;color:var(--slate-light);font-size:13px">
                    No students match the selected transcript filters.
                </div>
            @endforelse
        </div>
        @if($students->count() === 50)
            <div style="font-size:11px;color:var(--slate-light);margin-top:8px">Showing the first 50 matches. Refine the filters to narrow the results.</div>
        @endif
    @endif
</div>

<div>
    <div class="card" style="position:sticky;top:calc(var(--header-h) + 16px)">
        <div class="ch">About Transcripts</div>
        <div class="cb" style="font-size:13px;color:var(--slate);line-height:1.7">
            <p style="margin-bottom:10px">Transcripts show a student's cumulative academic record across all terms and sessions.</p>
            <p style="margin-bottom:10px">Use <strong style="color:var(--midnight)">Graduation Year</strong> and <strong style="color:var(--midnight)">Class Arm</strong> to locate cohorts, including historical enrolment records.</p>
            <p style="margin-bottom:10px">You can <strong style="color:var(--midnight)">download a PDF</strong> from the transcript view page.</p>
            <p style="color:#DC2626;font-size:12px;font-weight:600">🔒 Restricted to Admin, Principal, and Vice Principal only.</p>
        </div>
    </div>
</div>
</div>
@endsection
