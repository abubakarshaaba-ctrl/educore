@extends('layouts.portal')
@section('title','CBT Exams')
@section('content')
<h2 style="font-size:17px;font-weight:800;margin-bottom:18px">📝 CBT Exams</h2>
<div class="card">
    <div class="ch">Available Exams</div>
    <div style="overflow-x:auto">
    <table>
        <thead><tr><th>Exam</th><th>Subject</th><th>Date</th><th>Duration</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        @forelse($exams as $exam)
        @php $sess = $sessions->get($exam->id); @endphp
        <tr>
            <td style="font-weight:600">{{ $exam->title }}</td>
            <td>{{ optional(optional($exam->questionBank)->subject)->name ?? 'Mixed' }}</td>
            <td>
                @if($exam->scheduled_start)
                    {{ $exam->scheduled_start->format('d M Y, g:ia') }}
                    @if($exam->scheduled_end) - {{ $exam->scheduled_end->format('g:ia') }} @endif
                @else
                    —
                @endif
            </td>
            <td>{{ $exam->duration_minutes }} mins</td>
            <td>
                @if($sess)
                    @if($sess->status === 'graded')
                        @php $pct = $sess->display_percentage ?? 0; @endphp
                        <span class="badge b-g">{{ $pct }}% — Graded</span>
                    @elseif($sess->status === 'submitted')
                        <span class="badge b-a">Submitted</span>
                    @else
                        <span class="badge b-b">In Progress</span>
                    @endif
                @else
                    <span class="badge b-s">Not Started</span>
                @endif
            </td>
            <td>
                @if(!$sess)
                    <a href="{{ route('cbt.exams.start', $exam) }}" class="btn btn-primary" style="padding:5px 12px;font-size:12px">Start Exam</a>
                @elseif($sess->isFinal())
                    <span style="font-size:12px;color:var(--muted)">Completed</span>
                @else
                    <a href="{{ route('cbt.exams.start', $exam) }}" class="btn btn-ghost" style="padding:5px 12px;font-size:12px">Continue</a>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="empty">No exams available right now.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection
