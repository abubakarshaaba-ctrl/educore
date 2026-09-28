<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>EduCore — Class Assignment</title>
<style>
body{margin:0;background:#f4f6f8;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#17202a}.wrap{max-width:680px;margin:0 auto;padding:28px 16px}.card{background:#fff;border:1px solid #e1e6eb;border-radius:12px;padding:24px;box-shadow:0 8px 24px rgba(20,30,40,.06)}h1{font-size:24px;margin:0 0 8px}.muted{color:#66717d}.field{margin-top:18px}label{display:block;font-weight:700;margin-bottom:7px}select,textarea{width:100%;box-sizing:border-box;border:1px solid #ccd3da;border-radius:8px;padding:12px;font:inherit;background:#fff}textarea{min-height:150px}.btn{margin-top:14px;width:100%;border:0;border-radius:8px;padding:12px 16px;background:#1f2937;color:#fff;font-weight:700;cursor:pointer}.alert{padding:12px;border-radius:8px;margin:14px 0;background:#f1f5f9}.error{background:#fff1f2;color:#991b1b}.success{background:#ecfdf5;color:#065f46}.skip{margin-top:10px;font-size:14px}.brand{font-weight:800;letter-spacing:.2px;margin-bottom:18px}
</style></head>
<body><main class="wrap"><div class="brand">EduCore</div><section class="card">
<h1>Assign students to a class</h1>
<p class="muted">{{ $session->name }} · {{ $term->name }}</p>
<p>Select the destination class/class arm, then enter the students' admission numbers. The same secure link can be reused for any known class in this school until it expires or is revoked.</p>
@if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
@if(session('skipped'))<div class="alert error"><strong>Not assigned:</strong><div class="skip">@foreach(session('skipped') as $item)<div>{{ $item }}</div>@endforeach</div></div>@endif
@if($errors->any())<div class="alert error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<form method="post">@csrf
<div class="field"><label for="class_arm_id">Destination class / class arm</label>
<select id="class_arm_id" name="class_arm_id" required>
<option value="">Select a class / class arm</option>
@foreach($classes as $class)
<option value="{{ $class->id }}" @selected((string) $selectedClassArmId === (string) $class->id)>{{ $class->full_name }}</option>
@endforeach
</select></div>
<div class="field"><label for="admission_numbers">Admission numbers</label><textarea id="admission_numbers" name="admission_numbers" placeholder="e.g. EDU/2026/001&#10;EDU/2026/002" required>{{ old('admission_numbers', $lookup) }}</textarea></div><button class="btn" type="submit">Assign students</button></form>
</section></main></body></html>
