@extends('layouts.super')
@section('title','Agent — '.$agent->name)
@section('page-title','Agent Details')

@push('styles')
<style>
.breadcrumb{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--slate-light);margin-bottom:20px}
.breadcrumb a{color:var(--indigo);text-decoration:none;font-weight:500}
.breadcrumb svg{width:14px;height:14px}
.two-col{display:grid;grid-template-columns:280px 1fr;gap:16px;align-items:start}
.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px}
.stat-card{background:white;border:1px solid var(--border);border-radius:10px;padding:14px 16px}
.stat-val{font-size:20px;font-weight:800;color:var(--midnight)}
.stat-lbl{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;margin-top:2px}
.card{background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.05);overflow:hidden;margin-bottom:14px}
.card-header{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between}
.card-title{font-size:13px;font-weight:700;color:var(--midnight)}
.card-body{padding:18px}
.info-row{display:flex;justify-content:space-between;align-items:center;padding:10px 18px;border-bottom:1px solid var(--border);font-size:13px}
.info-row:last-child{border-bottom:none}
.info-key{color:var(--slate);font-size:12px}
.info-val{font-weight:600;color:var(--midnight)}
.agent-av{width:56px;height:56px;border-radius:50%;background:var(--indigo);color:white;font-size:22px;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 10px}
table{width:100%;border-collapse:collapse}
thead th{font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:10px 16px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:11px 16px;border-bottom:1px solid var(--border);font-size:13px;color:var(--midnight);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover td{background:#F8FAFC}
.badge{display:inline-flex;font-size:11px;font-weight:600;padding:3px 8px;border-radius:20px}
.badge-on{background:#ECFDF5;color:var(--emerald)}
.badge-off{background:#FEF2F2;color:var(--crimson)}
.badge-pending{background:#FFFBEB;color:var(--amber)}
.badge-approved{background:#ECFDF5;color:var(--emerald)}
.badge-paid{background:#EFF6FF;color:var(--indigo)}
.badge-rejected{background:#FEF2F2;color:var(--crimson)}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-primary{background:var(--indigo);color:white}.btn-primary:hover{background:#1D4ED8}
.btn-sm{padding:5px 10px;font-size:11px}
.btn-success{background:var(--emerald);color:white}
.btn-ghost{background:white;color:var(--midnight);border:1px solid var(--border)}
.btn-danger{background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA}
.form-group{margin-bottom:14px}
.form-label{display:block;font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px}
.form-control{width:100%;padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:7px;background:#F8FAFC;outline:none;transition:border-color 200ms}
.form-control:focus{border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,.1);background:white}
.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px}
.alert-error{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--crimson);margin-bottom:16px}
@media(max-width:900px){.two-col{grid-template-columns:1fr}.stats-row{grid-template-columns:1fr 1fr}}
</style>
@endpush

@section('content')
<div class="breadcrumb">
    <a href="{{ route('super.agents.index') }}">Agents</a>
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
    {{ $agent->name }}
</div>

@if(session('success'))<div class="alert-success">✓ {{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

<div class="two-col">
    {{-- LEFT: Agent Profile --}}
    <div>
        <div class="card">
            <div style="padding:22px;text-align:center;border-bottom:1px solid var(--border)">
                <div class="agent-av">{{ strtoupper(substr($agent->name, 0, 1)) }}</div>
                <div style="font-size:16px;font-weight:700;color:var(--midnight)">{{ $agent->name }}</div>
                <div style="font-size:12px;color:var(--slate-light);margin-top:3px">{{ $agent->email }}</div>
                <div style="margin-top:8px">
                    @if(!$agent->password)
                        <span class="badge badge-pending">Pending Activation</span>
                    @elseif($agent->is_active)
                        <span class="badge badge-on">Active</span>
                    @else
                        <span class="badge badge-off">Inactive</span>
                    @endif
                </div>
            </div>
            <div class="info-row">
                <span class="info-key">Referral Code</span>
                <span class="info-val" style="font-family:monospace;letter-spacing:.1em;color:var(--indigo)">{{ $agent->referral_code }}</span>
            </div>
            <div class="info-row">
                <span class="info-key">Referral Link</span>
                <span class="info-val" style="max-width:220px;text-align:right;word-break:break-all">
                    <a href="{{ $agent->referralLink() }}" target="_blank" rel="noopener" style="color:var(--indigo);text-decoration:none">{{ $agent->referralLink() }}</a>
                </span>
            </div>
            <div class="info-row">
                <span class="info-key">Commission Rate</span>
                <span class="info-val" style="color:var(--emerald)">{{ $agent->commission_rate }}%</span>
            </div>
            <div class="info-row"><span class="info-key">Phone</span><span class="info-val">{{ $agent->phone ?? '—' }}</span></div>
            <div class="info-row"><span class="info-key">State</span><span class="info-val">{{ $agent->state ?? '—' }}</span></div>
            <div class="info-row"><span class="info-key">Total Earned</span><span class="info-val" style="color:var(--emerald)">₦{{ number_format($agent->total_earned) }}</span></div>
            <div class="info-row"><span class="info-key">Total Paid</span><span class="info-val">₦{{ number_format($agent->total_paid) }}</span></div>
            <div class="info-row"><span class="info-key">Unpaid Balance</span><span class="info-val" style="color:var(--crimson)">₦{{ number_format($agent->unpaidBalance()) }}</span></div>
            @if($agent->bank_name)
            <div class="info-row"><span class="info-key">Bank</span><span class="info-val">{{ $agent->bank_name }}</span></div>
            <div class="info-row"><span class="info-key">Account No.</span><span class="info-val" style="font-family:monospace">{{ $agent->bank_account_number }}</span></div>
            <div class="info-row"><span class="info-key">Account Name</span><span class="info-val">{{ $agent->bank_account_name }}</span></div>
            @endif
        </div>

        {{-- Actions --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Actions</span></div>
            <div class="card-body">
                {{-- Record Payout --}}
                @if($agent->unpaidBalance() > 0)
                <div class="form-group" style="border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:14px">
                    <div style="font-size:12px;font-weight:700;color:var(--midnight);margin-bottom:10px">💳 Record Payout</div>
                    <form method="POST" action="{{ route('super.agents.pay', $agent) }}">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Amount (₦) — Max ₦{{ number_format($agent->unpaidBalance()) }}</label>
                            <input type="number" name="amount" class="form-control" max="{{ $agent->unpaidBalance() }}" min="1" required>
                        </div>
                        <div class="form-group" style="margin-bottom:10px">
                            <label class="form-label">Reference / Note</label>
                            <input type="text" name="note" class="form-control" placeholder="Transfer reference...">
                        </div>
                        <button type="submit" class="btn btn-success" style="width:100%;justify-content:center">Record Payment</button>
                    </form>
                </div>
                @endif

                {{-- Toggle status --}}
                @if($agent->password)
                <form method="POST" action="{{ route('super.agents.toggle', $agent) }}" style="margin-bottom:8px">
                    @csrf @method('PATCH')
                    <button class="btn {{ $agent->is_active ? 'btn-danger':'btn-success' }}" style="width:100%;justify-content:center">
                        {{ $agent->is_active ? 'Disable Agent' : 'Enable Agent' }}
                    </button>
                </form>
                @endif

                <div class="form-group" style="border:1px solid var(--border);border-radius:8px;padding:14px">
                    <div style="font-size:12px;font-weight:700;color:var(--midnight);margin-bottom:10px">🔒 Update Agent Password</div>
                    <form method="POST" action="{{ route('super.agents.password.update', $agent) }}">
                        @csrf
                        @method('PATCH')
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control" required placeholder="Minimum 8 characters">
                        </div>
                        <div class="form-group" style="margin-bottom:10px">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control" required placeholder="Repeat password">
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT: Stats + Referrals --}}
    <div>
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-val" style="color:var(--indigo)">{{ $referrals->total() }}</div>
                <div class="stat-lbl">Total Referrals</div>
            </div>
            <div class="stat-card">
                <div class="stat-val" style="color:var(--emerald)">₦{{ number_format($agent->total_earned) }}</div>
                <div class="stat-lbl">Total Earned</div>
            </div>
            <div class="stat-card">
                <div class="stat-val" style="color:var(--crimson)">₦{{ number_format($agent->unpaidBalance()) }}</div>
                <div class="stat-lbl">Unpaid</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span class="card-title">Referral History</span></div>
            <div class="tbl"><table>
                <thead>
                    <tr><th>School</th><th>Date</th><th>Sale Amount</th><th>Commission</th><th>Status</th></tr>
                </thead>
                <tbody>
                @forelse($referrals as $ref)
                <tr>
                    <td><strong>{{ optional($ref->tenant)->name ?? 'School #'.$ref->tenant_id }}</strong></td>
                    <td style="font-size:12px;color:var(--slate-light)">{{ \Carbon\Carbon::parse($ref->sale_date)->format('d M Y') }}</td>
                    <td>₦{{ number_format($ref->sale_amount) }}</td>
                    <td style="font-weight:600;color:var(--emerald)">₦{{ number_format($ref->commission_amount) }}</td>
                    <td><span class="badge badge-{{ $ref->status }}">{{ ucfirst($ref->status) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--slate-light)">No referrals recorded yet.</td></tr>
                @endforelse
                </tbody>
            </table></div>
            <div style="padding:12px 16px">{{ $referrals->links() }}</div>
        </div>
    </div>
</div>
@endsection
