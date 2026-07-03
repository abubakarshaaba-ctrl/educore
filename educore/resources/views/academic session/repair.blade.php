@extends('layouts.app')

@section('title', 'Academic Current-State Repair')
@section('page-title', 'Academic Current-State Repair')

@push('styles')
<style>
.cycle-alert{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin:12px 0}.cycle-alert-danger{background:#fef2f2;border-color:#fecaca}.cycle-alert-warning{background:#fffbeb;border-color:#fde68a}
</style>
@endpush

@section('content')
    <p>This page reviews your academic current-state for issues. Fixes aren't applied automatically here — review any items listed below and resolve them from the relevant setup screens, or contact support if you need a hand.</p>
    @include('academic session.partials.blockers', ['decision' => $decision])
@endsection
