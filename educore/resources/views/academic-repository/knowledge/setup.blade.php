@extends('layouts.app')

@section('page-title', 'Curriculum Knowledge')

@section('content')
<div style="max-width:760px;margin:36px auto">
    <div class="card" style="padding:34px;text-align:center">
        <div style="width:58px;height:58px;margin:0 auto 18px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#FFF7D6;color:#0B1F3A;font-size:28px;font-weight:800">!</div>
        <h1 style="margin:0 0 10px;color:#0B1F3A;font-size:24px">Curriculum Knowledge is being initialized</h1>
        <p style="margin:0 auto 22px;max-width:590px;color:#64748B;line-height:1.65">
            The feature is available, but its database tables have not finished initializing on this server. No academic data has been lost. Please retry after the latest deployment and database migration complete.
        </p>
        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
            <a href="{{ route('academic-repository.knowledge.index') }}" class="btn btn-primary">Retry</a>
            <a href="{{ route('academic-repository.index') }}" class="btn btn-secondary">Back to Academic Repository</a>
        </div>
    </div>
</div>
@endsection
