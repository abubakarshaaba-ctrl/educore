@extends('layouts.app')
@section('title','Bulk Score Import')
@section('page-title','Bulk Score Import')
@push('styles')
<style>
.pg{display:grid;grid-template-columns:1fr 360px;gap:16px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:18px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms;width:100%;justify-content:center}
.btn-p{background:var(--indigo);color:white}.btn-g{background:var(--emerald);color:white}
.steps{counter-reset:step;padding:0;list-style:none}
.step{counter-increment:step;display:flex;gap:12px;margin-bottom:14px;font-size:13px;color:var(--slate)}
.step::before{content:counter(step);min-width:22px;height:22px;border-radius:50%;background:var(--indigo);color:white;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:9px 14px;border-bottom:1px solid var(--border);font-size:12.5px}
tbody tr:last-child td{border-bottom:none}
.badge{display:inline-flex;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px}
.b-done{background:#ECFDF5;color:var(--emerald)}.b-failed{background:#FEF2F2;color:var(--crimson)}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
.alert-e{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--crimson);margin-bottom:14px}
@media(max-width:768px){.pg{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-e">{{ $errors->first() }}</div>@endif
<div class="pg">
  <div>
    <div class="card">
      <div class="ch">Step 1 — Download Score Template</div>
      <div class="cb">
        <p style="font-size:13px;color:var(--slate);margin-bottom:16px">Download a pre-filled CSV template for a specific class, term, and assessment type. Fill in the scores, then upload below.</p>
        <form method="GET" action="{{ route('scores.import.template') }}">
          <div class="fg"><label class="fl">Class *</label>
            <select name="class_arm_id" class="fc" required>
              <option value="">Select class</option>
              @foreach($classArms as $arm)<option value="{{ $arm->id }}">{{ $arm->classLevel->name }} {{ $arm->name }}</option>@endforeach
            </select>
          </div>
          <div class="fg"><label class="fl">Term *</label>
            <select name="term_id" class="fc" required>
              <option value="">Select term</option>
              @foreach($terms as $term)<option value="{{ $term->id }}" {{ $term->is_current ? 'selected' : '' }}>{{ $term->name }} — {{ $term->session->name }}</option>@endforeach
            </select>
          </div>
          <div class="fg"><label class="fl">Assessment Type *</label>
            <select name="assessment_type_id" class="fc" required>
              <option value="">Select type</option>
              @foreach($assessmentTypes as $at)<option value="{{ $at->id }}">{{ $at->name }} ({{ $at->weight_percentage }}%)</option>@endforeach
            </select>
          </div>
          <button type="submit" class="btn btn-g">⬇ Download Template</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="ch">Step 2 — Upload Filled Template</div>
      <div class="cb">
        <form method="POST" action="{{ route('scores.import.upload') }}" enctype="multipart/form-data">
          @csrf
          <div class="fg"><label class="fl">CSV File *</label><input type="file" name="import_file" class="fc" accept=".csv,.txt" required style="padding:6px"></div>
          <button type="submit" class="btn btn-p">⬆ Upload & Import Scores</button>
        </form>
      </div>
    </div>
  </div>
  <div>
    <div class="card">
      <div class="ch">Instructions</div>
      <div class="cb">
        <ol class="steps">
          <li class="step">Select class, term, and assessment type, then download the CSV template</li>
          <li class="step">Open the CSV in Excel or Google Sheets</li>
          <li class="step">Fill in scores in the subject columns (leave blank to skip)</li>
          <li class="step">Do NOT edit the first two header rows</li>
          <li class="step">Save as CSV and upload above</li>
        </ol>
      </div>
    </div>
    <div class="card">
      <div class="ch">Recent Imports</div>
      <div class="tbl"><table>
        <thead><tr><th>File</th><th>Imported</th><th>Failed</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($recentImports as $imp)
        <tr>
          <td style="font-size:11px">{{ Str::limit($imp->filename,30) }}</td>
          <td style="color:var(--emerald)">{{ $imp->rows_imported }}</td>
          <td style="color:{{ $imp->rows_failed>0?'var(--crimson)':'' }}">{{ $imp->rows_failed }}</td>
          <td><span class="badge b-done">Done</span></td>
        </tr>
        @empty
        <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--slate-light)">No imports yet</td></tr>
        @endforelse
        </tbody>
      </table></div>
    </div>
  </div>
</div>
@endsection