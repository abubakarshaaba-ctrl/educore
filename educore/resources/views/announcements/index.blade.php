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
.ann-image-link{display:block;margin-top:12px;cursor:zoom-in;text-decoration:none}
.ann-image{display:block;width:100%;max-height:360px;object-fit:contain;border-radius:10px;border:1px solid var(--border);background:#F8FAFC}
.ann-image-hint{display:block;margin-top:5px;font-size:11px;color:var(--slate-light)}
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
.image-lightbox{position:fixed;inset:0;z-index:10000;background:rgba(2,6,23,.94);display:none;align-items:center;justify-content:center;padding:24px}
.image-lightbox.open{display:flex}
.image-lightbox img{display:block;max-width:96vw;max-height:92vh;width:auto;height:auto;object-fit:contain;border-radius:8px;background:#fff}
.image-lightbox-close{position:absolute;top:16px;right:18px;width:42px;height:42px;border:0;border-radius:50%;background:rgba(255,255,255,.15);color:#fff;font-size:28px;line-height:1;cursor:pointer}
@media(max-width:768px){.ann-grid{grid-template-columns:1fr}.image-lightbox{padding:10px}.image-lightbox img{max-width:100%;max-height:90vh}}
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
        @if(filled($ann->body))<x-rich-text :text="$ann->body" class="ann-body" />@endif
        @if($ann->image_path)
          @php($imageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($ann->image_path))
          <a class="ann-image-link js-image-lightbox" href="{{ $imageUrl }}" data-image="{{ $imageUrl }}" aria-label="View full image: {{ $ann->title }}">
            <img class="ann-image" src="{{ $imageUrl }}" alt="{{ $ann->title }}" loading="lazy">
            <span class="ann-image-hint">Click image to view full size</span>
          </a>
        @endif
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
          <div class="fg"><label class="fl">Message *</label><x-rich-text-toolbar target="announcementBody" /><textarea id="announcementBody" name="body" class="fc edu-rich-target" rows="6" required></textarea></div>
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
<div id="imageLightbox" class="image-lightbox" role="dialog" aria-modal="true" aria-label="Full image viewer">
  <button type="button" class="image-lightbox-close" aria-label="Close image">×</button>
  <img src="" alt="Full announcement image">
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const lightbox = document.getElementById('imageLightbox');
    if (!lightbox) return;
    const fullImage = lightbox.querySelector('img');
    const closeButton = lightbox.querySelector('.image-lightbox-close');

    function closeLightbox() {
        lightbox.classList.remove('open');
        fullImage.src = '';
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.js-image-lightbox').forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            fullImage.src = link.dataset.image || link.href;
            fullImage.alt = link.getAttribute('aria-label') || 'Full announcement image';
            lightbox.classList.add('open');
            document.body.style.overflow = 'hidden';
        });
    });

    closeButton.addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', function (event) {
        if (event.target === lightbox) closeLightbox();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && lightbox.classList.contains('open')) closeLightbox();
    });
});
</script>
@endpush