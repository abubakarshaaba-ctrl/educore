@extends('layouts.app')
@section('title','Certificates & Testimonials')
@section('page-title','Certificates & Testimonials')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:18px}
.fr{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;border-radius:8px;border:none;cursor:pointer;font-family:inherit}
.btn-p{background:var(--indigo);color:white}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94A3B8;text-align:left}
td{padding:10px 14px;border-bottom:1px solid var(--border)}
tr:last-child td{border:none}
@media(max-width:640px){.fr{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<div class="card">
    <div class="ch">Generate Certificate</div>
    <div class="cb">
        <form method="POST" action="{{ route('certificates.generate') }}">
            @csrf
            <div class="fr">
                <div class="fg"><label class="fl">Student *</label>
                    <select name="student_id" class="fc" required>
                        <option value="">Select student...</option>
                        @foreach($students as $s)
                        <option value="{{ $s->id }}">{{ $s->full_name }} ({{ $s->admission_number }}) — {{ ucfirst($s->status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fg"><label class="fl">Certificate Type *</label>
                    <select name="certificate_type" class="fc" required>
                        <option value="leaving_certificate">Leaving Certificate</option>
                        <option value="testimonial">Testimonial</option>
                        <option value="transfer_certificate">Transfer Certificate</option>
                    </select>
                </div>
            </div>
            <div class="fg"><label class="fl">Additional Remarks</label>
                <textarea name="remarks" class="fc" rows="2" placeholder="Optional remarks to include on the document"></textarea>
            </div>
            <button type="submit" class="btn btn-p">📄 Generate & Download PDF</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="ch">Issued Certificates</div>
    <div style="overflow-x:auto"><table>
        <thead><tr><th>Student</th><th>Type</th><th>Serial No.</th><th>Issued By</th><th>Date</th></tr></thead>
        <tbody>
        @forelse($issuances as $i)
        <tr>
            <td>{{ optional($i->student)->full_name }}</td>
            <td>{{ ucwords(str_replace('_', ' ', $i->certificate_type)) }}</td>
            <td style="font-family:monospace;font-size:12px">{{ $i->serial_number }}</td>
            <td>{{ optional($i->issuer)->name ?? '—' }}</td>
            <td>{{ $i->issued_at?->format('d M Y') }}</td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;padding:30px;color:#94A3B8">No certificates issued yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="padding:14px">{{ $issuances->links() }}</div>
</div>
@endsection
