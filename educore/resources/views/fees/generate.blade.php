@extends('layouts.app')
@section('title', 'Invoice Generation')
@section('page-title', 'Invoice Generation')

@push('styles')
<style>
    .generator-stats{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px;margin-bottom:20px}
    .generator-stat{padding:14px;border:1px solid var(--border);border-radius:12px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.04)}
    .generator-stat-value{font-size:21px;font-weight:800;letter-spacing:-.025em;color:var(--brand-navy)}
    .generator-stat-label{margin-top:3px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light)}
    .generator-grid{display:grid;grid-template-columns:minmax(0,1fr) 380px;gap:20px;align-items:start}
    .finance-card{background:#fff;border:1px solid var(--border);border-radius:14px;box-shadow:0 8px 24px rgba(15,23,42,.05);overflow:hidden;margin-bottom:18px}
    .finance-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:15px 18px;border-bottom:1px solid var(--border);background:var(--brand-navy-soft)}
    .finance-card-title{font-size:14px;font-weight:800;color:var(--brand-navy)}
    .finance-card-body{padding:20px}
    .form-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.form-group{margin-bottom:16px}
    .form-label{display:block;margin-bottom:5px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate)}
    .form-help{margin-top:4px;font-size:11px;line-height:1.45;color:var(--slate-light)}
    .form-control{width:100%;min-height:44px;padding:9px 12px;border:1px solid var(--border);border-radius:9px;background:#F8FAFC;color:var(--midnight);font:inherit;font-size:13px;outline:none}.form-control:focus{background:#fff;border-color:var(--brand-gold-dark);box-shadow:0 0 0 3px rgba(215,154,33,.16)}
    .scope-tabs{display:flex;gap:6px;flex-wrap:wrap}.scope-tab{min-height:40px;padding:7px 13px;border:1px solid var(--border);border-radius:9px;background:#fff;color:var(--slate);font:inherit;font-size:12px;font-weight:700;cursor:pointer}.scope-tab:hover{background:var(--brand-navy-soft);color:var(--brand-navy)}.scope-tab.active{border-color:var(--brand-gold);background:var(--brand-gold-light);color:var(--brand-navy)}
    .check-row{display:flex;align-items:flex-start;gap:9px;margin:2px 0 16px;color:var(--midnight);font-size:13px}.check-row input{width:16px;height:16px;margin-top:2px;accent-color:var(--brand-gold-dark)}
    .action-row{display:flex;gap:10px;align-items:center}.btn-primary-finance,.btn-secondary-finance,.btn-danger-soft,.btn-link-finance{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:8px 15px;border-radius:9px;font:inherit;font-size:12px;font-weight:800;text-decoration:none;cursor:pointer}
    .btn-primary-finance{flex:1;border:1px solid var(--brand-gold);background:var(--brand-gold);color:var(--brand-navy)}.btn-primary-finance:hover{background:var(--brand-gold-dark);border-color:var(--brand-gold-dark)}
    .btn-secondary-finance{border:1px solid var(--border);background:#fff;color:var(--brand-navy)}.btn-secondary-finance:hover{background:var(--brand-navy-soft)}
    .btn-danger-soft{min-height:34px;padding:5px 10px;border:1px solid #FECACA;background:#FEF2F2;color:#B91C1C}.btn-danger-soft:hover{background:#FEE2E2}
    .btn-link-finance{min-height:36px;padding:6px 10px;border:0;background:transparent;color:var(--brand-navy)}.btn-link-finance:hover{color:var(--brand-gold-dark)}
    .preview-box{display:none;padding:18px;margin-bottom:18px;border:1px solid #BFDBFE;border-radius:12px;background:#EFF6FF}.preview-box.show{display:block}.preview-title{margin-bottom:12px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#1D4ED8}.preview-row{display:flex;justify-content:space-between;gap:16px;padding:5px 0;border-bottom:1px solid rgba(0,0,0,.06);font-size:13px}.preview-row:last-child{padding-top:10px;border-bottom:0;font-size:14px;font-weight:800}.preview-row .label{color:#334155}.preview-row .value{font-weight:700;color:var(--midnight)}.preview-items{margin:10px 0}.preview-item{display:flex;justify-content:space-between;gap:12px;padding:3px 0;font-size:12px;color:#475569}
    .loading{display:none;align-items:center;gap:8px;margin-bottom:14px;font-size:13px;color:var(--slate)}.loading.show{display:flex}.spinner{width:16px;height:16px;border:2px solid var(--border);border-top-color:var(--brand-gold-dark);border-radius:50%;animation:spin 600ms linear infinite}@keyframes spin{to{transform:rotate(360deg)}}
    .batch-table-wrap{overflow-x:auto}.batch-table{width:100%;min-width:760px;border-collapse:collapse}.batch-table th{padding:9px 12px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light)}.batch-table td{padding:11px 12px;border-bottom:1px solid var(--border);font-size:13px}.batch-table tbody tr:last-child td{border-bottom:0}.batch-table tbody tr:hover td{background:#FAFCFF}
    .badge{display:inline-flex;align-items:center;min-height:27px;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:800}.badge-green{background:#ECFDF5;color:#047857}.badge-red{background:#FEF2F2;color:#B91C1C}.badge-amber{background:#FFFBEB;color:#B45309}
    .discount-item{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 12px;margin-bottom:8px;border:1px solid var(--border);border-radius:9px}.discount-name{font-size:13px;font-weight:700;color:var(--midnight)}.discount-meta{margin-top:2px;font-size:11px;color:var(--slate-light)}.icon-danger{border:0;background:transparent;color:#94A3B8;font-size:16px;cursor:pointer}.icon-danger:hover{color:var(--crimson)}
    .quick-links{display:grid;gap:4px}.quick-link{display:flex;gap:10px;align-items:center;padding:11px 12px;border-radius:9px;color:var(--midnight);text-decoration:none}.quick-link:hover{background:var(--brand-navy-soft)}.quick-link-title{font-size:13px;font-weight:700;color:var(--brand-navy)}.quick-link-sub{margin-top:2px;font-size:11px;color:var(--slate-light)}
    .alert-success,.alert-error{margin-bottom:16px;padding:12px 16px;border-radius:9px;font-size:13px}.alert-success{border:1px solid #A7F3D0;background:#ECFDF5;color:#047857}.alert-error{border:1px solid #FECACA;background:#FEF2F2;color:#B91C1C}
    @media(max-width:1100px){.generator-stats{grid-template-columns:repeat(3,1fr)}}
    @media(max-width:900px){.generator-grid{grid-template-columns:1fr}}
    @media(max-width:650px){.generator-stats{grid-template-columns:repeat(2,1fr)}.form-row{grid-template-columns:1fr}.action-row{align-items:stretch;flex-direction:column}.btn-primary-finance,.btn-secondary-finance{width:100%}}
</style>
@endpush

@section('content')
@if(session('success'))<div class="alert-success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error" role="alert">{{ $errors->first() }}</div>@endif

@if($summary)
<div class="generator-stats">
    <div class="generator-stat"><div class="generator-stat-value">{{ number_format($summary['total_invoices']) }}</div><div class="generator-stat-label">{{ $summary['term']->name }} Invoices</div></div>
    <div class="generator-stat"><div class="generator-stat-value">₦{{ number_format($summary['total_value']/1000, 1) }}k</div><div class="generator-stat-label">Total Value</div></div>
    <div class="generator-stat"><div class="generator-stat-value" style="color:var(--emerald)">{{ $summary['paid'] }}</div><div class="generator-stat-label">Fully Paid</div></div>
    <div class="generator-stat"><div class="generator-stat-value" style="color:var(--amber)">{{ $summary['partial'] }}</div><div class="generator-stat-label">Partially Paid</div></div>
    <div class="generator-stat"><div class="generator-stat-value" style="color:var(--crimson)">{{ $summary['unpaid'] }}</div><div class="generator-stat-label">Unpaid</div></div>
    <div class="generator-stat"><div class="generator-stat-value" style="color:var(--crimson)">{{ $summary['overdue'] }}</div><div class="generator-stat-label">Overdue</div></div>
</div>
@endif

<div class="generator-grid">
    <div>
        <form method="POST" action="{{ route('fees.generate.store') }}" id="generateForm">
            @csrf
            <div class="finance-card">
                <div class="finance-card-head"><span class="finance-card-title">Generate Invoices</span><a href="{{ route('fees.generate.batches') }}" class="btn-link-finance">View History</a></div>
                <div class="finance-card-body">
                    <div class="form-group"><label class="form-label" for="termSelect">Academic Term</label><select name="term_id" id="termSelect" class="form-control" required><option value="">Select term</option>@foreach($terms as $term)<option value="{{ $term->id }}" {{ $term->is_current ? 'selected' : '' }}>{{ $term->name }} — {{ optional($term->session)->name }}{{ $term->is_current ? ' (Current)' : '' }}</option>@endforeach</select></div>

                    <div class="form-group">
                        <label class="form-label">Generation Scope</label>
                        <div class="scope-tabs" role="group" aria-label="Invoice generation scope">
                            <button type="button" class="scope-tab active" data-scope="class_level">By Class Level</button>
                            <button type="button" class="scope-tab" data-scope="class_arm">By Class Arm</button>
                            <button type="button" class="scope-tab" data-scope="all">All Students</button>
                        </div>
                        <input type="hidden" name="scope" id="scopeInput" value="class_level">
                    </div>

                    <div class="form-group" id="classLevelGroup"><label class="form-label" for="classLevelSelect">Class Level</label><select name="class_level_id" id="classLevelSelect" class="form-control"><option value="">All Levels</option>@foreach($classLevels as $level)<option value="{{ $level->id }}">{{ $level->name }}</option>@endforeach</select></div>
                    <div class="form-group" id="classArmGroup" style="display:none"><label class="form-label" for="classArmSelect">Class Arm</label><select name="class_arm_id" id="classArmSelect" class="form-control"><option value="">Select class arm</option>@foreach($classLevels as $level)@foreach($level->classArms as $arm)<option value="{{ $arm->id }}">{{ $level->name }} {{ $arm->name }}</option>@endforeach @endforeach</select></div>

                    <div class="form-row">
                        <div class="form-group"><label class="form-label" for="due_date">Due Date</label><input id="due_date" type="date" name="due_date" class="form-control" value="{{ date('Y-m-d', strtotime('+30 days')) }}"><div class="form-help">Defaults to the term end date if left blank.</div></div>
                        <div class="form-group"><label class="form-label" for="discountSelect">Discount Template</label><select name="discount_id" id="discountSelect" class="form-control"><option value="">No discount</option>@foreach($discounts as $d)<option value="{{ $d->id }}">{{ $d->label() }}</option>@endforeach</select></div>
                    </div>

                    <div class="form-group"><label class="form-label" for="notes">Notes</label><input id="notes" type="text" name="notes" class="form-control" maxlength="500" placeholder="e.g. 2026/27 First Term fees"></div>
                    <label class="check-row" for="overwriteCheck"><input type="checkbox" name="overwrite" value="1" id="overwriteCheck"><span><strong>Replace existing unpaid invoices</strong><br><span class="form-help">Paid invoices are not replaced.</span></span></label>

                    <div class="preview-box" id="previewBox" aria-live="polite"><div class="preview-title">Preview — What will be generated</div><div id="previewContent"></div></div>
                    <div class="loading" id="loadingIndicator" aria-live="polite"><div class="spinner"></div> Calculating preview…</div>

                    <div class="action-row"><button type="button" class="btn-secondary-finance" onclick="runPreview()">Preview</button><button type="submit" class="btn-primary-finance" id="generateBtn">Generate Invoices</button></div>
                </div>
            </div>
        </form>

        @if($batches->count())
        <div class="finance-card">
            <div class="finance-card-head"><span class="finance-card-title">Recent Generation Batches</span><a href="{{ route('fees.generate.batches') }}" class="btn-link-finance">View All</a></div>
            <div class="batch-table-wrap">
                <table class="batch-table">
                    <thead><tr><th scope="col">Term</th><th scope="col">Scope</th><th scope="col">Generated</th><th scope="col">Value</th><th scope="col">By</th><th scope="col">Status</th><th scope="col">Action</th></tr></thead>
                    <tbody>
                    @foreach($batches->take(8) as $batch)
                        <tr>
                            <td><strong>{{ optional($batch->term)->name }}</strong><div class="form-help">{{ $batch->created_at->format('d M Y H:i') }}</div></td>
                            <td>@if($batch->scope === 'class_level') {{ optional($batch->classLevel)->name }} @elseif($batch->scope === 'class_arm') {{ optional($batch->classArm)->name }} @else All Students @endif</td>
                            <td><strong>{{ $batch->generated_count }}</strong> <span class="form-help">/ {{ $batch->total_students }}</span></td>
                            <td><strong>₦{{ number_format($batch->total_value) }}</strong></td>
                            <td>{{ optional($batch->generatedBy)->name }}</td>
                            <td>@if($batch->status === 'completed')<span class="badge badge-green">Completed</span>@elseif($batch->status === 'partial')<span class="badge badge-amber">Partial</span>@else<span class="badge badge-red">Voided</span>@endif</td>
                            <td>@if($batch->status === 'completed')<form method="POST" action="{{ route('fees.generate.batch.void', $batch) }}" onsubmit="return confirm('Void this batch? All unpaid invoices in it will be deleted.')">@csrf @method('DELETE')<button type="submit" class="btn-danger-soft">Void</button></form>@endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <div>
        <div class="finance-card">
            <div class="finance-card-head"><span class="finance-card-title">Discount Templates</span></div>
            <div class="finance-card-body">
                <p class="form-help" style="margin:0 0 14px">Create reusable discounts to apply during invoice generation.</p>
                @forelse($discounts as $d)
                    <div class="discount-item"><div><div class="discount-name">{{ $d->name }}</div><div class="discount-meta">{{ $d->type === 'percentage' ? $d->value . '%' : '₦' . number_format($d->value, 2) }} off</div></div><form method="POST" action="{{ route('fees.generate.discount.destroy', $d) }}" onsubmit="return confirm('Delete this discount template?')">@csrf @method('DELETE')<button type="submit" class="icon-danger" aria-label="Delete {{ $d->name }}">✕</button></form></div>
                @empty
                    <div style="padding:18px;text-align:center;color:var(--slate-light);font-size:13px">No discount templates yet.</div>
                @endforelse

                <form method="POST" action="{{ route('fees.generate.discount.store') }}" style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border)">
                    @csrf
                    <div class="form-group"><label class="form-label" for="discountName">Template Name</label><input id="discountName" type="text" name="name" class="form-control" placeholder="e.g. Staff Ward Discount" required></div>
                    <div class="form-row"><div class="form-group"><label class="form-label" for="discountType">Type</label><select name="type" class="form-control" id="discountType"><option value="percentage">Percentage (%)</option><option value="fixed">Fixed Amount (₦)</option></select></div><div class="form-group"><label class="form-label" id="discountValueLabel" for="discountValue">Value (%)</label><input id="discountValue" type="number" name="value" class="form-control" step="0.01" min="0.01" required placeholder="50"></div></div>
                    <button type="submit" class="btn-primary-finance">Add Template</button>
                </form>
            </div>
        </div>

        <div class="finance-card">
            <div class="finance-card-head"><span class="finance-card-title">Quick Links</span></div>
            <div class="finance-card-body" style="padding:10px">
                <div class="quick-links">
                    <a href="{{ route('fees.structures') }}" class="quick-link"><div><div class="quick-link-title">Fee Structures</div><div class="quick-link-sub">Set amounts per class and term</div></div></a>
                    <a href="{{ route('fees.invoices') }}" class="quick-link"><div><div class="quick-link-title">All Invoices</div><div class="quick-link-sub">View and manage invoices</div></div></a>
                    <a href="{{ route('fees.reminders.index') }}" class="quick-link"><div><div class="quick-link-title">Fee Reminders</div><div class="quick-link-sub">Contact guardians with outstanding balances</div></div></a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.scope-tab').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.scope-tab').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const scope = this.dataset.scope;
        document.getElementById('scopeInput').value = scope;
        document.getElementById('classLevelGroup').style.display = scope === 'class_level' ? '' : 'none';
        document.getElementById('classArmGroup').style.display = scope === 'class_arm' ? '' : 'none';
        clearPreview();
    });
});

['termSelect','classLevelSelect','classArmSelect','discountSelect'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', clearPreview);
});

function clearPreview() {
    document.getElementById('previewBox').classList.remove('show');
}

document.getElementById('discountType').addEventListener('change', function () {
    document.getElementById('discountValueLabel').textContent = this.value === 'percentage' ? 'Value (%)' : 'Value (₦)';
});

async function runPreview() {
    const form = document.getElementById('generateForm');
    const data = new FormData(form);
    const loading = document.getElementById('loadingIndicator');
    const box = document.getElementById('previewBox');
    const content = document.getElementById('previewContent');

    loading.classList.add('show');
    box.classList.remove('show');

    try {
        const response = await fetch('{{ route("fees.generate.preview") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('[name=_token]').value,
                'Accept': 'application/json',
            },
            body: data,
        });
        const json = await response.json();
        loading.classList.remove('show');

        if (!response.ok) {
            content.innerHTML = `<div style="color:#B91C1C;font-size:13px">${json.error ?? 'Error loading preview'}</div>`;
            box.classList.add('show');
            return;
        }

        const itemsHtml = json.structures.map(s => `<div class="preview-item"><span>${s.name}</span><span>₦${Number(s.amount).toLocaleString()}</span></div>`).join('');
        content.innerHTML = `
            <div class="preview-items">${itemsHtml}</div>
            ${json.discount ? `<div class="preview-row"><span class="label">Discount (${json.discount})</span><span class="value" style="color:#047857">−₦${Number(json.discount_amount).toLocaleString()}</span></div>` : ''}
            <div class="preview-row"><span class="label">Amount per student</span><span class="value">₦${Number(json.final_amount).toLocaleString()}</span></div>
            <div class="preview-row"><span class="label">Students found</span><span class="value">${json.students_found}</span></div>
            <div class="preview-row"><span class="label">Existing (will skip)</span><span class="value" style="color:#B45309">${json.existing}</span></div>
            <div class="preview-row"><span class="label">Will generate</span><span class="value" style="color:#047857">${json.to_generate}</span></div>
            <div class="preview-row"><span class="label">Total invoiced value</span><span class="value">₦${Number(json.total_value).toLocaleString()}</span></div>
        `;
        box.classList.add('show');
    } catch (e) {
        loading.classList.remove('show');
        content.innerHTML = '<div style="color:#B91C1C;font-size:13px">Unable to load the preview. Check your connection and try again.</div>';
        box.classList.add('show');
        console.error(e);
    }
}

document.getElementById('generateForm').addEventListener('submit', function (e) {
    const preview = document.getElementById('previewBox');
    if (!preview.classList.contains('show')) {
        e.preventDefault();
        if (!confirm('A preview is recommended before generating invoices. Generate anyway?')) return;
        this.submit();
    }
});
</script>
@endpush