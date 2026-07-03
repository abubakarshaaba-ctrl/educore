@extends('layouts.app')
@section('title','Announcements')
@section('page-title','Announcements')
@push('styles')
<style>
.ann-grid{display:grid;grid-template-columns:1fr 360px;gap:16px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
.ann-item{padding:16px 20px;border-bottom:1px solid var(--border)}
.ann-item:last-child{border-bottom:none}
.ann-title{font-size:14px;font-weight:700;color:var(--midnight);display:flex;align-items:center;gap:8px}
.ann-body{font-size:13px;color:var(--slate);margin-top:5px;line-height:1.5}
.ann-meta{font-size:11px;color:var(--slate-light);margin-top:6px}
.pri{display:inline-flex;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px}
.pri-normal{background:#F1F5F9;color:var(--slate)}
.pri-important{background:#FFFBEB;color:var(--amber)}
.pri-urgent{background:#FEF2F2;color:var(--crimson)}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%;transition:border 200ms}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms;width:100%;justify-content:center}
.btn-p{background:var(--indigo);color:white}
.btn-sm{padding:4px 10px;font-size:11px;width:auto}
.btn-ghost{background:white;color:var(--midnight);border:1px solid var(--border)}
.manage-link{font-size:12px;color:var(--indigo);text-decoration:none}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
@media(max-width:768px){.ann-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
<div class="ann-grid">
  <div>
    <div class="card">
      <div class="ch">
        Active Announcements
        @can('notifications.send')<a href="{{ route('announcements.manage') }}" class="manage-link">Manage All →</a>@endcan
      </div>
      @forelse($announcements as $ann)
      <div class="ann-item">
        <div class="ann-title">
          @if($ann->priority!=='normal')<span class="pri pri-{{ $ann->priority }}">{{ ucfirst($ann->priority) }}</span>@endif
          {{ $ann->title }}
        </div>
        <div class="ann-body">{{ $ann->body }}</div>
        <div class="ann-meta">
          {{ \Carbon\Carbon::parse($ann->publish_date)->format('d M Y') }}
          · For: {{ ucfirst($ann->audience) }}
          @if($ann->expire_date) · Expires: {{ \Carbon\Carbon::parse($ann->expire_date)->format('d M Y') }}@endif
        </div>
      </div>
      @empty
      <div style="padding:40px;text-align:center;color:var(--slate-light)">No active announcements</div>
      @endforelse
      {{ $announcements->links() }}
    </div>
  </div>
  @can('notifications.send')
  <div>
    <div class="card">
      <div class="ch">Post Announcement</div>
      <div style="padding:16px">
        <form method="POST" action="{{ route('announcements.store') }}">
          @csrf
          <div class="fg"><label class="fl">Title *</label><input type="text" name="title" class="fc" required></div>
          <div class="fg"><label class="fl">Message *</label><textarea name="body" class="fc" rows="4" required></textarea></div>
          <div class="fg"><label class="fl">For *</label>
            <select name="audience" class="fc">
              <option value="all">All (Staff + Parents)</option>
              <option value="staff">Staff Only</option>
              <option value="students">Students</option>
              <option value="parents">Parents Only</option>
            </select>
          </div>
          <div class="fg"><label class="fl">Priority</label>
            <select name="priority" class="fc">
              <option value="normal">Normal</option>
              <option value="important">Important</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
          <div class="fg"><label class="fl">Publish Date *</label><input type="date" name="publish_date" class="fc" value="{{ date('Y-m-d') }}" required></div>
          <div class="fg"><label class="fl">Expire Date</label><input type="date" name="expire_date" class="fc"></div>
          <button type="submit" class="btn btn-p">Publish</button>
        </form>
      </div>
    </div>
  </div>
  @endcan
</div>
@endsection