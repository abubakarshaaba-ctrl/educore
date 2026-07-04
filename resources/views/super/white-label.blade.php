@extends('layouts.super')
@section('title','White-label Settings')
@section('page-title','White-label Settings')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px;width:100%}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:20px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%;transition:border 200ms}
.fc:focus{border-color:var(--indigo);background:white}
.fr{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}
.btn-g{background:var(--emerald);color:white}
.btn-ghost{background:white;border:1px solid var(--border);color:var(--midnight)}
.dns-box{background:#F8FAFC;border:1px solid var(--border);border-radius:8px;padding:12px 16px;font-family:monospace;font-size:13px;margin-bottom:12px}
.domain-status{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;padding:4px 10px;border-radius:20px}
.ds-verified{background:#ECFDF5;color:var(--emerald)}
.ds-pending{background:#FFFBEB;color:var(--amber)}
.ds-none{background:#F1F5F9;color:var(--slate)}
.back{font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
.alert-e{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--crimson);margin-bottom:14px}

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
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert-e">{{ session('error') }}</div>@endif
<a href="{{ route('super.tenant.show', $tenant) }}" class="back">← Back to School</a>

<div class="card">
    <div class="ch">White-label Settings — {{ $tenant->name }}</div>
    <div class="cb">
        <form method="POST" action="{{ route('super.white-label.save', $tenant) }}">
        @csrf
        <div class="fg">
            <label class="fl">Custom Domain</label>
            <input type="text" name="custom_domain" class="fc" value="{{ $tenant->custom_domain }}" placeholder="e.g. portal.schoolname.com.ng">
            <span style="font-size:11px;color:var(--slate-light)">Point your DNS CNAME to: sms.yourplatform.ng</span>
        </div>
        <div class="fr">
            <div class="fg">
                <label class="fl">Primary Colour</label>
                <div style="display:flex;gap:8px;align-items:center">
                    <input type="color" name="primary_color" value="{{ $tenant->primary_color ?? '#2563EB' }}" style="width:42px;height:38px;border:1px solid var(--border);border-radius:6px;cursor:pointer;padding:2px">
                    <input type="text" id="primary_text" value="{{ $tenant->primary_color ?? '#2563EB' }}" class="fc" style="flex:1" readonly>
                </div>
            </div>
            <div class="fg">
                <label class="fl">Secondary Colour</label>
                <div style="display:flex;gap:8px;align-items:center">
                    <input type="color" name="secondary_color" value="{{ $tenant->secondary_color ?? '#1E40AF' }}" style="width:42px;height:38px;border:1px solid var(--border);border-radius:6px;cursor:pointer;padding:2px">
                    <input type="text" id="secondary_text" value="{{ $tenant->secondary_color ?? '#1E40AF' }}" class="fc" style="flex:1" readonly>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-p">Save White-label Settings</button>
        </form>
    </div>
</div>

@if($tenant->custom_domain)
<div class="card">
    <div class="ch">Domain Verification
        <span class="domain-status {{ $tenant->domain_verified ? 'ds-verified' : 'ds-pending' }}">
            {{ $tenant->domain_verified ? '✅ Verified' : '⏳ Pending' }}
        </span>
    </div>
    <div class="cb">
        <p style="font-size:13px;color:var(--slate);margin-bottom:14px">Add the following TXT record to your DNS provider, then click Verify:</p>
        <div class="dns-box">
            <div><strong>Type:</strong> TXT</div>
            <div><strong>Host:</strong> @</div>
            <div><strong>Value:</strong> sms-verify={{ $tenant->id }}</div>
        </div>
        <form method="POST" action="{{ route('super.verify-domain', $tenant) }}">
            @csrf
            <button type="submit" class="btn btn-g">🔍 Verify Domain Now</button>
        </form>
    </div>
</div>
@endif
<script>
document.querySelectorAll('input[type="color"]').forEach(function(picker) {
    picker.addEventListener('input', function() {
        var textInput = this.parentElement.querySelector('input[type="text"]');
        if (textInput) textInput.value = this.value;
    });
});
</script>
@endsection