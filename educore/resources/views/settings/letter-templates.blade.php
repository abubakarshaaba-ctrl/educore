@extends('layouts.app')
@section('title','Offer Letter Templates')
@section('page-title','Offer Letter Templates')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:18px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%;resize:vertical}
.fc:focus{border-color:var(--indigo);background:white}
.fr{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;border-radius:8px;border:none;cursor:pointer;font-family:inherit}
.btn-p{background:var(--indigo);color:white}
.hint{background:#F8FAFC;border:1px solid var(--border);border-radius:10px;padding:12px 16px;font-size:12px;color:var(--slate);margin-bottom:16px;line-height:1.7}
.hint code{background:#EEF2FF;color:var(--indigo);padding:1px 6px;border-radius:4px;font-size:11px}
@media(max-width:700px){.fr{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@if(session('success'))
<div style="background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;font-weight:600">
    ✓ {{ session('success') }}
</div>
@endif

<div class="card">
    <div class="ch">📄 Admission Offer Letter</div>
    <div class="cb">
        <div class="hint">
            Placeholders you can use: <code>{applicant_name}</code> <code>{guardian_name}</code>
            <code>{school_name}</code> <code>{class}</code> <code>{academic_year}</code> <code>{application_number}</code>
        </div>
        <form method="POST" action="{{ route('settings.letter-templates.update', 'admission_offer') }}">
            @csrf @method('PUT')
            <div class="fg"><label class="fl">Opening Line</label><textarea name="intro_text" class="fc" rows="2">{{ $admissionOffer->intro_text }}</textarea></div>
            <div class="fg"><label class="fl">Main Paragraph</label><textarea name="body_text" class="fc" rows="5">{{ $admissionOffer->body_text }}</textarea></div>
            <div class="fg"><label class="fl">Closing Paragraph</label><textarea name="closing_text" class="fc" rows="3">{{ $admissionOffer->closing_text }}</textarea></div>
            <div class="fr">
                <div class="fg"><label class="fl">Signatory 1 Label</label><input type="text" name="signatory_1_label" class="fc" value="{{ $admissionOffer->signatory_1_label }}"></div>
                <div class="fg"><label class="fl">Signatory 2 Label</label><input type="text" name="signatory_2_label" class="fc" value="{{ $admissionOffer->signatory_2_label }}"></div>
            </div>
            <button type="submit" class="btn btn-p">Save Admission Offer Template</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="ch">💼 Job Offer Letter</div>
    <div class="cb">
        <div class="hint">
            Placeholders you can use: <code>{applicant_name}</code> <code>{school_name}</code>
            <code>{position}</code> <code>{department}</code>
        </div>
        <form method="POST" action="{{ route('settings.letter-templates.update', 'job_offer') }}">
            @csrf @method('PUT')
            <div class="fg"><label class="fl">Opening Line</label><textarea name="intro_text" class="fc" rows="2">{{ $jobOffer->intro_text }}</textarea></div>
            <div class="fg"><label class="fl">Main Paragraph</label><textarea name="body_text" class="fc" rows="5">{{ $jobOffer->body_text }}</textarea></div>
            <div class="fg"><label class="fl">Closing Paragraph</label><textarea name="closing_text" class="fc" rows="3">{{ $jobOffer->closing_text }}</textarea></div>
            <div class="fr">
                <div class="fg"><label class="fl">Signatory 1 Label</label><input type="text" name="signatory_1_label" class="fc" value="{{ $jobOffer->signatory_1_label }}"></div>
                <div class="fg"><label class="fl">Signatory 2 Label</label><input type="text" name="signatory_2_label" class="fc" value="{{ $jobOffer->signatory_2_label }}"></div>
            </div>
            <button type="submit" class="btn btn-p">Save Job Offer Template</button>
        </form>
    </div>
</div>
@endsection
