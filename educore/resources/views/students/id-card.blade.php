<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student ID Card — {{ $student->full_name }}</title>
<link rel="icon" type="image/svg+xml" href="/brand/favicon.svg">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif;background:#F1F5F9;display:flex;flex-direction:column;align-items:center;min-height:100vh;padding:28px 20px;gap:18px}
.ctrl{display:flex;align-items:center;gap:10px;background:white;border:1px solid #E2E8F0;border-radius:10px;padding:12px 20px}
.ctrl h2{font-size:14px;font-weight:700;color:#1E293B;margin-right:8px}
.btn{padding:8px 16px;font-size:12px;font-weight:700;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.btn-primary{background:#D79A21;color:#071E45}
.btn-ghost{background:#F1F5F9;color:#475569;border:1px solid #E2E8F0}

.id-card{width:338px;height:213px;border-radius:12px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.18);position:relative;background:white;page-break-inside:avoid;display:flex;flex-direction:column}
.ch{padding:9px 12px 8px;display:flex;align-items:center;justify-content:space-between;background:#071E45;flex-shrink:0}
.school-name{font-size:10px;font-weight:800;color:white;letter-spacing:.07em;text-transform:uppercase;line-height:1.3}
.school-tag{font-size:7px;color:rgba(255,255,255,.55);font-weight:600;letter-spacing:.06em;text-transform:uppercase;margin-top:1px}
.cb{flex:1;display:flex;padding:10px 12px;gap:11px;align-items:flex-start;overflow:hidden}
.photo-wrap{flex-shrink:0;width:68px}
.student-photo{width:68px;height:88px;object-fit:cover;object-position:top;border:2px solid #E2E8F0;background:#F1F5F9;display:block}
.student-photo-init{width:68px;height:88px;background:#071E45;color:#D79A21;font-size:26px;font-weight:800;display:flex;align-items:center;justify-content:center;border:2px solid #E2E8F0}
.info{flex:1;min-width:0}
.s-name{font-size:12.5px;font-weight:800;color:#0F172A;line-height:1.25}
.s-row{font-size:9px;color:#475569;margin-top:5px;display:flex;gap:5px}
.s-row b{color:#0F172A;font-weight:700}
.cf{padding:6px 12px;background:#F8FAFC;border-top:1px solid #E2E8F0;font-size:7px;color:#94A3B8;text-align:center}
@media print{
  body{background:white;padding:0}
  .ctrl{display:none}
  .id-card{box-shadow:none}
}
</style>
</head>
<body>
<div class="ctrl">
    <h2>Student ID Card</h2>
    <button class="btn btn-primary" onclick="window.print()">🖨 Print</button>
    <a href="javascript:history.back()" class="btn btn-ghost">← Back</a>
</div>

<div class="id-card">
    <div class="ch">
        <div>
            <div class="school-name">{{ $tenant?->name ?? 'EduCore School' }}</div>
            <div class="school-tag">Student Identification</div>
        </div>
        @if($logo)<img src="{{ $logo }}" alt="Logo" style="width:26px;height:26px;border-radius:5px">@endif
    </div>
    <div class="cb">
        <div class="photo-wrap">
            @if($hasPhoto)
                <img class="student-photo" src="{{ asset('storage/' . $student->passport_photo_path) }}" alt="Photo">
            @else
                <div class="student-photo-init">{{ strtoupper(substr($student->first_name, 0, 1)) }}</div>
            @endif
        </div>
        <div class="info">
            <div class="s-name">{{ $student->full_name }}</div>
            <div class="s-row"><span>Adm No:</span><b>{{ $student->admission_number }}</b></div>
            <div class="s-row"><span>Class:</span><b>{{ optional(optional($student->currentClassArm)->classLevel)->name }} {{ optional($student->currentClassArm)->name }}</b></div>
            <div class="s-row"><span>Gender:</span><b>{{ ucfirst($student->gender ?? '—') }}</b></div>
            <div class="s-row"><span>DOB:</span><b>{{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d M Y') : '—' }}</b></div>
        </div>
    </div>
    <div class="cf">If found, please return to {{ $tenant?->name ?? 'the school' }}. {{ $tenant?->phone ?? '' }}</div>
</div>
</body>
</html>
