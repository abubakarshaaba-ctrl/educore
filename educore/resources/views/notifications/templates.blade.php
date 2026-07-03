@extends('layouts.app')
@section('title','Message Templates')
@section('page-title','Message Templates')
@push('styles')
<style>
.pg{display:grid;grid-template-columns:1fr 380px;gap:16px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.tmpl-item{padding:16px 18px;border-bottom:1px solid var(--border);cursor:pointer;transition:background 150ms}
.tmpl-item:last-child{border-bottom:none}
.tmpl-item:hover{background:#F8FAFC}
.tmpl-name{font-size:13px;font-weight:700;color:var(--midnight)}
.tmpl-subject{font-size:11px;color:var(--indigo);margin-top:2px;font-weight:500}
.tmpl-body{font-size:12px;color:var(--slate);margin-top:5px;line-height:1.5}
.tmpl-tags{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px}
.tag{font-size:10px;background:#F1F5F9;color:var(--slate);padding:2px 7px;border-radius:4px;font-family:monospace}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;transition:all 150ms;margin-top:8px}
.btn-p{background:var(--indigo);color:white}
.back{font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px}
.info-box{background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:12px 16px;font-size:12.5px;color:var(--indigo);margin-bottom:14px}

@media (max-width: 1024px) {
    .two-col { grid-template-columns: 1fr !important; }
    .stats-row, .stat-row { grid-template-columns: repeat(2, 1fr) !important; }
    .kpi { grid-template-columns: repeat(2, 1fr) !important; }
}
@media (max-width: 640px) {
    .two, .fr { grid-template-columns: 1fr !important; }
}
@media (max-width: 480px) {
    .fr3 { grid-template-columns: 1fr !important; }
}
</style>
@endpush
@section('content')
<a href="{{ route('notifications.index') }}" class="back">← Back to Notifications</a>
<div class="info-box">
  💡 Placeholders like {guardian_name}, {student_name}, {balance} are replaced automatically when sending. Use these templates as starting points.
</div>
<div class="pg">
  <div>
    <div class="card">
      <div class="ch">Available Templates</div>
      @foreach($templates as $tmpl)
      <div class="tmpl-item">
        <div class="tmpl-name">{{ $tmpl['name'] }}</div>
        <div class="tmpl-subject">Subject: {{ $tmpl['subject'] }}</div>
        <div class="tmpl-body">{{ $tmpl['body'] }}</div>
        <div class="tmpl-tags">
          @foreach(array_filter(preg_match_all('/\{([^}]+)\}/', $tmpl['body'], $m) ? $m[0] : []) as $tag)
          <span class="tag">{{ $tag }}</span>
          @endforeach
        </div>
        <button class="btn btn-p" onclick="useTemplate('{{ addslashes($tmpl['subject']) }}','{{ addslashes($tmpl['body']) }}')">Use Template</button>
      </div>
      @endforeach
    </div>
  </div>
  <div>
    <div class="card">
      <div class="ch">Compose Message</div>
      <div style="padding:16px">
        <form method="POST" action="{{ route('notifications.send') }}">
        @csrf
        <div style="display:flex;flex-direction:column;gap:10px">
          <div><label style="font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;display:block;margin-bottom:4px">Subject</label>
            <input type="text" name="subject" id="msg-subject" style="width:100%;padding:9px 12px;font-size:13px;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;font-family:inherit"></div>
          <div><label style="font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;display:block;margin-bottom:4px">Message</label>
            <textarea name="message" id="msg-body" rows="6" style="width:100%;padding:9px 12px;font-size:13px;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;font-family:inherit;resize:vertical"></textarea></div>
          <div><label style="font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;display:block;margin-bottom:4px">Send To</label>
            <select name="recipients" style="width:100%;padding:9px 12px;font-size:13px;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;font-family:inherit">
              <option value="all_parents">All Parents</option>
              <option value="all_staff">All Staff</option>
              <option value="all">Everyone</option>
            </select>
          </div>
          <button type="submit" class="btn btn-p" style="width:100%;justify-content:center;padding:10px">Send Message</button>
        </div>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
function useTemplate(subject, body) {
    document.getElementById('msg-subject').value = subject;
    document.getElementById('msg-body').value = body;
    document.getElementById('msg-subject').scrollIntoView({behavior:'smooth'});
}
</script>
@endsection