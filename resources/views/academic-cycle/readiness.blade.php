@extends('layouts.app')

@section('title', 'Closure Readiness')
@section('page-title', 'Closure Readiness')

@push('styles')
<style>
.cycle-form,.cycle-panel{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:18px}
.cycle-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;align-items:end}
.cycle-grid select{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:10px}
.cycle-btn{border:0;border-radius:8px;padding:10px 14px;background:#0f3b6f;color:#fff;font-weight:800;cursor:pointer}
.cycle-alert{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin:12px 0}
.cycle-alert-danger{background:#fef2f2;border-color:#fecaca}.cycle-alert-warning{background:#fffbeb;border-color:#fde68a}
</style>
@endpush

@section('content')
    <form class="cycle-form" method="GET" action="{{ route('academic-cycle.readiness') }}">
        <div class="cycle-grid">
            <label>Session readiness
                <select name="session_id">
                    <option value="">Select session</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" @selected(request('session_id') == $session->id)>{{ $session->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Term readiness
                <select name="term_id">
                    <option value="">Select term</option>
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}" @selected(request('term_id') == $term->id)>{{ optional($term->session)->name }} - {{ $term->name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="cycle-btn" type="submit">Preview Readiness</button>
        </div>
    </form>

    @if($sessionDecision)
        <div class="cycle-panel">
            <h3>Session Closure Readiness</h3>
            @include('academic-cycle.partials.blockers', ['decision' => $sessionDecision])
        </div>
    @endif

    @if($termDecision)
        <div class="cycle-panel">
            <h3>Term Closure Readiness</h3>
            @include('academic-cycle.partials.blockers', ['decision' => $termDecision])
        </div>
    @endif
@endsection
