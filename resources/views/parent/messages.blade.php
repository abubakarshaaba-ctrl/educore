<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Messages - Parent Portal</title>

@include('parent.partials.base')
<style>
.nav{background:var(--midnight);padding:0 24px;height:58px;display:flex;align-items:center;justify-content:space-between}
.nav a{color:#94A3B8;text-decoration:none;font-size:13px;font-weight:600;padding:7px 12px;border-radius:7px}.nav a:hover{color:white;background:rgba(255,255,255,0.1)}
.nav-title{font-size:14px;font-weight:800;color:white}.content{max-width:900px;margin:0 auto;padding:24px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.card-head{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:space-between}
.thread{padding:14px 18px;border-bottom:1px solid var(--border)}.thread:last-child{border-bottom:none}
.subject{font-size:14px;font-weight:700;margin-bottom:4px}.meta{font-size:12px;color:#64748B}.reply{margin-top:10px;padding:10px 12px;background:#F8FAFC;border-radius:8px;font-size:13px;color:var(--slate)}
.badge{display:inline-flex;font-size:11px;font-weight:700;padding:3px 8px;border-radius:999px}.open{background:#ECFDF5;color:#059669}.closed{background:#F1F5F9;color:#64748B}
</style>
@include('parent.partials.responsive')
{!! \App\Helpers\ThemeHelper::css() !!}
</head>
<body>
<nav class="nav">
    <span class="nav-title">Parent Portal</span>
    <div style="display:flex;gap:6px">
        <a href="{{ route('portal.parent.dashboard') }}">Home</a>
        <a href="{{ route('portal.parent.results') }}">Results</a>
        <a href="{{ route('portal.parent.fees') }}">Fees</a>
        <a href="{{ route('portal.parent.messages') }}">Messages</a>
    </div>
</nav>
<div class="content">
    <div class="card">
        <div class="card-head">
            <span>Messages</span>
            <span style="font-size:12px;color:#64748B">{{ optional($account->guardian)->first_name }}</span>
        </div>
        @forelse($threads as $thread)
            <div class="thread">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                    <div>
                        <div class="subject">{{ $thread->subject }}</div>
                        <div class="meta">
                            {{ optional($thread->student)->full_name ?? 'Linked student' }}
                            @if($thread->created_at) - {{ $thread->created_at->format('d M Y') }} @endif
                        </div>
                    </div>
                    <span class="badge {{ $thread->status === 'open' ? 'open' : 'closed' }}">{{ ucfirst($thread->status) }}</span>
                </div>
                @foreach($thread->replies->take(2) as $reply)
                    <div class="reply">
                        <strong>{{ optional($reply->sender)->name ?? 'School' }}:</strong>
                        {{ $reply->body }}
                    </div>
                @endforeach
            </div>
        @empty
            <div style="text-align:center;padding:48px;color:#94A3B8;font-size:13px">No messages found for your linked students.</div>
        @endforelse
    </div>
</div>
</body>
</html>
