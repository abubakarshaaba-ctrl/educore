@extends('layouts.app')

@section('title', 'Progression Decisions')
@section('page-title', 'Progression Decisions')

@push('styles')
<style>
.cycle-form,.cycle-panel{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:18px}
.cycle-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;align-items:end}
.cycle-grid select{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:10px}
.cycle-btn{border:0;border-radius:8px;padding:10px 14px;background:#0f3b6f;color:#fff;font-weight:800;cursor:pointer}
.cycle-table-wrap{overflow-x:auto}.cycle-table{width:100%;border-collapse:collapse;min-width:760px}.cycle-table th,.cycle-table td{padding:11px 12px;border-bottom:1px solid #e5e7eb;text-align:left}.cycle-table th{background:#f8fafc;color:#64748b;font-size:12px;text-transform:uppercase}.cycle-table select{border:1px solid #cbd5e1;border-radius:8px;padding:8px}
</style>
@endpush

@section('content')
<a href="{{ route('academic-cycle.index') }}" style="display:inline-flex;align-items:center;gap:6px;color:var(--indigo);font-size:13px;font-weight:600;text-decoration:none;margin-bottom:16px">&larr; Back to Academic Cycle</a>
    <form class="cycle-form" method="GET" action="{{ route('academic-cycle.promotion.index') }}">
        <div class="cycle-grid">
            <label>Term
                <select name="term_id" required>
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}" @selected($termId == $term->id)>{{ optional($term->session)->name }} - {{ $term->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Class arm
                <select name="class_arm_id" required>
                    <option value="">Select class arm</option>
                    @foreach($classArms as $arm)
                        <option value="{{ $arm->id }}" @selected($classArmId == $arm->id)>{{ $arm->full_name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="cycle-btn" type="submit">Load Students</button>
        </div>
    </form>

    @if($students->isNotEmpty())
        <form class="cycle-panel" method="POST" action="{{ route('academic-cycle.promotion.store') }}">
            @csrf
            <input type="hidden" name="term_id" value="{{ $termId }}">
            <div class="cycle-table-wrap">
                <table class="cycle-table">
                    <thead><tr><th>Student</th><th>Admission No.</th><th>Current Class</th><th>Decision</th></tr></thead>
                    <tbody>
                    @foreach($students as $student)
                        <tr>
                            <td>{{ $student->full_name }}</td>
                            <td>{{ $student->admission_number }}</td>
                            <td>{{ optional($student->currentClassArm)->full_name }}</td>
                            <td>
                                <select name="decisions[{{ $student->id }}]">
                                    @foreach($decisions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <p>Decisions are stored on existing termly summaries. Destination classes are resolved during rollover preview.</p>
            <button class="cycle-btn" type="submit">Save Decisions</button>
        </form>
    @elseif(request()->filled('class_arm_id'))
        <div class="cycle-panel">No active students found for this class arm.</div>
    @endif
@endsection
