@extends('layouts.app')
@section('title','Parallel Curriculum Skills Rating')
@section('page-title','Parallel Curriculum Skills Rating')

@push('styles')
<style>
.pcs{max-width:1280px;margin:0 auto}.pcs-hero{padding:16px 18px;border-radius:13px;background:linear-gradient(135deg,#071E45,#0B2D63);color:#fff;margin-bottom:14px}.pcs-hero h2{margin:0 0 5px;font-size:18px}.pcs-hero p{margin:0;color:#DCE5F2;font-size:11.5px;line-height:1.55}.panel{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}.head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:11px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:12px;font-weight:800;color:var(--midnight)}.body{padding:14px}.filters{display:grid;grid-template-columns:minmax(220px,1fr) minmax(220px,1fr) auto;gap:9px;align-items:end}.fg{display:flex;flex-direction:column;gap:5px}.fl{font-size:10px;font-weight:800;color:var(--slate)}.fc{width:100%;min-height:39px;border:1px solid var(--border);border-radius:8px;padding:8px 10px;background:#fff;font:500 11.5px inherit}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:39px;padding:8px 13px;border:0;border-radius:8px;font:800 11px inherit;cursor:pointer}.btn-p{background:var(--indigo);color:#fff}.note{padding:10px 12px;border-radius:8px;margin-bottom:12px;font-size:10.5px}.note.ok{background:#ECFDF3;border:1px solid #ABEFC6;color:#067647}.note.err{background:#FEF3F2;border:1px solid #FECDCA;color:#B42318}.rating-key{display:flex;gap:6px;flex-wrap:wrap}.key{padding:4px 7px;border-radius:999px;background:#F2F4F7;color:#475467;font-size:9.5px;font-weight:800}.student{border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:10px}.student-head{padding:10px 12px;background:#FBFCFE;border-bottom:1px solid #EEF2F7}.student-head strong{display:block;font-size:12px;color:var(--midnight)}.student-head span{font-size:9.5px;color:var(--slate-light)}.domains{display:grid;grid-template-columns:1fr 1fr;gap:0}.domain{padding:12px}.domain+ .domain{border-left:1px solid #EEF2F7}.domain h4{margin:0 0 8px;font-size:11px;color:var(--midnight)}.skill-row{display:grid;grid-template-columns:minmax(0,1fr) 90px;gap:8px;align-items:center;padding:7px 0;border-bottom:1px solid #F1F5F9}.skill-row:last-child{border-bottom:0}.skill-row label{font-size:10.5px;color:var(--slate)}.savebar{position:sticky;bottom:8px;display:flex;justify-content:flex-end;padding:10px;margin-top:12px;border:1px solid var(--border);border-radius:10px;background:rgba(255,255,255,.96);backdrop-filter:blur(8px)}.empty{padding:24px;text-align:center;color:var(--slate-light);font-size:11.5px}
@media(max-width:760px){.filters{grid-template-columns:1fr}.domains{grid-template-columns:1fr}.domain+.domain{border-left:0;border-top:1px solid #EEF2F7}.savebar .btn{width:100%}}
</style>
@include('parallel-curriculum.partials.global-ui')
@endpush

@section('content')
<div class="pcs">
    @include('parallel-curriculum.partials.module-navigation')

    @if(session('success'))<div class="note ok">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="note err">{{ $errors->first() }}</div>@endif

    <div class="pcs-hero">
        <h2>Parallel Curriculum Skills Rating</h2>
        <p>Rate affective and psychomotor development independently for the selected parallel class arm and term. These ratings appear only on the parallel curriculum result.</p>
    </div>

    <section class="panel">
        <div class="head"><span>Working context</span><span>Parallel arm · academic term</span></div>
        <div class="body">
            <form method="GET" action="{{ route('parallel-curriculum.skills.index') }}" class="filters">
                <div class="fg">
                    <label class="fl">Parallel class arm</label>
                    <select class="fc" name="arm_id" required>
                        @foreach($arms as $item)
                            <option value="{{ $item->id }}" @selected((int)$armId === (int)$item->id)>
                                {{ $item->curriculumClass?->curriculum?->name }} · {{ $item->curriculumClass?->name }} {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="fg">
                    <label class="fl">Academic term</label>
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

    <section class="panel">
        <div class="head">
            <span>{{ $arm?->curriculumClass?->curriculum?->name }} · {{ $arm?->curriculumClass?->name }} {{ $arm?->name }}</span>
            <div class="rating-key">
                <span class="key">1 Poor</span><span class="key">2 Fair</span><span class="key">3 Good</span><span class="key">4 Very Good</span><span class="key">5 Excellent</span>
            </div>
        </div>
        <div class="body">
            @if(!$arm || !$term)
                <div class="empty">Select a parallel class arm and term to begin rating learners.</div>
            @elseif($enrolments->isEmpty())
                <div class="empty">No active learner is assigned to this parallel class arm for the selected session.</div>
            @else
                <form method="POST" action="{{ route('parallel-curriculum.skills.save') }}">
                    @csrf
                    <input type="hidden" name="arm_id" value="{{ $arm->id }}">
                    <input type="hidden" name="term_id" value="{{ $term->id }}">

                    @foreach($enrolments as $enrolment)
                        @php($studentRatings=$existingRatings->get($enrolment->id, collect()))
                        <article class="student">
                            <div class="student-head">
                                <strong>{{ $loop->iteration }}. {{ $enrolment->student?->full_name }}</strong>
                                <span>{{ $enrolment->student?->admission_number }}</span>
                            </div>
                            <div class="domains">
                                <div class="domain">
                                    <h4>Affective Domain</h4>
                                    @foreach($affectiveSkills as $skill)
                                        @php($saved=$studentRatings->get($skill->id)?->rating)
                                        <div class="skill-row">
                                            <label for="rating-{{ $enrolment->id }}-{{ $skill->id }}">{{ $skill->name }}</label>
                                            <select class="fc" id="rating-{{ $enrolment->id }}-{{ $skill->id }}" name="ratings[{{ $enrolment->id }}][{{ $skill->id }}]">
                                                <option value="">—</option>
                                                @for($rating=1;$rating<=5;$rating++)
                                                    <option value="{{ $rating }}" @selected((int)$saved===$rating)>{{ $rating }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="domain">
                                    <h4>Psychomotor Domain</h4>
                                    @foreach($psychomotorSkills as $skill)
                                        @php($saved=$studentRatings->get($skill->id)?->rating)
                                        <div class="skill-row">
                                            <label for="rating-{{ $enrolment->id }}-{{ $skill->id }}">{{ $skill->name }}</label>
                                            <select class="fc" id="rating-{{ $enrolment->id }}-{{ $skill->id }}" name="ratings[{{ $enrolment->id }}][{{ $skill->id }}]">
                                                <option value="">—</option>
                                                @for($rating=1;$rating<=5;$rating++)
                                                    <option value="{{ $rating }}" @selected((int)$saved===$rating)>{{ $rating }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </article>
                    @endforeach

                    <div class="savebar">
                        <button class="btn btn-p" type="submit">Save Parallel Skills Ratings</button>
                    </div>
                </form>
            @endif
        </div>
    </section>
</div>
@endsection
