@extends('layouts.app')
@section('title','Health Record')
@section('page-title','Health Records')
@push('styles')
<style>
.page-grid{display:grid;grid-template-columns:260px 1fr;gap:16px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:18px}
.fr{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}
.info-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px}
.info-row:last-child{border-bottom:none}
.ik{color:var(--slate);font-weight:500}.iv{font-weight:600;color:var(--midnight)}
.big-av{width:64px;height:64px;border-radius:50%;background:var(--indigo);color:white;font-size:24px;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 12px}
.back{font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
@media(max-width:768px){.page-grid{grid-template-columns:1fr}.fr{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
<a href="{{ route('health.index') }}" class="back">← Back to Health Records</a>
<div class="page-grid">
  <div>
    <div class="card">
      <div style="padding:20px;text-align:center;border-bottom:1px solid var(--border)">
        <div class="big-av">{{ strtoupper(substr($student->first_name,0,1)) }}</div>
        <div style="font-size:15px;font-weight:700">{{ $student->full_name }}</div>
        <div style="font-size:12px;color:var(--slate-light);margin-top:3px">{{ $student->admission_number }}</div>
        <div style="font-size:11px;color:var(--slate);margin-top:3px">{{ optional(optional($student->currentClassArm)->classLevel)->name }} {{ optional($student->currentClassArm)->name }}</div>
      </div>
      <div style="padding:12px 16px">
        <div class="info-row"><span class="ik">Blood Group</span><span class="iv" style="color:var(--crimson)">{{ $record->blood_group ?? '—' }}</span></div>
        <div class="info-row"><span class="ik">Genotype</span><span class="iv">{{ $record->genotype ?? '—' }}</span></div>
        <div class="info-row"><span class="ik">Emergency</span><span class="iv" style="font-size:11px">{{ $record->emergency_contact_phone ?? '—' }}</span></div>
      </div>
    </div>
  </div>
  <div>
    <div class="card">
      <div class="ch">Health Record</div>
      <div class="cb">
        <form method="POST" action="{{ route('health.upsert',$student) }}">
        @csrf
        <div class="fr">
          <div class="fg"><label class="fl">Blood Group</label>
            <select name="blood_group" class="fc">
              <option value="">Unknown</option>
              @foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bg)<option value="{{ $bg }}" {{ $record->blood_group===$bg?'selected':'' }}>{{ $bg }}</option>@endforeach
            </select>
          </div>
          <div class="fg"><label class="fl">Genotype</label>
            <select name="genotype" class="fc">
              <option value="">Unknown</option>
              @foreach(['AA','AS','SS','AC','SC'] as $g)<option value="{{ $g }}" {{ $record->genotype===$g?'selected':'' }}>{{ $g }}</option>@endforeach
            </select>
          </div>
        </div>
        <div class="fg"><label class="fl">Known Allergies</label><textarea name="allergies" class="fc" rows="2" placeholder="e.g. Penicillin, peanuts">{{ $record->allergies }}</textarea></div>
        <div class="fg"><label class="fl">Chronic Conditions</label><textarea name="chronic_conditions" class="fc" rows="2" placeholder="e.g. Asthma, diabetes">{{ $record->chronic_conditions }}</textarea></div>
        <div class="fg"><label class="fl">Current Medications</label><textarea name="current_medications" class="fc" rows="2">{{ $record->current_medications }}</textarea></div>
        <div class="fg"><label class="fl">Disability / Special Needs</label><input type="text" name="disability" class="fc" value="{{ $record->disability }}"></div>
        <div style="border-top:1px solid var(--border);padding-top:14px;margin-top:4px;margin-bottom:14px;font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em">Emergency Contact</div>
        <div class="fr">
          <div class="fg"><label class="fl">Contact Name</label><input type="text" name="emergency_contact_name" class="fc" value="{{ $record->emergency_contact_name }}"></div>
          <div class="fg"><label class="fl">Phone</label><input type="tel" name="emergency_contact_phone" class="fc" value="{{ $record->emergency_contact_phone }}"></div>
          <div class="fg"><label class="fl">Relationship</label><input type="text" name="emergency_contact_relationship" class="fc" value="{{ $record->emergency_contact_relationship }}"></div>
          <div class="fg"><label class="fl">Doctor Name</label><input type="text" name="doctor_name" class="fc" value="{{ $record->doctor_name }}"></div>
        </div>
        <div class="fg"><label class="fl">Additional Notes</label><textarea name="notes" class="fc" rows="2">{{ $record->notes }}</textarea></div>
        <button type="submit" class="btn btn-p">Save Health Record</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection