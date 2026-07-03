@extends('layouts.portal')
@section('title','My Subjects')
@section('content')
<h2 style="font-size:17px;font-weight:800;margin-bottom:6px">📚 My Subjects</h2>
<p style="font-size:13px;color:var(--muted);margin-bottom:18px">{{ optional($session)->name }} session</p>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px">
@forelse($subjects as $i => $sub)
<div style="background:white;border:1px solid var(--border);border-radius:12px;padding:16px;display:flex;align-items:center;gap:12px">
    <div style="width:38px;height:38px;background:#EFF6FF;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0">📖</div>
    <div>
        <div style="font-weight:700;font-size:13px">{{ $sub->name }}</div>
        @if($sub->code)<div style="font-size:11px;color:var(--muted)">{{ $sub->code }}</div>@endif
    </div>
</div>
@empty
<div class="card" style="grid-column:1/-1"><div class="empty"><div class="empty-icon">📚</div><div>No subjects assigned yet.</div></div></div>
@endforelse
</div>
@endsection
