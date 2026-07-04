@extends('layouts.app')
@section('title','Library Loans')
@section('page-title','Library — Loans & Returns')
@push('styles')
<style>
.page-grid{display:grid;grid-template-columns:1fr 380px;gap:16px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:16px}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:10px 14px;border-bottom:1px solid var(--border);font-size:12.5px}
tbody tr:last-child td{border-bottom:none}tbody tr:hover td{background:#F8FAFC}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms;width:100%;justify-content:center}
.btn-p{background:var(--indigo);color:white}.btn-g{background:var(--emerald);color:white}
.btn-sm{padding:4px 10px;font-size:11px;width:auto}
.badge{display:inline-flex;font-size:10px;font-weight:600;padding:2px 7px;border-radius:20px}
.b-issued{background:#EFF6FF;color:var(--indigo)}.b-returned{background:#ECFDF5;color:var(--emerald)}.b-overdue{background:#FEF2F2;color:var(--crimson)}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%;transition:border 200ms}
.fc:focus{border-color:var(--indigo);background:white}
.back-link{font-size:13px;color:var(--indigo);text-decoration:none;font-weight:500;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
@media(max-width:768px){.page-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
<a href="{{ route('library.index') }}" class="back-link">← Back to Library</a>
<div class="page-grid">
  <div>
    <div class="card">
      <div class="ch">Active & Recent Loans</div>
      <div class="tbl"><table>
        <thead><tr><th>Book</th><th>Student</th><th>Issued</th><th>Due</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($loans as $loan)
        <tr>
            <td><strong>{{ optional($loan->book)->title }}</strong></td>
            <td>{{ optional($loan->student)->full_name ?? '—' }}</td>
            <td style="font-size:11px">{{ \Carbon\Carbon::parse($loan->issue_date)->format('d M Y') }}</td>
            <td style="font-size:11px;color:{{ $loan->status==='overdue'?'var(--crimson)':'' }}">{{ \Carbon\Carbon::parse($loan->due_date)->format('d M Y') }}</td>
            <td><span class="badge b-{{ $loan->status }}">{{ ucfirst($loan->status) }}</span></td>
            <td>
                @if($loan->status!=='returned')
                <form method="POST" action="{{ route('library.return',$loan) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-g btn-sm">Return</button>
                </form>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--slate-light)">No loans found</td></tr>
        @endforelse
        </tbody>
      </table></div>
      {{ $loans->links() }}
    </div>
  </div>
  <div>
    <div class="card">
      <div class="ch">Issue Book</div>
      <div class="cb">
        <form method="POST" action="{{ route('library.issue') }}">
          @csrf
          <div class="fg"><label class="fl">Book *</label>
            <select name="book_id" class="fc" required>
                <option value="">Select book</option>
                @foreach($books as $b)<option value="{{ $b->id }}">{{ $b->title }} ({{ $b->available_copies }} avail.)</option>@endforeach
            </select>
          </div>
          <div class="fg"><label class="fl">Student *</label>
            <select name="student_id" class="fc" required>
                <option value="">Select student</option>
                @foreach($students as $s)<option value="{{ $s->id }}">{{ $s->full_name }} - {{ $s->admission_number }}</option>@endforeach
            </select>
          </div>
          <div class="fg"><label class="fl">Due Date *</label><input type="date" name="due_date" class="fc" required value="{{ now()->addDays(14)->format('Y-m-d') }}"></div>
          <button type="submit" class="btn btn-p">Issue Book</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection