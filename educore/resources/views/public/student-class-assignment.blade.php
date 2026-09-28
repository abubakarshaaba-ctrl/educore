<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>EduCore — Class Assignment</title>
<style>
body{margin:0;background:#f4f6f8;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#17202a}.wrap{max-width:680px;margin:0 auto;padding:28px 16px}.card{background:#fff;border:1px solid #e1e6eb;border-radius:12px;padding:24px;box-shadow:0 8px 24px rgba(20,30,40,.06)}h1{font-size:24px;margin:0 0 8px}.muted{color:#66717d}.field{margin-top:18px}label{display:block;font-weight:700;margin-bottom:7px}textarea{width:100%;box-sizing:border-box;min-height:150px;border:1px solid #ccd3da;border-radius:8px;padding:12px;font:inherit}.btn{margin-top:14px;width:100%;border:0;border-radius:8px;padding:12px 16px;background:#1f2937;color:#fff;font-weight:700;cursor:pointer}.alert{padding:12px;border-radius:8px;margin:14px 0;background:#f1f5f9}.error{background:#fff1f2;color:#991b1b}.success{background:#ecfdf5;color:#065f46}.skip{margin-top:10px;font-size:14px}.brand{font-weight:800;letter-spacing:.2px;margin-bottom:18px}
</style></head>
<body><main class="wrap"><div class="brand">EduCore</div><section class="card">
<h1>Add students to {{ $classArm->full_name }}</h1>
<p class="muted">{{ $session->name }} · {{ $term->name }}</p>
<p>Enter student admission numbers separated by commas, spaces, or new lines. Only active students without a current class can be assigned.</p>
@if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
@if(session('skipped'))<div class="alert error"><strong>Not assigned:</strong><div class="skip">@foreach(session('skipped') as $item)<div>{{ $item }}</div>@endforeach</div></div>@endif
@if($errors->any())<div class="alert error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<form method="post">@csrf<div class="field"><label for="admission_numbers">Admission numbers</label><textarea id="admission_numbers" name="admission_numbers" placeholder="e.g. EDU/2026/001&#10;EDU/2026/002" required>{{ old('admission_numbers', $lookup) }}</textarea></div><button class="btn" type="submit">Assign students</button></form>
</section></main></body></html>