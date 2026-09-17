@extends('layouts.app')

@section('title', 'Student Status')
@section('page-title', 'Student Status')

@push('styles')
<style>
    .status-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;min-width:0}
    .status-head>div{min-width:0}
    .status-head h2,.status-head .muted{overflow-wrap:anywhere}
    .page-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(280px,360px);gap:16px;min-width:0}
    .card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:18px;min-width:0}
    .muted{color:var(--slate);font-size:13px;overflow-wrap:anywhere}
    .form-row{margin-bottom:14px;min-width:0}
    .form-row label{display:block;font-size:12px;font-weight:700;color:var(--slate);margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em}
    .form-control{width:100%;min-width:0;max-width:100%;box-sizing:border-box;border:1px solid var(--border);border-radius:8px;padding:10px 12px;font:inherit}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;border:0;border-radius:8px;padding:9px 14px;min-height:40px;font-weight:700;text-decoration:none;cursor:pointer}
    .btn-primary{background:var(--indigo);color:#fff}
    .btn-ghost{background:#fff;color:var(--midnight);border:1px solid var(--border)}
    .error{color:#dc2626;font-size:12px;margin-top:4px;overflow-wrap:anywhere}
    .timeline{display:flex;flex-direction:column;gap:10px;min-width:0}
    .timeline-item{border-left:3px solid var(--indigo);padding-left:12px;min-width:0;overflow-wrap:anywhere}
    .timeline-item>div:first-child{display:flex;align-items:center;gap:6px;flex-wrap:wrap}
    .badge{display:inline-flex;max-width:100%;border-radius:999px;padding:3px 8px;font-size:11px;font-weight:700;background:#eff6ff;color:#2563eb;overflow-wrap:anywhere}
    .status-extra label[style]{text-wrap:pretty}
    .status-extra input[type="checkbox"]{flex:0 0 auto;margin-top:2px}
    @media(max-width:900px){.page-grid{grid-template-columns:1fr}}
    @media(max-width:640px){
        .status-head{align-items:flex-start;flex-direction:column}
        .status-head .btn{width:100%}
        .card{padding:15px}
        .btn-primary{width:100%}
        .timeline-item{padding-left:10px}
    }
    @media(max-width:420px){
        .card{padding:13px}
        .form-control{padding:9px 10px}
        .badge{font-size:10px}
    }
</style>
@endpush

@section('content')
<div class="status-head">
    <div>
        <h2 style="margin:0;font-size:20px">{{ $student->full_name }}</h2>
        <div class="muted">{{ $student->admission_number }} - Current status: <strong>{{ $student->status_label }}</strong></div>
    </div>
    <a href="{{ route('students.show', $student) }}" class="btn btn-ghost">Back to Profile</a>
</div>

@if($errors->any())
    <div class="card" style="border-color:#fecaca;background:#fef2f2;color:#991b1b;margin-bottom:16px">
        {{ $errors->first() }}
    </div>
@endif

<div class="page-grid">
    <div class="card">
        <h3 style="margin-top:0">Change Lifecycle Status</h3>
        @if(empty($allowedDestinations))
            <p class="muted">This student's current status cannot be changed from this form. Use the archive reactivation, readmission, or graduation correction workflow where applicable.</p>
        @elseif(!(auth()->user()?->can('student.status.change') && auth()->user()?->can('student.status.approve')))
            <p class="muted">You can view this student's lifecycle status. Direct lifecycle execution requires both status change and status approval permission.</p>
        @else
            <p class="muted">This action takes effect immediately after you submit it. It is not a pending approval request.</p>
            <form method="POST" action="{{ route('students.status.update', $student) }}" enctype="multipart/form-data" id="statusForm">
                @csrf
                <div class="form-row">
                    <label for="new_status">New Status</label>
                    <select name="new_status" id="new_status" class="form-control" required>
                        <option value="">Select status</option>
                        @foreach($allowedDestinations as $status)
                            <option value="{{ $status }}" {{ old('new_status') === $status ? 'selected' : '' }}>
                                {{ $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('new_status')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="form-row">
                    <label for="effective_date" id="effectiveDateLabel">Effective Date</label>
                    <input type="date" name="effective_date" id="effective_date" class="form-control" value="{{ old('effective_date') }}" required>
                    @error('effective_date')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="form-row">
                    <label for="reason" id="reasonLabel">Reason</label>
                    <textarea name="reason" id="reason" rows="4" class="form-control" required>{{ old('reason') }}</textarea>
                    @error('reason')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="form-row status-extra" data-status="transferred_out" style="display:none">
                    <label for="destination_school">Destination School</label>
                    <input type="text" name="destination_school" id="destination_school" class="form-control" value="{{ old('destination_school') }}">
                    @error('destination_school')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="form-row status-extra" data-status="transferred_out" style="display:none">
                    <label for="transfer_certificate_number">Transfer Certificate Number</label>
                    <input type="text" name="transfer_certificate_number" id="transfer_certificate_number" class="form-control" value="{{ old('transfer_certificate_number') }}">
                    @error('transfer_certificate_number')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="form-row">
                    <label for="document">Supporting Document</label>
                    <input type="file" name="document" id="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    <div class="muted">Optional. PDF, Word, JPG or PNG. Max 5 MB.</div>
                    @error('document')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="form-row status-extra" data-status="graduated" style="display:none">
                    <label style="display:flex;gap:8px;align-items:center;text-transform:none;letter-spacing:0">
                        <input type="checkbox" name="confirmation" value="1" {{ old('confirmation') ? 'checked' : '' }}>
                        I confirm this is a graduation lifecycle change.
                    </label>
                    @error('confirmation')<div class="error">{{ $message }}</div>@enderror
                </div>

                <button class="btn btn-primary" type="submit">Save Status Change</button>
            </form>
        @endif
    </div>

    <div class="card">
        <h3 style="margin-top:0">Status History</h3>
        <div class="timeline">
            @forelse($histories as $history)
                <div class="timeline-item">
                    <div>
                        <span class="badge">{{ $statusLabels[$history->old_status] ?? $history->old_status ?? 'None' }}</span>
                        to
                        <span class="badge">{{ $statusLabels[$history->new_status] ?? $history->new_status }}</span>
                    </div>
                    <div class="muted">{{ optional($history->effective_date)->format('d M Y') }} by {{ optional($history->changedBy)->name ?? 'Unknown' }}</div>
                    <div style="font-size:13px;margin-top:4px">{{ $history->reason }}</div>
                    @if($history->document_path)
                        <a href="{{ route('students.status-history.document', $history) }}" class="muted">Download document</a>
                    @endif
                </div>
            @empty
                <p class="muted">No lifecycle status history yet.</p>
            @endforelse
        </div>
    </div>
</div>

<script>
    (function(){
        const select = document.getElementById('new_status');
        const extras = document.querySelectorAll('.status-extra');
        const effectiveLabel = document.getElementById('effectiveDateLabel');
        const reasonLabel = document.getElementById('reasonLabel');

        function updateFields(){
            const value = select ? select.value : '';
            extras.forEach((field) => {
                field.style.display = field.dataset.status === value ? '' : 'none';
            });
            if (effectiveLabel) {
                effectiveLabel.textContent = value === 'graduated'
                    ? 'Graduation Date'
                    : (value === 'suspended' ? 'Suspension Start Date' : 'Effective Date');
            }
            if (reasonLabel) {
                reasonLabel.textContent = value === 'withdrawn'
                    ? 'Withdrawal Reason'
                    : (value === 'graduated' ? 'Graduation Note' : 'Reason');
            }
        }

        if (select) {
            select.addEventListener('change', updateFields);
            updateFields();
        }
    })();
</script>
@endsection
