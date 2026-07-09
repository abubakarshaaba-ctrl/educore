@extends('layouts.app')

@section('title', $student->full_name)
@section('page-title', 'Student Profile')

@push('styles')
<style>
    .breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--slate-light); margin-bottom: 20px; }
    .breadcrumb a { color: var(--indigo); text-decoration: none; font-weight: 500; }
    .breadcrumb svg { width: 14px; height: 14px; }

    .profile-grid { display: grid; grid-template-columns: 300px 1fr; gap: 16px; }

    .profile-card { background: white; border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }

    .profile-hero { padding: 28px 24px; text-align: center; border-bottom: 1px solid var(--border); }

    .profile-big-avatar {
        width: 72px; height: 72px;
        border-radius: 50%;
        background: var(--indigo);
        color: white;
        font-size: 26px; font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 14px;
    }

    .profile-name { font-size: 17px; font-weight: 700; color: var(--midnight); letter-spacing: -0.02em; }
    .profile-adm  { font-size: 12px; color: var(--slate-light); margin-top: 4px; }

    .profile-meta { padding: 20px 24px; }
    .meta-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 13px; }
    .meta-row:last-child { border-bottom: none; }
    .meta-key { color: var(--slate); font-weight: 500; }
    .meta-val { color: var(--midnight); font-weight: 600; text-align: right; }

    .badge { display: inline-flex; align-items: center; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 20px; }
    .badge-success { background: #ECFDF5; color: var(--emerald); }
    .badge-warning { background: #FFFBEB; color: var(--amber); }
    .badge-error   { background: #FEF2F2; color: var(--crimson); }
    .badge-info    { background: var(--indigo-bg); color: var(--indigo); }

    .section-card { background: white; border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 16px; overflow: hidden; }
    .section-header { padding: 14px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .section-title { font-size: 14px; font-weight: 600; color: var(--midnight); }
    .section-body { padding: 20px; }

    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .info-item { display: flex; flex-direction: column; gap: 3px; }
    .info-label { font-size: 11px; font-weight: 600; color: var(--slate-light); text-transform: uppercase; letter-spacing: 0.05em; }
    .info-value { font-size: 13px; font-weight: 500; color: var(--midnight); }

    .guardian-card { background: #F8FAFC; border: 1px solid var(--border); border-radius: 10px; padding: 16px; margin-bottom: 10px; }
    .hidden { display: none !important; }
    .guardian-name { font-size: 14px; font-weight: 600; color: var(--midnight); }
    .guardian-meta { font-size: 12px; color: var(--slate); margin-top: 4px; }

    table { width: 100%; border-collapse: collapse; }
    thead th { font-size: 11px; font-weight: 600; color: var(--slate-light); text-transform: uppercase; letter-spacing: 0.05em; padding: 10px 16px; text-align: left; background: #F8FAFC; border-bottom: 1px solid var(--border); }
    tbody td { padding: 12px 16px; border-bottom: 1px solid var(--border); font-size: 13px; color: var(--midnight); }
    tbody tr:last-child td { border-bottom: none; }

    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; font-size: 12px; font-weight: 600; font-family: inherit; border-radius: 7px; border: none; cursor: pointer; text-decoration: none; transition: background 150ms; }
    .btn-primary { background: var(--indigo); color: white; }
    .btn-ghost { background: white; color: var(--midnight); border: 1px solid var(--border); }
    .btn-ghost:hover { background: #F8FAFC; }

    .empty-state { text-align: center; padding: 30px; color: var(--slate-light); font-size: 13px; }

    @media (max-width: 1024px) {
        .profile-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('students.index') }}">Students</a>
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
    {{ $student->full_name }}
</div>

<div class="profile-grid">

    {{-- LEFT: Profile sidebar --}}
    <div>
        <div class="profile-card" style="margin-bottom:16px">
            <div class="profile-hero">
                <div class="profile-big-avatar">{{ strtoupper(substr($student->first_name,0,1)) }}</div>
                <div class="profile-name">{{ $student->full_name }}</div>
                <div class="profile-adm">{{ $student->admission_number }}</div>
                <div style="margin-top:10px">
                    @if($student->status === $studentStatuses['active'])
                        <span class="badge badge-success">Active</span>
                    @elseif($student->status === $studentStatuses['suspended'])
                        <span class="badge badge-warning">Suspended</span>
                    @else
                        <span class="badge badge-error">{{ $student->status_label }}</span>
                    @endif
                </div>
            </div>
            <div class="profile-meta">
                <div class="meta-row">
                    <span class="meta-key">Class</span>
                    <span class="meta-val">{{ optional($student->currentClassArm)->classLevel->name }} {{ optional($student->currentClassArm)->name ?? '—' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-key">Gender</span>
                    <span class="meta-val">{{ ucfirst($student->gender ?? '—') }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-key">Date of Birth</span>
                    <span class="meta-val">{{ optional($student->date_of_birth)->format('d M Y') ?? '—' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-key">Admitted</span>
                    <span class="meta-val">{{ optional($student->admission_date)->format('d M Y') ?? '—' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-key">Blood Group</span>
                    <span class="meta-val">{{ $student->blood_group ?? '—' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-key">Genotype</span>
                    <span class="meta-val">{{ $student->genotype ?? '—' }}</span>
                </div>
            </div>
        </div>

        @if(auth()->user()->canAccessModule('transcript'))
        <a href="{{ route('students.transcript', $student) }}" style="display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12px;font-weight:600;background:#EFF6FF;color:#2563EB;border-radius:8px;text-decoration:none;border:1px solid #BFDBFE">📋 Transcript</a>
        @endif
        <a href="{{ route('curriculum.student-subjects', $student) }}" style="display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12px;font-weight:600;background:#F0FDF4;color:#059669;border-radius:8px;text-decoration:none;border:1px solid #A7F3D0">📚 Subjects</a>
        <a href="{{ route('students.id-card', $student) }}" target="_blank" style="display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12px;font-weight:600;background:#FEF9EC;color:#92400E;border-radius:8px;text-decoration:none;border:1px solid #F2C35B66">🪪 ID Card</a>
        @can('students.edit')
        <a href="{{ route('students.edit', $student) }}" class="btn btn-ghost" style="width:100%;justify-content:center">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
            Edit Student
        </a>
        @endcan
        @can('student.status.view')
        <a href="{{ route('students.status.show', $student) }}" class="btn btn-ghost" style="width:100%;justify-content:center;margin-top:8px">
            Lifecycle Status
        </a>
        @endcan
    </div>

    {{-- RIGHT: Details --}}
    <div>
        {{-- Origin & Medical --}}
        <div class="section-card">
            <div class="section-header">
                <span class="section-title">Additional Information</span>
            </div>
            <div class="section-body">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">State of Origin</span>
                        <span class="info-value">{{ $student->state_of_origin ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">LGA of Origin</span>
                        <span class="info-value">{{ $student->lga_of_origin ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Religion</span>
                        <span class="info-value">{{ $student->religion ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Guardians --}}
        <div class="section-card">
            <div class="section-header">
                <span class="section-title">Guardians / Parents</span>
                <button onclick="document.getElementById('add-guardian-form').classList.toggle('hidden')"
                    style="background:#EEF2FF;color:var(--indigo);border:1px solid #C7D2FE;border-radius:7px;padding:5px 12px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit">
                    + Add Guardian
                </button>
            </div>
            <div class="section-body">
                @forelse($student->guardians as $guardian)
                <div class="guardian-card" style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <div class="guardian-name">
                            {{ $guardian->full_name }}
                            @if($guardian->pivot->is_primary_contact)
                                <span class="badge badge-info" style="margin-left:6px">Primary</span>
                            @endif
                        </div>
                        <div class="guardian-meta">
                            {{ ucfirst($guardian->relationship) }}
                            @if($guardian->phone) · {{ $guardian->phone }} @endif
                            @if($guardian->email) · {{ $guardian->email }} @endif
                            @if($guardian->occupation) · {{ $guardian->occupation }} @endif
                        </div>
                        @if($guardian->address)
                        <div class="guardian-meta" style="margin-top:3px">{{ $guardian->address }}</div>
                        @endif
                    </div>
                    <div style="display:flex;gap:6px;flex-shrink:0;margin-left:10px">
                        <a href="{{ route('guardians.edit', $guardian) }}"
                           style="background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;border-radius:6px;padding:4px 10px;font-size:11px;font-weight:600;text-decoration:none">
                           Edit
                        </a>
                        @if(!$guardian->pivot->is_primary_contact)
                        <form method="POST" action="{{ route('guardians.set-primary', [$student, $guardian]) }}" style="display:inline">
                            @csrf
                            <button type="submit" style="background:#F0FDF4;color:#166534;border:1px solid #A7F3D0;border-radius:6px;padding:4px 10px;font-size:11px;font-weight:600;cursor:pointer;font-family:inherit">
                                Set Primary
                            </button>
                        </form>
                        @endif
                        @if($student->guardians->count() > 1)
                        <form method="POST" action="{{ route('guardians.detach', [$student, $guardian]) }}"
                              onsubmit="return confirm('Remove {{ $guardian->full_name }} from this student?')" style="display:inline">
                            @csrf @method('DELETE')
                            <button type="submit" style="background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA;border-radius:6px;padding:4px 10px;font-size:11px;font-weight:600;cursor:pointer;font-family:inherit">
                                Remove
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @empty
                <div class="empty-state">No guardian records found.</div>
                @endforelse

                {{-- Add Guardian inline form --}}
                <div id="add-guardian-form" class="hidden" style="margin-top:14px;background:#F8FAFC;border:1px solid var(--border);border-radius:10px;padding:16px">
                    <div style="font-size:13px;font-weight:600;color:var(--midnight);margin-bottom:12px">Add New Guardian</div>
                    <form method="POST" action="{{ route('guardians.store', $student) }}">
                        @csrf
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                            <div>
                                <label style="font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:4px">First Name *</label>
                                <input type="text" name="first_name" required class="form-control" style="width:100%;padding:7px 9px;font-size:13px;border:1px solid var(--border);border-radius:7px;background:white;font-family:inherit;outline:none">
                            </div>
                            <div>
                                <label style="font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:4px">Last Name *</label>
                                <input type="text" name="last_name" required style="width:100%;padding:7px 9px;font-size:13px;border:1px solid var(--border);border-radius:7px;background:white;font-family:inherit;outline:none">
                            </div>
                            <div>
                                <label style="font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:4px">Phone *</label>
                                <input type="text" name="phone" required style="width:100%;padding:7px 9px;font-size:13px;border:1px solid var(--border);border-radius:7px;background:white;font-family:inherit;outline:none">
                            </div>
                            <div>
                                <label style="font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:4px">Email</label>
                                <input type="email" name="email" style="width:100%;padding:7px 9px;font-size:13px;border:1px solid var(--border);border-radius:7px;background:white;font-family:inherit;outline:none">
                            </div>
                            <div>
                                <label style="font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:4px">Relationship *</label>
                                <select name="relationship" required style="width:100%;padding:7px 9px;font-size:13px;border:1px solid var(--border);border-radius:7px;background:white;font-family:inherit;outline:none">
                                    <option value="father">Father</option>
                                    <option value="mother">Mother</option>
                                    <option value="guardian">Guardian</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:4px">Occupation</label>
                                <input type="text" name="occupation" style="width:100%;padding:7px 9px;font-size:13px;border:1px solid var(--border);border-radius:7px;background:white;font-family:inherit;outline:none">
                            </div>
                        </div>
                        <div style="margin-top:10px">
                            <label style="font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:4px">Address</label>
                            <input type="text" name="address" style="width:100%;padding:7px 9px;font-size:13px;border:1px solid var(--border);border-radius:7px;background:white;font-family:inherit;outline:none">
                        </div>
                        <div style="margin-top:10px;display:flex;align-items:center;gap:8px">
                            <input type="checkbox" name="is_primary" value="1" id="new_primary" style="width:15px;height:15px">
                            <label for="new_primary" style="font-size:12px;color:var(--slate);cursor:pointer">Set as primary contact</label>
                        </div>
                        <div style="margin-top:12px;display:flex;gap:8px">
                            <button type="submit" style="background:var(--indigo);color:white;border:none;border-radius:7px;padding:8px 18px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit">Add Guardian</button>
                            <button type="button" onclick="document.getElementById('add-guardian-form').classList.add('hidden')"
                                style="background:white;color:var(--midnight);border:1px solid var(--border);border-radius:7px;padding:8px 14px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Recent Invoices --}}
        <div class="section-card">
            <div class="section-header">
                <span class="section-title">Recent Invoices</span>
                <span class="badge badge-info">Last 5</span>
            </div>
            @if($student->invoices->count())
            <div class="tbl"><table>
                <thead>
                    <tr>
                        <th>Invoice No.</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($student->invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->invoice_number }}</td>
                        <td>&#8358;{{ number_format($invoice->total_amount) }}</td>
                        <td>&#8358;{{ number_format($invoice->amount_paid) }}</td>
                        <td>
                            @if($invoice->status === 'paid')
                                <span class="badge badge-success">Paid</span>
                            @elseif($invoice->status === 'partially_paid')
                                <span class="badge badge-warning">Partial</span>
                            @else
                                <span class="badge badge-error">Unpaid</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table></div>
            @else
            <div class="empty-state">No invoices generated yet.</div>
            @endif
        </div>

        {{-- ── Report Cards Panel ────────────────────────────────────────── --}}
        <div class="section-card" style="margin-top:20px">
            <div class="section-header" style="display:flex;align-items:center;justify-content:space-between">
                <span class="section-title">📋 Report Cards</span>
                @if(auth()->user()->canAccessModule('transcript'))
        <a href="{{ route('students.transcript', $student) }}"
                   style="font-size:12px;color:var(--indigo);font-weight:600;text-decoration:none">
                    📑 Full Transcript →
                </a>
        @endif
            </div>

            @if($summaries->isEmpty())
            <div class="empty-state">No report cards computed yet.</div>
            @else
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:10px;padding:2px 0">
                @foreach($summaries as $s)
                @php
                    $avg   = $s->final_average ?? 0;
                    $color = $avg >= 70 ? '#059669' : ($avg >= 50 ? '#D97706' : '#DC2626');
                    $status = optional(optional($s->publications)->first())->status ?? 'draft';
                @endphp
                <div style="border:1px solid var(--border);border-radius:10px;padding:14px;background:white;position:relative;overflow:hidden">
                    <div style="position:absolute;top:0;left:0;bottom:0;width:4px;background:{{ $color }}"></div>
                    <div style="padding-left:8px">
                        <div style="font-size:13px;font-weight:700;color:var(--midnight)">
                            {{ optional($s->term)->name }}
                        </div>
                        <div style="font-size:11px;color:var(--slate-light);margin-top:1px">
                            {{ optional(optional($s->term)->session)->name }}
                            · {{ optional(optional($s->classArm)->classLevel)->name }} {{ optional($s->classArm)->name }}
                        </div>
                        <div style="display:flex;align-items:center;gap:12px;margin-top:10px">
                            <div>
                                <div style="font-size:18px;font-weight:800;color:{{ $color }}">
                                    {{ number_format($avg, 1) }}%
                                </div>
                                <div style="font-size:10px;color:var(--slate-light)">Average</div>
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:700;color:var(--indigo)">
                                    {{ $s->position_in_class ? $s->position_in_class.'/'.$s->total_students_in_class : '—' }}
                                </div>
                                <div style="font-size:10px;color:var(--slate-light)">Position</div>
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:700">
                                    {{ $s->subjects_offered ?? '—' }}
                                </div>
                                <div style="font-size:10px;color:var(--slate-light)">Subjects</div>
                            </div>
                        </div>
                        <div style="display:flex;gap:6px;margin-top:12px;flex-wrap:wrap">
                            <a href="{{ route('reports.preview', ['class_arm_id' => $student->current_class_arm_id, 'term_id' => $s->term_id]) }}"
                               style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;font-size:11px;font-weight:600;background:#EFF6FF;color:var(--indigo);border-radius:7px;text-decoration:none">
                                👁 Preview
                            </a>
                            <a href="{{ route('reports.pdf', [$student, 'term_id' => $s->term_id]) }}"
                               style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;font-size:11px;font-weight:600;background:var(--indigo);color:white;border-radius:7px;text-decoration:none"
                               target="_blank">
                                🖨 Print PDF
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>
</div>

@endsection
