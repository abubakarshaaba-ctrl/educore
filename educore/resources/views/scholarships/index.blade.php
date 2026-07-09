@extends('layouts.app')
@section('title','Scholarships & Bursaries')
@section('page-title','Scholarships & Fee Waivers')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:18px}
.fr{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;border-radius:8px;border:none;cursor:pointer;font-family:inherit}
.btn-p{background:var(--indigo);color:white}
.btn-danger{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;padding:5px 10px;font-size:11px}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94A3B8;text-align:left}
td{padding:10px 14px;border-bottom:1px solid var(--border)}
tr:last-child td{border:none}
.badge{display:inline-flex;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:capitalize}
.b-active{background:#ECFDF5;color:#059669}.b-revoked{background:#FEF2F2;color:#DC2626}.b-expired{background:#F1F5F9;color:#475569}
@media(max-width:800px){.fr{grid-template-columns:1fr 1fr}}
@media(max-width:560px){.fr{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<div class="card">
    <div class="ch">Award Scholarship / Fee Waiver</div>
    <div class="cb">
        <form method="POST" action="{{ route('scholarships.store') }}">
            @csrf
            <div class="fr">
                <div class="fg"><label class="fl">Student *</label>
                    <select name="student_id" class="fc" required>
                        <option value="">Select student...</option>
                        @foreach($students as $s)
                        <option value="{{ $s->id }}">{{ $s->full_name }} ({{ $s->admission_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="fg"><label class="fl">Name *</label>
                    <input type="text" name="name" class="fc" placeholder="e.g. Merit Scholarship 2026" required>
                </div>
                <div class="fg"><label class="fl">Type *</label>
                    <select name="type" class="fc" required onchange="document.getElementById('valBox').style.display=this.value==='full_waiver'?'none':'block'">
                        <option value="percentage">Percentage discount</option>
                        <option value="fixed_amount">Fixed amount (₦)</option>
                        <option value="full_waiver">Full fee waiver</option>
                    </select>
                </div>
                <div class="fg" id="valBox"><label class="fl">Value</label>
                    <input type="number" step="0.01" name="value" class="fc" placeholder="e.g. 50 (for 50%) or 20000">
                </div>
                <div class="fg"><label class="fl">Starts</label><input type="date" name="starts_at" class="fc"></div>
                <div class="fg"><label class="fl">Ends</label><input type="date" name="ends_at" class="fc"></div>
            </div>
            <div class="fg"><label class="fl">Reason</label><textarea name="reason" class="fc" rows="2"></textarea></div>
            <button type="submit" class="btn btn-p">Award Scholarship</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="ch">All Scholarships</div>
    <div style="overflow-x:auto"><table>
        <thead><tr><th>Student</th><th>Name</th><th>Type</th><th>Value</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($scholarships as $sc)
        <tr>
            <td>{{ optional($sc->student)->full_name }}</td>
            <td>{{ $sc->name }}</td>
            <td>{{ ucwords(str_replace('_',' ', $sc->type)) }}</td>
            <td>{{ $sc->type === 'percentage' ? $sc->value.'%' : ($sc->type === 'full_waiver' ? 'Full waiver' : '₦'.number_format($sc->value,2)) }}</td>
            <td><span class="badge b-{{ $sc->status }}">{{ ucfirst($sc->status) }}</span></td>
            <td>
                @if($sc->status === 'active')
                <form method="POST" action="{{ route('scholarships.revoke', $sc) }}" onsubmit="return confirm('Revoke this scholarship?')">
                    @csrf @method('PATCH')
                    <button class="btn btn-danger">Revoke</button>
                </form>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;padding:30px;color:#94A3B8">No scholarships recorded yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="padding:14px">{{ $scholarships->links() }}</div>
</div>
@endsection
