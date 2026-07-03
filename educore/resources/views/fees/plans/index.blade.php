@extends('layouts.app')
@section('title','Fee Payment Plans')
@section('page-title','Fee Payment Plans')
@push('styles')
<style>
.pg{display:grid;grid-template-columns:1fr 380px;gap:16px}
.pg>*{min-width:0}
.sg{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:16px}
.sc{background:white;border:1px solid var(--border);border-radius:10px;padding:14px 16px;text-align:center}
.sv{font-size:20px;font-weight:800;letter-spacing:-0.02em}.sl{font-size:10px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;margin-top:2px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
.plan-card{border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:12px;transition:border-color 200ms}
.plan-card.default{border-color:var(--indigo);background:#FAFBFF}
.plan-name{font-size:14px;font-weight:700;color:var(--midnight);display:flex;align-items:center;gap:8px}
.plan-desc{font-size:12px;color:var(--slate-light);margin-top:4px}
.install-row{display:flex;gap:0;margin:12px 0;border-radius:8px;overflow:hidden;border:1px solid var(--border)}
.install-slot{flex:1;text-align:center;padding:8px 4px;background:#F8FAFC;border-right:1px solid var(--border);font-size:11px}
.install-slot:last-child{border-right:none}
.install-slot .pct{font-size:18px;font-weight:800;color:var(--indigo)}
.install-slot .label{font-size:10px;color:var(--slate-light);margin-top:2px}
.badge{display:inline-flex;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px}
.b-on{background:#ECFDF5;color:var(--emerald)}.b-off{background:#FEF2F2;color:var(--crimson)}.b-default{background:#EFF6FF;color:var(--indigo)}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 12px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;transition:all 150ms;text-decoration:none}
.btn-p{background:var(--indigo);color:white}.btn-r{background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA}
.btn-a{background:var(--amber);color:white}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%;transition:border 200ms}
.fc:focus{border-color:var(--indigo);background:white}
.installments-builder{display:grid;gap:10px}
.slot-row{display:grid;grid-template-columns:auto 1fr 1fr auto;gap:8px;align-items:center;background:#F8FAFC;border:1px solid var(--border);border-radius:8px;padding:10px 12px}
.slot-num{width:26px;height:26px;border-radius:50%;background:var(--indigo);color:white;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.pct-total{font-size:13px;font-weight:700;padding:8px 12px;border-radius:8px;text-align:center;margin-top:8px}
.pct-ok{background:#ECFDF5;color:var(--emerald)}.pct-bad{background:#FEF2F2;color:var(--crimson)}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
.alert-e{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--crimson);margin-bottom:14px}
.info-box{background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--indigo);margin-bottom:14px;line-height:1.5}
@media(max-width:1024px){.pg{grid-template-columns:1fr}.sg{grid-template-columns:repeat(3,1fr)}}
@media(max-width:640px){.sg{grid-template-columns:1fr 1fr}.slot-row{grid-template-columns:1fr}.install-row{overflow-x:auto}.install-slot{min-width:90px}}
</style>

@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-e">{{ $errors->first() }}</div>@endif

<div class="sg">
    <div class="sc"><div class="sv" style="color:var(--indigo)">{{ $stats['active_plans'] }}</div><div class="sl">Active Plans</div></div>
    <div class="sc"><div class="sv">{{ $stats['invoices_on_plan'] }}</div><div class="sl">Invoices on Plan</div></div>
    <div class="sc"><div class="sv" style="color:var(--crimson)">{{ $stats['overdue_installments'] }}</div><div class="sl">Overdue</div></div>
    <div class="sc"><div class="sv" style="color:var(--amber)">{{ $stats['due_this_week'] }}</div><div class="sl">Due This Week</div></div>
    <div class="sc"><div class="sv" style="color:var(--emerald)">₦{{ number_format($stats['collected_this_month']) }}</div><div class="sl">Collected This Month</div></div>
</div>

<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
    <a href="{{ route('fees.plans.overdue') }}" class="btn btn-r">&#9888; View Overdue</a>
    <form method="POST" action="{{ route('fees.plans.reminders') }}" style="display:inline">
        @csrf
        <input type="hidden" name="days" value="3">
        <button type="submit" class="btn btn-a" onclick="return confirm('Send SMS reminders to all guardians with installments due in 3 days?')">&#128241; Send 3-Day Reminders</button>
    </form>
</div>

<div class="pg">
  <div>
    <div class="card">
        <div class="ch">Payment Plans</div>
        <div style="padding:16px">
        @forelse($plans as $plan)
        <div class="plan-card {{ $plan->is_default ? 'default' : '' }}">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px">
                <div>
                    <div class="plan-name">
                        {{ $plan->name }}
                        @if($plan->is_default)<span class="badge b-default">Default</span>@endif
                        <span class="badge {{ $plan->is_active ? 'b-on' : 'b-off' }}">{{ $plan->is_active ? 'Active' : 'Inactive' }}</span>
                    </div>
                    @if($plan->description)<div class="plan-desc">{{ $plan->description }}</div>@endif
                    @if($plan->surcharge_pct > 0)<div class="plan-desc" style="color:var(--amber)">+{{ $plan->surcharge_pct }}% surcharge applies</div>@endif
                </div>
                <div style="display:flex;gap:6px;flex-shrink:0">
                    <form method="POST" action="{{ route('fees.plans.toggle',$plan) }}">@csrf @method('PATCH')
                        <button type="submit" class="btn" style="background:white;border:1px solid var(--border);color:var(--midnight)">{{ $plan->is_active ? 'Disable' : 'Enable' }}</button>
                    </form>
                    <form method="POST" action="{{ route('fees.plans.destroy',$plan) }}" onsubmit="return confirm('Delete this plan?')">@csrf @method('DELETE')
                        <button type="submit" class="btn btn-r">Delete</button>
                    </form>
                </div>
            </div>
            <div class="install-row">
                @foreach($plan->installment_schedule ?? [] as $slot)
                <div class="install-slot">
                    <div class="pct">{{ $slot['percentage'] }}%</div>
                    <div class="label">{{ $slot['label'] }}</div>
                    <div class="label">Day {{ $slot['due_days'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @empty
        <div style="text-align:center;padding:40px;color:var(--slate-light)">No payment plans yet. Create one using the form.</div>
        @endforelse
        </div>
    </div>
  </div>

  <div>
    <div class="info-box">
        &#128161; Payment plans let parents pay school fees in <strong>2–6 installments</strong>. Assign a plan to any invoice from the invoice detail page. Automatic SMS reminders are sent before due dates.
    </div>

    <div class="card">
        <div class="ch">Create Payment Plan</div>
        <div style="padding:16px">
        <form method="POST" action="{{ route('fees.plans.store') }}" id="plan-form">
        @csrf
        <div class="fg"><label class="fl">Plan Name <span style="color:var(--crimson)">*</span></label>
            <input type="text" name="name" class="fc" required placeholder="e.g. 2 Installments, Term Payment Plan">
        </div>
        <div class="fg"><label class="fl">Description</label>
            <input type="text" name="description" class="fc" placeholder="Brief description for admin reference">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div class="fg"><label class="fl">Installments <span style="color:var(--crimson)">*</span></label>
                <select name="installments_count" class="fc" id="inst-count" onchange="buildSlots(this.value)">
                    <option value="2">2 Installments</option>
                    <option value="3" selected>3 Installments</option>
                    <option value="4">4 Installments</option>
                </select>
            </div>
            <div class="fg"><label class="fl">Surcharge %</label>
                <input type="number" name="surcharge_pct" class="fc" value="0" min="0" max="50" step="0.5">
            </div>
        </div>
        <div class="fg">
            <label class="fl">Installment Schedule <span style="color:var(--crimson)">*</span></label>
            <div style="font-size:11px;color:var(--slate-light);margin-bottom:8px">Set % and days-from-start for each installment. Must total 100%.</div>
            <div class="installments-builder" id="slots-builder"></div>
            <div class="pct-total" id="pct-total">Total: 0%</div>
        </div>
        <div class="fg" style="flex-direction:row;align-items:center;gap:10px;margin-bottom:16px">
            <input type="checkbox" name="is_default" value="1" id="is-default" style="width:16px;height:16px;accent-color:var(--indigo)">
            <label for="is-default" style="font-size:13px;font-weight:500;cursor:pointer">Set as default plan</label>
        </div>
        <button type="submit" class="btn btn-p" style="width:100%;justify-content:center;padding:10px">&#43; Create Plan</button>
        </form>
        </div>
    </div>
  </div>
</div>

<script>
function buildSlots(count) {
    const builder = document.getElementById('slots-builder');
    builder.innerHTML = '';
    const defaults = {
        2: [{pct:50,days:0},{pct:50,days:30}],
        3: [{pct:50,days:0},{pct:30,days:30},{pct:20,days:60}],
        4: [{pct:40,days:0},{pct:30,days:30},{pct:20,days:60},{pct:10,days:90}],
    };
    const def = defaults[count] || [];
    for(let i=0;i<count;i++){
        const d = def[i] || {pct:Math.floor(100/count),days:i*30};
        builder.innerHTML += `
        <div class="slot-row">
            <div class="slot-num">${i+1}</div>
            <div><label class="fl" style="font-size:10px">Percentage</label>
                <input type="number" name="percentages[]" class="fc pct-input" value="${d.pct}" min="1" max="100" required oninput="updateTotal()" style="padding:7px 10px;font-size:13px">
            </div>
            <div><label class="fl" style="font-size:10px">Days from start</label>
                <input type="number" name="due_days[]" class="fc" value="${d.days}" min="0" required style="padding:7px 10px;font-size:13px">
            </div>
        </div>`;
    }
    updateTotal();
}
function updateTotal(){
    const inputs = document.querySelectorAll('.pct-input');
    const total = Array.from(inputs).reduce((s,i)=>s+parseInt(i.value||0),0);
    const el = document.getElementById('pct-total');
    el.textContent = 'Total: '+total+'%';
    el.className = 'pct-total '+(total===100?'pct-ok':'pct-bad');
}
buildSlots(3);
</script>
@endsection
