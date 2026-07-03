@extends('layouts.super')
@section('title','Plans & Pricing')
@section('page-title','Plans & Pricing')
@push('styles')
<style>
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:#065F46;margin-bottom:16px}
.alert-e{background:#FEF2F2;border:1px solid #FCA5A5;border-radius:8px;padding:12px 16px;font-size:13px;color:#991B1B;margin-bottom:16px}
.plan-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(380px,1fr));gap:18px;margin-bottom:24px}
@media(max-width:640px){.plan-grid{grid-template-columns:1fr}}

/* Plan card */
.pc{background:white;border:1.5px solid var(--border);border-radius:14px;overflow:hidden}
.pc.active-plan{border-color:#2563EB}
.pc-head{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:#F8FAFC}
.pc-name{font-size:15px;font-weight:800;color:var(--midnight)}
.pc-price{font-size:13px;color:var(--slate-light);margin-top:2px}
.pc-badge{padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700}
.pc-body{padding:16px 20px}

/* Features summary on card */
.feat-tags{display:flex;flex-wrap:wrap;gap:5px;margin-bottom:12px;min-height:24px}
.feat-tag{padding:2px 8px;background:#EFF6FF;color:#2563EB;border-radius:20px;font-size:10px;font-weight:700}

/* Collapsible edit form */
.edit-toggle{width:100%;padding:8px;background:#F8FAFC;border:1px solid var(--border);border-radius:8px;font-size:12px;font-weight:700;color:var(--midnight);cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:space-between;margin-bottom:0}
.edit-body{display:none;padding-top:14px}
.edit-body.open{display:block}

/* Form fields */
.fg{margin-bottom:12px}
.fl{display:block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light);margin-bottom:3px}
.fc{width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:7px;font-size:12.5px;font-family:inherit;background:#F8FAFC;outline:none}
.fc:focus{border-color:#2563EB;background:white}
.fr{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.fr3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}

/* Feature checklist */
.feat-section{margin-bottom:14px}
.feat-section-title{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);margin-bottom:7px;padding-bottom:4px;border-bottom:1px solid var(--border)}
.feat-grid{display:grid;grid-template-columns:1fr 1fr;gap:4px}
.feat-check{display:flex;align-items:center;gap:7px;padding:5px 7px;border-radius:6px;cursor:pointer;font-size:12px;color:var(--midnight);border:1px solid transparent}
.feat-check:hover{background:#F0F4FF;border-color:#BFDBFE}
.feat-check input{width:14px;height:14px;accent-color:#2563EB;cursor:pointer;flex-shrink:0}
.feat-check input:checked + span{font-weight:700;color:#2563EB}

/* Create plan */
.create-card{background:white;border:1.5px solid var(--border);border-radius:14px;overflow:hidden}
.create-head{padding:14px 20px;border-bottom:1px solid var(--border);background:linear-gradient(135deg,var(--midnight),#1a3a6b);color:white;font-size:15px;font-weight:800}
.create-body{padding:20px}
.btn-p{padding:9px 18px;background:#2563EB;color:white;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit}
.btn-p:hover{background:#1D4ED8}
.btn-sm-save{width:100%;padding:9px;background:#059669;color:white;border:none;border-radius:8px;font-size:12.5px;font-weight:700;cursor:pointer;font-family:inherit;margin-top:10px}
.divider{border:none;border-top:1px solid var(--border);margin:12px 0}
.hint{font-size:10.5px;color:var(--slate-light);margin-top:3px}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">&#10003; {{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-e"><strong>Could not save:</strong>@foreach($errors->all() as $e)<div>&bull; {{ $e }}</div>@endforeach</div>@endif

@php
/**
 * EduCore feature catalogue — audited against actual routes in routes/web.php.
 * Key  = stored in plan's features JSON.
 * Each key maps to a FEATURE_ROUTE_PREFIXES entry in User.php.
 *
 * Groups match the sidebar sections in layouts/app.blade.php.
 * Keys that have NO matching route in this project have been removed.
 */
$FEATURES = [
    'Core' => [
        'dashboard'           => 'Admin Dashboard',
        'students'            => 'Student Management',
        'student_transfer'    => 'Student Transfers & Class Moves',
        'student_archive'     => 'Student Archive',
        'staff'               => 'Staff Management',
        'staff_archive'       => 'Staff Archive',
        'classes'             => 'Classes & Arms',
        'subjects'            => 'Subjects',
        'academic_cycle'      => 'Academic Sessions & Terms',
        'promotion'           => 'Promotion Engine',
        'curriculum'          => 'Curriculum / Lesson Plans',
        'school_setup'        => 'School Setup, Branding & Portal Accounts',
    ],
    'Admissions' => [
        'admissions'          => 'Online Admissions Portal',
    ],
    'Academics' => [
        'timetable'           => 'Timetable',
        'scores'              => 'Score Entry & Assessment',
        'assessment_types'    => 'Assessment Types Configuration',
        'report_cards'        => 'Report Cards & Transcripts',
        'broadsheet'          => 'Broadsheet',
        'skill_ratings'       => 'Skill / Psychomotor Ratings',
        'gradebook'           => 'Gradebook',
    ],
    'Attendance' => [
        'student_attendance'  => 'Student Attendance',
        'staff_attendance'    => 'Staff Attendance',
        'staff_id_cards'      => 'Staff ID Cards & QR Clock-in',
    ],
    'CBT' => [
        'cbt'                 => 'CBT Exam Engine (MCQ, Essay, Short Answer)',
    ],
    'Finance' => [
        'fees'                => 'Fee Setup & Categories',
        'invoices'            => 'Invoice Generation',
        'payment_plans'       => 'Payment Plans',
        'fee_reminders'       => 'Fee Reminders',
        'online_payments'     => 'Online Payments (Paystack / Monnify)',
        'expenses'            => 'Expenses',
        'payroll'             => 'Staff Payroll & PAYE Tax',
        'financial_report'    => 'Financial Reports',
    ],
    'Communication' => [
        'messages'            => 'Internal Messaging',
        'sms'                 => 'SMS Notifications',
        'notifications'       => 'System Notifications',
        'announcements'       => 'Announcements',
        'auto_triggers'       => 'Auto Triggers / Notification Rules',
        'push_notifications'  => 'Push Notifications (Mobile)',
    ],
    'Portals' => [
        'parent_portal'       => 'Parent Portal',
        'student_portal'      => 'Student Portal (Results, Fees, Exams)',
    ],
    'Operations' => [
        'library'             => 'Library',
        'transport'           => 'Transport / Fleet Management',
        'health_records'      => 'Health Records',
        'calendar'            => 'School Calendar',
        'risk_flags'          => 'Academic Risk Flags',
        'analytics'           => 'Analytics & Reporting',
        'export_data'         => 'Data Export',
    ],
];
@endphp

{{-- ══ Existing plans ══════════════════════════════════════════════ --}}
<div class="plan-grid">
@foreach($plans as $plan)
@php $planFeatures = is_array($plan->features) ? $plan->features : (json_decode($plan->features ?? '[]', true) ?? []); @endphp
<div class="pc {{ $plan->is_active ? 'active-plan' : '' }}">
    <div class="pc-head">
        <div>
            <div class="pc-name">{{ $plan->name }}</div>
            <div class="pc-price">₦{{ number_format($plan->monthly_price) }}/mo &nbsp;·&nbsp; ₦{{ number_format($plan->annual_price) }}/yr</div>
        </div>
        <div>
            <span class="pc-badge" style="background:{{ $plan->is_active?'#ECFDF5':'#F1F5F9' }};color:{{ $plan->is_active?'#059669':'#64748B' }}">{{ $plan->is_active?'Active':'Inactive' }}</span>
        </div>
    </div>
    <div class="pc-body">
        <div class="feat-tags">
            @forelse($planFeatures as $fk)
            @php $flabel = collect($FEATURES)->flatten(1)[$fk] ?? $fk; @endphp
            <span class="feat-tag">{{ $flabel }}</span>
            @empty
            <span style="font-size:11px;color:var(--slate-light)">No features selected</span>
            @endforelse
        </div>
        <div style="font-size:11px;color:var(--slate-light);margin-bottom:10px">
            👨‍🎓 {{ number_format($plan->max_students) }} students &nbsp;·&nbsp; 👩‍🏫 {{ $plan->max_staff }} staff
        </div>

        {{-- Edit form --}}
        <button type="button" class="edit-toggle" onclick="toggleEdit({{ $plan->id }})">
            ✎ Edit Plan <span id="arrow-{{ $plan->id }}">▼</span>
        </button>
        <div class="edit-body" id="edit-{{ $plan->id }}">
            <form method="POST" action="{{ route('super.plans.update', $plan->id) }}">
                @csrf @method('PATCH')

                <div class="fr" style="margin-top:12px">
                    <div class="fg"><label class="fl">Plan Name</label><input type="text" name="name" class="fc" value="{{ $plan->name }}" required></div>
                    <div class="fg">
                        <label class="fl">Status</label>
                        <select name="is_active" class="fc">
                            <option value="1" {{ $plan->is_active?'selected':'' }}>Active</option>
                            <option value="0" {{ !$plan->is_active?'selected':'' }}>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="fr3">
                    <div class="fg"><label class="fl">Monthly ₦</label><input type="number" name="monthly_price" class="fc" value="{{ $plan->monthly_price }}" min="0" step="100" required></div>
                    <div class="fg"><label class="fl">Annual ₦</label><input type="number" name="annual_price" class="fc" value="{{ $plan->annual_price }}" min="0" step="1000" required></div>
                    <div class="fg"><label class="fl">Max Students</label><input type="number" name="max_students" class="fc" value="{{ $plan->max_students }}" min="1" required></div>
                    <div class="fg"><label class="fl">Max Staff</label><input type="number" name="max_staff" class="fc" value="{{ $plan->max_staff }}" min="1" required></div>
                </div>
                <div class="fg">
                    <label class="fl">Description</label>
                    <input type="text" name="description" class="fc" value="{{ $plan->description }}" placeholder="Short plan description">
                </div>

                <div class="divider"></div>
                <div class="feat-section-title" style="font-size:11px;font-weight:800;color:var(--midnight);margin-bottom:10px">&#9989; Feature Access</div>
                @foreach($FEATURES as $group => $feats)
                <div class="feat-section">
                    <div class="feat-section-title">{{ $group }}</div>
                    <div class="feat-grid">
                        @foreach($feats as $key => $label)
                        <label class="feat-check">
                            <input type="checkbox" name="features[]" value="{{ $key }}"
                                   {{ in_array($key, $planFeatures) ? 'checked' : '' }}>
                            <span>{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach

                <button type="submit" class="btn-sm-save">💾 Save Changes to {{ $plan->name }}</button>
            </form>
        </div>
    </div>
</div>
@endforeach
</div>

{{-- ══ Create new plan ════════════════════════════════════════════ --}}
<div class="create-card">
    <div class="create-head">+ Create New Plan</div>
    <div class="create-body">
        <form method="POST" action="{{ route('super.plans.store') }}">
            @csrf
            <div class="fr3" style="margin-bottom:12px">
                <div class="fg"><label class="fl">Plan Name *</label><input type="text" name="name" class="fc" required placeholder="e.g. Enterprise"><div class="hint">Displayed to schools</div></div>
                <div class="fg"><label class="fl">Slug *</label><input type="text" name="slug" class="fc" required placeholder="e.g. standard"><div class="hint">Lowercase, hyphens only</div></div>
                <div class="fg"></div>
                <div class="fg"><label class="fl">Monthly Price ₦ *</label><input type="number" name="monthly_price" class="fc" value="0" min="0" step="100" required></div>
                <div class="fg"><label class="fl">Annual Price ₦ *</label><input type="number" name="annual_price" class="fc" value="0" min="0" step="1000" required></div>
                <div class="fg"></div>
                <div class="fg"><label class="fl">Max Students *</label><input type="number" name="max_students" class="fc" value="1000" min="1" required></div>
                <div class="fg"><label class="fl">Max Staff *</label><input type="number" name="max_staff" class="fc" value="100" min="1" required></div>
                <div class="fg"></div>
            </div>
            <div class="fg" style="margin-bottom:16px"><label class="fl">Description</label><input type="text" name="description" class="fc" placeholder="Short plan description"></div>

            <div class="divider"></div>
            <div style="font-size:12px;font-weight:800;color:var(--midnight);margin-bottom:12px">&#9989; Features included in this plan:</div>

            @foreach($FEATURES as $group => $feats)
            <div class="feat-section">
                <div class="feat-section-title">{{ $group }}</div>
                <div class="feat-grid">
                    @foreach($feats as $key => $label)
                    <label class="feat-check">
                        <input type="checkbox" name="features[]" value="{{ $key }}">
                        <span>{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endforeach

            <div class="divider"></div>
            <button type="submit" class="btn-p">+ Create Plan</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
function toggleEdit(id) {
    var body  = document.getElementById('edit-' + id);
    var arrow = document.getElementById('arrow-' + id);
    var open  = body.classList.toggle('open');
    arrow.textContent = open ? '▲' : '▼';
}
// Auto-generate slug from name on create form
document.querySelector('input[name=name]').addEventListener('input', function() {
    var slugInput = document.querySelector('input[name=slug]');
    if (slugInput && slugInput.dataset.touched !== '1') {
        slugInput.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }
});
document.querySelector('input[name=slug]').addEventListener('input', function() {
    this.dataset.touched = '1';
});
</script>
@endpush
@endsection
