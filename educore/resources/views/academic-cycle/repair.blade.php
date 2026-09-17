@extends('layouts.app')

@section('title', 'Academic Current-State Repair')
@section('page-title', 'Academic Current-State Repair')

@push('styles')
<style>
.repair-intro{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px 18px;margin-bottom:16px;color:#475569;line-height:1.65;overflow-wrap:anywhere}
.cycle-alert{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin:12px 0;line-height:1.55;overflow-wrap:anywhere}
.cycle-alert-danger{background:#fef2f2;border-color:#fecaca}.cycle-alert-warning{background:#fffbeb;border-color:#fde68a}
@media(max-width:768px){
    .repair-intro{padding:14px 16px}
    .cycle-alert{padding:12px 14px}
}
@media(max-width:480px){
    .repair-intro{padding:12px 14px;border-radius:8px;font-size:13px}
    .cycle-alert{padding:12px;border-radius:6px;font-size:13px}
}
</style>
@endpush

@section('content')
    <div class="repair-intro">
        This page reviews your academic current-state for issues. Fixes aren't applied automatically here — review any items listed below and resolve them from the relevant setup screens, or contact support if you need a hand.
    </div>
    @include('academic-cycle.partials.blockers', ['decision' => $decision])
@endsection
