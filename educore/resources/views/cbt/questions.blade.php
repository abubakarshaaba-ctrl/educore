@extends('layouts.builder')
@section('title','Manage Questions — '.$bank->name)
@section('builder-title', $bank->name)
@section('builder-subtitle', optional($bank->subject)->name . ' · ' . optional($bank->classLevel)->name)
@section('builder-bar-right')
    <span class="builder-pill">📚 {{ $questions->total() }} question{{ $questions->total() === 1 ? '' : 's' }}</span>
    <form method="POST" action="{{ route('cbt.banks.reshuffle', $bank) }}"
          onsubmit="return confirm('Reshuffle the order of all questions in this bank?')" style="display:inline">
        @csrf
        <button type="submit" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;background:rgba(255,255,255,.08);color:white;border-color:rgba(255,255,255,.2)">🔀 Reshuffle</button>
    </form>
    <a href="{{ route('cbt.banks.edit', $bank) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;background:rgba(255,255,255,.08);color:white;border-color:rgba(255,255,255,.2)">✏️ Edit Bank</a>
    <a href="{{ route('cbt.bulk-upload', $bank) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;background:rgba(255,255,255,.08);color:white;border-color:rgba(255,255,255,.2)">⬆ Bulk Import</a>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css">
<style>
.breadcrumb{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--slate-light);margin-bottom:20px}
.breadcrumb a{color:var(--indigo);text-decoration:none;font-weight:500}
/* Full-height split workspace — each pane scrolls independently, no page scroll */
.two-col{display:grid;grid-template-columns:1fr 440px;gap:0}
.two-col > div{height:100%;overflow-y:auto;padding:20px}
.two-col > div:first-child{border-right:1px solid var(--border)}
.two-col .card{margin-bottom:12px}
.alert-s,.alert-e{margin:20px 20px 0}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:12px}
.card-header{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between}
.card-title{font-size:13px;font-weight:700;color:var(--midnight)}
.card-body{padding:18px}
.form-group{margin-bottom:14px}
.form-label{display:block;font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px}
.form-label span{color:var(--crimson)}
.form-control{width:100%;padding:9px 12px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms}
.form-control:focus{border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1);background:white}
.option-row{display:flex;gap:8px;align-items:center;margin-bottom:8px}
.option-label{font-size:12px;font-weight:700;color:var(--slate);width:22px;flex-shrink:0}
.option-input{flex:1;padding:8px 10px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:7px;background:#F8FAFC;outline:none}
.option-input:focus{border-color:var(--emerald)}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-primary{background:var(--indigo);color:white;width:100%;justify-content:center}
.btn-primary:hover{background:#1D4ED8}
.btn-ghost{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.btn-danger{background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA;font-size:11px;padding:4px 10px}
.btn-danger:hover{background:#DC2626;color:white}
.q-card{border:1px solid var(--border);border-radius:9px;padding:14px;margin-bottom:10px;background:#FAFBFF}
.q-num{font-weight:700;color:var(--midnight);font-size:13px;margin-bottom:4px;display:flex;align-items:center;gap:8px}
.q-text{font-size:13px;color:var(--midnight);line-height:1.5;margin-bottom:10px}
.q-options{display:flex;flex-direction:column;gap:4px;margin-bottom:10px}
.q-opt{font-size:12px;padding:5px 10px;border-radius:6px;background:#F8FAFC;color:var(--slate)}
.q-opt.correct{background:#ECFDF5;color:#059669;font-weight:600}
.q-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap}

/* ── Science Equation Editor ────────────────────────────────────── */
.sci-toolbar{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:6px;padding:6px;background:#F0F4FF;border-radius:8px 8px 0 0;border:1.5px solid #BFDBFE;border-bottom:none}
.sci-btn{padding:3px 8px;font-size:12px;border:1px solid #BFDBFE;border-radius:5px;background:white;cursor:pointer;font-family:inherit;color:#1E40AF;font-weight:600;transition:all 120ms}
.sci-btn:hover{background:var(--indigo);color:white;border-color:var(--indigo)}
.sci-btn.sci-math{border-color:#7C3AED;color:#7C3AED}
.sci-btn.sci-math:hover{background:#7C3AED;color:white;border-color:#7C3AED}
.sci-btn.sci-chem{border-color:#059669;color:#059669}
.sci-btn.sci-chem:hover{background:#059669;color:white;border-color:#059669}
.sci-btn.sci-phys{border-color:#D97706;color:#D97706}
.sci-btn.sci-phys:hover{background:#D97706;color:white;border-color:#D97706}
.sci-sep{width:1px;background:#BFDBFE;margin:0 2px;align-self:stretch}
.sci-group-label{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94A3B8;align-self:center;padding:0 4px}

/* Math Modal */
.sci-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9000;align-items:center;justify-content:center}
.sci-modal-bg.open{display:flex}
.sci-modal{background:white;border-radius:14px;width:min(640px,95vw);max-height:90vh;overflow:auto;box-shadow:0 20px 60px rgba(0,0,0,.25)}
.sci-modal-head{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.sci-modal-head h3{font-size:15px;font-weight:800;color:var(--midnight)}
.sci-modal-close{background:none;border:none;font-size:20px;cursor:pointer;color:var(--slate-light);padding:0 4px}
.sci-modal-body{padding:16px 20px}
.sci-tabs{display:flex;gap:4px;flex-wrap:wrap;margin-bottom:14px}
.sci-tab{padding:6px 14px;font-size:12px;font-weight:700;border-radius:8px;border:1.5px solid var(--border);background:white;cursor:pointer;font-family:inherit;transition:all 150ms}
.sci-tab.active{background:var(--indigo);color:white;border-color:var(--indigo)}
.sci-templates{display:none;flex-wrap:wrap;gap:6px;margin-bottom:14px}
.sci-templates.active{display:flex}
.sci-tpl{padding:5px 10px;font-size:11px;border:1px solid var(--border);border-radius:6px;background:#F8FAFC;cursor:pointer;font-family:monospace;color:var(--navy);transition:all 120ms}
.sci-tpl:hover{background:var(--indigo);color:white;border-color:var(--indigo)}
.sci-input-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px}
.sci-input-wrap label{display:block;font-size:11px;font-weight:700;color:var(--slate-light);text-transform:uppercase;margin-bottom:4px}
.sci-input{width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-family:monospace;font-size:13px;outline:none}
.sci-input:focus{border-color:var(--indigo);background:white}
.sci-preview-label{font-size:11px;font-weight:700;text-transform:uppercase;color:var(--slate-light);margin-bottom:8px}
.sci-preview{min-height:60px;padding:14px;background:#F8FAFC;border:1.5px solid var(--border);border-radius:8px;font-size:15px;line-height:1.8;overflow-x:auto}
.sci-mode-row{display:flex;gap:8px;margin-bottom:12px}
.sci-mode-btn{padding:5px 12px;font-size:12px;font-weight:700;border-radius:6px;border:1.5px solid var(--border);background:white;cursor:pointer;font-family:inherit}
.sci-mode-btn.active{background:#EFF6FF;border-color:#2563EB;color:#1D4ED8}
.sci-insert-btn{width:100%;padding:10px;background:var(--indigo);color:white;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;margin-top:8px}
.sci-insert-btn:hover{background:#1D4ED8}
.type-badge{display:inline-flex;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:uppercase;letter-spacing:.04em}
.diff-badge{font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;background:#F1F5F9;color:#64748B}
.diff-1{background:#ECFDF5;color:#059669}
.diff-2{background:#FFFBEB;color:#D97706}
.diff-3{background:#FEF2F2;color:#DC2626}
.essay-answer{background:#F5F3FF;border-radius:6px;padding:8px 12px;font-size:12px;color:#6D28D9;margin-top:6px}
.hint{font-size:11px;color:var(--slate-light);margin-top:4px}
.type-tabs{display:flex;gap:4px;margin-bottom:16px;flex-wrap:wrap}
.type-tab{padding:6px 12px;font-size:12px;font-weight:600;border-radius:7px;border:1.5px solid var(--border);background:white;color:var(--slate);cursor:pointer;font-family:inherit;transition:all 150ms}
.type-tab.active{background:var(--indigo);border-color:var(--indigo);color:white}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:14px}
.alert-e{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px 14px;font-size:13px;color:#DC2626;margin-bottom:14px}
.marks-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
</style>
@endpush

@section('content')
<div style="display:flex;flex-direction:column;height:100%">

@if(session('success'))<div class="alert-s">✓ {{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-e">{{ $errors->first() }}</div>@endif
@if(session('errors_list') && count(session('errors_list')))
<div class="alert-e">
    <strong>Import warnings:</strong><br>
    @foreach(session('errors_list') as $e)<div>{{ $e }}</div>@endforeach
</div>
@endif

<div class="two-col" style="flex:1;min-height:0">
    {{-- Left: Question list --}}
    <div>
        <div class="card">
            <div class="card-header">
                <span class="card-title">📚 Questions ({{ $questions->total() }})</span>
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                    <form method="POST" action="{{ route('cbt.banks.reshuffle', $bank) }}"
                          onsubmit="return confirm('Reshuffle the order of all questions in this bank?')">
                        @csrf
                        <button type="submit" class="btn btn-ghost" style="padding:5px 12px;font-size:12px">🔀 Reshuffle</button>
                    </form>
                    <a href="{{ route('cbt.banks.edit', $bank) }}" class="btn btn-ghost" style="padding:5px 12px;font-size:12px">✏️ Edit Bank</a>
                    <a href="{{ route('cbt.bulk-upload', $bank) }}" class="btn btn-ghost" style="padding:5px 12px;font-size:12px">⬆ Bulk Import</a>
                </div>
            </div>
            <div class="card-body">
            @forelse($questions as $q)
            @php
                [$tbg, $tclr] = $q->typeBadgeColor();
            @endphp
            <div class="q-card">
                <div class="q-num">
                    <span>{{ $loop->iteration }}.</span>
                    <span class="type-badge" style="background:{{ $tbg }};color:{{ $tclr }}">{{ $q->typeLabel() }}</span>
                    <span class="diff-badge diff-{{ $q->difficulty }}">
                        {{ ['','Easy','Medium','Hard'][$q->difficulty ?? 1] }}
                    </span>
                    <span style="font-size:10px;color:var(--slate-light);margin-left:auto">
                        {{ $q->marks ?? 1 }} mark{{ ($q->marks ?? 1) != 1 ? 's':'' }}
                    </span>
                </div>
                <div class="q-text">{!! nl2br(e($q->question_html ?: $q->question_text)) !!}</div>
                @if($q->image_path)
                <div style="margin-bottom:12px">
                    <img src="{{ Storage::url($q->image_path) }}" alt="Question image"
                         style="max-width:100%;max-height:250px;border-radius:8px;border:1px solid var(--border)">
                </div>
                @endif

                {{-- MCQ / True-False options --}}
                @if($q->isMcq() || $q->isTrueFalse())
                <div class="q-options">
                    @foreach($q->optionsArray() as $letter => $text)
                    @php $isCorrect = $letter === $q->correct_answer_letter; @endphp
                    <div class="q-opt {{ $isCorrect ? 'correct':'' }}">
                        <strong>{{ strtoupper($letter) }}.</strong> {{ $text }} @if($isCorrect) ✓ @endif
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Essay/Short answer model answer --}}
                @if($q->isManualGraded() && $q->model_answer)
                <div class="essay-answer">
                    📝 Model Answer: {{ Str::limit($q->model_answer, 120) }}
                </div>
                @endif
                @if($q->isFillBlank() && $q->model_answer)
                <div class="essay-answer" style="background:#EFF6FF;color:#2563EB">
                    ✏ Answer: {{ $q->model_answer }}
                </div>
                @endif

                <div class="q-meta" style="margin-top:8px">
                    @if($q->explanation)
                    <span style="font-size:11px;color:var(--slate-light)">💡 {{ Str::limit($q->explanation, 80) }}</span>
                    @endif
                    <div style="display:flex;gap:6px;margin-left:auto">
                        <a href="{{ route('cbt.questions.edit', $q) }}" class="btn" style="padding:4px 10px;font-size:11px;background:#EFF6FF;color:var(--indigo);border:1px solid #BFDBFE">✏️ Edit</a>
                        <form method="POST" action="{{ route('cbt.questions.destroy', $q) }}"
                              onsubmit="return confirm('Delete this question?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div style="text-align:center;padding:50px;color:var(--slate-light)">
                <div style="font-size:36px;margin-bottom:12px">📝</div>
                <div style="font-weight:600">No questions yet</div>
                <div style="font-size:12px;margin-top:4px">Add questions using the form →</div>
            </div>
            @endforelse
            <div style="margin-top:10px">{{ $questions->links() }}</div>
            </div>
        </div>
    </div>

    {{-- Right: Add question form --}}
    <div>
        <div class="card">
            <div class="card-header"><span class="card-title">➕ Add Question</span></div>
            <div class="card-body">

            {{-- Question type selector --}}
            <div class="form-group">
                <label class="form-label">Question Type *</label>
                <div class="type-tabs">
                    @foreach(['mcq'=>'MCQ','essay'=>'Essay','short_answer'=>'Short Answer','fill_blank'=>'Fill Blank','true_false'=>'True / False'] as $val => $label)
                    <button type="button" class="type-tab {{ old('type','mcq') === $val ? 'active':'' }}"
                            onclick="setType('{{ $val }}')">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <form method="POST" action="{{ route('cbt.questions.store', $bank) }}" id="qForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="type" id="qType" value="{{ old('type','mcq') }}">

                <div class="form-group">
                    <label class="form-label">Question Text *</label>
                    {{-- Science equation toolbar --}}
                    <div class="sci-toolbar">
                        <span class="sci-group-label">Format</span>
                        <button type="button" class="sci-btn" onclick="sciInsert('\\(','\\)')" title="Inline math">∫ Math</button>
                        <button type="button" class="sci-btn" onclick="sciInsert('$$\n','$$')" title="Block equation">⎡ Block</button>
                        <div class="sci-sep"></div>
                        <span class="sci-group-label">Math</span>
                        <button type="button" class="sci-btn sci-math" onclick="openSciModal('math')" title="Equation builder">∑ Builder</button>
                        <button type="button" class="sci-btn sci-math" onclick="sciInsert('\\frac{','}{}')">a/b</button>
                        <button type="button" class="sci-btn sci-math" onclick="sciInsert('\\sqrt{','}')">√</button>
                        <button type="button" class="sci-btn sci-math" onclick="sciInsert('x^{','}')" title="Power">xⁿ</button>
                        <button type="button" class="sci-btn sci-math" onclick="sciInsert('x_{','}')" title="Subscript">x₂</button>
                        <div class="sci-sep"></div>
                        <span class="sci-group-label">Chemistry</span>
                        <button type="button" class="sci-btn sci-chem" onclick="openSciModal('chem')" title="Chemical equation">⚗ Builder</button>
                        <button type="button" class="sci-btn sci-chem" onclick="sciInsert('\\ce{','}')">\ce{}</button>
                        <button type="button" class="sci-btn sci-chem" onclick="sciInsert('\\ce{','->}')" title="Reaction arrow">→</button>
                        <button type="button" class="sci-btn sci-chem" onclick="sciInsert('\\ce{','<=>}')" title="Equilibrium">⇌</button>
                        <div class="sci-sep"></div>
                        <span class="sci-group-label">Physics</span>
                        <button type="button" class="sci-btn sci-phys" onclick="openSciModal('phys')" title="Physics formulas">⚛ Formulas</button>
                        <div class="sci-sep"></div>
                        <span class="sci-group-label">Symbols</span>
                        @foreach(['α','β','γ','Δ','θ','λ','μ','π','Σ','ω','°','∞','≤','≥','≠','≈','±'] as $sym)
                        <button type="button" class="sci-btn" onclick="sciInsert('{{ $sym }}','')">{{ $sym }}</button>
                        @endforeach
                    </div>
                    <textarea name="question_text" id="qText" class="form-control" rows="4"
                              placeholder="Type your question here. Use \( x^2 \) for inline math, $$ \frac{a}{b} $$ for block equations, \ce{H2O} for chemistry." required>{{ old('question_text') }}</textarea>
                    <div style="font-size:11px;color:var(--slate-light);margin-top:4px">
                        📐 Math: <code>\( E=mc^2 \)</code> &nbsp;⚗ Chemistry: <code>\ce{H2SO4 + 2NaOH -> Na2SO4 + 2H2O}</code> &nbsp;⚛ Physics: <code>\( F=ma \)</code>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Diagram / Image (optional)</label>
                    <input type="file" name="image" accept="image/*" class="form-control" style="padding:6px">
                    <div class="hint">Attach a diagram, chart, graph or image (JPG, PNG, GIF, WEBP · Max 4MB)</div>
                </div>

                {{-- MCQ Options --}}
                <div id="mcqSection">
                    <div class="form-group">
                        <label class="form-label">Options — mark the correct answer ✓</label>
                        @foreach(['a'=>'A','b'=>'B','c'=>'C','d'=>'D'] as $val => $letter)
                        <div class="option-row">
                            <input type="radio" name="correct_answer_letter" value="{{ $val }}"
                                   {{ old('correct_answer_letter','a') === $val ? 'checked':'' }}
                                   required id="opt_{{ $val }}">
                            <label class="option-label" for="opt_{{ $val }}">{{ $letter }}.</label>
                            <input type="text" name="option_{{ $val }}" class="option-input"
                                   placeholder="Option {{ $letter }}"
                                   value="{{ old('option_'.$val) }}"
                                   {{ in_array($val,['a','b']) ? 'required':'' }}>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- True / False --}}
                <div id="tfSection" style="display:none">
                    <div class="form-group">
                        <label class="form-label">Correct Answer *</label>
                        <div style="display:flex;gap:16px;align-items:center;padding:10px 0">
                            <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                                <input type="radio" name="correct_answer_letter" value="a" id="tfTrue"> True
                            </label>
                            <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                                <input type="radio" name="correct_answer_letter" value="b" id="tfFalse"> False
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Essay / Short Answer --}}
                <div id="essaySection" style="display:none">
                    <div class="form-group">
                        <label class="form-label">Model / Reference Answer (optional)</label>
                        <textarea name="model_answer" class="form-control" rows="3"
                                  placeholder="Reference answer for manual marking...">{{ old('model_answer') }}</textarea>
                    </div>
                    <div class="form-group" id="wordLimitGroup">
                        <label class="form-label">Word Limit (optional)</label>
                        <input name="word_limit" type="number" class="form-control"
                               min="10" value="{{ old('word_limit') }}" placeholder="e.g. 200">
                    </div>
                </div>

                {{-- Fill Blank --}}
                <div id="fillSection" style="display:none">
                    <div class="form-group">
                        <label class="form-label">Correct Answer *</label>
                        <input name="model_answer" class="form-control"
                               placeholder="The answer that fills the blank" value="{{ old('model_answer') }}">
                        <div class="hint">Use _____ in the question text to show the blank.</div>
                    </div>
                </div>

                {{-- Common fields --}}
                <div class="marks-row">
                    <div class="form-group">
                        <label class="form-label">Marks</label>
                        <input name="marks" type="number" step="0.5" min="0.5"
                               class="form-control" value="{{ old('marks',1) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Difficulty</label>
                        <select name="difficulty" class="form-control">
                            <option value="1" {{ old('difficulty') == 1 ? 'selected':'' }}>Easy</option>
                            <option value="2" {{ old('difficulty',2) == 2 ? 'selected':'' }}>Medium</option>
                            <option value="3" {{ old('difficulty') == 3 ? 'selected':'' }}>Hard</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Explanation (optional)</label>
                    <textarea name="explanation" class="form-control" rows="2"
                              placeholder="Why is this the correct answer?">{{ old('explanation') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">➕ Add Question</button>
            </form>

            </div>
        </div>
    {{-- Bulk Import Card --}}
    <div class="card" style="border:2px dashed #BFDBFE;background:#EFF6FF">
        <div class="card-header" style="background:#EFF6FF;border-color:#BFDBFE">
            <span class="card-title" style="color:var(--indigo)">⬆ Bulk Import from CSV</span>
        </div>
        <div class="card-body" style="font-size:13px">
            <p style="color:var(--slate);margin-bottom:12px;line-height:1.6">
                Import multiple questions at once using a CSV file.
                Supports <strong>MCQ, Essay, Short Answer, Fill Blank, True/False</strong>.
            </p>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <a href="{{ route('cbt.bulk-upload', $bank) }}"
                   class="btn btn-primary" style="background:var(--indigo);color:white;flex:1;justify-content:center">
                    ⬆ Upload CSV Questions
                </a>
                <a href="{{ route('cbt.bulk-template') }}"
                   class="btn btn-ghost" style="white-space:nowrap">
                    ↓ Download Template
                </a>
            </div>
            <div style="margin-top:10px;font-size:11px;color:var(--slate-light)">
                CSV columns: type, question_text, option_a, option_b, option_c, option_d, correct_option (a/b/c/d), explanation, difficulty (1-3), marks, model_answer
            </div>
        </div>
    </div>
    </div>
</div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     SCIENCE EQUATION MODAL
     Tabs: Math | Chemistry | Physics | Biology
════════════════════════════════════════════════════════════ --}}
<div class="sci-modal-bg" id="sciModal">
<div class="sci-modal">
    <div class="sci-modal-head">
        <h3>⚗ Science Equation Builder</h3>
        <button class="sci-modal-close" onclick="closeSciModal()" aria-label="Close">&times;</button>
    </div>
    <div class="sci-modal-body">
        {{-- Tabs --}}
        <div class="sci-tabs">
            <button class="sci-tab active" data-tab="math" onclick="switchSciTab('math')">📐 Math</button>
            <button class="sci-tab" data-tab="chem" onclick="switchSciTab('chem')">⚗ Chemistry</button>
            <button class="sci-tab" data-tab="phys" onclick="switchSciTab('phys')">⚛ Physics</button>
            <button class="sci-tab" data-tab="bio" onclick="switchSciTab('bio')">🧬 Biology</button>
        </div>

        {{-- Math templates --}}
        <div class="sci-templates active" data-tab="math">
            <button class="sci-tpl" onclick="insertSciTemplate('\\frac{a}{b}')" title="Fraction">a/b</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\sqrt{x}')" title="Square root">√x</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\sqrt[n]{x}')" title="nth root">ⁿ√x</button>
            <button class="sci-tpl" onclick="insertSciTemplate('x^{n}')" title="Power">xⁿ</button>
            <button class="sci-tpl" onclick="insertSciTemplate('x_{n}')" title="Subscript">xₙ</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\int_{a}^{b} f(x)\\,dx')" title="Definite integral">∫ᵇₐ</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\sum_{i=1}^{n} x_i')" title="Summation">Σ</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\lim_{x \\to \\infty}')" title="Limit">lim</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\vec{v}')" title="Vector">v⃗</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\overline{AB}')" title="Line segment">AB̄</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\angle ABC')" title="Angle">∠ABC</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\triangle ABC')" title="Triangle">△ABC</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\pi r^2')" title="Circle area">πr²</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\frac{-b \\pm \\sqrt{b^2-4ac}}{2a}')" title="Quadratic formula">Quadratic</button>
            <button class="sci-tpl" onclick="insertSciTemplate('a^2 + b^2 = c^2')" title="Pythagoras">Pythagoras</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\begin{pmatrix} a & b \\\\ c & d \\end{pmatrix}')" title="Matrix">Matrix</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\log_{b}(x)')" title="Logarithm">logₙ</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\sin(\\theta)')" title="Sine">sin θ</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\cos(\\theta)')" title="Cosine">cos θ</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\tan(\\theta)')" title="Tangent">tan θ</button>
        </div>

        {{-- Chemistry templates --}}
        <div class="sci-templates" data-tab="chem">
            <button class="sci-tpl" onclick="insertSciTemplate('\\ce{H2O}')" title="Water">H₂O</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\ce{H2SO4}')" title="Sulfuric acid">H₂SO₄</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\ce{NaOH}')" title="Sodium hydroxide">NaOH</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\ce{CO2}')" title="Carbon dioxide">CO₂</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\ce{A + B -> C + D}')" title="Reaction">A → B</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\ce{A <=> B}')" title="Equilibrium">A ⇌ B</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\ce{2H2 + O2 -> 2H2O}')" title="Combustion H2">2H₂+O₂</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\ce{CH4 + 2O2 -> CO2 + 2H2O}')" title="Methane combustion">CH₄ combustion</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\ce{H2SO4 + 2NaOH -> Na2SO4 + 2H2O}')" title="Neutralisation">Neutralisation</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\ce{Fe^{2+}}')" title="Iron II ion">Fe²⁺</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\ce{Cl-}')" title="Chloride ion">Cl⁻</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\ce{Ca(OH)2}')" title="Calcium hydroxide">Ca(OH)₂</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\ce{CaCO3 ->[$\\Delta$] CaO + CO2}')" title="Thermal decomposition">Decomposition</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\ce{Zn + H2SO4 -> ZnSO4 + H2^{}}')" title="H2 production">Zn+H₂SO₄</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\ce{[Ag(NH3)2]+}')" title="Complex ion">Complex ion</button>
        </div>

        {{-- Physics templates --}}
        <div class="sci-templates" data-tab="phys">
            <button class="sci-tpl" onclick="insertSciTemplate('F = ma')" title="Newton 2nd law">F=ma</button>
            <button class="sci-tpl" onclick="insertSciTemplate('E = mc^2')" title="Mass-energy">E=mc²</button>
            <button class="sci-tpl" onclick="insertSciTemplate('v = u + at')" title="SUVAT 1">v=u+at</button>
            <button class="sci-tpl" onclick="insertSciTemplate('s = ut + \\frac{1}{2}at^2')" title="SUVAT 2">s=ut+½at²</button>
            <button class="sci-tpl" onclick="insertSciTemplate('v^2 = u^2 + 2as')" title="SUVAT 3">v²=u²+2as</button>
            <button class="sci-tpl" onclick="insertSciTemplate('P = \\frac{W}{t}')" title="Power">P=W/t</button>
            <button class="sci-tpl" onclick="insertSciTemplate('V = IR')" title="Ohm's Law">V=IR</button>
            <button class="sci-tpl" onclick="insertSciTemplate('W = mg')" title="Weight">W=mg</button>
            <button class="sci-tpl" onclick="insertSciTemplate('KE = \\frac{1}{2}mv^2')" title="Kinetic energy">½mv²</button>
            <button class="sci-tpl" onclick="insertSciTemplate('PE = mgh')" title="Potential energy">mgh</button>
            <button class="sci-tpl" onclick="insertSciTemplate('P = \\frac{F}{A}')" title="Pressure">P=F/A</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\rho = \\frac{m}{V}')" title="Density">ρ=m/V</button>
            <button class="sci-tpl" onclick="insertSciTemplate('f = \\frac{1}{T}')" title="Frequency">f=1/T</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\lambda f = c')" title="Wave speed">λf=c</button>
            <button class="sci-tpl" onclick="insertSciTemplate('Q = mc\\Delta T')" title="Heat">Q=mcΔT</button>
            <button class="sci-tpl" onclick="insertSciTemplate('F = \\frac{Gm_1m_2}{r^2}')" title="Gravity">Gravitation</button>
        </div>

        {{-- Biology --}}
        <div class="sci-templates" data-tab="bio">
            <button class="sci-tpl" onclick="insertSciTemplate('\\ce{6CO2 + 6H2O ->[$\\text{light}$] C6H12O6 + 6O2}')" title="Photosynthesis">Photosynthesis</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\ce{C6H12O6 + 6O2 -> 6CO2 + 6H2O + ATP}')" title="Respiration">Respiration</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\text{DNA} \\xrightarrow{\\text{transcription}} \\text{mRNA} \\xrightarrow{\\text{translation}} \\text{Protein}')" title="Central dogma">Central Dogma</button>
            <button class="sci-tpl" onclick="insertSciTemplate('\\frac{\\text{Number of organisms}}{\\text{Total area}} = \\text{Population density}')" title="Population density">Pop. Density</button>
            <button class="sci-tpl" onclick="insertSciTemplate('P_1 = 0.5\\;Aa, \\quad P_2 = 0.25\\;AA + 0.5\\;Aa + 0.25\\;aa')" title="Mendelian ratio">Mendelian</button>
        </div>

        {{-- LaTeX input --}}
        <div style="margin-top:8px">
            <div class="sci-preview-label">LaTeX Code</div>
            <div class="sci-mode-row">
                <button class="sci-mode-btn active" data-mode="inline" onclick="setSciMode('inline')">Inline \( ... \)</button>
                <button class="sci-mode-btn" data-mode="block" onclick="setSciMode('block')">Block $$ ... $$</button>
            </div>
            <input type="text" class="sci-input" id="sciLatexInput" placeholder="Type or click a template above, e.g.  \frac{a}{b}  or  \ce{H2O}">
        </div>

        {{-- Preview --}}
        <div style="margin-top:12px">
            <div class="sci-preview-label">Live Preview</div>
            <div class="sci-preview" id="sciPreview"><span style="color:#94A3B8">Preview will appear here</span></div>
        </div>

        <button class="sci-insert-btn" onclick="insertSciEquation()">Insert into Question ✓</button>
    </div>
</div>
</div>

@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/mhchem.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js"
    onload="renderListMath()"></script>
<script>
/* ── Science equation toolbar quick insert ── */
function sciInsert(before, after) {
    var el = document.getElementById('qText');
    if (!el) return;
    var s = el.selectionStart, e = el.selectionEnd;
    var sel = el.value.slice(s, e);
    el.value = el.value.slice(0, s) + before + sel + after + el.value.slice(e);
    el.selectionStart = el.selectionEnd = s + before.length + sel.length;
    el.focus();
}

/* ── KaTeX auto-render on question list cards ── */
function renderListMath() {
    if (typeof renderMathInElement === 'undefined') return;
    var opts = {
        delimiters: [
            {left:'$$', right:'$$', display:true},
            {left:'\\[', right:'\\]', display:true},
            {left:'\\(', right:'\\)', display:false},
            {left:'$', right:'$', display:false}
        ],
        throwOnError: false
    };
    document.querySelectorAll('.q-text,.q-opt').forEach(function(el) {
        renderMathInElement(el, opts);
    });
}

/* ── Science Modal ── */
var _sciMode = 'inline';
function openSciModal(tab) {
    document.getElementById('sciModal').classList.add('open');
    switchSciTab(tab || 'math');
    renderSciPreview();
    setTimeout(function() { var i=document.getElementById('sciLatexInput'); if(i) i.focus(); }, 100);
}
function closeSciModal() { document.getElementById('sciModal').classList.remove('open'); }
function switchSciTab(tab) {
    document.querySelectorAll('.sci-tab').forEach(function(t) { t.classList.toggle('active', t.dataset.tab===tab); });
    document.querySelectorAll('.sci-templates').forEach(function(t) { t.classList.toggle('active', t.dataset.tab===tab); });
}
function renderSciPreview() {
    var latex = (document.getElementById('sciLatexInput')||{}).value||'';
    var prev  = document.getElementById('sciPreview');
    if (!prev) return;
    latex = latex.trim();
    if (!latex) { prev.innerHTML = '<span style="color:#94A3B8">Preview will appear here</span>'; return; }
    try {
        prev.innerHTML = katex.renderToString(latex, {displayMode:_sciMode==='block', throwOnError:false, trust:false});
    } catch(e) {
        prev.innerHTML = '<span style="color:#DC2626;font-size:12px">&#9888; '+e.message+'</span>';
    }
}
function insertSciEquation() {
    var latex = (document.getElementById('sciLatexInput')||{}).value||'';
    if (!latex.trim()) return;
    var b = _sciMode==='block' ? '\n$$\n' : '\\(';
    var a = _sciMode==='block' ? '\n$$\n' : '\\)';
    sciInsert(b + latex.trim() + a, '');
    closeSciModal();
    document.getElementById('sciLatexInput').value = '';
}
function setSciMode(mode) {
    _sciMode = mode;
    document.querySelectorAll('.sci-mode-btn').forEach(function(b) { b.classList.toggle('active', b.dataset.mode===mode); });
    renderSciPreview();
}
function insertSciTemplate(latex) {
    document.getElementById('sciLatexInput').value = latex;
    renderSciPreview();
    document.getElementById('sciLatexInput').focus();
}
document.addEventListener('DOMContentLoaded', function() {
    var inp = document.getElementById('sciLatexInput');
    if (inp) inp.addEventListener('input', renderSciPreview);
    var bg = document.getElementById('sciModal');
    if (bg) bg.addEventListener('click', function(e){ if(e.target===bg) closeSciModal(); });
    /* type-tab wiring */
    var smap = {'MCQ':'mcq','Essay':'essay','Short Answer':'short_answer','Fill Blank':'fill_blank','True / False':'true_false'};
    document.querySelectorAll('.type-tab').forEach(function(btn) {
        btn.addEventListener('click', function() { setType(smap[btn.textContent.trim()]); });
    });
});
var sects = {mcq:['mcqSection'],true_false:['tfSection'],essay:['essaySection'],short_answer:['essaySection'],fill_blank:['fillSection']};
function setType(type) {
    document.getElementById('qType').value = type;
    document.querySelectorAll('.type-tab').forEach(function(t) {
        t.classList.toggle('active',
            (type==='mcq'&&t.textContent.trim()==='MCQ')||
            (type==='essay'&&t.textContent.trim()==='Essay')||
            (type==='short_answer'&&t.textContent.trim()==='Short Answer')||
            (type==='fill_blank'&&t.textContent.trim()==='Fill Blank')||
            (type==='true_false'&&t.textContent.trim()==='True / False')
        );
    });
    ['mcqSection','tfSection','essaySection','fillSection'].forEach(function(id){ document.getElementById(id).style.display='none'; });
    (sects[type]||['mcqSection']).forEach(function(id){ document.getElementById(id).style.display=''; });
    var isMcq = type==='mcq';
    document.querySelectorAll('input[name^="option_"]').forEach(function(el,i){ el.required=isMcq&&i<2; });
    document.querySelectorAll('input[name="correct_answer_letter"]').forEach(function(el){ el.required=['mcq','true_false'].includes(type); });
}
setType(document.getElementById('qType').value || 'mcq');
</script>
@endpush
