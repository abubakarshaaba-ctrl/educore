@extends('layouts.app')
@section('title', 'Exam Timetables')
@section('page-title', 'Exam Timetables')

@section('content')
<div class="page-header">
    <div class="page-title">Exam Timetables &amp; Supervision</div>
    <div class="page-header-actions">
        <a href="{{ route('exams.create') }}" class="btn btn-primary">+ New Exam Period</a>
    </div>
</div>

@if(session('success'))<div class="alert-success" style="margin-bottom:16px">{{ session('success') }}</div>@endif

<div class="card">
    <div class="ch">Exam Periods</div>
    <div class="cb" style="padding:0">
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:#F8FAFC">
                    <th style="padding:10px 16px;text-align:left;font-size:11px;color:var(--slate);text-transform:uppercase">Title</th>
                    <th style="padding:10px 16px;text-align:left;font-size:11px;color:var(--slate);text-transform:uppercase">Term</th>
                    <th style="padding:10px 16px;text-align:left;font-size:11px;color:var(--slate);text-transform:uppercase">Dates</th>
                    <th style="padding:10px 16px;text-align:left;font-size:11px;color:var(--slate);text-transform:uppercase">Status</th>
                    <th style="padding:10px 16px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($periods as $p)
                <tr style="border-top:1px solid var(--border)">
                    <td style="padding:12px 16px;font-weight:700;color:var(--midnight)">{{ $p->title }}</td>
                    <td style="padding:12px 16px">{{ optional($p->term)->name }}</td>
                    <td style="padding:12px 16px">{{ $p->start_date->format('d M') }} – {{ $p->end_date->format('d M Y') }}</td>
                    <td style="padding:12px 16px">
                        <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;
                            background:{{ $p->status === 'published' ? '#ECFDF5' : ($p->status === 'draft' ? '#F1F5F9' : '#FFFBEB') }};
                            color:{{ $p->status === 'published' ? '#047857' : ($p->status === 'draft' ? '#475569' : '#B45309') }}">
                            {{ ucfirst(str_replace('_',' ',$p->status)) }}
                        </span>
                    </td>
                    <td style="padding:12px 16px;text-align:right">
                        <a href="{{ route('exams.show', $p) }}" class="btn btn-ghost btn-sm">Open</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" style="padding:40px;text-align:center;color:var(--slate-light)">No exam periods yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $periods->links() }}
@endsection
