@extends('layouts.app')
@section('title','Parallel Form Teacher Comments')
@section('page-title','Parallel Form Teacher Comments')

@push('styles')
<style>
.ftc{max-width:1100px;margin:0 auto}.ftc-toolbar{display:flex;gap:7px;flex-wrap:wrap;margin-bottom:12px}.ftc-link{display:inline-flex;align-items:center;min-height:35px;padding:7px 11px;border:1px solid var(--border);border-radius:8px;background:#fff;color:var(--midnight);text-decoration:none;font-size:10.5px;font-weight:800}
.ftc-hero{padding:16px 18px;border-radius:13px;background:linear-gradient(135deg,#071E45,#0B2D63);color:#fff;margin-bottom:13px}.ftc-hero h2{margin:0 0 5px;font-size:18px}.ftc-hero p{margin:0;color:#DCE5F2;font-size:11px;line-height:1.5}
.ftc-panel{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:13px}.ftc-head{padding:11px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;gap:8px;align-items:center;flex-wrap:wrap}.ftc-head strong{font-size:12.5px;color:var(--midnight)}.ftc-body{padding:14px}
.ftc-filters{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr) auto;gap:9px;align-items:end}.fg{display:flex;flex-direction:column;gap:5px;min-width:0}.fl{font-size:10px;font-weight:800;color:var(--slate)}.fc{width:100%;min-height:39px;border:1px solid var(--border);border-radius:8px;background:#fff;padding:8px 10px;font:500 11.5px inherit}
.btn{display:inline-flex;align-items:center;justify-content:center;min-height:38px;border:0;border-radius:8px;padding:8px 13px;font:800 11px inherit;cursor:pointer;text-decoration:none}.btn-p{background:var(--indigo);color:#fff}
.ftc-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.student-card{border:1px solid var(--border);border-radius:10px;padding:12px;min-width:0}.student-card strong{display:block;font-size:12px;color:var(--midnight);overflow-wrap:anywhere}.student-card span{display:block;margin-top:2px;font-size:9.5px;color:var(--slate-light)}.student-card textarea{width:100%;min-height:105px;margin-top:9px;border:1px solid var(--border);border-radius:8px;padding:9px 10px;resize:vertical;font:500 11.5px/1.5 inherit}.student-card textarea:focus{outline:none;border-color:var(--indigo)}.student-card textarea:disabled{background:#F8FAFC;color:var(--slate)}
.note{padding:10px 12px;border-radius:8px;font-size:10.5px;line-height:1.45;margin-bottom:11px}.note.warn{background:#FFFAEB;border:1px solid #FEDF89;color:#B54708}.note.ok{background:#ECFDF3;border:1px solid #ABEFC6;color:#067647}.empty{padding:22px;text-align:center;color:var(--slate-light);font-size:11.5px}.savebar{display:flex;justify-content:flex-end;margin-top:12px}
@media(max-width:760px){.ftc-filters{grid-template-columns:1fr 1fr}.ftc-filters .btn{grid-column:1/-1}.ftc-list{grid-template-columns:1fr}}
@media(max-width:500px){.ftc-filters{grid-template-columns:1fr}.ftc-filters .btn{grid-column:auto}.ftc-body{padding:12px}.savebar .btn{width:100%}}
</style>
@include('parallel-curriculum.partials.global-ui')
@endpush

@section('content')
<div class="ftc">
    <div class="ftc-toolbar">
        <a class="ftc-link" href="{{ route('attendance.index') }}">← Attendance</a>
        <a class="ftc-link" href="{{ route('parallel-curriculum.index') }}">Parallel Curriculum</a>
    </div>

    @if(session('success'))<div class="note ok">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="note warn">{{ $errors->first() }}</div>@endif

    <div class="ftc-hero">
        <h2>Parallel Form Teacher Comments</h2>
        <p>The teacher assigned to all subjects in an arm also owns its form-teacher responsibility. Comments are stored per learner and academic term and appear on the standalone parallel result.</p>
    </div>

    <section class="ftc-panel">
        <div class="ftc-head"><strong>Working context</strong><span style="font-size:10px;color:var(--slate-light)">Parallel arm · academic term</span></div>
        <div class="ftc-body">
            <form method="GET" action="{{ route('parallel-curriculum.form-teacher-comments.index') }}" class="ftc-filters">
                <div class="fg">
                    <label class="fl">My Parallel Form Class</label>
                    <select class="fc" name="arm_id" required>
                        @foreach($arms as $item)
                            <option value="{{ $item->id }}" @selected((int)$armId === (int)$item->id)>
                                {{ $item->curriculumClass?->curriculum?->name }} · {{ $item->curriculumClass?->name }} {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="fg">
                    <label class="fl">Academic Term</label>
                    <select class="fc" name="term_id" required>
                        @foreach($terms as $item)
                            <option value="{{ $item->id }}" @selected((int)$termId === (int)$item->id)>
                                {{ $item->session?->name }} · {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-p" type="submit">Load Learners</button>
            </form>
        </div>
    </section>

    @if($arm && $term)
    <section class="ftc-panel">
        <div class="ftc-head">
            <strong>{{ $arm->curriculumClass?->curriculum?->name }} · {{ $arm->curriculumClass?->name }} {{ $arm->name }}</strong>
            <span style="font-size:10px;color:var(--slate-light)">{{ $term->session?->name }} · {{ $term->name }}</span>
        </div>
        <div class="ftc-body">
            @if($isPublished)
                <div class="note warn">This result is published, so its form-teacher comments are read-only. Unpublish the result before editing them.</div>
            @endif

            @if($enrolments->isEmpty())
                <div class="empty">No active learner is assigned to this parallel class arm for the selected session.</div>
            @else
                <form method="POST" action="{{ route('parallel-curriculum.form-teacher-comments.save') }}">
                    @csrf
                    <input type="hidden" name="arm_id" value="{{ $arm->id }}">
                    <input type="hidden" name="term_id" value="{{ $term->id }}">
                    <div class="ftc-list">
                        @foreach($enrolments as $enrolment)
                            @php($savedComment=$comments->get($enrolment->id)?->form_teacher_comment)
                            <article class="student-card">
                                <strong>{{ $enrolment->student?->full_name }}</strong>
                                <span>{{ $enrolment->student?->admission_number }}</span>
                                <textarea name="comments[{{ $enrolment->id }}]" maxlength="1000" placeholder="Enter form-teacher comment..." @disabled($isPublished)>{{ old('comments.'.$enrolment->id, $savedComment) }}</textarea>
                            </article>
                        @endforeach
                    </div>
                    @unless($isPublished)
                        <div class="savebar"><button class="btn btn-p" type="submit">Save Form Teacher Comments</button></div>
                    @endunless
                </form>
            @endif
        </div>
    </section>
    @endif
</div>
@endsection
