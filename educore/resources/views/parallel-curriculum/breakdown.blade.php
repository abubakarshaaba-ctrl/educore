@extends('layouts.app')
@section('title','Composite Result Breakdown')
@section('page-title','Parallel Curriculum')

@push('styles')
<style>
.wrap{max-width:920px;margin:0 auto}.card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}.head{padding:13px 16px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:12px;font-weight:800;color:var(--midnight)}.body{padding:16px}.hero{padding:17px;border-radius:12px;background:linear-gradient(135deg,#071E45,#0B2D63);color:#fff;margin-bottom:14px}.hero h2{font-size:18px;margin:0 0 4px}.hero p{margin:0;color:#DCE5F2;font-size:11px}.stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.stat{padding:11px;border:1px solid var(--border);border-radius:9px}.stat span{display:block;font-size:9.5px;color:var(--slate-light);text-transform:uppercase;font-weight:800}.stat strong{display:block;margin-top:3px;font-size:15px;color:var(--midnight)}.row{display:grid;grid-template-columns:1.5fr .7fr .7fr;gap:10px;padding:9px 0;border-bottom:1px solid #EEF2F7;align-items:center;font-size:11px}.row:last-child{border-bottom:0}.badge{display:inline-flex;padding:3px 7px;border-radius:999px;font-size:9px;font-weight:800}.ok{background:#ECFDF3;color:#067647}.wait{background:#FFFAEB;color:#B54708}.btn{display:inline-flex;padding:9px 14px;border:1px solid var(--border);border-radius:8px;background:#fff;color:var(--midnight);text-decoration:none;font-size:11px;font-weight:800}.message{padding:10px 12px;border-radius:8px;background:#F8FAFC;color:var(--slate);font-size:10.5px;line-height:1.5;margin-top:12px}
@media(max-width:640px){.stats{grid-template-columns:1fr}.row{grid-template-columns:1fr auto}.row>div:nth-child(2){display:none}.body{padding:13px}}
</style>
@endpush

@push('styles')
@include('parallel-curriculum.partials.global-ui')
@endpush

@section('content')
<div class="wrap pc-breakdown">
    <a class="btn" href="{{ route('parallel-curriculum.index') }}">← Back to Parallel Curriculum</a>

    <div class="hero" style="margin-top:12px">
        <h2>{{ $composite->student?->full_name }}</h2>
        <p>{{ $composite->curriculum?->name }} · {{ $composite->curriculumClass?->name }} → {{ $composite->destinationSubject?->name ?: 'No destination subject' }} · {{ $composite->term?->name }}</p>
    </div>

    <div class="card">
        <div class="body">
            <div class="stats">
                <div class="stat"><span>Composite average</span><strong>{{ $composite->average_score === null ? '—' : number_format($composite->average_score,2).'%' }}</strong></div>
                <div class="stat"><span>Completed subjects</span><strong>{{ $composite->completed_subject_count }}/{{ $composite->subject_count }}</strong></div>
                <div class="stat"><span>Sync status</span><strong>{{ ucfirst($composite->sync_status) }}</strong></div>
            </div>
            <div class="message">{{ $composite->sync_message }}</div>
        </div>
    </div>

    <div class="card">
        <div class="head">Parallel subject breakdown</div>
        <div class="body">
            @forelse(($composite->subject_breakdown ?? []) as $subject)
                <div class="row">
                    <div><strong style="color:var(--midnight)">{{ $subject['subject_name'] ?? 'Subject' }}</strong><div style="font-size:9.5px;color:var(--slate-light);margin-top:3px">@foreach(($subject['components'] ?? []) as $component){{ $component['name'] }}: {{ $component['score'] ?? '—' }}/{{ $component['weight'] }}@if(!$loop->last) · @endif @endforeach</div></div>
                    <div>{{ isset($subject['percentage']) && $subject['percentage'] !== null ? number_format($subject['percentage'],2).'%' : '—' }}</div>
                    <div><span class="badge {{ !empty($subject['complete']) ? 'ok' : 'wait' }}">{{ !empty($subject['complete']) ? 'Complete' : 'Pending' }}</span></div>
                </div>
            @empty
                <div style="padding:20px;text-align:center;color:var(--slate-light);font-size:11px">No subject breakdown has been computed yet.</div>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="head">Conventional score distribution</div>
        <div class="body">
            @forelse($distribution as $score)
                <div class="row">
                    <div><strong style="color:var(--midnight)">{{ $score->assessmentType?->name ?: 'Assessment' }}</strong></div>
                    <div>Weight {{ number_format($score->assessmentType?->weight_percentage ?? 0,0) }}%</div>
                    <div><strong>{{ number_format($score->score,2) }}</strong> 🔒</div>
                </div>
            @empty
                <div style="padding:20px;text-align:center;color:var(--slate-light);font-size:11px">No conventional score rows have been synchronized for this composite.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
