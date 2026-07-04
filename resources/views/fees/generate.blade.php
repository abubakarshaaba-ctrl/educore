@extends('layouts.app')
@section('title', 'Invoice Generation')
@section('page-title', 'Invoice Generation')

@push('styles')
<style>
.gen-grid { display:grid; grid-template-columns:1fr 380px; gap:20px; align-items:start; }
.card { background:white; border:1px solid var(--border); border-radius:12px; overflow:hidden; }
.card-head { padding:13px 18px; border-bottom:1px solid var(--border); background:#F8FAFC;
    display:flex; align-items:center; justify-content:space-between; }
.card-title { font-size:13px; font-weight:700; color:var(--midnight); }
.card-body { padding:20px; }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.form-group { margin-bottom:16px; }
.form-label { display:block; font-size:11px; font-weight:700; text-transform:uppercase;
    letter-spacing:.05em; color:var(--slate-light); margin-bottom:5px; }
.form-control { width:100%; padding:9px 12px; font-size:13px; font-family:inherit;
    border:1.5px solid var(--border); border-radius:8px; background:#F8FAFC;
    transition:border-color 150ms; outline:none; }
.form-control:focus { border-color:var(--indigo); background:white; }
select.form-control { cursor:pointer; }
.btn { display:inline-flex; align-items:center; gap:6px; padding:10px 18px; font-size:13px;
    font-weight:600; font-family:inherit; border:none; border-radius:8px; cursor:pointer; transition:all 150ms; }
.btn-primary { background:var(--indigo); color:white; }
.btn-primary:hover { background:#1946C0; }
.btn-success { background:#059669; color:white; }
.btn-success:hover { background:#047857; }
.btn-danger { background:#EF4444; color:white; font-size:12px; padding:7px 14px; }
.btn-danger:hover { background:#DC2626; }
.btn-ghost { background:#F1F5F9; color:var(--slate); }
.btn-ghost:hover { background:#E2E8F0; }
.btn-full { width:100%; justify-content:center; }

/* Summary cards */
.sum-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:20px; }
.sum-card { background:#F8FAFC; border:1px solid var(--border); border-radius:10px;
    padding:12px 14px; text-align:center; }
.sum-val { font-size:22px; font-weight:800; color:var(--midnight); letter-spacing:-0.03em; }
.sum-val.green { color:#059669; } .sum-val.red { color:#EF4444; } .sum-val.amber { color:#D97706; }
.sum-label { font-size:10px; font-weight:700; text-transform:uppercase;
    letter-spacing:.06em; color:var(--slate-light); margin-top:3px; }

/* Preview box */
.preview-box { background:#EFF6FF; border:1.5px solid #BFDBFE; border-radius:12px;
    padding:18px; margin-bottom:20px; display:none; }
.preview-box.show { display:block; }
.preview-row { display:flex; justify-content:space-between; align-items:center;
    font-size:13px; padding:5px 0; border-bottom:1px solid rgba(0,0,0,0.06); }
.preview-row:last-child { border:none; font-weight:700; font-size:14px; padding-top:10px; }
.preview-row .label { color:#334155; }
.preview-row .value { font-weight:600; color:var(--midnight); }
.preview-items { margin:10px 0; }
.preview-item { font-size:12px; color:#475569; padding:3px 0;
    display:flex; justify-content:space-between; }

/* Scope toggle */
.scope-tabs { display:flex; gap:6px; margin-bottom:16px; }
.scope-tab { padding:7px 14px; font-size:12px; font-weight:600; border-radius:8px;
    border:1.5px solid var(--border); background:white; cursor:pointer;
    color:var(--slate); transition:all 150ms; font-family:inherit; }
.scope-tab.active { background:var(--indigo); border-color:var(--indigo); color:white; }

/* Batch history table */
.batch-table { width:100%; border-collapse:collapse; font-size:13px; }
.batch-table th { padding:9px 12px; text-align:left; font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.05em; color:var(--slate-light);
    border-bottom:1px solid var(--border); background:#F8FAFC; }
.batch-table td { padding:10px 12px; border-bottom:1px solid var(--border); }
.batch-table tr:last-child td { border:none; }
.batch-table tr:hover td { background:#F8FAFC; }
.badge { display:inline-flex; font-size:11px; font-weight:600; padding:2px 9px;
    border-radius:20px; }
.badge-green { background:#ECFDF5; color:#059669; }
.badge-red { background:#FEF2F2; color:#DC2626; }
.badge-amber { background:#FFFBEB; color:#D97706; }

.alert-success { background:#ECFDF5; border:1px solid #A7F3D0; border-radius:10px;
    padding:12px 16px; font-size:13px; color:#059669; margin-bottom:16px;
    display:flex; align-items:center; gap:8px; }
.alert-error { background:#FEF2F2; border:1px solid #FECACA; border-radius:10px;
    padding:12px 16px; font-size:13px; color:#DC2626; margin-bottom:16px; }

.loading { display:none; align-items:center; gap:8px; font-size:13px; color:var(--slate); }
.loading.show { display:flex; }
.spinner { width:16px; height:16px; border:2px solid var(--border);
    border-top-color:var(--indigo); border-radius:50%; animation:spin 600ms linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }

@media(max-width:900px){ .gen-grid { grid-template-columns:1fr; } .form-row { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')

@if(session('success'))
<div class="alert-success">&#10003; {{ session('success') }}</div>
@endif

@if($errors->any())
<div class="alert-error">{{ $errors->first() }}</div>
@endif

{{-- ── Current term summary ────────────────────────────────────────── --}}
@if($summary)
<div class="sum-grid" style="margin-bottom:20px">
    <div class="sum-card">
        <div class="sum-val">{{ number_format($summary['total_invoices']) }}</div>
        <div class="sum-label">{{ $summary['term']->name }} Invoices</div>
    </div>
    <div class="sum-card">
        <div class="sum-val">₦{{ number_format($summary['total_value']/1000, 1) }}k</div>
        <div class="sum-label">Total Value</div>
    </div>
    <div class="sum-card">
        <div class="sum-val green">{{ $summary['paid'] }}</div>
        <div class="sum-label">Fully Paid</div>
    </div>
    <div class="sum-card">
        <div class="sum-val amber">{{ $summary['partial'] }}</div>
        <div class="sum-label">Partially Paid</div>
    </div>
    <div class="sum-card">
        <div class="sum-val red">{{ $summary['unpaid'] }}</div>
        <div class="sum-label">Unpaid</div>
    </div>
    <div class="sum-card">
        <div class="sum-val red">{{ $summary['overdue'] }}</div>
        <div class="sum-label">Overdue</div>
    </div>
</div>
@endif

<div class="gen-grid">

    {{-- ── LEFT: Generation Form ─────────────────────────────────────── --}}
    <div>
        <form method="POST" action="{{ route('fees.generate.store') }}" id="generateForm">
        @csrf
        <div class="card">
            <div class="card-head">
                <span class="card-title">&#9889; Generate Invoices</span>
                <a href="{{ route('fees.generate.batches') }}" class="btn btn-ghost" style="font-size:12px;padding:6px 12px">View History</a>
            </div>
            <div class="card-body">

                {{-- Term --}}
                <div class="form-group">
                    <label class="form-label">Academic Term *</label>
                    <select name="term_id" id="termSelect" class="form-control" required>
                        <option value="">— Select Term —</option>
                        @foreach($terms as $term)
                        <option value="{{ $term->id }}" {{ $term->is_current ? 'selected' : '' }}>
                            {{ $term->name }} — {{ optional($term->session)->name }}
                            {{ $term->is_current ? '(Current)' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Scope tabs --}}
                <div class="form-group">
                    <label class="form-label">Generation Scope</label>
                    <div class="scope-tabs">
                        <button type="button" class="scope-tab active" data-scope="class_level">By Class Level</button>
                        <button type="button" class="scope-tab" data-scope="class_arm">By Class Arm</button>
                        <button type="button" class="scope-tab" data-scope="all">All Students</button>
                    </div>
                    <input type="hidden" name="scope" id="scopeInput" value="class_level">
                </div>

                {{-- Class Level selector --}}
                <div class="form-group" id="classLevelGroup">
                    <label class="form-label">Class Level *</label>
                    <select name="class_level_id" id="classLevelSelect" class="form-control">
                        <option value="">— All Levels —</option>
                        @foreach($classLevels as $level)
                        <option value="{{ $level->id }}">{{ $level->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Class Arm selector --}}
                <div class="form-group" id="classArmGroup" style="display:none">
                    <label class="form-label">Class Arm *</label>
                    <select name="class_arm_id" id="classArmSelect" class="form-control">
                        <option value="">— Select Class Arm —</option>
                        @foreach($classLevels as $level)
                            @foreach($level->classArms as $arm)
                            <option value="{{ $arm->id }}">{{ $level->name }} {{ $arm->name }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>

                <div class="form-row">
                    {{-- Due date --}}
                    <div class="form-group">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control"
                               value="{{ date('Y-m-d', strtotime('+30 days')) }}">
                        <div style="font-size:11px;color:var(--slate-light);margin-top:3px">
                            Defaults to term end date if blank
                        </div>
                    </div>

                    {{-- Discount --}}
                    <div class="form-group">
                        <label class="form-label">Discount Template</label>
                        <select name="discount_id" id="discountSelect" class="form-control">
                            <option value="">— No Discount —</option>
                            @foreach($discounts as $d)
                            <option value="{{ $d->id }}">{{ $d->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Notes (optional)</label>
                    <input type="text" name="notes" class="form-control" maxlength="500"
                           placeholder="e.g. 2024/25 First Term fees">
                </div>

                <div class="form-group" style="display:flex;align-items:center;gap:8px">
                    <input type="checkbox" name="overwrite" value="1" id="overwriteCheck" style="accent-color:var(--indigo)">
                    <label for="overwriteCheck" style="font-size:13px;color:#334155;cursor:pointer">
                        Replace existing unpaid invoices for this term
                    </label>
                </div>

                {{-- Preview result --}}
                <div class="preview-box" id="previewBox">
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#1D4ED8;margin-bottom:12px">
                        Preview — What will be generated
                    </div>
                    <div id="previewContent"></div>
                </div>

                <div class="loading" id="loadingIndicator">
                    <div class="spinner"></div> Calculating preview…
                </div>

                <div style="display:flex;gap:10px;margin-top:4px">
                    <button type="button" class="btn btn-ghost" onclick="runPreview()">
                        &#128269; Preview
                    </button>
                    <button type="submit" class="btn btn-success" style="flex:1;justify-content:center" id="generateBtn">
                        &#9889; Generate Invoices
                    </button>
                </div>
            </div>
        </div>
        </form>

        {{-- ── Recent Batches ───────────────────────────────────────────── --}}
        @if($batches->count())
        <div class="card" style="margin-top:20px">
            <div class="card-head">
                <span class="card-title">&#128196; Recent Generation Batches</span>
                <a href="{{ route('fees.generate.batches') }}" style="font-size:12px;color:var(--indigo);text-decoration:none">View All</a>
            </div>
            <div style="overflow-x:auto">
                <table class="batch-table">
                    <thead>
                        <tr>
                            <th>Term</th>
                            <th>Scope</th>
                            <th>Generated</th>
                            <th>Value</th>
                            <th>By</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($batches->take(8) as $batch)
                    <tr>
                        <td>
                            <div style="font-weight:600">{{ optional($batch->term)->name }}</div>
                            <div style="font-size:11px;color:var(--slate-light)">{{ $batch->created_at->format('d M Y H:i') }}</div>
                        </td>
                        <td style="font-size:12px">
                            @if($batch->scope === 'class_level') 📚 {{ optional($batch->classLevel)->name }}
                            @elseif($batch->scope === 'class_arm') 🏫 {{ optional($batch->classArm)->name }}
                            @else 🌍 All Students
                            @endif
                        </td>
                        <td>
                            <span style="font-weight:700">{{ $batch->generated_count }}</span>
                            <span style="font-size:11px;color:var(--slate-light)">/ {{ $batch->total_students }}</span>
                        </td>
                        <td style="font-weight:600">₦{{ number_format($batch->total_value) }}</td>
                        <td style="font-size:12px">{{ optional($batch->generatedBy)->name }}</td>
                        <td>
                            @if($batch->status === 'completed')
                                <span class="badge badge-green">Done</span>
                            @elseif($batch->status === 'partial')
                                <span class="badge badge-amber">Partial</span>
                            @else
                                <span class="badge badge-red">Voided</span>
                            @endif
                        </td>
                        <td>
                            @if($batch->status === 'completed')
                            <form method="POST" action="{{ route('fees.generate.batch.void', $batch) }}"
                                  onsubmit="return confirm('Void this batch? All unpaid invoices in it will be deleted.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger" style="padding:5px 10px;font-size:11px">Void</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    {{-- ── RIGHT: Discount Templates ──────────────────────────────────── --}}
    <div>
        <div class="card">
            <div class="card-head">
                <span class="card-title">&#127873; Discount Templates</span>
            </div>
            <div class="card-body">
                <p style="font-size:12px;color:var(--slate-light);margin-bottom:14px">
                    Create reusable discounts (e.g. Staff Ward 50%, Scholarship ₦20,000) to apply during invoice generation.
                </p>

                {{-- Existing discounts --}}
                @forelse($discounts as $d)
                <div style="display:flex;align-items:center;justify-content:space-between;
                    padding:10px 12px;border:1px solid var(--border);border-radius:9px;margin-bottom:8px">
                    <div>
                        <div style="font-size:13px;font-weight:600">{{ $d->name }}</div>
                        <div style="font-size:11px;color:var(--slate-light)">
                            {{ $d->type === 'percentage' ? $d->value . '%' : '₦' . number_format($d->value, 2) }} off
                        </div>
                    </div>
                    <form method="POST" action="{{ route('fees.generate.discount.destroy', $d) }}"
                          onsubmit="return confirm('Delete this discount template?')">
                        @csrf @method('DELETE')
                        <button type="submit" style="background:none;border:none;cursor:pointer;color:#94A3B8;font-size:16px;padding:4px">✕</button>
                    </form>
                </div>
                @empty
                <div style="text-align:center;padding:20px;color:var(--slate-light);font-size:13px">
                    No discount templates yet
                </div>
                @endforelse

                {{-- Add new --}}
                <form method="POST" action="{{ route('fees.generate.discount.store') }}" style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border)">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Template Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Staff Ward Discount" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-control" id="discountType">
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (₦)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" id="discountValueLabel">Value (%)</label>
                            <input type="number" name="value" class="form-control"
                                   step="0.01" min="0.01" required placeholder="50">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">+ Add Template</button>
                </form>
            </div>
        </div>

        {{-- Quick links --}}
        <div class="card" style="margin-top:16px">
            <div class="card-head"><span class="card-title">&#128279; Quick Links</span></div>
            <div class="card-body" style="padding:12px">
                <a href="{{ route('fees.structures') }}" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;text-decoration:none;color:var(--midnight);transition:background 150ms" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='none'">
                    <span style="font-size:18px">📐</span>
                    <div>
                        <div style="font-size:13px;font-weight:600">Fee Structures</div>
                        <div style="font-size:11px;color:var(--slate-light)">Set amounts per class per term</div>
                    </div>
                </a>
                <a href="{{ route('fees.invoices') }}" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;text-decoration:none;color:var(--midnight);transition:background 150ms" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='none'">
                    <span style="font-size:18px">🧾</span>
                    <div>
                        <div style="font-size:13px;font-weight:600">All Invoices</div>
                        <div style="font-size:11px;color:var(--slate-light)">View and manage invoices</div>
                    </div>
                </a>
                <a href="{{ route('fees.reminders.index') }}" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;text-decoration:none;color:var(--midnight);transition:background 150ms" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='none'">
                    <span style="font-size:18px">📬</span>
                    <div>
                        <div style="font-size:13px;font-weight:600">Fee Reminders</div>
                        <div style="font-size:11px;color:var(--slate-light)">Send SMS to outstanding debtors</div>
                    </div>
                </a>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
// ── Scope tabs ────────────────────────────────────────────────────────
document.querySelectorAll('.scope-tab').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.scope-tab').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const scope = this.dataset.scope;
        document.getElementById('scopeInput').value = scope;
        document.getElementById('classLevelGroup').style.display = scope === 'class_level' ? '' : 'none';
        document.getElementById('classArmGroup').style.display   = scope === 'class_arm' ? '' : 'none';
        clearPreview();
    });
});

// ── Clear preview on any change ───────────────────────────────────────
['termSelect','classLevelSelect','classArmSelect','discountSelect'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', clearPreview);
});

function clearPreview() {
    document.getElementById('previewBox').classList.remove('show');
}

// ── Discount type label ────────────────────────────────────────────────
document.getElementById('discountType').addEventListener('change', function () {
    document.getElementById('discountValueLabel').textContent =
        this.value === 'percentage' ? 'Value (%)' : 'Value (₦)';
});

// ── AJAX Preview ──────────────────────────────────────────────────────
async function runPreview() {
    const form     = document.getElementById('generateForm');
    const data     = new FormData(form);
    const loading  = document.getElementById('loadingIndicator');
    const box      = document.getElementById('previewBox');

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
            document.getElementById('previewContent').innerHTML =
                `<div style="color:#DC2626;font-size:13px">⚠️ ${json.error ?? 'Error loading preview'}</div>`;
            box.classList.add('show');
            return;
        }

        // Render preview
        let itemsHtml = json.structures.map(s =>
            `<div class="preview-item"><span>${s.name}</span><span>₦${Number(s.amount).toLocaleString()}</span></div>`
        ).join('');

        document.getElementById('previewContent').innerHTML = `
            <div class="preview-items">${itemsHtml}</div>
            ${json.discount ? `<div class="preview-row"><span class="label">Discount (${json.discount})</span><span class="value" style="color:#059669">−₦${Number(json.discount_amount).toLocaleString()}</span></div>` : ''}
            <div class="preview-row"><span class="label">Amount per student</span><span class="value">₦${Number(json.final_amount).toLocaleString()}</span></div>
            <div class="preview-row"><span class="label">Students found</span><span class="value">${json.students_found}</span></div>
            <div class="preview-row"><span class="label">Existing (will skip)</span><span class="value" style="color:#D97706">${json.existing}</span></div>
            <div class="preview-row"><span class="label">Will generate</span><span class="value" style="color:#059669">${json.to_generate}</span></div>
            <div class="preview-row"><span class="label">Total invoiced value</span><span class="value">₦${Number(json.total_value).toLocaleString()}</span></div>
        `;
        box.classList.add('show');

    } catch (e) {
        loading.classList.remove('show');
        console.error(e);
    }
}

// Confirm before generating
document.getElementById('generateForm').addEventListener('submit', function (e) {
    const preview = document.getElementById('previewBox');
    if (!preview.classList.contains('show')) {
        e.preventDefault();
        if (!confirm('Run preview first is recommended. Generate anyway?')) return;
        this.submit();
    }
});
</script>
@endpush
