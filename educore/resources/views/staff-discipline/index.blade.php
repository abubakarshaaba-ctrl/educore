@extends('layouts.app')
@section('title','Staff Disciplinary Actions')
@section('page-title','Staff Disciplinary Actions')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:18px}
.fr{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;border-radius:8px;border:none;cursor:pointer;font-family:inherit}
.btn-p{background:var(--indigo);color:white}
.btn-sm{padding:5px 10px;font-size:11px}
.btn-danger{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA}
.btn-ghost{background:#F1F5F9;color:#475569}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94A3B8;text-align:left}
td{padding:10px 14px;border-bottom:1px solid var(--border);vertical-align:top}
tr:last-child td{border:none}
.badge{display:inline-flex;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:capitalize}
.b-warning{background:#F1F5F9;color:#475569}
.b-surcharge,.b-suspension_without_pay{background:#FFFBEB;color:#92400E}
.b-dismissal,.b-termination{background:#FEF2F2;color:#DC2626}
.b-active{background:#ECFDF5;color:#059669}
.b-rescinded{background:#F1F5F9;color:#94A3B8}
.mini{font-size:11px;color:#94A3B8}
.filter-bar{display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap}
@media(max-width:600px){.fr{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@if(session('success'))
<div style="background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;font-weight:600">✓ {{ session('success') }}</div>
@endif
@if($errors->any())
<div style="background:#FEF2F2;color:#991B1B;border:1px solid #FECACA;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px">{{ $errors->first() }}</div>
@endif

<div class="card">
    <div class="ch">Record a Disciplinary Action</div>
    <div class="cb">
        <form method="POST" action="{{ route('staff-discipline.store') }}" id="disciplineForm">
            @csrf
            <div class="fr">
                <div class="fg"><label class="fl">Staff Member *</label>
                    <select name="staff_id" class="fc" required>
                        <option value="">Select staff…</option>
                        @foreach($staffList as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fg"><label class="fl">Offence *</label>
                    <select name="offence_type" class="fc" required>
                        @foreach($offenceTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="fg"><label class="fl">Offence Description</label><textarea name="offence_description" class="fc" rows="2"></textarea></div>

            <div class="fr">
                <div class="fg"><label class="fl">Action Taken *</label>
                    <select name="action_type" class="fc" id="actionType" required onchange="toggleActionFields()">
                        @foreach($actionTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fg"><label class="fl">Effective Date *</label><input type="date" name="effective_date" class="fc" required></div>
            </div>

            <div class="fg" id="amountField" style="display:none">
                <label class="fl">Surcharge Amount (₦) *</label>
                <input type="number" name="amount" class="fc" min="0" step="0.01">
            </div>

            <div class="fr" id="suspensionFields" style="display:none">
                <div class="fg"><label class="fl">Suspension Start *</label><input type="date" name="suspension_start_date" class="fc"></div>
                <div class="fg"><label class="fl">Suspension End *</label><input type="date" name="suspension_end_date" class="fc"></div>
            </div>
            <div class="mini" id="suspensionHint" style="display:none;margin:-6px 0 12px">
                The withheld-pay amount is calculated automatically from the staff member's salary settings (gross ÷ 30 × suspended days) and linked to their payroll as a deduction.
            </div>

            <div class="mini" id="exitHint" style="display:none;margin:-6px 0 12px">
                This will also end the staff member's employment (employment status → Terminated) using the standard staff lifecycle workflow, including its usual safeguards (requires lifecycle permissions, cannot remove the last active administrator, etc).
            </div>

            <div class="fg"><label class="fl">Notes</label><textarea name="notes" class="fc" rows="2" placeholder="Additional context, references, disciplinary panel decision, etc."></textarea></div>

            <button type="submit" class="btn btn-p">Record Action</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="ch">
        <form method="GET" class="filter-bar" style="margin:0">
            <select name="action_type" class="fc" style="max-width:220px" onchange="this.form.submit()">
                <option value="">All Actions</option>
                @foreach($actionTypes as $key => $label)
                <option value="{{ $key }}" {{ request('action_type')===$key?'selected':'' }}>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <div style="overflow-x:auto"><table>
        <thead><tr><th>Staff</th><th>Offence</th><th>Action</th><th>Financial Impact</th><th>Status</th><th>Recorded</th><th></th></tr></thead>
        <tbody>
        @forelse($actions as $a)
        <tr>
            <td style="font-weight:600">{{ $a->staff->name ?? '—' }}</td>
            <td>
                {{ $a->offenceLabel() }}
                @if($a->offence_description)<div class="mini">{{ \Illuminate\Support\Str::limit($a->offence_description, 60) }}</div>@endif
            </td>
            <td><span class="badge b-{{ $a->action_type }}">{{ $a->actionLabel() }}</span></td>
            <td class="mini">
                @if($a->isFinanceLinked())
                    ₦{{ number_format($a->amount ?? 0, 2) }}
                    @if($a->action_type === 'suspension_without_pay' && $a->suspension_start_date)
                        <div>{{ $a->suspension_start_date->format('d M') }} – {{ $a->suspension_end_date->format('d M Y') }}</div>
                    @endif
                    @if($a->staffDeduction)
                        <div>{{ $a->staffDeduction->is_active ? '⏳ Applying to payroll' : '✓ Applied / stopped' }}</div>
                    @endif
                @elseif($a->isEmploymentExit())
                    Employment ended
                @else
                    —
                @endif
            </td>
            <td><span class="badge b-{{ $a->status }}">{{ ucfirst($a->status) }}</span></td>
            <td class="mini">{{ $a->recordedBy->name ?? '—' }}<br>{{ $a->created_at->format('d M Y') }}</td>
            <td>
                @if($a->status === 'active')
                <div style="display:flex;flex-direction:column;gap:4px">
                    @if($a->staffDeduction && $a->staffDeduction->is_active)
                    <form method="POST" action="{{ route('staff-discipline.deactivate-deduction', $a) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-ghost btn-sm" style="width:100%">Stop Deduction</button>
                    </form>
                    @endif
                    <form method="POST" action="{{ route('staff-discipline.rescind', $a) }}" onsubmit="return confirm('Rescind this disciplinary action?')">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-danger btn-sm" style="width:100%">Rescind</button>
                    </form>
                </div>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:30px;color:#94A3B8">No disciplinary actions recorded yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="padding:14px">{{ $actions->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
function toggleActionFields() {
    const type = document.getElementById('actionType').value;
    document.getElementById('amountField').style.display = type === 'surcharge' ? 'block' : 'none';
    document.getElementById('suspensionFields').style.display = type === 'suspension_without_pay' ? 'grid' : 'none';
    document.getElementById('suspensionHint').style.display = type === 'suspension_without_pay' ? 'block' : 'none';
    document.getElementById('exitHint').style.display = (type === 'dismissal' || type === 'termination') ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded', toggleActionFields);
</script>
@endpush
