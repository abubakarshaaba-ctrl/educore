@extends('layouts.app')
@section('title','Application Details')
@section('page-title','Admissions')
@push('styles')
<style>
.page-grid{display:grid;grid-template-columns:300px 1fr;gap:16px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:18px}
.info-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px}
.info-row:last-child{border-bottom:none}
.ik{color:var(--slate);font-size:12px}.iv{font-weight:600;color:var(--midnight)}
.status-big{display:inline-flex;font-size:13px;font-weight:700;padding:6px 16px;border-radius:20px;margin-bottom:14px}
.s-pending{background:#FFFBEB;color:#92400E}.s-admitted{background:#ECFDF5;color:#065F46}
.s-shortlisted{background:#EFF6FF;color:#1E40AF}.s-rejected{background:#FEF2F2;color:#991B1B}.s-withdrawn{background:#F1F5F9;color:var(--slate)}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms;width:100%;justify-content:center;text-decoration:none}
.btn-p{background:var(--indigo);color:white}.btn-g{background:var(--emerald);color:white}.btn-r{background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA}
.btn-ghost{background:white;border:1px solid var(--border);color:var(--midnight)}
.enrolled-notice{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;padding:14px 16px;font-size:13px;color:#065F46;margin-bottom:14px}
.back{font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
@media(max-width:768px){.page-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
<a href="{{ route('admissions.index') }}" class="back">← Back to Admissions</a>

@if($admission->enrolled_as_student_id)
<div class="enrolled-notice">
  ✅ This applicant has been enrolled as a student.
  <a href="{{ route('students.show',$admission->enrolled_as_student_id) }}" style="font-weight:700;color:inherit"> View student record →</a>
</div>
@endif

<div class="page-grid">
  <div>
    <div class="card">
      <div style="padding:20px;text-align:center;border-bottom:1px solid var(--border)">
        <div style="width:64px;height:64px;border-radius:50%;background:var(--indigo);color:white;font-size:24px;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 12px">{{ strtoupper(substr($admission->first_name,0,1)) }}</div>
        <div style="font-size:16px;font-weight:700">{{ $admission->first_name }} {{ $admission->last_name }}</div>
        <div style="font-size:11px;color:var(--slate-light);margin-top:3px;font-family:monospace">{{ $admission->application_number }}</div>
        <div style="margin-top:8px"><span class="status-big s-{{ $admission->status }}">{{ ucfirst($admission->status) }}</span></div>
      </div>
      <div style="padding:12px 16px">
        <div class="info-row"><span class="ik">Applied</span><span class="iv" style="font-size:12px">{{ \Carbon\Carbon::parse($admission->application_date)->format('d M Y') }}</span></div>
        <div class="info-row"><span class="ik">DOB</span><span class="iv" style="font-size:12px">{{ \Carbon\Carbon::parse($admission->date_of_birth)->format('d M Y') }}</span></div>
        <div class="info-row"><span class="ik">Gender</span><span class="iv">{{ ucfirst($admission->gender) }}</span></div>
        <div class="info-row"><span class="ik">Religion</span><span class="iv">{{ $admission->religion ?? '—' }}</span></div>
        <div class="info-row"><span class="ik">State</span><span class="iv">{{ $admission->state_of_origin ?? '—' }}</span></div>
        <div class="info-row"><span class="ik">For Class</span><span class="iv">{{ optional($admission->applyingForClassLevel)->name ?? '—' }}</span></div>
        <div class="info-row"><span class="ik">Prev School</span><span class="iv" style="font-size:12px">{{ $admission->previous_school ?? '—' }}</span></div>
      </div>
    </div>

    <div class="card">
      <div class="ch">Guardian</div>
      <div style="padding:12px 16px">
        <div class="info-row"><span class="ik">Name</span><span class="iv">{{ $admission->guardian_name }}</span></div>
        <div class="info-row"><span class="ik">Phone</span><span class="iv">{{ $admission->guardian_phone }}</span></div>
        <div class="info-row"><span class="ik">Email</span><span class="iv" style="font-size:12px">{{ $admission->guardian_email ?? '—' }}</span></div>
        <div class="info-row"><span class="ik">Relationship</span><span class="iv">{{ ucfirst($admission->guardian_relationship) }}</span></div>
        <div class="info-row"><span class="ik">Occupation</span><span class="iv">{{ $admission->guardian_occupation ?? '—' }}</span></div>
      </div>
    </div>
  </div>

  <div>
    @if($admission->notes)
    <div class="card">
      <div class="ch">Notes</div>
      <div class="cb" style="font-size:13px;color:var(--slate);line-height:1.6">{{ $admission->notes }}</div>
    </div>
    @endif

    @if($admission->status !== 'admitted')
    <div class="card">
      <div class="ch">Update Application Status</div>
      <div class="cb">
        <form method="POST" action="{{ route('admissions.status',$admission) }}">
          @csrf @method('PATCH')
          <div class="fg"><label class="fl">New Status</label>
            <select name="status" class="fc">
              @foreach(['pending','shortlisted','admitted','rejected','withdrawn'] as $s)
              <option value="{{ $s }}" {{ $admission->status===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
              @endforeach
            </select>
          </div>
          @if($admission->status !== 'admitted')
          <div class="fg"><label class="fl">Assign Class Arm (on admission)</label>
            <select name="class_arm_id" class="fc">
              <option value="">— Assign later —</option>
              @foreach($classArms as $arm)<option value="{{ $arm->id }}">{{ $arm->classLevel->name }} {{ $arm->name }}</option>@endforeach
            </select>
          </div>
          @endif
          <div class="fg"><label class="fl">Notes / Decision Reason</label><textarea name="notes" class="fc" rows="3" placeholder="Optional reason for this decision">{{ $admission->notes }}</textarea></div>
          <button type="submit" class="btn btn-p">Update Status</button>
        </form>
      </div>
    </div>
    @endif

    @if($admission->status === 'admitted')
    <div class="card">
      <div class="ch">Admission Offer Letter</div>
      <div class="cb" style="display:flex;flex-direction:column;gap:8px">
        @if($admission->offer_letter_sent)
          <div style="font-size:12px;color:var(--slate)">✓ Offer sent {{ $admission->offer_sent_at?->format('d M Y, h:ia') }}</div>
        @endif
        <form method="POST" action="{{ route('admissions.offer',$admission) }}">
          @csrf
          <button type="submit" class="btn btn-p" style="width:100%">
            {{ $admission->offer_letter_sent ? 'Resend Offer Letter' : 'Send Offer Letter' }}
          </button>
        </form>
        <a href="{{ route('admissions.offer.download',$admission) }}" class="btn btn-ghost">⬇ Download Offer Letter PDF</a>
      </div>
    </div>
    @endif

    <div class="card">
      <div class="ch">Actions</div>
      <div class="cb" style="display:flex;flex-direction:column;gap:8px">
        <a href="{{ route('admissions.create') }}" class="btn btn-ghost">+ New Application</a>
        @if($admission->status==='rejected' || $admission->status==='withdrawn')
        <form method="POST" action="{{ route('admissions.destroy',$admission) }}" onsubmit="return confirm('Permanently delete this application?')">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-r">Delete Application</button>
        </form>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection