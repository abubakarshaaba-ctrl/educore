@extends('layouts.app')
@section('title','Edit Question')
@section('page-title','CBT Exams')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css">
<style>
.form-page{width:100%}
.breadcrumb{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--slate-light);margin-bottom:20px}
.breadcrumb a{color:var(--indigo);text-decoration:none;font-weight:500}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:14px 20px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;gap:8px}
.cb{padding:22px}
.fg{display:flex;flex-direction:column;gap:6px;margin-bottom:16px}
.fl{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:10px 12px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%;transition:border 200ms}
.fc:focus{border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1);background:white}
.two{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.option-row{display:flex;align-items:center;gap:10px;margin-bottom:10px}
.opt-letter{width:30px;height:30px;border-radius:6px;background:#F1F5F9;border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:var(--slate);flex-shrink:0}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-g{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.type-tabs{display:flex;gap:4px;margin-bottom:16px;flex-wrap:wrap}
.type-tab{padding:6px 12px;font-size:12px;font-weight:600;border-radius:7px;border:1.5px solid var(--border);background:white;color:var(--slate);cursor:pointer;font-family:inherit;transition:all 150ms}
.type-tab.active{background:var(--indigo);border-color:var(--indigo);color:white}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:14px}
.alert-e{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px 14px;font-size:13px;color:var(--crimson);margin-bottom:14px}
.hint{font-size:11px;color:var(--slate-light);margin-top:4px}
.img-preview{max-width:100%;max-height:200px;border-radius:8px;border:1px solid var(--border);margin-bottom:10px}
/* Rich text editor */
.editor-toolbar{display:flex;flex-wrap:wrap;gap:4px;padding:8px;background:#F8FAFC;border:1.5px solid var(--border);border-bottom:none;border-radius:8px 8px 0 0}
.ed-btn{padding:4px 8px;font-size:12px;font-family:inherit;border:1px solid var(--border);border-radius:5px;background:white;cursor:pointer;color:var(--slate);transition:all 120ms}
.ed-btn:hover{background:var(--indigo);color:white;border-color:var(--indigo)}
.ed-sep{width:1px;background:var(--border);margin:2px 4px}
.editor-area{width:100%;min-height:120px;padding:12px;font-size:14px;font-family:inherit;border:1.5px solid var(--border);border-top:none;border-radius:0 0 8px 8px;outline:none;line-height:1.65;color:var(--midnight)}
.editor-area:focus{border-color:var(--indigo)}
@media(max-width:600px){.two{grid-template-columns:1fr}}
.sci-toolbar{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:0;padding:6px;background:#F0F4FF;border-radius:8px 8px 0 0;border:1.5px solid #BFDBFE;border-bottom:none}
.sci-btn{padding:3px 8px;font-size:11px;border:1px solid #BFDBFE;border-radius:5px;background:white;cursor:pointer;font-family:inherit;color:#1E40AF;font-weight:600}
.sci-btn:hover{background:var(--indigo);color:white}.sci-btn.sci-math{border-color:#7C3AED;color:#7C3AED}.sci-btn.sci-math:hover{background:#7C3AED;color:white}
.sci-btn.sci-chem{border-color:#059669;color:#059669}.sci-btn.sci-chem:hover{background:#059669;color:white}
.sci-btn.sci-phys{border-color:#D97706;color:#D97706}.sci-btn.sci-phys:hover{background:#D97706;color:white}
.sci-sep{width:1px;background:#BFDBFE;margin:0 2px;align-self:stretch}.sci-group-label{font-size:9px;font-weight:700;text-transform:uppercase;color:#94A3B8;align-self:center;padding:0 4px}
.sci-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9000;align-items:center;justify-content:center}.sci-modal-bg.open{display:flex}
.sci-modal{background:white;border-radius:14px;width:min(640px,95vw);max-height:90vh;overflow:auto;box-shadow:0 20px 60px rgba(0,0,0,.25)}
.sci-modal-head{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.sci-modal-head h3{font-size:15px;font-weight:800;color:var(--midnight)}.sci-modal-close{background:none;border:none;font-size:20px;cursor:pointer;color:var(--slate-light);padding:0 4px}
.sci-modal-body{padding:16px 20px}.sci-tabs{display:flex;gap:4px;flex-wrap:wrap;margin-bottom:14px}
.sci-tab{padding:6px 14px;font-size:12px;font-weight:700;border-radius:8px;border:1.5px solid var(--border);background:white;cursor:pointer;font-family:inherit}.sci-tab.active{background:var(--indigo);color:white;border-color:var(--indigo)}
.sci-templates{display:none;flex-wrap:wrap;gap:6px;margin-bottom:14px}.sci-templates.active{display:flex}
.sci-tpl{padding:5px 10px;font-size:11px;border:1px solid var(--border);border-radius:6px;background:#F8FAFC;cursor:pointer;font-family:monospace}.sci-tpl:hover{background:var(--indigo);color:white}
.sci-input{width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-family:monospace;font-size:13px;outline:none}.sci-input:focus{border-color:var(--indigo)}
.sci-preview-label{font-size:11px;font-weight:700;text-transform:uppercase;color:var(--slate-light);margin-bottom:8px}
.sci-preview{min-height:60px;padding:14px;background:#F8FAFC;border:1.5px solid var(--border);border-radius:8px;font-size:15px;overflow-x:auto}
.sci-mode-row{display:flex;gap:8px;margin-bottom:12px}.sci-mode-btn{padding:5px 12px;font-size:12px;font-weight:700;border-radius:6px;border:1.5px solid var(--border);background:white;cursor:pointer;font-family:inherit}.sci-mode-btn.active{background:#EFF6FF;border-color:#2563EB;color:#1D4ED8}
.sci-insert-btn{width:100%;padding:10px;background:var(--indigo);color:white;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;margin-top:8px}
</style>
@endpush

@section('content')
<div class="form-page">
<div class="breadcrumb">
    <a href="{{ route('cbt.banks') }}">Banks</a>
    <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
    <a href="{{ route('cbt.questions', $bank) }}">{{ $bank->name }}</a>
    <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
    Edit Question
</div>

@if(session('success'))<div class="alert-s">✓ {{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-e">{{ $errors->first() }}</div>@endif

<form method="POST" action="{{ route('cbt.questions.update', $q) }}" enctype="multipart/form-data" id="qForm">
@csrf @method('PUT')
<input type="hidden" name="type" id="qType" value="{{ old('type', $q->type ?? 'mcq') }}">

{{-- Type + Marks + Difficulty --}}
<div class="card">
    <div class="ch">📝 Question Details</div>
    <div class="cb">
        <div class="fg">
            <label class="fl">Question Type *</label>
            <div class="type-tabs">
                @foreach(['mcq'=>'MCQ','essay'=>'Essay','short_answer'=>'Short Answer','fill_blank'=>'Fill Blank','true_false'=>'True / False'] as $val => $lbl)
                <button type="button" class="type-tab {{ ($q->type ?? 'mcq') === $val ? 'active':'' }}"
                        data-type="{{ $val }}" onclick="setType('{{ $val }}')">{{ $lbl }}</button>
                @endforeach
            </div>
        </div>

        {{-- Question text with science equation toolbar --}}
        <div class="fg">
            <label class="fl">Question Text *</label>
            <div class="sci-toolbar" id="edToolbar">
                <span class="sci-group-label">Format</span>
                <button type="button" class="sci-btn" onclick="fmt('bold')"><strong>B</strong></button>
                <button type="button" class="sci-btn" onclick="fmt('italic')"><em>I</em></button>
                <button type="button" class="sci-btn" onclick="fmt('underline')"><u>U</u></button>
                <button type="button" class="sci-btn" onclick="fmt('subscript')">X₂</button>
                <button type="button" class="sci-btn" onclick="fmt('superscript')">X²</button>
                <div class="sci-sep"></div>
                <span class="sci-group-label">Math</span>
                <button type="button" class="sci-btn sci-math" onclick="openSciModal('math')">∑ Builder</button>
                <button type="button" class="sci-btn sci-math" onclick="wrapLatex('\\(','\\)')">∫ Inline</button>
                <button type="button" class="sci-btn sci-math" onclick="wrapLatex('\n$$\n','\n$$\n')">⎡ Block</button>
                <button type="button" class="sci-btn sci-math" onclick="wrapLatex('\\frac{','}{}')">a/b</button>
                <button type="button" class="sci-btn sci-math" onclick="wrapLatex('\\sqrt{','}')">√</button>
                <button type="button" class="sci-btn sci-math" onclick="wrapLatex('^{','}')">xⁿ</button>
                <div class="sci-sep"></div>
                <span class="sci-group-label">Chemistry</span>
                <button type="button" class="sci-btn sci-chem" onclick="openSciModal('chem')">⚗ Builder</button>
                <button type="button" class="sci-btn sci-chem" onclick="wrapLatex('\\ce{','}')">\ce{}</button>
                <button type="button" class="sci-btn sci-chem" onclick="wrapLatex('\\ce{','->}')">→</button>
                <button type="button" class="sci-btn sci-chem" onclick="wrapLatex('\\ce{','<=>}')">⇌</button>
                <div class="sci-sep"></div>
                <span class="sci-group-label">Physics</span>
                <button type="button" class="sci-btn sci-phys" onclick="openSciModal('phys')">⚛ Formulas</button>
                <div class="sci-sep"></div>
                <span class="sci-group-label">Symbols</span>
                @foreach(['α','β','γ','Δ','θ','λ','μ','π','Σ','ω','°','∞','≤','≥','≠','≈','±'] as $sym)
                <button type="button" class="sci-btn" onclick="insertSymbol('{{ $sym }}')">{{ $sym }}</button>
                @endforeach
            </div>
            <div class="editor-area" id="qEditor" contenteditable="true"
                 oninput="syncText()">{{ old('question_text', $q->question_text) }}</div>
            <input type="hidden" name="question_text" id="qTextHidden" value="{{ old('question_text', $q->question_text) }}">
            <div style="font-size:11px;color:var(--slate-light);margin-top:4px">
                📐 Math: <code>\( x^2 + y^2 \)</code> &nbsp;⚗ Chem: <code>\ce{H2SO4 + 2NaOH -> Na2SO4 + 2H2O}</code>
            </div>
        </div>

        {{-- Image attachment --}}
        <div class="fg">
            <label class="fl">Diagram / Image (optional)</label>
            @if($q->image_path)
            <img src="{{ Storage::url($q->image_path) }}" class="img-preview" alt="Question image" id="existingImg">
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                <input type="checkbox" name="remove_image" value="1" onchange="document.getElementById('existingImg').style.opacity=this.checked?'.3':'1'">
                Remove existing image
            </label>
            <div class="hint">Or upload a replacement below:</div>
            @endif
            <input type="file" name="image" accept="image/*,image/svg+xml" class="fc" style="padding:6px">
            <div class="hint">JPG, PNG, GIF, SVG, WebP · Max 4MB. Diagrams, charts, equations, maps, graphs.</div>
        </div>

        <div class="two">
            <div class="fg"><label class="fl">Marks</label>
                <input name="marks" type="number" step="0.5" min="0.5" class="fc" value="{{ old('marks', $q->marks ?? 1) }}">
            </div>
            <div class="fg"><label class="fl">Difficulty</label>
                <select name="difficulty" class="fc">
                    <option value="1" {{ ($q->difficulty??1)==1?'selected':'' }}>Easy</option>
                    <option value="2" {{ ($q->difficulty??1)==2?'selected':'' }}>Medium</option>
                    <option value="3" {{ ($q->difficulty??1)==3?'selected':'' }}>Hard</option>
                </select>
            </div>
        </div>
    </div>
</div>

{{-- MCQ Options --}}
<div class="card" id="mcqSection" style="{{ !in_array($q->type??'mcq',['mcq']) ? 'display:none':'' }}">
    <div class="ch">📋 Options — Click radio to mark correct answer</div>
    <div class="cb">
        @foreach(['a'=>'A','b'=>'B','c'=>'C','d'=>'D'] as $val => $lbl)
        <div class="option-row">
            <input type="radio" name="correct_answer_letter" value="{{ $val }}"
                   {{ old('correct_answer_letter', $q->correct_answer_letter ?? 'a') === $val ? 'checked':'' }}
                   style="width:16px;height:16px;accent-color:var(--indigo)">
            <div class="opt-letter">{{ $lbl }}</div>
            <input type="text" name="option_{{ $val }}" class="fc"
                   placeholder="Option {{ $lbl }}"
                   value="{{ old('option_'.$val, $q->{'option_'.$val}) }}"
                   {{ in_array($val,['a','b']) ? 'required':'' }}>
        </div>
        @endforeach
        <div class="hint">● Select the radio button next to the correct answer.</div>
    </div>
</div>

{{-- True / False --}}
<div class="card" id="tfSection" style="{{ ($q->type??'mcq') !== 'true_false' ? 'display:none':'' }}">
    <div class="ch">✓ True / False — Select the correct answer</div>
    <div class="cb">
        <div style="display:flex;gap:20px;padding:8px 0">
            <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer">
                <input type="radio" name="correct_answer_letter" value="a"
                       {{ old('correct_answer_letter', $q->correct_answer_letter) === 'a' ? 'checked':'' }}
                       style="width:16px;height:16px;accent-color:#059669">
                <strong style="color:#059669">✓ True</strong>
            </label>
            <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer">
                <input type="radio" name="correct_answer_letter" value="b"
                       {{ old('correct_answer_letter', $q->correct_answer_letter) === 'b' ? 'checked':'' }}
                       style="width:16px;height:16px;accent-color:#DC2626">
                <strong style="color:#DC2626">✗ False</strong>
            </label>
        </div>
    </div>
</div>

{{-- Essay / Short / Fill --}}
<div class="card" id="essaySection" style="{{ !in_array($q->type??'mcq',['essay','short_answer','fill_blank']) ? 'display:none':'' }}">
    <div class="ch">📄 Answer Details</div>
    <div class="cb">
        <div class="fg">
            <label class="fl">Model / Reference Answer</label>
            <textarea name="model_answer" class="fc" rows="4"
                      placeholder="Reference answer for marking...">{{ old('model_answer', $q->model_answer) }}</textarea>
        </div>
        <div class="fg" id="wordLimitGroup">
            <label class="fl">Word Limit (optional — for essay only)</label>
            <input name="word_limit" type="number" class="fc" min="10"
                   value="{{ old('word_limit', $q->word_limit) }}" placeholder="e.g. 200">
        </div>
    </div>
</div>

{{-- Explanation --}}
<div class="card">
    <div class="ch">💡 Explanation (shown after submission)</div>
    <div class="cb">
        <textarea name="explanation" class="fc" rows="2"
                  placeholder="Why is this the correct answer?">{{ old('explanation', $q->explanation) }}</textarea>
    </div>
</div>

<div style="display:flex;gap:10px;margin-bottom:30px">
    <button type="submit" class="btn btn-p">✓ Save Question</button>
    <a href="{{ route('cbt.questions', $bank) }}" class="btn btn-g">Cancel</a>
</div>
</form>
</div>
{{-- Include Science Modal --}}
@include('cbt.partials.sci-modal')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/mhchem.min.js"></script>
<script>
// ── Rich text editor ──────────────────────────────────────────────────
var ed = document.getElementById('qEditor');
var hidden = document.getElementById('qTextHidden');

function fmt(cmd) { document.execCommand(cmd, false, null); ed.focus(); syncText(); }
function syncText() { hidden.value = ed.innerText; }

function insertSymbol(sym) {
    ed.focus();
    var sel = window.getSelection();
    if (sel.rangeCount) {
        var r = sel.getRangeAt(0); r.deleteContents();
        r.insertNode(document.createTextNode(sym));
        r.collapse(false); sel.removeAllRanges(); sel.addRange(r);
    } else { ed.innerHTML += sym; }
    syncText();
}

function wrapLatex(before, after) {
    ed.focus();
    var sel = window.getSelection();
    var txt = sel.rangeCount ? sel.getRangeAt(0).toString() : '';
    insertSymbol(before + (txt || 'expression') + after);
}

// ── Question type switcher ───────────────────────────────────────────
var SECTIONS = {
    mcq:['mcqSection'], true_false:['tfSection'],
    essay:['essaySection'], short_answer:['essaySection'], fill_blank:['essaySection']
};
function setType(type) {
    document.getElementById('qType').value = type;
    document.querySelectorAll('.type-tab').forEach(function(b) { b.classList.toggle('active', b.dataset.type === type); });
    ['mcqSection','tfSection','essaySection'].forEach(function(id) { document.getElementById(id).style.display='none'; });
    (SECTIONS[type]||['mcqSection']).forEach(function(id) { document.getElementById(id).style.display=''; });
    var wlg = document.getElementById('wordLimitGroup');
    if (wlg) wlg.style.display = type === 'essay' ? '' : 'none';
    document.querySelectorAll('[name^="option_"]').forEach(function(el,i) { el.required = type === 'mcq' && i < 2; });
    document.querySelectorAll('[name="correct_answer_letter"]').forEach(function(el) {
        el.required = ['mcq','true_false'].includes(type);
    });
}
setType(document.getElementById('qType').value || 'mcq');

// ── Science Modal ────────────────────────────────────────────────────
var _sciMode = 'inline';

function openSciModal(tab) {
    document.getElementById('sciModal').classList.add('open');
    switchSciTab(tab || 'math');
    renderSciPreview();
    setTimeout(function() { var i = document.getElementById('sciLatexInput'); if(i) i.focus(); }, 100);
}
function closeSciModal() { document.getElementById('sciModal').classList.remove('open'); }

function switchSciTab(tab) {
    document.querySelectorAll('.sci-tab').forEach(function(t) { t.classList.toggle('active', t.dataset.tab === tab); });
    document.querySelectorAll('.sci-templates').forEach(function(t) { t.classList.toggle('active', t.dataset.tab === tab); });
}

function renderSciPreview() {
    var latex = document.getElementById('sciLatexInput').value.trim();
    var prev = document.getElementById('sciPreview');
    if (!latex) { prev.innerHTML = '<span style="color:#94A3B8">Preview will appear here</span>'; return; }
    try {
        prev.innerHTML = katex.renderToString(latex, { displayMode: _sciMode === 'block', throwOnError: false, trust: false });
    } catch(e) {
        prev.innerHTML = '<span style="color:#DC2626;font-size:12px">&#9888; ' + e.message + '</span>';
    }
}

function insertSciEquation() {
    var latex = document.getElementById('sciLatexInput').value.trim();
    if (!latex) return;
    var before = _sciMode === 'block' ? '\n$$\n' : '\\(';
    var after  = _sciMode === 'block' ? '\n$$\n' : '\\)';
    insertSymbol(before + latex + after);
    closeSciModal();
    document.getElementById('sciLatexInput').value = '';
}

function setSciMode(mode) {
    _sciMode = mode;
    document.querySelectorAll('.sci-mode-btn').forEach(function(b) { b.classList.toggle('active', b.dataset.mode === mode); });
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
    if (bg) bg.addEventListener('click', function(e) { if (e.target === bg) closeSciModal(); });
});
</script>
@endpush
