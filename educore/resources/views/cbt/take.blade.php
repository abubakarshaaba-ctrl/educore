@extends(auth()->user()?->isStudent() ? 'layouts.portal' : 'layouts.app')
@section('title', $exam->title)
@section('page-title', 'CBT Examination')

@php
    $questionFrames = collect();

    foreach ($sectionPayload as $sectionItem) {
        $section = $sectionItem['section'];
        $questions = $sectionItem['questions']->values();
        $isTheory = in_array($section->section_type, ['theory', 'essay'], true)
            || $section->scoring_method === 'manual';

        if (! $isTheory) {
            foreach ($questions as $question) {
                $questionFrames->push([
                    'section' => $section,
                    'questions' => collect([$question]),
                    'manual' => false,
                    'representative_id' => $question->id,
                ]);
            }
            continue;
        }

        $questionsById = $questions->keyBy('id');
        $branches = [];
        foreach ($questions as $question) {
            $root = $question;
            $visited = [];
            while ($root->parent_question_id && $questionsById->has($root->parent_question_id)) {
                if (isset($visited[$root->id])) break;
                $visited[$root->id] = true;
                $root = $questionsById->get($root->parent_question_id);
            }
            $branches[$root->id] ??= collect();
            $branches[$root->id]->push($question);
        }

        foreach ($branches as $rootId => $branchQuestions) {
            $root = $branchQuestions->firstWhere('id', $rootId) ?? $branchQuestions->first();
            $questionFrames->push([
                'section' => $section,
                'questions' => $branchQuestions,
                'manual' => true,
                'representative_id' => $root->id,
            ]);
        }
    }
@endphp

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css">
<style>
    .exam-shell{max-width:980px;margin:0 auto;color:var(--midnight)}
    .exam-toolbar{position:sticky;top:0;z-index:30;display:flex;align-items:center;gap:14px;padding:12px 15px;margin-bottom:14px;background:rgba(255,255,255,.97);border:1px solid var(--border);border-radius:12px;box-shadow:0 7px 24px rgba(15,35,75,.08);backdrop-filter:blur(8px)}
    .exam-heading{flex:1;min-width:0}.exam-heading strong{display:block;font-size:15px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.exam-heading span{display:block;margin-top:2px;font-size:10px;color:var(--slate)}
    .timer{font-size:17px;font-weight:850;font-variant-numeric:tabular-nums}.save-state{min-width:58px;font-size:10px;color:#047857}
    .security-banner{display:none;padding:11px 14px;margin-bottom:12px;border:1px solid #FECACA;border-radius:10px;background:#FEF2F2;color:#991B1B;font-size:11px}.security-banner.show{display:block}
    .section-tabs{display:flex;gap:7px;overflow-x:auto;margin:0 0 12px;padding:2px 1px 5px;scrollbar-width:thin}.section-tab{flex:0 0 auto;min-width:128px;padding:9px 12px;border:1px solid var(--border);border-radius:9px;background:#fff;color:var(--slate);font:750 11px inherit;text-align:left;cursor:pointer}.section-tab span{display:block;margin-top:2px;font-size:9px;font-weight:500;color:var(--muted)}.section-tab.active{border-color:#0B234B;background:#0B234B;color:#fff}.section-tab.active span{color:#CBD5E1}
    .question-stage{min-height:430px}.question-frame{display:none}.question-frame.active{display:block}
    .question-card{overflow:hidden;background:#fff;border:1px solid var(--border);border-radius:15px;box-shadow:0 9px 28px rgba(15,35,75,.06)}
    .question-card-head{display:flex;align-items:center;gap:10px;padding:14px 17px;border-bottom:1px solid var(--border);background:#F8FAFC}
    .section-pill{padding:5px 9px;border-radius:7px;background:#0B234B;color:#fff;font-size:10px;font-weight:800;letter-spacing:.04em}.question-position{font-size:11px;font-weight:700;color:var(--slate)}
    .type-pill{margin-left:auto;padding:5px 9px;border-radius:999px;background:#E0E7FF;color:#3730A3;font-size:9px;font-weight:800;text-transform:uppercase}.type-pill.theory{background:#FEF3C7;color:#92400E}
    .flag-btn{border:1px solid var(--border);border-radius:7px;background:#fff;color:var(--slate);padding:6px 9px;font:700 10px inherit;cursor:pointer}.flag-btn.flagged{border-color:#F59E0B;background:#FFFBEB;color:#B45309}
    .question-content{padding:24px 25px 26px}.question-row{display:grid;grid-template-columns:42px minmax(0,1fr);gap:13px;align-items:start}.question-row+.question-row{margin-top:19px;padding-top:19px;border-top:1px solid #E8EDF5}
    .question-number{width:42px;min-height:36px;display:grid;place-items:center;border-radius:9px;background:#EFF6FF;color:#1D4ED8;font-size:12px;font-weight:850}.theory-frame .question-number{background:#FFF7E6;color:#B45309}
    .question-text{font-size:14px;line-height:1.72;font-weight:650;color:var(--midnight)}.question-meta{margin-top:5px;font-size:10px;color:var(--slate)}
    .question-image{display:block;max-width:min(100%,620px);max-height:340px;object-fit:contain;margin:15px 0;border:1px solid var(--border);border-radius:10px}
    .options{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:18px}.option{display:flex;align-items:flex-start;gap:10px;min-height:49px;padding:12px;border:1px solid #DDE5F0;border-radius:10px;background:#fff;font-size:12px;line-height:1.5;cursor:pointer;transition:.16s ease}.option:hover{border-color:#93C5FD;background:#F8FBFF}.option.chosen{border-color:#2563EB;background:#EFF6FF;box-shadow:0 0 0 1px #2563EB inset}.option input{margin-top:2px;accent-color:#1D4ED8}.option-letter{font-weight:850;color:#1D4ED8}
    .short-answer{width:100%;margin-top:17px;padding:12px;border:1px solid var(--border);border-radius:9px;font:inherit;font-size:12px}
    .booklet-notice{display:flex;gap:10px;align-items:flex-start;padding:12px 14px;margin:18px 0 0;border:1px solid #FDE68A;border-radius:10px;background:#FFFBEB;color:#854D0E;font-size:11px;line-height:1.55}.booklet-notice strong{display:block;margin-bottom:2px}
    .question-controls{display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:10px;align-items:center;margin-top:12px;padding:10px;background:#fff;border:1px solid var(--border);border-radius:12px}
    .move-btn{width:42px;height:38px;border:1px solid var(--border);border-radius:9px;background:#fff;color:var(--midnight);font-size:19px;cursor:pointer}.move-btn:hover:not(:disabled){background:#EFF6FF;border-color:#93C5FD}.move-btn:disabled{opacity:.35;cursor:not-allowed}
    .navigator-shell{min-width:0}.question-navigator{display:grid;grid-template-columns:repeat(10,minmax(0,1fr));grid-template-rows:repeat(2,34px);gap:6px;overflow:hidden;padding:2px}.nav-question{width:100%;height:34px;padding:0 3px;border:1px solid #DDE5F0;border-radius:8px;background:#fff;color:var(--slate);font:750 10px inherit;cursor:pointer}.nav-question[hidden]{display:none}.nav-question.active{border-color:#0B234B;background:#0B234B;color:#fff}.nav-question.answered{border-color:#86EFAC;background:#DCFCE7;color:#166534}.nav-question.paper{border-color:#FDE68A;background:#FFFBEB;color:#92400E}.nav-question.flagged{box-shadow:0 0 0 2px #F59E0B inset}.nav-question.active{color:#fff;background:#0B234B}.navigator-range{display:block;margin-top:5px;text-align:center;color:var(--muted);font-size:9px}
    .keyboard-help{display:flex;flex-wrap:wrap;justify-content:center;gap:7px;margin:9px 0 14px;color:var(--slate);font-size:9px}.keyboard-help kbd{padding:2px 5px;border:1px solid #CBD5E1;border-bottom-width:2px;border-radius:4px;background:#fff;color:var(--midnight);font:700 9px inherit}
    .exam-footer{position:sticky;bottom:7px;z-index:25;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 13px;background:rgba(255,255,255,.98);border:1px solid var(--border);border-radius:12px;box-shadow:0 -7px 22px rgba(15,35,75,.08)}.exam-footer span{font-size:10px;color:var(--slate)}
    .btn{border:0;border-radius:8px;padding:9px 13px;font:750 11px inherit;cursor:pointer}.btn-light{border:1px solid var(--border);background:#fff;color:var(--midnight)}.btn-danger{background:#B91C1C;color:#fff}
    .empty-exam{padding:42px;text-align:center;background:#fff;border:1px solid var(--border);border-radius:13px;color:var(--slate)}
    .calc-panel{display:none;position:fixed;right:22px;bottom:22px;z-index:80;width:290px;padding:12px;border-radius:13px;background:#0F172A;color:#fff;box-shadow:0 18px 44px rgba(15,23,42,.35)}.calc-panel.open{display:block}.calc-screen{min-height:46px;margin-bottom:8px;padding:10px;overflow:hidden;border-radius:8px;background:#020617;text-align:right;font-size:19px}.calc-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:5px}.calc-key{border:0;border-radius:7px;padding:9px 4px;background:#1E293B;color:#fff;font:700 11px inherit;cursor:pointer}.calc-key.op{background:#1D4ED8}.calc-title{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;font-size:11px;font-weight:800}.calc-title button{border:0;background:none;color:#CBD5E1;font-size:18px;cursor:pointer}
    @media(max-width:700px){.exam-shell{margin:-4px}.exam-toolbar{border-radius:9px;padding:10px}.exam-heading span,.save-state{display:none}.timer{font-size:14px}.section-tab{min-width:112px}.question-stage{min-height:360px}.question-content{padding:18px 15px 20px}.question-row{grid-template-columns:34px minmax(0,1fr);gap:10px}.question-number{width:34px;min-height:32px}.question-text{font-size:13px}.options{grid-template-columns:1fr}.question-controls{grid-template-columns:38px minmax(0,1fr) 38px;gap:6px}.question-navigator{grid-template-columns:repeat(5,minmax(0,1fr))}.move-btn{width:38px}.keyboard-help{display:none}.exam-footer span{display:none}.calc-panel{left:10px;right:10px;bottom:10px;width:auto}}
</style>
@endpush

@section('content')
<div class="exam-shell">
    <div id="securityBanner" class="security-banner">A prohibited navigation event was detected. Your current answers are being secured.</div>

    <header class="exam-toolbar">
        <div class="exam-heading">
            <strong>{{ $exam->title }}</strong>
            <span>{{ $exam->questionBank->subject->name ?? '' }} · {{ $existing ? 'Attempt '.$existing->attempt_number : 'Staff preview' }}</span>
        </div>
        @if($existing)
            <button type="button" id="calcToggle" class="btn btn-light">Calculator</button>
            <span id="saveState" class="save-state">Saved</span>
            <span id="timer" class="timer">--:--:--</span>
        @else
            <span class="timer">Preview</span>
        @endif
    </header>

    @if($sectionPayload->count() > 1)
        <nav class="section-tabs" aria-label="Exam sections">
            @foreach($sectionPayload as $sectionIndex => $sectionItem)
                @php
                    $tabSection = $sectionItem['section'];
                    $tabQuestions = $questionFrames->filter(fn ($frame) => (int) $frame['section']->id === (int) $tabSection->id)->count();
                @endphp
                <button type="button" class="section-tab {{ $sectionIndex === 0 ? 'active' : '' }}" data-section-target="{{ $tabSection->id }}">
                    {{ $tabSection->code }} · {{ $tabSection->name }}
                    <span>{{ $tabQuestions }} {{ Str::plural('question', $tabQuestions) }}</span>
                </button>
            @endforeach
        </nav>
    @endif

    @if($existing)
    <form id="examForm" method="POST" action="{{ route('cbt.session.submit', $existing) }}">
        @csrf
    @endif

    @if($questionFrames->isEmpty())
        <div class="empty-exam">No questions are available in this examination.</div>
    @else
        <div class="question-stage" id="questionStage">
            @foreach($questionFrames as $frameIndex => $frame)
                @php $section = $frame['section']; @endphp
                <section class="question-frame {{ $frameIndex === 0 ? 'active' : '' }} {{ $frame['manual'] ? 'theory-frame' : 'objective-frame' }}"
                         data-question-frame
                         data-frame-index="{{ $frameIndex }}"
                         data-section-id="{{ $section->id }}"
                         data-manual="{{ $frame['manual'] ? 1 : 0 }}"
                         data-representative-id="{{ $frame['representative_id'] }}">
                    <article class="question-card">
                        <div class="question-card-head">
                            <span class="section-pill">SECTION {{ $section->code }}</span>
                            <span class="question-position">Question {{ $frameIndex + 1 }} of {{ $questionFrames->count() }}</span>
                            <span class="type-pill {{ $frame['manual'] ? 'theory' : '' }}">{{ $frame['manual'] ? 'Theory · booklet' : 'Objective' }}</span>
                            @if($existing)
                                <button type="button" class="flag-btn" data-flag="{{ $frame['representative_id'] }}">Flag</button>
                            @endif
                        </div>
                        <div class="question-content">
                            @foreach($frame['questions'] as $question)
                                <div class="question-row" data-question-id="{{ $question->id }}">
                                    <span class="question-number">{{ $question->display_path }}</span>
                                    <div>
                                        <div class="question-text math-content">{!! nl2br(e($question->question_text)) !!}</div>
                                        @if($question->image_path)
                                            <img class="question-image" src="{{ Storage::url($question->image_path) }}" alt="Question diagram">
                                        @endif
                                        <div class="question-meta">{{ $question->is_instruction_only ? 'Instruction' : number_format((float) ($question->pivot->marks_override ?? $question->marks), 1).' mark(s)' }}</div>

                                        @if(! $frame['manual'] && ! $question->is_instruction_only && $question->requires_answer)
                                            @if(in_array($question->type, ['mcq', 'true_false'], true))
                                                <div class="options">
                                                    @foreach($question->optionsArray() as $letter => $option)
                                                        <label class="option">
                                                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ $letter }}" @checked(($existing?->answers[$question->id] ?? '') === $letter)>
                                                            <span><span class="option-letter">{{ strtoupper($letter) }}.</span> {{ $option }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            @elseif($question->type === 'fill_blank')
                                                <input class="short-answer" type="text" name="answers[{{ $question->id }}]" value="{{ $existing?->answers[$question->id] ?? '' }}" placeholder="Type your answer">
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            @if($frame['manual'])
                                <div class="booklet-notice"><span aria-hidden="true">✎</span><div><strong>Answer in the official booklet.</strong>Write the main question and every branch in your answer booklet. This question is marked manually; no response is entered on this screen.</div></div>
                            @endif
                        </div>
                    </article>
                </section>
            @endforeach
        </div>

        <div class="question-controls" aria-label="Question navigation">
            <button type="button" class="move-btn" id="previousQuestion" aria-label="Previous question">←</button>
            <div class="navigator-shell">
                <div class="question-navigator" id="questionNavigator">
                    @foreach($questionFrames as $frameIndex => $frame)
                        <button type="button" class="nav-question {{ $frameIndex === 0 ? 'active' : '' }} {{ $frame['manual'] ? 'paper' : '' }}" data-nav-frame="{{ $frameIndex }}" data-nav-representative="{{ $frame['representative_id'] }}" title="Question {{ $frameIndex + 1 }}">{{ $frameIndex + 1 }}</button>
                    @endforeach
                </div>
                <span class="navigator-range" id="navigatorRange"></span>
            </div>
            <button type="button" class="move-btn" id="nextQuestion" aria-label="Next question">→</button>
        </div>

        <div class="keyboard-help">
            <span><kbd>←</kbd> <kbd>P</kbd> Previous</span>
            <span><kbd>→</kbd> <kbd>N</kbd> Next</span>
            <span><kbd>A</kbd> <kbd>B</kbd> <kbd>C</kbd> <kbd>D</kbd> Choose objective answer</span>
        </div>
    @endif

    @if($existing)
        <footer class="exam-footer">
            <span>Objective answers save automatically. Theory answers must be written in the official booklet.</span>
            <button type="submit" class="btn btn-danger" onclick="return confirm('Submit your final answers? You cannot edit them afterwards.')">Submit examination</button>
        </footer>
    </form>
    @endif

    @if($existing)
        <div id="calcPanel" class="calc-panel">
            <div class="calc-title"><span>Scientific calculator</span><button type="button" id="calcClose">×</button></div>
            <div id="calcDisplay" class="calc-screen">0</div>
            <div class="calc-grid">
                @foreach(['7','8','9','÷','4','5','6','×','1','2','3','−','0','.','(',')','sin(','cos(','tan(','√(','π','^','C','⌫'] as $key)
                    <button type="button" class="calc-key {{ in_array($key, ['÷','×','−','^'], true) ? 'op' : '' }}" data-calc="{{ $key }}">{{ $key }}</button>
                @endforeach
                <button type="button" class="calc-key op" data-calc="=">=</button>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/mhchem.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js"></script>
<script>
(() => {
    const frames = [...document.querySelectorAll('[data-question-frame]')];
    const navButtons = [...document.querySelectorAll('[data-nav-frame]')];
    const sectionTabs = [...document.querySelectorAll('[data-section-target]')];
    const previousButton = document.getElementById('previousQuestion');
    const nextButton = document.getElementById('nextQuestion');
    const form = document.getElementById('examForm');
    const existing = @json((bool) $existing);
    const csrf = @json(csrf_token());
    const flaggedQuestions = new Set(@json(array_values(array_map('intval', (array) ($existing?->flagged_questions ?? [])))));
    let currentFrame = 0;
    let finalizing = false;
    let integrityLocked = false;
    let pendingIntegrity = null;
    let saveTimer;
    let lastIntegrity = 0;

    function showFrame(index, focus = false) {
        if (! frames.length) return;
        currentFrame = Math.max(0, Math.min(index, frames.length - 1));
        frames.forEach((frame, position) => frame.classList.toggle('active', position === currentFrame));
        navButtons.forEach((button, position) => button.classList.toggle('active', position === currentFrame));
        const activeSection = frames[currentFrame]?.dataset.sectionId;
        sectionTabs.forEach(tab => tab.classList.toggle('active', tab.dataset.sectionTarget === activeSection));
        updateNavigatorWindow();
        if (previousButton) previousButton.disabled = currentFrame === 0;
        if (nextButton) nextButton.disabled = currentFrame === frames.length - 1;
        if (focus) document.getElementById('questionStage')?.scrollIntoView({behavior: 'smooth', block: 'start'});
    }

    function navigatorPageSize() { return window.matchMedia('(max-width: 700px)').matches ? 10 : 20; }

    function updateNavigatorWindow() {
        if (! navButtons.length) return;
        const pageSize = navigatorPageSize();
        const start = Math.floor(currentFrame / pageSize) * pageSize;
        const end = Math.min(start + pageSize, navButtons.length);
        navButtons.forEach((button, index) => button.hidden = index < start || index >= end);
        const range = document.getElementById('navigatorRange');
        if (range) range.textContent = `Questions ${start + 1}–${end} of ${navButtons.length}`;
    }

    function moveFrame(delta) { showFrame(currentFrame + delta, true); }

    function payload() {
        if (! form) return {answers: {}, essay_answers: {}, flagged_questions: [...flaggedQuestions]};
        const data = new FormData(form);
        const answers = {};
        for (const [key, value] of data) {
            const match = key.match(/^answers\[(\d+)\]$/);
            if (match) answers[match[1]] = value;
        }
        return {answers, essay_answers: {}, flagged_questions: [...flaggedQuestions]};
    }

    function frameAnswered(frame) {
        if (frame.dataset.manual === '1') return false;
        return !!frame.querySelector('input[type="radio"]:checked') || !!frame.querySelector('input[type="text"]')?.value.trim();
    }

    function updateProgress() {
        frames.forEach((frame, index) => {
            const nav = navButtons[index];
            nav?.classList.toggle('answered', frameAnswered(frame));
            nav?.classList.toggle('flagged', flaggedQuestions.has(Number(frame.dataset.representativeId)));
            frame.querySelectorAll('.option').forEach(option => option.classList.toggle('chosen', !!option.querySelector('input:checked')));
            const flag = frame.querySelector('[data-flag]');
            if (flag) flag.classList.toggle('flagged', flaggedQuestions.has(Number(flag.dataset.flag)));
        });
    }

    function selectObjectiveAnswer(letter) {
        const radio = frames[currentFrame]?.querySelector(`input[type="radio"][value="${letter}"]`);
        if (! radio || radio.disabled) return;
        radio.checked = true;
        radio.dispatchEvent(new Event('change', {bubbles: true}));
    }

    previousButton?.addEventListener('click', () => moveFrame(-1));
    nextButton?.addEventListener('click', () => moveFrame(1));
    navButtons.forEach((button, index) => button.addEventListener('click', () => showFrame(index, true)));
    sectionTabs.forEach(tab => tab.addEventListener('click', () => {
        const index = frames.findIndex(frame => frame.dataset.sectionId === tab.dataset.sectionTarget);
        if (index >= 0) showFrame(index, true);
    }));
    window.addEventListener('resize', updateNavigatorWindow);
    document.querySelectorAll('[data-flag]').forEach(button => button.addEventListener('click', () => {
        const id = Number(button.dataset.flag);
        flaggedQuestions.has(id) ? flaggedQuestions.delete(id) : flaggedQuestions.add(id);
        updateProgress();
        autosave();
    }));

    document.addEventListener('keydown', event => {
        const typing = document.activeElement?.tagName === 'INPUT' && document.activeElement?.type === 'text';
        if (typing || event.ctrlKey || event.altKey || event.metaKey) return;
        const key = event.key.toLowerCase();
        if (event.key === 'ArrowLeft' || key === 'p') {
            event.preventDefault(); moveFrame(-1);
        } else if (event.key === 'ArrowRight' || key === 'n') {
            event.preventDefault(); moveFrame(1);
        } else if (['a', 'b', 'c', 'd'].includes(key)) {
            event.preventDefault(); selectObjectiveAnswer(key);
        }
    });

    document.querySelectorAll('input[name^="answers["]').forEach(input => input.addEventListener('change', () => { updateProgress(); autosave(); }));
    form?.addEventListener('input', () => { updateProgress(); clearTimeout(saveTimer); saveTimer = setTimeout(autosave, 650); });
    form?.addEventListener('submit', () => { finalizing = true; });
    showFrame(0);
    updateProgress();

    async function autosave() {
        if (! existing || ! form || finalizing || integrityLocked) return;
        const state = document.getElementById('saveState');
        if (state) state.textContent = 'Saving…';
        try {
            const response = await fetch(@json($existing ? route('cbt.session.autosave', $existing) : ''), {
                method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'}, body: JSON.stringify(payload()),
            });
            if (response.status === 409) { finalizing = true; location.href = @json(route('student.portal.exams')); return; }
            if (state) state.textContent = response.ok ? 'Saved' : 'Retrying';
        } catch (error) { if (state) state.textContent = 'Offline'; }
    }

    if (existing) setInterval(autosave, 20000);

    const strictIntegrity = @json((bool) ($existing && $exam->malpractice_enabled && $exam->focus_loss_policy === 'submit'));
    async function deliverIntegrity() {
        if (! pendingIntegrity || finalizing || ! existing) return;
        try {
            const response = await fetch(@json($existing ? route('cbt.session.integrity', $existing) : ''), {
                method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'}, keepalive: true, body: JSON.stringify(pendingIntegrity),
            });
            const data = await response.json();
            if (data.submitted || ['auto_submitted', 'submitted', 'graded'].includes(data.status)) {
                finalizing = true;
                document.getElementById('securityBanner').textContent = 'Examination submitted automatically. Your saved responses have been recorded.';
                setTimeout(() => location.href = @json(route('student.portal.exams')), 900);
                return;
            }
            pendingIntegrity = null;
            if (! strictIntegrity) integrityLocked = false;
        } catch (error) {
            const state = document.getElementById('saveState');
            if (state) state.textContent = 'Reconnecting…';
            setTimeout(deliverIntegrity, 3000);
        }
    }

    function integrity(type, metadata = {}) {
        if (! existing || finalizing || integrityLocked) return;
        const now = Date.now();
        const prohibited = ['visibility_hidden', 'window_blur', 'page_hidden', 'focus_lost', 'fullscreen_exit'].includes(type);
        if (now - lastIntegrity < 900 && prohibited) return;
        lastIntegrity = now;
        document.getElementById('securityBanner').classList.add('show');
        pendingIntegrity = {event_uuid: crypto.randomUUID(), event_type: type, metadata, ...payload()};
        if (strictIntegrity && prohibited) {
            integrityLocked = true;
            document.querySelectorAll('.exam-shell input,.exam-shell button').forEach(element => element.disabled = true);
        }
        deliverIntegrity();
    }

    if (existing) {
        window.addEventListener('online', () => pendingIntegrity && deliverIntegrity());
        document.addEventListener('visibilitychange', () => document.hidden && integrity('visibility_hidden'));
        window.addEventListener('blur', () => integrity('window_blur'));
        window.addEventListener('pagehide', () => integrity('page_hidden'));
        window.addEventListener('beforeunload', event => { if (! finalizing) { integrity('beforeunload'); event.preventDefault(); event.returnValue = ''; } });
        document.addEventListener('fullscreenchange', () => { if (@json((bool) $exam->require_fullscreen) && ! document.fullscreenElement) integrity('fullscreen_exit'); });
        document.addEventListener('contextmenu', event => event.preventDefault());
        document.addEventListener('copy', event => event.preventDefault());
        document.addEventListener('paste', event => event.preventDefault());
        document.addEventListener('keydown', event => { if (event.key === 'PrintScreen' || (event.ctrlKey && ['p', 's', 'u'].includes(event.key.toLowerCase()))) event.preventDefault(); });
    }

    const calcPanel = document.getElementById('calcPanel');
    const calcDisplay = document.getElementById('calcDisplay');
    document.getElementById('calcToggle')?.addEventListener('click', () => calcPanel.classList.toggle('open'));
    document.getElementById('calcClose')?.addEventListener('click', () => calcPanel.classList.remove('open'));
    let calcExpression = '';
    document.querySelectorAll('[data-calc]').forEach(key => key.addEventListener('click', () => {
        const value = key.dataset.calc;
        if (value === 'C') calcExpression = '';
        else if (value === '⌫') calcExpression = calcExpression.slice(0, -1);
        else if (value === '=') {
            try {
                const source = calcExpression.replace(/×/g, '*').replace(/÷/g, '/').replace(/−/g, '-').replace(/π/g, 'Math.PI').replace(/√\(/g, 'Math.sqrt(').replace(/sin\(/g, 'Math.sin(').replace(/cos\(/g, 'Math.cos(').replace(/tan\(/g, 'Math.tan(').replace(/\^/g, '**');
                calcExpression = String(Function(`"use strict";return (${source})`)());
            } catch (error) { calcExpression = 'Error'; }
        } else { if (calcExpression === 'Error') calcExpression = ''; calcExpression += value; }
        if (calcDisplay) calcDisplay.textContent = calcExpression || '0';
    }));

    window.addEventListener('load', () => {
        if (window.renderMathInElement) document.querySelectorAll('.math-content').forEach(element => renderMathInElement(element, {
            delimiters: [{left: '$$', right: '$$', display: true}, {left: '\\(', right: '\\)', display: false}, {left: '$', right: '$', display: false}], throwOnError: false, trust: false,
        }));
    });

    if (existing) {
        const started = new Date(@json($existing?->started_at?->toIso8601String()));
        const scheduledEnd = @json($exam->scheduled_end?->toIso8601String());
        let deadline = started.getTime() + {{ (int) $exam->duration_minutes }} * 60000;
        if (scheduledEnd) deadline = Math.min(deadline, new Date(scheduledEnd).getTime());
        function tick() {
            const left = Math.max(0, deadline - Date.now());
            const hours = Math.floor(left / 3600000), minutes = Math.floor(left % 3600000 / 60000), seconds = Math.floor(left % 60000 / 1000);
            const timer = document.getElementById('timer');
            if (timer) timer.textContent = [hours, minutes, seconds].map(value => String(value).padStart(2, '0')).join(':');
            if (left <= 0 && ! finalizing && form) { finalizing = true; form.submit(); }
        }
        tick();
        setInterval(tick, 1000);
        @if($exam->require_fullscreen)
            document.documentElement.requestFullscreen?.().catch(() => {});
        @endif
    }
})();
</script>
@endpush
