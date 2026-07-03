@extends('layouts.super')
@section('title', 'Broadcasts')
@section('page-title', 'Broadcasts to Schools')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:20px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:700;color:var(--midnight);background:#F8FAFC}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
label{font-size:12px;font-weight:600;color:var(--midnight)}
.fc{padding:10px 13px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:9px;background:white;outline:none;width:100%;transition:border 200ms}
.fc:focus{border-color:#DC2626}
select.fc{cursor:pointer}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer}
.btn-p{background:#DC2626;color:white}
.btn-del{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:11px 16px;font-size:13px;color:#059669;margin-bottom:14px}
.bcast{padding:16px 18px;border-bottom:1px solid var(--border)}
.bcast:last-child{border-bottom:none}
.b-title{font-size:14px;font-weight:700;color:var(--midnight);margin-bottom:4px}
.b-body{font-size:13px;color:var(--slate);white-space:pre-line;margin-bottom:8px}
.b-meta{font-size:11px;color:#94A3B8;display:flex;align-items:center;gap:12px}
.badge{display:inline-flex;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;background:#EFF6FF;color:#3B82F6}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif

<div class="card">
    <div class="ch">Send Broadcast Message</div>
    <div style="padding:18px">
        <form method="POST" action="{{ route('super.broadcasts.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="fg">
                    <label>Title / Headline</label>
                    <input type="text" name="title" class="fc" placeholder="e.g. System maintenance on Friday" required value="{{ old('title') }}">
                    @error('title')<span style="font-size:11px;color:#DC2626">{{ $message }}</span>@enderror
                </div>
                <div class="fg">
                    <label>Target Audience</label>
                    <select name="target" class="fc">
                        <option value="all" {{ old('target')=='all'?'selected':'' }}>All Schools</option>
                        <option value="trial" {{ old('target')=='trial'?'selected':'' }}>Trial Schools Only</option>
                        <option value="active" {{ old('target')=='active'?'selected':'' }}>Active Subscriptions Only</option>
                        <option value="expired" {{ old('target')=='expired'?'selected':'' }}>Expired Schools Only</option>
                    </select>
                </div>
            </div>
            <div class="fg">
                <label>Message Body</label>
                <textarea name="body" class="fc" rows="4" placeholder="Write your broadcast message here..." required>{{ old('body') }}</textarea>
                @error('body')<span style="font-size:11px;color:#DC2626">{{ $message }}</span>@enderror
            </div>
            <div class="fg" style="max-width:220px">
                <label>Expires On (optional)</label>
                <input type="date" name="expires_at" class="fc" value="{{ old('expires_at') }}" min="{{ date('Y-m-d', strtotime('+1 day')) }}">
            </div>
            <button type="submit" class="btn btn-p">Send Broadcast</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="ch">Sent Broadcasts</div>
    @forelse($broadcasts as $bc)
    <div class="bcast">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px">
            <div style="flex:1">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                    <div class="b-title">{{ $bc->title }}</div>
                    <span class="badge">{{ ucfirst($bc->target) }}</span>
                    @if($bc->expires_at && \Carbon\Carbon::parse($bc->expires_at)->isPast())
                        <span style="font-size:11px;color:#DC2626;font-weight:600">Expired</span>
                    @endif
                </div>
                <div class="b-body">{{ $bc->body }}</div>
                <div class="b-meta">
                    <span>By {{ $bc->creator_name ?? 'Admin' }}</span>
                    <span>· {{ \Carbon\Carbon::parse($bc->created_at)->format('d M Y, H:i') }}</span>
                    @if($bc->expires_at) <span>· Expires {{ \Carbon\Carbon::parse($bc->expires_at)->format('d M Y') }}</span> @endif
                </div>
            </div>
            <form method="POST" action="{{ route('super.broadcasts.delete', $bc->id) }}">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-del" onclick="return confirm('Delete this broadcast?')">Delete</button>
            </form>
        </div>
    </div>
    @empty
    <div style="padding:40px;text-align:center;color:#94A3B8;font-size:13px">No broadcasts sent yet.</div>
    @endforelse
</div>
{{ $broadcasts->links() }}
@endsection
