@extends('layouts.app')

@section('title', 'Request Interclass Transfer')
@section('page-title', 'Request Interclass Transfer')

@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.ch{padding:14px 20px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:14px;font-weight:700;color:var(--midnight)}
.cb{padding:20px}
.fg{margin-bottom:16px}
.fl{display:block;font-size:12px;font-weight:700;color:var(--midnight);margin-bottom:6px}
.fc{width:100%;padding:10px 13px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC}
.fc:focus{outline:none;border-color:var(--indigo);background:white}
.fc:disabled{opacity:.5;cursor:not-allowed}
.fc.invalid{border-color:#FECACA;background:#FEF2F2}
.field-error{font-size:11px;color:var(--crimson);margin-top:4px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;font-size:13px;font-weight:700;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none}
.btn-p{background:var(--indigo);color:white}
.btn-p:disabled{opacity:.5;cursor:not-allowed}
.btn-g{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.alert-warning{background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:12px 16px;font-size:13px;color:#92400E;margin-bottom:16px}
.alert-info{background:var(--indigo-bg);border:1px solid #FDE68A;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--slate);margin-bottom:16px}
.alert-error{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;font-size:13px;color:#DC2626;margin-bottom:16px}
.row-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.row-third{display:grid;grid-template-columns:1fr 2fr;gap:16px}
@media(max-width:700px){.row-grid,.row-third{grid-template-columns:1fr}}
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px">
    <div style="font-size:12px;color:var(--slate-light)">Create a pending request. The student's class changes only after approval.</div>
    <a href="{{ route('students.class-transfers.index') }}" class="btn btn-g">← Back to Transfers</a>
</div>

@if(!$activeContext)
<div class="alert-warning">⚠ Exactly one active academic session and active term is required before a transfer request can be created.</div>
@else
<div class="alert-info">ℹ Active context: <strong>{{ $activeContext['session']->name }} / {{ $activeContext['term']->name }}</strong></div>
@endif

@if($errors->any())
<div class="alert-error">
    <ul style="margin:0;padding-left:18px">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card">
    <div class="ch">↔ New Transfer Request</div>
    <div class="cb">
        <form method="POST" action="{{ route('students.class-transfers.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="row-grid">
            <div class="fg">
                <label class="fl">Student</label>
                <select name="student_id" class="fc @error('student_id') invalid @enderror" required @disabled(!$activeContext)>
                    <option value="">Select active student</option>
                    @foreach($students as $student)
                    <option value="{{ $student->id }}" @selected((string)old('student_id') === (string)$student->id)>
                        {{ $student->full_name }} - {{ $student->admission_number }} ({{ optional($student->currentClassArm)->full_name ?? 'No class' }})
                    </option>
                    @endforeach
                </select>
                @error('student_id')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="fg">
                <label class="fl">Destination Class Arm</label>
                <select name="to_class_arm_id" class="fc @error('to_class_arm_id') invalid @enderror" required @disabled(!$activeContext)>
                    <option value="">Select destination</option>
                    @foreach($classArms as $arm)
                    <option value="{{ $arm->id }}" @selected((string)old('to_class_arm_id') === (string)$arm->id)>
                        {{ $arm->full_name }}
                    </option>
                    @endforeach
                </select>
                @error('to_class_arm_id')<div class="field-error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row-third">
            <div class="fg">
                <label class="fl">Effective Date</label>
                <input type="date" name="effective_date" class="fc @error('effective_date') invalid @enderror" value="{{ old('effective_date', now()->toDateString()) }}" required @disabled(!$activeContext)>
                @error('effective_date')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="fg">
                <label class="fl">Supporting Document</label>
                <input type="file" name="supporting_document" class="fc @error('supporting_document') invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" @disabled(!$activeContext)>
                @error('supporting_document')<div class="field-error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="fg">
            <label class="fl">Reason</label>
            <textarea name="reason" class="fc @error('reason') invalid @enderror" rows="5" required @disabled(!$activeContext)>{{ old('reason') }}</textarea>
            @error('reason')<div class="field-error">{{ $message }}</div>@enderror
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:8px">
            <a href="{{ route('students.class-transfers.index') }}" class="btn btn-g">Cancel</a>
            <button type="submit" class="btn btn-p" @disabled(!$activeContext)>Create Request</button>
        </div>
        </form>
    </div>
</div>

@endsection
