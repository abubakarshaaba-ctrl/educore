@extends('layouts.app')

@section('title', 'Student Status')
@section('page-title', 'Student Status')

@push('styles')
<style>
.page-grid{display:grid;grid-template-columns:1fr 360px;gap:18px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:18px;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:18px}
.fg{margin-bottom:16px}
.fl{display:block;font-size:12px;font-weight:700;color:var(--midnight);margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em}
.fc{width:100%;padding:10px 13px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC}
.fc:focus{outline:none;border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;font-size:13px;font-weight:700;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none}
.btn-p{background:var(--indigo);color:white}
.btn-g{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.field-error{color:var(--crimson);font-size:11px;margin-top:4px}
.alert-error{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px 14px;font-size:13px;color:#DC2626;margin-bottom:16px}
.status-note{font-size:12.5px;color:var(--slate-light);line-height:1.6}
.timeline{display:flex;flex-direction:column;gap:14px}
.t-item{position:relative;padding-left:14px;border-left:3px solid var(--indigo)}
.badge{display:inline-flex;align-items:center;font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;background:var(--indigo-bg);color:var(--indigo)}
.confirm-row{display:flex;gap:8px;align-items:flex-start;font-size:12.5px;color:var(--slate);text-transform:none;letter-spacing:0;margin-top:4px}
.confirm-row input{margin-top:2px}
@media(max-width:900px){.page-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px">
    <div>
        <div style="font-size:16px;font-weight:800;color:var(--midnight)">{{ $student->full_name }}</div>
        <div style="font-size:11px;color:var(--slate-light)">{{ $student->admission_number }} &middot; Current status: <strong>{{ $student->status_label }}</strong></div>
    </div>
    <a href="{{ route('students.show', $student) }}" class="btn btn-g">← Back to Profile</a>
</div>

@if($errors->any())
<div class="alert-error">{{ $errors->first() }}</div>
@endif

<div class="page-grid">
    <div>
        <div class="card">
            <div class="ch">⚠ Change Lifecycle Status</div>
            <div class="cb">
                @if(empty($allowedDestinations))
                <p class="status-note">This student's current status cannot be changed from this form. Use the archive reactivation, readmission, or graduation correction workflow where applicable.</p>
                @elseif(!(auth()->user()?->can('student.status.change') && auth()->user()?->can('student.status.approve')))
                <p class="status-note">You can view this student's lifecycle status. Direct lifecycle execution requires both status change and status approval permission.</p>
                @else
                <p class="status-note" style="margin-bottom:18px">This action takes effect immediately after you submit it. It is not a pending approval request.</p>
                <form method="POST" action="{{ route('students.status.update', $student) }}" enctype="multipart/form-data" id="statusForm">
                    @csrf
                    <div class="fg">
                        <label class="fl" for="new_status">New Status</label>
                        <select name="new_status" id="new_status" class="fc" required>
                            <option value="">Select status</option>
                            @foreach($allowedDestinations as $status)
                            <option value="{{ $status }}" {{ old('new_status') === $status ? 'selected' : '' }}>
                                {{ $statusLabels[$status] ?? ucfirst(str_replace('_',' ',$status)) }}
                            </option>
                            @endforeach
                        </select>
                        @error('new_status')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="fg">
                        <label class="fl" for="effective_date" id="effectiveDateLabel">Effective Date</label>
                        <input type="date" name="effective_date" id="effective_date" class="fc" value="{{ old('effective_date') }}" required>
                        @error('effective_date')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="fg">
                        <label class="fl" for="reason" id="reasonLabel">Reason</label>
                        <textarea name="reason" id="reason" class="fc" rows="4" required>{{ old('reason') }}</textarea>
                        @error('reason')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="fg status-extra" data-status="transferred_out" style="display:none">
                        <label class="fl" for="destination_school">Destination School</label>
                        <input type="text" name="destination_school" id="destination_school" class="fc" value="{{ old('destination_school') }}">
                        @error('destination_school')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="fg status-extra" data-status="transferred_out" style="display:none">
                        <label class="fl" for="transfer_certificate_number">Transfer Certificate Number</label>
                        <input type="text" name="transfer_certificate_number" id="transfer_certificate_number" class="fc" value="{{ old('transfer_certificate_number') }}">
                        @error('transfer_certificate_number')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="fg">
                        <label class="fl" for="document">Supporting Document</label>
                        <input type="file" name="document" id="document" class="fc" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <div style="font-size:11px;color:var(--slate-light);margin-top:4px">Optional. PDF, Word, JPG or PNG. Max 5 MB.</div>
                        @error('document')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="fg status-extra" data-status="graduated" style="display:none">
                        <label class="confirm-row">
                            <input type="checkbox" name="confirmation" value="1" {{ old('confirmation') ? 'checked' : '' }}>
                            I confirm this is a graduation lifecycle change.
                        </label>
                        @error('confirmation')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-p">Save Status Change</button>
                </form>
                @endif
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="ch">📋 Status History</div>
            <div class="cb">
                <div class="timeline">
                @forelse($histories as $history)
                <div class="t-item">
                    <div>
                        <span class="badge">{{ $statusLabels[$history->old_status] ?? $history->old_status ?? 'None' }}</span>
                        →
                        <span class="badge">{{ $statusLabels[$history->new_status] ?? $history->new_status }}</span>
                    </div>
                    <div style="font-size:11px;color:var(--slate-light);margin-top:4px">{{ optional($history->effective_date)->format('d M Y') }} by {{ optional($history->changedBy)->name ?? 'Unknown' }}</div>
                    <div style="font-size:12.5px;color:var(--slate);margin-top:4px">{{ $history->reason }}</div>
                    @if($history->document_path)
                    <a href="{{ route('students.status-history.document', $history) }}" style="font-size:11px;color:var(--indigo);display:inline-block;margin-top:4px">📄 Download document</a>
                    @endif
                </div>
                @empty
                <p class="status-note">No lifecycle status history yet.</p>
                @endforelse
                </div>
            </div>
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
