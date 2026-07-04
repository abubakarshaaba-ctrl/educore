@extends('layouts.portal')
@section('title','Notices & Updates')
@section('content')
<h2 style="font-size:17px;font-weight:800;margin-bottom:18px">📢 School Notices & Updates</h2>
<div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start">
    <div class="card">
        <div class="ch">Announcements ({{ $announcements->total() }})</div>
        @forelse($announcements as $ann)
        <div style="padding:14px 18px;border-bottom:1px solid var(--border)">
            <div style="font-weight:700;font-size:13px">{{ $ann->title }}</div>
            <div style="font-size:11px;color:var(--muted);margin-top:2px;margin-bottom:8px">
                {{ $ann->publish_date ? \Carbon\Carbon::parse($ann->publish_date)->format('d M Y') : '' }}
                @if($ann->audience !== 'all') · <span class="badge b-b">{{ ucfirst($ann->audience) }}</span> @endif
            </div>
            @if($ann->content)<div style="font-size:13px;color:var(--slate);line-height:1.6">{{ strip_tags($ann->content) }}</div>@endif
        </div>
        @empty
        <div class="empty"><div class="empty-icon">📢</div><div>No announcements.</div></div>
        @endforelse
        <div style="padding:14px">{{ $announcements->links() }}</div>
    </div>
    <div class="card">
        <div class="ch">📆 Upcoming Events</div>
        @forelse($calendar as $ev)
        <div style="display:flex;gap:12px;padding:10px 16px;border-bottom:1px solid var(--border);align-items:center">
            <div style="text-align:center;min-width:40px">
                <div style="font-size:16px;font-weight:800;color:#2563EB">{{ \Carbon\Carbon::parse($ev->date)->format('d') }}</div>
                <div style="font-size:9px;color:var(--muted);text-transform:uppercase">{{ \Carbon\Carbon::parse($ev->date)->format('M') }}</div>
            </div>
            <div><div style="font-weight:600;font-size:13px">{{ $ev->title }}</div></div>
        </div>
        @empty
        <div class="empty" style="padding:30px"><div class="empty-icon">📆</div><div>No events.</div></div>
        @endforelse
    </div>
</div>
@endsection
