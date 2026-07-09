@extends('layouts.app')
@section('title','Applicant Documents')
@section('page-title','Applicant Documents')

@push('styles')
<style>
.doc-card{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border:1px solid var(--border);border-radius:12px;margin-bottom:12px;background:white;transition:box-shadow 150ms;gap:12px;flex-wrap:wrap}
.doc-card:hover{box-shadow:0 4px 12px rgba(0,0,0,0.07)}
.doc-icon{font-size:32px;width:48px;text-align:center;flex-shrink:0}
.doc-info{flex:1;min-width:0;margin:0 8px}
.doc-type{font-size:13px;font-weight:700;color:var(--midnight)}
.doc-name{font-size:11px;color:var(--slate-light);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.doc-note{font-size:11px;color:var(--slate-light);margin-top:4px;font-style:italic}
.doc-actions{display:flex;gap:8px;flex-shrink:0;align-items:center;flex-wrap:wrap}
.badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700}
.badge-pending{background:#FEF3C7;color:#92400E}
.badge-verified{background:#D1FAE5;color:#065F46}
.badge-rejected{background:#FEE2E2;color:#991B1B}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;font-size:12px;font-weight:600;font-family:inherit;border:none;border-radius:8px;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-view{background:#EFF6FF;color:var(--indigo)}
.btn-view:hover{background:#DBEAFE}
.btn-download{background:#059669;color:white}
.btn-download:hover{background:#047857}
.btn-verify{background:#10B981;color:white}
.btn-verify:hover{background:#059669}
.btn-reject{background:#EF4444;color:white}
.btn-reject:hover{background:#DC2626}
.btn-reset{background:#F1F5F9;color:var(--slate)}
.btn-reset:hover{background:#E2E8F0}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:9999;align-items:center;justify-content:center}
.modal-overlay.active{display:flex}
.modal-box{background:white;border-radius:14px;padding:24px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,0.2)}
.modal-title{font-size:15px;font-weight:700;color:var(--midnight);margin-bottom:16px}
.modal-box textarea{width:100%;border:1px solid var(--border);border-radius:8px;padding:10px 12px;font-size:13px;font-family:inherit;resize:vertical;min-height:80px;box-sizing:border-box}
.modal-actions{display:flex;gap:8px;margin-top:16px;justify-content:flex-end}
</style>
@endpush

@section('content')
<a href="{{ route('recruitment.show', $applicant->job_posting_id) }}"
   style="font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px">
    ← Back to Posting
</a>

@if(session('success'))
<div style="background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;font-weight:600">
    ✓ {{ session('success') }}
</div>
@endif

<div style="background:white;border:1px solid var(--border);border-radius:14px;overflow:hidden">
    <div style="padding:14px 20px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <div>
            <div style="font-size:14px;font-weight:700;color:var(--midnight)">
                Documents — {{ $applicant->name }}
            </div>
            <div style="font-size:12px;color:var(--slate-light);margin-top:2px">{{ $applicant->jobPosting->title }}</div>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
            @php
                $verified = $docs->where('verification_status','verified')->count();
                $rejected = $docs->where('verification_status','rejected')->count();
                $pending  = $docs->where('verification_status','pending')->count();
            @endphp
            <span class="badge badge-verified">✓ {{ $verified }} verified</span>
            @if($rejected)<span class="badge badge-rejected">✗ {{ $rejected }} rejected</span>@endif
            @if($pending)<span class="badge badge-pending">⏳ {{ $pending }} pending</span>@endif
        </div>
    </div>

    <div style="padding:20px">
        @forelse($docs as $doc)
        @php
            $ext = strtolower(pathinfo($doc->original_name ?? $doc->file_path, PATHINFO_EXTENSION));
            $icon = match($ext) {
                'pdf'  => '📄',
                'jpg','jpeg','png','gif','webp' => '🖼️',
                'doc','docx' => '📝',
                default => '📎'
            };
            $badgeClass = match($doc->verification_status) {
                'verified' => 'badge-verified',
                'rejected' => 'badge-rejected',
                default    => 'badge-pending',
            };
            $badgeIcon = match($doc->verification_status) {
                'verified' => '✓',
                'rejected' => '✗',
                default    => '⏳',
            };
        @endphp
        <div class="doc-card">
            <div class="doc-icon">{{ $icon }}</div>
            <div class="doc-info">
                <div class="doc-type">{{ ucwords(str_replace('_',' ', $doc->document_type)) }}</div>
                <div class="doc-name">{{ $doc->original_name ?? basename($doc->file_path) }}</div>
                @if($doc->verification_note)
                <div class="doc-note">Note: {{ $doc->verification_note }}</div>
                @endif
                @if($doc->verified_at && $doc->verifiedBy)
                <div class="doc-note">
                    {{ ucfirst($doc->verification_status) }} by {{ $doc->verifiedBy->name }} on {{ $doc->verified_at->format('M d, Y') }}
                </div>
                @endif
            </div>
            <div class="doc-actions">
                <span class="badge {{ $badgeClass }}">{{ $badgeIcon }} {{ ucfirst($doc->verification_status) }}</span>

                <a href="{{ asset('storage/' . ltrim($doc->file_path, 'storage/')) }}" target="_blank" class="btn btn-view">
                    👁 View
                </a>
                <a href="{{ route('recruitment.applicants.documents.download', [$applicant, $doc]) }}" class="btn btn-download">
                    ⬇ Download
                </a>

                @if(!$doc->isVerified())
                <button type="button" class="btn btn-verify"
                    onclick="openVerifyModal({{ $doc->id }}, 'verified', '{{ route('recruitment.applicants.documents.verify', [$applicant, $doc]) }}')">
                    ✓ Verify
                </button>
                @endif

                @if(!$doc->isRejected())
                <button type="button" class="btn btn-reject"
                    onclick="openVerifyModal({{ $doc->id }}, 'rejected', '{{ route('recruitment.applicants.documents.verify', [$applicant, $doc]) }}')">
                    ✗ Reject
                </button>
                @endif

                @if(!$doc->isPending())
                <form method="POST" action="{{ route('recruitment.applicants.documents.verify', [$applicant, $doc]) }}" style="display:inline">
                    @csrf
                    <input type="hidden" name="action" value="pending">
                    <button type="submit" class="btn btn-reset">↺ Reset</button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div style="text-align:center;padding:60px 20px;color:var(--slate-light)">
            <div style="font-size:48px;margin-bottom:12px">📂</div>
            <div style="font-size:14px;font-weight:600">No documents uploaded</div>
            <div style="font-size:12px;margin-top:4px">This applicant has no attached files.</div>
        </div>
        @endforelse
    </div>
</div>

{{-- Verify / Reject modal --}}
<div class="modal-overlay" id="verifyModal">
    <div class="modal-box">
        <div class="modal-title" id="modalTitle">Verify Document</div>
        <label style="font-size:12px;font-weight:600;color:var(--slate);display:block;margin-bottom:6px">
            Note <span style="font-weight:400;color:var(--slate-light)">(optional)</span>
        </label>
        <textarea id="modalNote" placeholder="Add a note for this decision…"></textarea>
        <div class="modal-actions">
            <button type="button" class="btn btn-reset" onclick="closeVerifyModal()">Cancel</button>
            <button type="button" class="btn" id="modalSubmit" onclick="submitVerify()">Confirm</button>
        </div>
    </div>
</div>

<form id="verifyForm" method="POST" style="display:none">
    @csrf
    <input type="hidden" name="action" id="formAction">
    <input type="hidden" name="note"   id="formNote">
</form>
@endsection

@push('scripts')
<script>
let _action = '', _formUrl = '';

function openVerifyModal(docId, action, url) {
    _action  = action;
    _formUrl = url;
    document.getElementById('modalTitle').textContent = action === 'verified' ? 'Verify Document' : 'Reject Document';
    const btn = document.getElementById('modalSubmit');
    btn.textContent = action === 'verified' ? '✓ Confirm Verification' : '✗ Confirm Rejection';
    btn.style.background = action === 'verified' ? '#10B981' : '#EF4444';
    btn.style.color = 'white';
    document.getElementById('modalNote').value = '';
    document.getElementById('verifyModal').classList.add('active');
}

function closeVerifyModal() {
    document.getElementById('verifyModal').classList.remove('active');
}

function submitVerify() {
    const form = document.getElementById('verifyForm');
    form.action = _formUrl;
    document.getElementById('formAction').value = _action;
    document.getElementById('formNote').value   = document.getElementById('modalNote').value;
    form.submit();
}

document.getElementById('verifyModal').addEventListener('click', function(e) {
    if (e.target === this) closeVerifyModal();
});
</script>
@endpush
