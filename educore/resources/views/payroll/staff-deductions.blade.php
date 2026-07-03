@extends('layouts.app')
@section('title','Staff Deductions')
@section('page-title','Staff Deductions')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:10px;border-bottom:1px solid var(--border);font-size:12.5px;vertical-align:top}
tbody tr:last-child td{border-bottom:none}
.fc{padding:7px 9px;font-size:12px;font-family:inherit;border:1px solid var(--border);border-radius:7px;background:#F8FAFC;outline:none;width:100%;transition:border 200ms}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-sm{padding:4px 10px;font-size:11px}
.btn-del{background:#FEF2F2;color:#DC2626}
.back{font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
.alert-e{background:#FEF2F2;border:1px solid #FCA5A5;border-radius:8px;padding:12px 16px;font-size:13px;color:#991B1B;margin-bottom:14px}
.chip{display:inline-flex;align-items:center;gap:6px;background:#F1F5F9;border-radius:6px;padding:4px 9px;font-size:11px;font-weight:600;color:var(--midnight);margin:2px 4px 2px 0}
.chip form{display:inline}
.chip button{background:none;border:none;color:#DC2626;cursor:pointer;font-size:13px;line-height:1;padding:0;margin-left:2px}
.empty{color:var(--slate-light);font-size:11px;font-style:italic}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-e">{{ $errors->first() }}</div>@endif
<a href="{{ route('payroll.salary') }}" class="back">← Back to Salary Settings</a>

@if($templates->isEmpty())
<div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:10px;padding:16px 18px;margin-bottom:16px;display:flex;align-items:start;gap:14px">
    <div style="font-size:24px;flex-shrink:0">💡</div>
    <div>
        <div style="font-size:13px;font-weight:700;color:#92400E;margin-bottom:4px">No deduction templates yet</div>
        <div style="font-size:12px;color:#B45309;line-height:1.6">
            Before you can assign deductions to staff, you need to create at least one deduction template
            (e.g. "School Loan", "Cooperative Contribution", "Child School Fees").
        </div>
        <a href="{{ route('payroll.templates') }}"
           style="display:inline-flex;align-items:center;gap:6px;margin-top:10px;padding:8px 16px;background:#D97706;color:white;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none">
            ✂️ Go to Payroll → Templates to create one →
        </a>
    </div>
</div>
@endif

<div class="card">
    <div class="ch">Peculiar Deductions — School Loans, Cooperative, Child School Fees, etc.</div>
    <div class="tbl"><table>
        <thead><tr><th style="width:18%">Staff</th><th style="width:30%">Currently Assigned</th><th style="width:42%">Assign New Deduction</th></tr></thead>
        <tbody>
        @forelse($staff as $s)
        <tr>
            <td><strong>{{ $s->name }}</strong><div style="font-size:10px;color:var(--slate-light);text-transform:capitalize">{{ str_replace('_',' ',$s->role) }}</div></td>
            <td>
                @forelse($assigned->get($s->id, collect()) as $d)
                    <span class="chip">
                        {{ optional($d->template)->name ?? 'Deduction' }}
                        @if($d->custom_amount !== null)
                            (₦{{ number_format($d->custom_amount, 2) }})
                        @endif
                        @if($d->notes)
                            — {{ $d->notes }}
                        @endif
                        <form method="POST" action="{{ route('payroll.staff-deductions.destroy', $d) }}" onsubmit="return confirm('Remove this deduction?')">
                            @csrf @method('DELETE')
                            <button type="submit" title="Remove">×</button>
                        </form>
                    </span>
                @empty
                    <span class="empty">No peculiar deductions assigned.</span>
                @endforelse
            </td>
            <td>
                @if($templates->isEmpty())
                    <span class="empty">No deduction templates yet — create one in Payroll → Templates first.</span>
                @else
                <form method="POST" action="{{ route('payroll.staff-deductions.store') }}" style="display:flex;gap:6px;align-items:start">
                    @csrf
                    <input type="hidden" name="staff_id" value="{{ $s->id }}">
                    <select name="payroll_deduction_template_id" class="fc" style="width:130px" required>
                        <option value="">Select…</option>
                        @foreach($templates as $t)
                            <option value="{{ $t->id }}">{{ $t->label() }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="custom_amount" class="fc" placeholder="₦ override (optional)" step="0.01" min="0" style="width:130px">
                    <input type="text" name="notes" class="fc" placeholder="Note (optional)" maxlength="150" style="width:120px">
                    <button type="submit" class="btn btn-p btn-sm">Assign</button>
                </form>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="3" class="empty">No payroll-eligible staff found.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
<div style="font-size:12px;color:var(--slate-light)">
    Leave "₦ override" blank to use the template's own percentage/fixed amount. Set it to give this specific staff member a different amount on the same deduction (e.g. two staff both on "School Loan" repaying different balances).
</div>
@endsection
