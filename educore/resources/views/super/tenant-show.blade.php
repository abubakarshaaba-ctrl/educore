@extends('layouts.super')
@section('title','School Details')
@section('page-title','School Details')

@push('styles')
<style>
.pg{display:grid;grid-template-columns:280px 1fr;gap:16px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
.info-row{display:flex;justify-content:space-between;gap:12px;padding:9px 16px;border-bottom:1px solid var(--border);font-size:13px}
.info-row:last-child{border-bottom:none}
.ik{color:var(--slate);font-size:12px}.iv{font-weight:600;color:var(--midnight);overflow-wrap:anywhere;text-align:right}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:10px 14px;border-bottom:1px solid var(--border);font-size:13px}
tbody tr:last-child td{border-bottom:none}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12.5px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-sm{padding:4px 10px;font-size:11px}.btn-ghost{background:white;color:var(--midnight);border:1px solid var(--border)}
.btn[disabled]{opacity:.55;cursor:not-allowed}
.badge{display:inline-flex;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px}
.b-active{background:#ECFDF5;color:var(--emerald)}.b-expired{background:#FEF2F2;color:var(--crimson)}.b-trial{background:#FFFBEB;color:var(--amber)}
.back{font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.progress{height:8px;background:#E5E7EB;border-radius:999px;overflow:hidden}
.progress span{display:block;height:100%;background:var(--indigo)}
.check-list{display:flex;flex-direction:column;gap:8px;padding:14px 16px}
.check-item{font-size:12px;line-height:1.45;padding:8px 10px;border-radius:8px}
.check-blocking{background:#FEF2F2;color:var(--crimson)}
.check-warning{background:#FFFBEB;color:#92400E}
.check-complete{background:#ECFDF5;color:var(--emerald)}
@media(max-width:768px){.pg{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

<a href="{{ route('super.tenants') }}" class="back">&larr; Back to Schools</a>

<div class="pg">
  <div>
    <div class="card">
      <div style="padding:20px;text-align:center;border-bottom:1px solid var(--border)">
        @if($tenant->logo_path)
          <img src="{{ asset($tenant->logo_path) }}" style="width:64px;height:64px;border-radius:50%;object-fit:cover;margin:0 auto 10px;display:block" alt="{{ $tenant->name }} logo">
        @else
          <div style="width:64px;height:64px;border-radius:50%;background:var(--indigo);color:white;font-size:24px;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 10px">{{ strtoupper(substr($tenant->name,0,1)) }}</div>
        @endif
        <div style="font-size:15px;font-weight:700">{{ $tenant->name }}</div>
        <div style="font-size:11px;color:var(--slate-light);margin-top:3px">{{ $tenant->subdomain ? $tenant->subdomain.'.educore.test' : 'No local subdomain set' }}</div>
        <div style="margin-top:8px"><span class="badge b-{{ $tenant->status }}">{{ ucfirst($tenant->status) }} &middot; {{ \App\Services\PricingService::capacityFor($tenant) }} students</span></div>
      </div>

      <div class="info-row"><span class="ik">Email</span><span class="iv">{{ $tenant->email }}</span></div>
      <div class="info-row"><span class="ik">Phone</span><span class="iv">{{ $tenant->phone ?? '-' }}</span></div>
      <div class="info-row"><span class="ik">Location</span><span class="iv">{{ $tenant->address ?? '-' }}</span></div>
      <div class="info-row"><span class="ik">Users</span><span class="iv">{{ $tenant->users->count() }}</span></div>
      <div class="info-row"><span class="ik">Expires</span><span class="iv">{{ $tenant->subscription_expires_at ? \Carbon\Carbon::parse($tenant->subscription_expires_at)->format('d M Y') : '-' }}</span></div>
      <div class="info-row"><span class="ik">School Portal</span><span class="iv"><a href="{{ route('tenant.portal.landing', $tenant->slug) }}" target="_blank" rel="noopener">/school/{{ $tenant->slug }}</a></span></div>
      <div class="info-row"><span class="ik">School Login</span><span class="iv"><a href="{{ route('tenant.login', $tenant->slug) }}" target="_blank" rel="noopener">/school/{{ $tenant->slug }}/login</a></span></div>
      <div class="info-row"><span class="ik">Admissions</span><span class="iv"><a href="{{ route('portal.landing', $tenant->slug) }}" target="_blank" rel="noopener">/apply/{{ $tenant->slug }}</a></span></div>
      <div class="info-row"><span class="ik">Local Host Login</span><span class="iv"><a href="{{ $onboardingStatus->urls['local_subdomain_login'] ?? '#' }}" target="_blank" rel="noopener">{{ $onboardingStatus->urls['local_subdomain_login'] ?? '-' }}</a></span></div>
      @if(!empty($onboardingStatus->urls['custom_domain']))
        <div class="info-row"><span class="ik">Custom Domain</span><span class="iv"><a href="{{ $onboardingStatus->urls['custom_domain'] }}" target="_blank" rel="noopener">{{ $onboardingStatus->urls['custom_domain'] }}</a></span></div>
      @endif

      <div style="padding:12px 16px">
        <a href="{{ route('super.tenant.edit', $tenant) }}" class="btn btn-p" style="width:100%;justify-content:center;margin-bottom:8px">Edit School</a>
        <a href="{{ route('super.white-label', $tenant) }}" class="btn btn-ghost" style="width:100%;justify-content:center;margin-bottom:8px">White-label Settings</a>
        <form method="POST" action="{{ route('super.tenant.toggle',$tenant) }}">
          @csrf @method('PATCH')
          <input type="hidden" name="status" value="{{ $tenant->status === 'active' ? 'suspended' : 'active' }}">
          <button type="submit" class="btn {{ $tenant->status === 'active' ? 'btn-ghost':'btn-p' }}" style="width:100%;justify-content:center" {{ $tenant->status !== 'active' && !$onboardingStatus->can_activate ? 'disabled' : '' }}>
            {{ $tenant->status === 'active' ? 'Suspend School':'Activate School' }}
          </button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="ch">Onboarding Readiness</div>
      <div style="padding:14px 16px">
        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:8px">
          <strong>{{ $onboardingStatus->progress_percentage }}% complete</strong>
          <span>{{ $onboardingStatus->complete ? 'Ready' : 'Setup required' }}</span>
        </div>
        <div class="progress"><span style="width:{{ $onboardingStatus->progress_percentage }}%"></span></div>
      </div>
      <div class="check-list">
        @forelse($onboardingStatus->blocking_items as $item)
          <div class="check-item check-blocking">{{ $item }}</div>
        @empty
          <div class="check-item check-complete">No blocking readiness items.</div>
        @endforelse
        @foreach($onboardingStatus->warning_items as $item)
          <div class="check-item check-warning">{{ $item }}</div>
        @endforeach
      </div>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="ch">Payment History</div>
      <div class="tbl"><table>
        <thead><tr><th>Amount</th><th>Date</th><th>Method</th></tr></thead>
        <tbody>
        @forelse($payments as $pay)
        <tr>
            <td style="font-weight:700">NGN {{ number_format($pay->amount) }}</td>
            <td style="font-size:11px">{{ \Carbon\Carbon::parse($pay->paid_at)->format('d M Y') }}</td>
            <td style="font-size:11px;text-transform:capitalize">{{ $pay->payment_method ?? '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="3" style="text-align:center;padding:20px;color:var(--slate-light)">No payments recorded</td></tr>
        @endforelse
        </tbody>
      </table></div>
    </div>

    <div class="card">
      <div class="ch">Renew Subscription</div>
      <div style="padding:16px;font-size:12.5px;color:var(--slate-light)">
        Renewals are pay-per-student now — generate and mark an invoice paid from
        <a href="{{ route('super.billing') }}">Billing &amp; Invoicing</a> instead of a fixed plan.
        Paying an invoice automatically extends this school's subscription and paid capacity.
      </div>
    </div>
  </div>
</div>
@endsection
