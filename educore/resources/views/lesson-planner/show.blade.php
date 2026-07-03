@extends('layouts.app')
@section('title', $lessonPlan->topic)

@section('content')
<div class="page-content">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px">
        <div>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
                <a href="{{ route('lesson-planner.index') }}" style="color:var(--slate-light);text-decoration:none;font-size:13px">← Lesson Planner</a>
            </div>
            <h1 style="font-size:20px;font-weight:800;color:var(--midnight)">{{ $lessonPlan->topic }}</h1>
            @if($lessonPlan->subtopic)
            <p style="font-size:13px;color:var(--slate-light);margin-top:2px">{{ $lessonPlan->subtopic }}</p>
            @endif
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="{{ route('lesson-planner.edit', $lessonPlan) }}" class="btn btn-secondary">Edit</a>
            <a href="{{ route('lesson-planner.print', $lessonPlan) }}" target="_blank" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px;margin-right:4px"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
                Print Plan
            </a>
            @if($lessonPlan->lesson_notes)
            <a href="{{ route('lesson-planner.notes', $lessonPlan) }}" class="btn btn-secondary" style="background:linear-gradient(135deg,#7C3AED,#4F46E5);color:white;border:none">
                📖 View Student Notes
            </a>
            @endif
            <button id="generateNotesBtn" onclick="generateStudentNotes()" class="btn btn-primary" style="background:linear-gradient(135deg,#059669,#047857);border:none">
                ✨ {{ $lessonPlan->lesson_notes ? 'Regenerate' : 'Generate' }} Student Notes
            </button>
            <form method="POST" action="{{ route('lesson-planner.destroy', $lessonPlan) }}" onsubmit="return confirm('Delete this lesson plan?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-secondary" style="color:var(--crimson)">Delete</button>
            </form>
        </div>
    </div>

    {{-- Meta info --}}
    <div style="background:white;border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:20px">
        <div style="display:flex;flex-wrap:wrap;gap:20px">
            <div>
                <div style="font-size:11px;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;font-weight:600">Subject</div>
                <div style="font-size:14px;font-weight:600;color:var(--midnight);margin-top:2px">{{ $lessonPlan->subject->name }}</div>
            </div>
            <div>
                <div style="font-size:11px;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;font-weight:600">Class</div>
                <div style="font-size:14px;font-weight:600;color:var(--midnight);margin-top:2px">{{ $lessonPlan->classLevel->name }}{{ $lessonPlan->classArm ? ' ' . $lessonPlan->classArm->name : '' }}</div>
            </div>
            <div>
                <div style="font-size:11px;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;font-weight:600">Curriculum</div>
                <div style="margin-top:4px">
                    @if($lessonPlan->isNerdc())
                    <span style="font-size:12px;background:#FEF9EC;color:#92400E;padding:3px 10px;border-radius:20px;font-weight:600">NERDC / TRCN</span>
                    @else
                    <span style="font-size:12px;background:#EFF6FF;color:#1E40AF;padding:3px 10px;border-radius:20px;font-weight:600">British</span>
                    @endif
                </div>
            </div>
            @if($lessonPlan->term)
            <div>
                <div style="font-size:11px;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;font-weight:600">Term</div>
                <div style="font-size:14px;font-weight:600;color:var(--midnight);margin-top:2px">{{ $lessonPlan->term->name ?? '—' }}</div>
            </div>
            @endif
            @if($lessonPlan->week_number)
            <div>
                <div style="font-size:11px;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;font-weight:600">Week</div>
                <div style="font-size:14px;font-weight:600;color:var(--midnight);margin-top:2px">Week {{ $lessonPlan->week_number }}</div>
            </div>
            @endif
            <div>
                <div style="font-size:11px;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;font-weight:600">Duration</div>
                <div style="font-size:14px;font-weight:600;color:var(--midnight);margin-top:2px">{{ $lessonPlan->duration_minutes }} min</div>
            </div>
            @if($lessonPlan->plan_date)
            <div>
                <div style="font-size:11px;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;font-weight:600">Date</div>
                <div style="font-size:14px;font-weight:600;color:var(--midnight);margin-top:2px">{{ $lessonPlan->plan_date->format('d M Y') }}</div>
            </div>
            @endif
            <div>
                <div style="font-size:11px;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;font-weight:600">Status</div>
                <div style="margin-top:4px">
                    @if($lessonPlan->isPublished())
                    <span class="badge-success" style="font-size:12px">Published</span>
                    @else
                    <span style="font-size:12px;background:#F1F5F9;color:var(--slate);padding:3px 10px;border-radius:20px">Draft</span>
                    @endif
                </div>
            </div>
            @if($lessonPlan->ai_generated)
            <div style="display:flex;align-items:center">
                <span style="font-size:12px;background:#EDE9FE;color:#6D28D9;padding:3px 10px;border-radius:20px;font-weight:600">✨ AI Generated</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Sections --}}
    @php $sections = $lessonPlan->sections(); $i = 1; @endphp
    @foreach($sections as $field => $label)
    @if($lessonPlan->$field)
    <div style="background:white;border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:12px">
        <div style="font-size:11px;font-weight:700;color:var(--brand-gold);text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;padding-bottom:8px;border-bottom:2px solid var(--brand-gold)">
            {{ $i++ }}. {{ $label }}
        </div>
        <div style="font-size:14px;color:var(--slate);line-height:1.8;white-space:pre-wrap">{{ $lessonPlan->$field }}</div>
    </div>
    @endif
    @endforeach

    @if($i === 1)
    <div style="text-align:center;padding:40px;background:white;border-radius:var(--radius);border:1px solid var(--border);color:var(--slate-light)">
        No content yet. <a href="{{ route('lesson-planner.edit', $lessonPlan) }}">Edit this plan</a> to add content.
    </div>
    @endif

    {{-- Notes generation status --}}
    <div id="notesStatus" style="display:none;margin-top:16px;padding:16px;border-radius:var(--radius);background:#F0FDF4;border:1px solid #BBF7D0;color:#166534;font-size:14px"></div>
</div>

@push('scripts')
<script>
function generateStudentNotes() {
    const btn = document.getElementById('generateNotesBtn');
    const status = document.getElementById('notesStatus');
    btn.disabled = true;
    btn.textContent = '⏳ Generating notes...';
    status.style.display = 'none';

    fetch('{{ route('lesson-planner.generate-notes', $lessonPlan) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            status.style.display = 'block';
            status.innerHTML = '✅ Student notes generated successfully! <a href="{{ route('lesson-planner.notes', $lessonPlan) }}" style="font-weight:700;color:#166534">View Notes →</a>';
            btn.textContent = '✨ Regenerate Student Notes';
        } else {
            status.style.display = 'block';
            status.style.background = '#FEF2F2';
            status.style.borderColor = '#FECACA';
            status.style.color = '#991B1B';
            status.textContent = '⚠ ' + data.message;
            btn.textContent = '✨ Generate Student Notes';
        }
        btn.disabled = false;
    })
    .catch(() => {
        status.style.display = 'block';
        status.textContent = '⚠ Network error. Please try again.';
        btn.disabled = false;
        btn.textContent = '✨ Generate Student Notes';
    });
}
</script>
@endpush
@endsection
