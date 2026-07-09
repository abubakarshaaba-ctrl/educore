@extends('layouts.app')
@section('title', $posting->title)
@section('page-title', $posting->title)
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
td{padding:10px 14px;border-bottom:1px solid var(--border);vertical-align:top}
tr:last-child td{border:none}
select.inline{padding:5px 8px;font-size:11px;border:1px solid var(--border);border-radius:6px}
.back{font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:14px}
.mini{font-size:11px;color:#94A3B8}
details summary{cursor:pointer;color:var(--indigo);font-size:11px;font-weight:600;margin-top:6px}
@media(max-width:600px){.fr{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<a href="{{ route('recruitment.index') }}" class="back">← All Postings</a>

<div class="card">
    <div class="ch">Add Applicant</div>
    <div class="cb">
        <form method="POST" action="{{ route('recruitment.applicants.store', $posting) }}" enctype="multipart/form-data">
            @csrf
            <div class="fr">
                <div class="fg"><label class="fl">Name *</label><input type="text" name="name" class="fc" required></div>
                <div class="fg"><label class="fl">Email</label><input type="email" name="email" class="fc"></div>
                <div class="fg"><label class="fl">Phone</label><input type="text" name="phone" class="fc"></div>
                <div class="fg"><label class="fl">Resume (PDF/DOC)</label><input type="file" name="resume" class="fc"></div>
            </div>
            <div class="fg"><label class="fl">Cover Letter</label><textarea name="cover_letter" class="fc" rows="2"></textarea></div>
            <button type="submit" class="btn btn-p">Add Applicant</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="ch">Applicants ({{ $applicants->total() }})</div>
    <div style="overflow-x:auto"><table>
        <thead><tr><th>Name</th><th>Contact</th><th>Status</th><th>Interviews</th><th>Schedule Interview</th><th>Messages</th></tr></thead>
        <tbody>
        @forelse($applicants as $a)
        <tr>
            <td>
                <div style="font-weight:600">{{ $a->name }}</div>
                @if($a->resume_path)<a href="{{ asset('storage/' . $a->resume_path) }}" target="_blank" class="mini">📄 Resume</a>@endif
            </td>
            <td class="mini">{{ $a->email }}<br>{{ $a->phone }}</td>
            <td>
                <form method="POST" action="{{ route('recruitment.applicants.status', $a) }}">
                    @csrf @method('PATCH')
                    <select name="status" class="inline" onchange="this.form.submit()">
                        @foreach(['applied','shortlisted','interview_scheduled','interviewed','offered','hired','rejected'] as $st)
                        <option value="{{ $st }}" {{ $a->status === $st ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$st)) }}</option>
                        @endforeach
                    </select>
                </form>
            </td>
            <td class="mini">
                @forelse($a->interviews as $iv)
                    {{ $iv->interview_at->format('d M Y, h:ia') }} — {{ ucfirst($iv->outcome) }}<br>
                @empty
                    None scheduled
                @endforelse
            </td>
            <td>
                <details>
                    <summary>Schedule</summary>
                    <form method="POST" action="{{ route('recruitment.applicants.interview', $a) }}" style="margin-top:6px">
                        @csrf
                        <input type="datetime-local" name="interview_at" class="fc" style="margin-bottom:6px" required>
                        <button type="submit" class="btn" style="background:#F1F5F9;color:#475569;padding:5px 10px;font-size:11px">Set</button>
                    </form>
                </details>
            </td>
            <td style="min-width:220px">
                <details>
                    <summary>{{ $a->messages->count() }} message{{ $a->messages->count() === 1 ? '' : 's' }}</summary>
                    <div style="margin-top:8px;display:flex;flex-direction:column;gap:6px;max-height:180px;overflow-y:auto">
                        @forelse($a->messages as $m)
                            <div style="font-size:11px;padding:6px 8px;border-radius:6px;background:{{ $m->sender_type === 'school' ? '#EEF2FF' : '#F1F5F9' }}">
                                <strong>{{ $m->sender_type === 'school' ? 'You' : $a->name }}:</strong> {{ $m->body }}
                                <div class="mini">{{ $m->created_at->format('d M, h:ia') }}</div>
                            </div>
                        @empty
                            <div class="mini">No messages yet.</div>
                        @endforelse
                    </div>
                    <form method="POST" action="{{ route('recruitment.applicants.message', $a) }}" style="margin-top:6px">
                        @csrf
                        <textarea name="body" class="fc" rows="2" placeholder="Message applicant..." required></textarea>
                        <button type="submit" class="btn" style="background:#F1F5F9;color:#475569;padding:5px 10px;font-size:11px;margin-top:4px">Send</button>
                    </form>
                </details>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;padding:30px;color:#94A3B8">No applicants yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="padding:14px">{{ $applicants->links() }}</div>
</div>
@endsection
