@extends('layouts.app')

@section('title', 'Reactivate Student')
@section('page-title', 'Reactivate Student')

@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;width:100%;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.ch{padding:14px 20px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:14px;font-weight:700;color:var(--midnight)}
.cb{padding:20px}
.fg{margin-bottom:16px}
.fl{display:block;font-size:12px;font-weight:700;color:var(--midnight);margin-bottom:6px}
.fc{width:100%;padding:10px 13px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC}
.fc:focus{outline:none;border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;font-size:13px;font-weight:700;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none}
.btn-p{background:var(--indigo);color:white}
.btn-g{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.alert-error{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px 14px;font-size:13px;color:#DC2626;margin-bottom:16px}
.status-note{background:var(--indigo-bg);border:1px solid #FDE68A;border-radius:8px;padding:12px 16px;font-size:12.5px;color:var(--slate);margin-bottom:18px;line-height:1.6}
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;gap:10px;margin-bottom:18px">
    <a href="{{ route('students.archive.show', $student) }}" class="btn btn-g">← {{ $student->full_name }}</a>
</div>

<div class="card">
    <div class="ch">↺ Reactivate {{ $student->full_name }}</div>
    <div class="cb">
        <div class="status-note">Current status: <strong style="color:var(--midnight)">{{ $student->status_label }}</strong></div>

        @if($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('students.reactivate', $student) }}" enctype="multipart/form-data">
            @csrf
            <div class="fg">
                <label class="fl">Effective Date</label>
                <input type="date" name="effective_date" class="fc" value="{{ old('effective_date') }}" required>
            </div>

            @if($requiresClassArm)
            <div class="fg">
                <label class="fl">Class Arm</label>
                <select name="class_arm_id" class="fc" required>
                    <option value="">Select class</option>
                    @foreach($classArms as $arm)
                    <option value="{{ $arm->id }}" {{ old('class_arm_id') == $arm->id ? 'selected' : '' }}>
                        {{ optional($arm->classLevel)->name }} {{ $arm->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="fg">
                <label class="fl">Reason</label>
                <textarea name="reason" class="fc" rows="4" required>{{ old('reason') }}</textarea>
            </div>

            <div class="fg">
                <label class="fl">Supporting Document</label>
                <input type="file" name="document" class="fc" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
            </div>

            <button type="submit" class="btn btn-p">↺ Reactivate Student</button>
            <a href="{{ route('students.archive.show', $student) }}" class="btn btn-g">Cancel</a>
        </form>
    </div>
</div>

@endsection
