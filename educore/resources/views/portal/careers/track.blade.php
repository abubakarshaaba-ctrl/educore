<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Application Status – {{ $tenant->name }}</title>
<style>
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
:root {
    --navy:   #0B1D3A;
    --blue:   #1A56DB;
    --slate:  #64748B;
    --border: #E2E8F0;
    --bg:     #F8FAFC;
    --green:  #10B981;
    --red:    #EF4444;
}
html,body{max-width:100%;overflow-x:hidden}
body { font-family:'Inter',-apple-system,sans-serif; background:var(--bg); color:var(--navy); line-height:1.6; min-height:100vh; }
.topnav { position:sticky; top:0; z-index:50; background:rgba(11,29,58,0.97); backdrop-filter:blur(12px); padding:0 24px; display:flex; align-items:center; justify-content:space-between; gap:12px; height:60px; min-width:0; }
.topnav-brand { display:flex; align-items:center; gap:10px; text-decoration:none; min-width:0; }
.topnav-logo { width:36px; height:36px; border-radius:9px; background:var(--blue); display:flex; align-items:center; justify-content:center; font-size:15px; font-weight:800; color:white; overflow:hidden; flex-shrink:0; }
.topnav-logo img { width:100%; height:100%; object-fit:cover; }
.topnav-name { font-size:14px; font-weight:700; color:white; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.nav-link { font-size:13px; font-weight:500; color:rgba(255,255,255,0.7); text-decoration:none; padding:6px 14px; border-radius:8px; white-space:nowrap; }
.nav-link:hover { color:white; background:rgba(255,255,255,0.08); }
.wrap { max-width:680px; margin:0 auto; padding:40px 24px 64px; width:100%; }
.card { background:white; border:1px solid var(--border); border-radius:16px; padding:28px; margin-bottom:20px; min-width:0; }
.title { font-size:22px; font-weight:800; color:var(--navy); letter-spacing:-0.02em; margin-bottom:6px; overflow-wrap:anywhere; }
.meta { font-size:13px; color:var(--slate); margin-bottom:16px; overflow-wrap:anywhere; }
.badge { display:inline-flex; max-width:100%; font-size:11px; font-weight:700; padding:4px 12px; border-radius:20px; text-transform:capitalize; overflow-wrap:anywhere; }
.b-applied,.b-shortlisted{background:#EFF6FF;color:#1D4ED8}
.b-interview_scheduled,.b-interviewed{background:#FFFBEB;color:#B45309}
.b-offered,.b-hired{background:#ECFDF5;color:#059669}
.b-rejected{background:#FEF2F2;color:#DC2626}
.subhead { font-size:13px; font-weight:700; color:var(--navy); text-transform:uppercase; letter-spacing:0.05em; margin:8px 0 14px; overflow-wrap:anywhere; }
.thread { display:flex; flex-direction:column; gap:12px; margin-bottom:20px; min-width:0; }
.msg { max-width:80%; padding:12px 16px; border-radius:14px; font-size:13px; line-height:1.5; overflow-wrap:anywhere; word-break:break-word; }
.msg-school { background:#F1F5F9; color:#334155; align-self:flex-start; border-bottom-left-radius:4px; }
.msg-applicant { background:var(--blue); color:white; align-self:flex-end; border-bottom-right-radius:4px; }
.msg-meta { font-size:10px; opacity:0.6; margin-top:4px; overflow-wrap:anywhere; }
.empty-thread { text-align:center; color:var(--slate); font-size:13px; padding:20px; }
.fc { padding:11px 14px; font-size:14px; font-family:inherit; border:1px solid var(--border); border-radius:10px; background:#F8FAFC; outline:none; width:100%; max-width:100%; resize:vertical; }
.fc:focus { border-color:var(--blue); background:white; }
.btn-submit { margin-top:10px; padding:11px 22px; font-size:14px; font-weight:700; border-radius:10px; border:none; cursor:pointer; background:var(--blue); color:white; font-family:inherit; }
.btn-submit:hover { background:#1946C0; }
.alert { padding:14px 18px; border-radius:10px; font-size:13px; margin-bottom:20px; overflow-wrap:anywhere; }
.alert-success { background:#ECFDF5; color:#065F46; border:1px solid #A7F3D0; }
@media(max-width:600px){
    .topnav{padding:0 14px;height:56px}
    .topnav-logo{width:32px;height:32px}
    .topnav-name{max-width:145px;font-size:13px}
    .nav-link{padding:6px 8px;font-size:12px}
    .wrap{padding:24px 14px 42px}
    .card{padding:20px 16px;border-radius:12px}
    .msg{max-width:92%;padding:11px 13px}
    .fc{font-size:16px}
    .btn-submit{width:100%;min-height:42px}
}
@media(max-width:380px){
    .topnav-name{max-width:105px}
    .nav-link{font-size:11px}
    .wrap{padding-left:10px;padding-right:10px}
    .card{padding:18px 14px}
    .msg{max-width:96%}
}
</style>
</head>
<body>
<nav class="topnav">
    <a href="#" class="topnav-brand">
        <div class="topnav-logo">
            @if($tenant->logo_path)
                <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="{{ $tenant->name }}">
            @else
                {{ strtoupper(substr($tenant->name, 0, 1)) }}
            @endif
        </div>
        <span class="topnav-name">{{ $tenant->name }}</span>
    </a>
    <div><a href="{{ route('careers.landing', $tenant->slug) }}" class="nav-link">← All Openings</a></div>
</nav>

<div class="wrap">
    @if(session('success'))
        <div class="alert alert-success">✓ {{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="title">{{ $applicant->jobPosting->title }}</div>
        <div class="meta">Applied {{ $applicant->applied_at?->format('d F Y') ?? $applicant->created_at->format('d F Y') }}</div>
        <span class="badge b-{{ $applicant->status }}">{{ ucwords(str_replace('_',' ',$applicant->status)) }}</span>
    </div>

    <div class="card">
        <div class="subhead">Messages with {{ $tenant->name }}</div>
        <div class="thread">
            @forelse($applicant->messages as $m)
                <div class="msg msg-{{ $m->sender_type }}">
                    {{ $m->body }}
                    <div class="msg-meta">{{ $m->sender_type === 'school' ? $tenant->name : $applicant->name }} · {{ $m->created_at->format('d M, h:ia') }}</div>
                </div>
            @empty
                <div class="empty-thread">No messages yet. Send a message below if you have a question.</div>
            @endforelse
        </div>

        <form method="POST" action="{{ url()->current() . '/reply' }}">
            @csrf
            <textarea name="body" class="fc" rows="3" placeholder="Write a message to {{ $tenant->name }}..." required></textarea>
            <button type="submit" class="btn-submit">Send Message</button>
        </form>
    </div>
</div>
</body>
</html>
