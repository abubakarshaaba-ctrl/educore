<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $exam->title }}</title>
{{-- KaTeX: math, chemistry (mhchem), physics equations --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/mhchem.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js"
    onload="renderAllMath()"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --navy:#0F172A;--navy2:#1E293B;--navy3:#334155;
  --blue:#2563EB;--blue2:#1D4ED8;--blue3:#3B82F6;
  --green:#059669;--amber:#D97706;--red:#DC2626;--purple:#7C3AED;
  --slate:#475569;--muted:#94A3B8;--border:#E2E8F0;--bg:#F1F5F9;
  --white:#FFFFFF;
}
html,body{height:100%;overflow:hidden;font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif;background:var(--bg);color:var(--navy)}

/* ══════════════ TOP BAR ══════════════════════════════════════════════ */
.topbar{
  height:54px;background:var(--navy);color:white;flex-shrink:0;
  display:flex;align-items:center;gap:0;border-bottom:1px solid rgba(255,255,255,0.07);
  position:relative;z-index:300;
}
.tb-brand{
  width:56px;flex-shrink:0;display:flex;align-items:center;justify-content:center;
  border-right:1px solid rgba(255,255,255,0.07);height:100%;
}
.tb-logo{width:32px;height:32px;background:var(--blue);border-radius:8px;display:flex;align-items:center;justify-content:center;}
.tb-logo svg{width:18px;height:18px;fill:white;}
.tb-title{flex:1;padding:0 20px;display:flex;flex-direction:column;justify-content:center;}
.tb-exam{font-size:14px;font-weight:700;line-height:1.2;}
.tb-meta{font-size:11px;color:#64748B;margin-top:1px;}
.tb-right{display:flex;align-items:center;gap:12px;padding:0 18px;flex-shrink:0;}
.timer-box{
  display:flex;flex-direction:column;align-items:center;
  background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);
  border-radius:8px;padding:5px 14px;
}
.timer-val{font-family:'JetBrains Mono',monospace;font-size:17px;font-weight:700;color:white;letter-spacing:.04em;line-height:1;}
.timer-lbl{font-size:9px;color:#64748B;text-transform:uppercase;letter-spacing:.07em;margin-top:2px;}
.timer-box.warn  .timer-val{color:#FDE047;}
.timer-box.danger .timer-val{color:#FCA5A5;animation:blink 1s infinite;}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.5}}
.student-info{font-size:11px;color:#94A3B8;text-align:right;}
.student-info strong{display:block;font-size:13px;font-weight:700;color:white;}

/* ══════════════ SECTION TABS ═════════════════════════════════════════ */
.section-bar{
  background:var(--navy2);flex-shrink:0;
  display:flex;align-items:center;gap:0;
  border-bottom:2px solid rgba(255,255,255,0.05);
  padding:0 18px;
}
.sec-tab{
  padding:0 20px;height:42px;display:flex;align-items:center;gap:7px;
  font-size:12px;font-weight:700;color:#64748B;cursor:pointer;
  border-bottom:2px solid transparent;margin-bottom:-2px;
  transition:all 150ms;white-space:nowrap;background:none;border-top:none;border-left:none;border-right:none;
  font-family:inherit;
}
.sec-tab:hover{color:#94A3B8;}
.sec-tab.active{color:white;border-bottom-color:var(--blue3);}
.sec-count{
  font-size:10px;font-weight:800;padding:2px 7px;border-radius:20px;
  background:rgba(255,255,255,0.08);color:#94A3B8;
}
.sec-tab.active .sec-count{background:var(--blue);color:white;}
.sec-done{
  font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;
  background:rgba(5,150,105,0.2);color:#34D399;margin-left:4px;
}

/* ══════════════ MAIN LAYOUT ══════════════════════════════════════════ */
.main{display:flex;flex:1;overflow:hidden;}

/* ══════════════ QUESTION PANEL (left numbers) ═══════════════════════ */
.q-panel{
  width:220px;flex-shrink:0;background:white;border-right:1px solid var(--border);
  display:flex;flex-direction:column;overflow:hidden;
}
.qp-head{
  padding:12px 14px;border-bottom:1px solid var(--border);background:#F8FAFC;
}
.qp-title{font-size:10px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;}
.qp-sub{font-size:11px;color:var(--muted);margin-top:3px;}
.qp-grid{padding:12px 14px;display:grid;grid-template-columns:repeat(5,1fr);gap:5px;overflow-y:auto;flex:1;align-content:start;}
.qn{
  aspect-ratio:1;border-radius:6px;border:1.5px solid var(--border);
  background:white;font-size:11px;font-weight:700;color:var(--slate);
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  transition:all 120ms;font-family:inherit;
}
.qn:hover{border-color:var(--blue3);color:var(--blue);}
.qn.active{border-color:var(--blue);background:var(--blue);color:white;box-shadow:0 2px 8px rgba(37,99,235,.35);}
.qn.answered{background:#DBEAFE;border-color:var(--blue3);color:var(--blue2);}
.qn.answered.active{background:var(--blue);color:white;}
.qn.essay-ans{background:#D1FAE5;border-color:#6EE7B7;color:var(--green);}
.qn.essay-ans.active{background:var(--green);color:white;}
.qn.flagged::after{content:'';position:absolute;top:2px;right:2px;width:5px;height:5px;border-radius:50%;background:var(--amber);}
.qn{position:relative;}
.qp-legend{padding:10px 14px;border-top:1px solid var(--border);background:#F8FAFC;}
.leg{display:flex;align-items:center;gap:6px;font-size:10px;color:var(--muted);margin-bottom:4px;}
.ld{width:10px;height:10px;border-radius:3px;flex-shrink:0;}

/* ══════════════ EXAM AREA (center) ══════════════════════════════════ */
.exam-area{flex:1;display:flex;flex-direction:column;overflow:hidden;}
.q-viewport{flex:1;overflow:hidden;display:flex;align-items:stretch;}
.q-slide{flex:1;padding:28px 36px;display:none;overflow-y:auto;}
.q-slide.visible{display:flex;flex-direction:column;}
.q-slide::-webkit-scrollbar{width:4px;}
.q-slide::-webkit-scrollbar-thumb{background:#CBD5E1;border-radius:4px;}

/* Question header */
.qs-header{display:flex;align-items:center;gap:10px;margin-bottom:20px;flex-wrap:wrap;}
.qs-num{font-size:12px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;}
.type-pill{font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;text-transform:uppercase;letter-spacing:.04em;}
.marks-pill{font-size:11px;font-weight:600;color:var(--muted);background:#F8FAFC;border:1px solid var(--border);padding:3px 10px;border-radius:20px;margin-left:auto;}
.flag-btn{
  background:none;border:1.5px solid var(--border);border-radius:6px;
  padding:4px 8px;font-size:11px;font-weight:600;color:var(--muted);cursor:pointer;font-family:inherit;transition:all 150ms;
}
.flag-btn:hover{border-color:var(--amber);color:var(--amber);}
.flag-btn.flagged{background:#FFFBEB;border-color:var(--amber);color:var(--amber);}

.qs-text{
  font-size:16px;font-weight:600;color:var(--navy);line-height:1.7;
  margin-bottom:28px;
}

/* MCQ options */
.options{display:flex;flex-direction:column;gap:10px;max-width:680px;}
.opt{
  display:flex;align-items:flex-start;gap:14px;
  padding:14px 18px;border:2px solid var(--border);
  border-radius:11px;cursor:pointer;transition:all 150ms;user-select:none;
  background:white;
}
.opt:hover{border-color:#93C5FD;background:#F0F9FF;}
.opt.chosen{border-color:var(--blue);background:#EFF6FF;}
.opt input{display:none;}
.opt-key{
  width:30px;height:30px;border-radius:7px;border:2px solid #CBD5E1;
  background:#F8FAFC;display:flex;align-items:center;justify-content:center;
  font-size:12px;font-weight:800;color:var(--slate);flex-shrink:0;
  font-family:'JetBrains Mono',monospace;transition:all 150ms;
}
.opt.chosen .opt-key{background:var(--blue);border-color:var(--blue);color:white;}
.opt-text{font-size:14px;color:#334155;line-height:1.55;padding-top:4px;}
.opt.chosen .opt-text{color:#1E40AF;font-weight:500;}

/* TF */
.tf-row{display:flex;gap:14px;max-width:500px;}
.tf-btn{
  flex:1;display:flex;align-items:center;justify-content:center;gap:10px;
  padding:20px;border:2px solid var(--border);border-radius:12px;
  cursor:pointer;font-size:15px;font-weight:700;transition:all 150ms;
  background:white;font-family:inherit;color:var(--slate);
}
.tf-btn:hover{transform:translateY(-1px);box-shadow:0 3px 10px rgba(0,0,0,0.08);}
.tf-btn.t-sel{border-color:var(--green);background:#ECFDF5;color:var(--green);}
.tf-btn.f-sel{border-color:var(--red);background:#FEF2F2;color:var(--red);}
.tf-hint{font-size:11px;color:var(--muted);margin-top:10px;}

/* Essay */
.essay-area{
  width:100%;max-width:720px;padding:16px;font-size:14px;font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif;
  border:2px solid var(--border);border-radius:10px;resize:vertical;
  min-height:180px;line-height:1.7;outline:none;transition:border 200ms;color:var(--navy);
}
.essay-area:focus{border-color:var(--green);box-shadow:0 0 0 3px rgba(5,150,105,0.1);}
.essay-meta{display:flex;justify-content:space-between;align-items:center;margin-top:8px;font-size:11px;color:var(--muted);max-width:720px;}
.wc-over{color:var(--red);}

/* Fill blank */
.fill-input{
  padding:14px 18px;font-size:15px;font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif;
  border:2px solid var(--border);border-radius:10px;width:100%;max-width:480px;
  outline:none;transition:border 200ms;color:var(--navy);
}
.fill-input:focus{border-color:#8B5CF6;box-shadow:0 0 0 3px rgba(139,92,246,0.1);}
.fill-hint{font-size:11px;color:var(--muted);margin-top:8px;}

/* No-options warning */
.no-opts{
  background:#FFFBEB;border:1.5px solid #FCD34D;border-radius:10px;
  padding:14px 18px;font-size:13px;color:#92400E;max-width:500px;
}

/* ══════════════ NAV BAR (bottom) ═════════════════════════════════════ */
.nav-bar{
  height:60px;background:white;border-top:1px solid var(--border);
  display:flex;align-items:center;padding:0 28px;gap:12px;flex-shrink:0;
  position:relative;z-index:100;
}
.nbtn{
  display:flex;align-items:center;gap:7px;padding:10px 20px;
  font-size:13px;font-weight:700;font-family:inherit;border-radius:9px;
  border:1.5px solid var(--border);cursor:pointer;transition:all 150ms;
  background:white;color:var(--slate);
}
.nbtn:hover{border-color:var(--blue3);color:var(--blue);background:#EFF6FF;}
.nbtn:disabled{opacity:.4;cursor:not-allowed;}
.nbtn-key{
  font-family:'JetBrains Mono',monospace;font-size:10px;font-weight:700;
  background:#F1F5F9;border:1px solid #CBD5E1;padding:1px 5px;border-radius:4px;
  color:var(--muted);
}
.q-position{
  flex:1;text-align:center;font-size:12px;font-weight:600;color:var(--muted);
}
.q-position strong{color:var(--navy);font-size:14px;}
.nbtn-submit{
  display:flex;align-items:center;gap:8px;padding:11px 24px;
  font-size:13px;font-weight:700;font-family:inherit;
  background:var(--blue);color:white;border:none;border-radius:9px;
  cursor:pointer;transition:all 150ms;
}
.nbtn-submit:hover{background:var(--blue2);transform:translateY(-1px);box-shadow:0 4px 14px rgba(37,99,235,.35);}
.nbtn-submit:disabled{opacity:.5;cursor:not-allowed;transform:none;box-shadow:none;}
.answered-badge{
  font-size:12px;font-weight:700;padding:6px 12px;border-radius:20px;
  background:#DBEAFE;color:var(--blue2);flex-shrink:0;
}

/* ══════════════ THEORY SECTION DIVIDER ══════════════════════════════ */
.section-divider{
  display:flex;align-items:center;gap:12px;margin-bottom:24px;
  padding-bottom:16px;border-bottom:1px dashed var(--border);
}
.sd-badge{
  font-size:11px;font-weight:800;padding:4px 12px;border-radius:6px;
  text-transform:uppercase;letter-spacing:.06em;
}

/* ══════════════ KEYBOARD HINT ════════════════════════════════════════ */
.kb-hint{
  position:fixed;bottom:70px;right:16px;z-index:200;
  background:var(--navy2);color:#94A3B8;font-size:10px;
  border-radius:8px;padding:8px 12px;line-height:1.8;
  border:1px solid rgba(255,255,255,0.07);pointer-events:none;
  opacity:.8;
}
.kb{font-family:'JetBrains Mono',monospace;background:rgba(255,255,255,0.1);
  padding:1px 5px;border-radius:3px;color:white;font-size:10px;}

@media(max-width:800px){.q-panel{display:none;}.kb-hint{display:none;}}
/* ══════════════ SCIENTIFIC CALCULATOR ════════════════════════════════ */
.calc-toggle{display:flex;align-items:center;gap:6px;height:34px;padding:0 13px;margin-right:10px;background:var(--navy3);color:#fff;border:1px solid rgba(255,255,255,.12);border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit}
.calc-toggle:hover{background:var(--blue)}
.calc-panel{position:fixed;top:90px;right:24px;width:290px;background:var(--navy);border:1px solid var(--navy3);border-radius:14px;box-shadow:0 18px 50px rgba(0,0,0,.45);z-index:600;display:none;overflow:hidden;user-select:none}
.calc-panel.open{display:block}
.calc-head{display:flex;align-items:center;justify-content:space-between;padding:9px 13px;background:var(--navy2);cursor:move}
.calc-head span{font-size:12px;font-weight:800;color:#fff;letter-spacing:.03em}
.calc-close{background:none;border:none;color:var(--muted);font-size:18px;cursor:pointer;line-height:1}
.calc-close:hover{color:#fff}
.calc-screen{padding:11px 14px;text-align:right;background:var(--navy)}
.calc-mode{display:inline-block;float:left;font-size:9px;font-weight:700;color:var(--blue3);border:1px solid var(--navy3);border-radius:5px;padding:1px 6px}
.calc-expr{font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--muted);min-height:16px;word-break:break-all;line-height:1.4}
.calc-out{font-family:'JetBrains Mono',monospace;font-size:24px;font-weight:700;color:#fff;min-height:30px;word-break:break-all}
.calc-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:6px;padding:12px}
.ck{height:38px;border:none;border-radius:8px;background:var(--navy2);color:#E2E8F0;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;transition:background .12s}
.ck:hover{background:var(--navy3)}
.ck.fn{background:#172033;color:var(--blue3);font-size:12px}
.ck.op{background:var(--navy3);color:#fff}
.ck.eq{background:var(--blue);color:#fff;grid-column:span 2}
.ck.eq:hover{background:var(--blue2)}
.ck.clr{background:#7F1D1D;color:#FCA5A5}
@media(max-width:640px){.calc-panel{right:8px;left:8px;width:auto;top:64px}}
</style>
</head>
<body style="display:flex;flex-direction:column;">

{{-- ════ Organise questions into sections ════ --}}
@php
    $mcqTypes    = ['mcq','true_false','fill_blank'];
    $theoryTypes = ['essay','short_answer'];
    $mcqQs       = $questions->filter(fn($q) => in_array($q->type ?? 'mcq', $mcqTypes))->values();
    $theoryQs    = $questions->filter(fn($q) => in_array($q->type ?? 'mcq', $theoryTypes))->values();
    $hasMcq      = $mcqQs->count() > 0;
    $hasTheory   = $theoryQs->count() > 0;

    // Per-question mark weight from exam-level section config (overrides per-question marks
    // when the exam has a section config; otherwise falls back to per-question default).
    $objMarkEach    = $exam->section_objective_count > 0 && $exam->section_objective_marks > 0
        ? (float) $exam->section_objective_marks
        : null; // null = use per-question marks
    $theoryMarkEach = $exam->section_theory_count > 0 && $exam->section_theory_marks > 0
        ? (float) $exam->section_theory_marks
        : null;

    // Section totals for display
    $secATotal = $mcqQs->sum(fn($q)   => $objMarkEach    ?? ($q->marks ?? 1));
    $secBTotal = $theoryQs->sum(fn($q) => $theoryMarkEach ?? ($q->marks ?? 1));
    $totalMarks = $secATotal + $secBTotal;

    // Build unified index: each question gets a global index and a section
    $allQ = collect();
    foreach($mcqQs    as $q) $allQ->push(['q'=>$q,'section'=>'mcq','label'=>'Section A','mark'=>$objMarkEach ?? ($q->marks ?? 1)]);
    foreach($theoryQs as $q) $allQ->push(['q'=>$q,'section'=>'theory','label'=>'Section B','mark'=>$theoryMarkEach ?? ($q->marks ?? 1)]);
    $totalQ = $allQ->count();
@endphp

{{-- ════ TOP BAR ════ --}}
<div class="topbar">
    <div class="tb-brand">
        <div class="tb-logo">
            <svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>
        </div>
    </div>
    <div class="tb-title">
        <div class="tb-exam">{{ $exam->title }}</div>
        <div class="tb-meta">
            {{ $totalQ }} Questions &nbsp;·&nbsp;
            {{ $exam->duration_minutes }} min
            @if($hasMcq && $hasTheory) &nbsp;·&nbsp; 2 Sections @endif
        </div>
    </div>
    <div class="tb-right">
        <button type="button" class="calc-toggle" id="calcToggle" title="Scientific calculator">🧮 Calculator</button>
        <div class="student-info">
            <strong>{{ auth()->user()->name }}</strong>
            {{ auth()->user()->roleLabel() }}
        </div>
        <div class="timer-box" id="timerBox">
            <div class="timer-val" id="timerVal">{{ str_pad($exam->duration_minutes,2,'0',STR_PAD_LEFT) }}:00</div>
            <div class="timer-lbl">Time Left</div>
        </div>
    </div>
</div>

{{-- ════ SECTION TABS ════ --}}
@if($hasMcq && $hasTheory)
<div class="section-bar">
    @if($hasMcq)
    <button class="sec-tab active" id="stab-mcq" onclick="switchSection('mcq')">
        Section A — Objectives
        <span class="sec-count" id="sc-mcq">{{ $mcqQs->count() }} Q</span>
        <span style="font-size:10px;font-weight:600;color:#64748B;margin-left:4px">/ {{ $secATotal }} marks</span>
        <span class="sec-done" id="sd-mcq" style="display:none">✓ Done</span>
    </button>
    @endif
    @if($hasTheory)
    <button class="sec-tab {{ !$hasMcq ? 'active':'' }}" id="stab-theory" onclick="switchSection('theory')">
        Section B — Theory
        <span class="sec-count" id="sc-theory">{{ $theoryQs->count() }} Q</span>
        <span style="font-size:10px;font-weight:600;color:#64748B;margin-left:4px">/ {{ $secBTotal }} marks</span>
        <span class="sec-done" id="sd-theory" style="display:none">✓ Done</span>
    </button>
    @endif
</div>
@endif

<div class="main">

{{-- ════ QUESTION NUMBERS PANEL ════ --}}
<nav class="q-panel">
    <div class="qp-head">
        <div class="qp-title">Questions</div>
        <div class="qp-sub" id="navSub">0 / {{ $totalQ }} answered</div>
    </div>
    <div class="qp-grid" id="navGrid">
        @foreach($allQ as $idx => $item)
        <button type="button"
                class="qn {{ $idx === 0 ? 'active':'' }}"
                id="qn-{{ $idx }}"
                onclick="goTo({{ $idx }})"
                title="{{ $item['label'] }} · Q{{ $idx+1 }} · {{ $item['q']->typeLabel() }}">
            {{ $idx + 1 }}
        </button>
        @endforeach
    </div>
    <div class="qp-legend">
        <div class="leg"><div class="ld" style="background:#DBEAFE;border:1px solid #93C5FD"></div>Answered (MCQ)</div>
        <div class="leg"><div class="ld" style="background:#D1FAE5;border:1px solid #6EE7B7"></div>Answered (Essay)</div>
        <div class="leg"><div class="ld" style="background:var(--blue)"></div>Current question</div>
    </div>
</nav>

{{-- ════ EXAM AREA ════ --}}
<div class="exam-area">
    <form method="POST"
          action="{{ $existing ? route('cbt.session.submit', $existing) : '#' }}"
          id="examForm">
    @csrf

    <div class="q-viewport">
    @foreach($allQ as $idx => $item)
    @php
        $q          = $item['q'];
        $sec        = $item['section'];
        $savedMcq   = $existing?->answers[$q->id] ?? null;
        $savedEssay = $existing?->essay_answers[$q->id] ?? null;
        [$tbg,$tclr]= $q->typeBadgeColor();
        $opts       = $q->optionsArray();
        $hasOpts    = count($opts) >= 2;
        $isEssay    = in_array($q->type ?? 'mcq', ['essay','short_answer']);
    @endphp

    <div class="q-slide {{ $idx === 0 ? 'visible':'' }}"
         id="slide-{{ $idx }}" data-idx="{{ $idx }}" data-section="{{ $sec }}" data-qid="{{ $q->id }}">

        {{-- Section label on first question of each section --}}
        @if($idx === 0 || ($idx > 0 && $allQ[$idx-1]['section'] !== $sec))
        <div class="section-divider">
            <span class="sd-badge"
                  style="background:{{ $sec==='mcq' ? '#DBEAFE':'#D1FAE5' }};color:{{ $sec==='mcq' ? '#1E40AF':'#065F46' }}">
                {{ $item['label'] }}
            </span>
            <span style="font-size:12px;color:var(--muted)">
                @if($sec==='mcq') Objective Questions — Select one answer per question
                @else Theory Questions — Write detailed answers @endif
            </span>
        </div>
        @endif

        <div class="qs-header">
            <span class="qs-num">Question {{ $idx + 1 }} of {{ $totalQ }}</span>
            <span class="type-pill" style="background:{{ $tbg }};color:{{ $tclr }}">{{ $q->typeLabel() }}</span>
            <span class="marks-pill">{{ $item['mark'] }} mark{{ $item['mark'] != 1 ? 's':'' }}</span>
            <button type="button" class="flag-btn" id="flag-{{ $idx }}" onclick="toggleFlag({{ $idx }})">🚩 Flag</button>
        </div>

        <div class="qs-text">{!! $q->question_html ?: nl2br(e($q->question_text)) !!}</div>
        @if($q->image_path)
        <div style="margin-bottom:22px">
            <img src="{{ Storage::url($q->image_path) }}" alt="Question diagram"
                 style="max-width:100%;max-height:340px;border-radius:10px;border:1px solid #E2E8F0;display:block">
        </div>
        @endif

        {{-- MCQ --}}
        @if($q->isMcq())
            @if($hasOpts)
            <div class="options" id="opts-{{ $idx }}">
                @foreach($opts as $letter => $text)
                <label class="opt {{ $savedMcq === $letter ? 'chosen':'' }}"
                       id="opt-{{ $idx }}-{{ $letter }}">
                    <input type="radio" name="answers[{{ $q->id }}]" value="{{ $letter }}"
                           {{ $savedMcq === $letter ? 'checked':'' }}
                           onchange="onMcq({{ $idx }}, '{{ $letter }}')">
                    <div class="opt-key">{{ strtoupper($letter) }}</div>
                    <div class="opt-text">{!! nl2br(e($text)) !!}</div>
                </label>
                @endforeach
            </div>
            <p style="font-size:11px;color:var(--muted);margin-top:12px;">
                💡 Press <kbd style="font-family:monospace;background:#F1F5F9;padding:1px 5px;border-radius:3px;border:1px solid #CBD5E1">A</kbd>
                <kbd style="font-family:monospace;background:#F1F5F9;padding:1px 5px;border-radius:3px;border:1px solid #CBD5E1">B</kbd>
                <kbd style="font-family:monospace;background:#F1F5F9;padding:1px 5px;border-radius:3px;border:1px solid #CBD5E1">C</kbd>
                <kbd style="font-family:monospace;background:#F1F5F9;padding:1px 5px;border-radius:3px;border:1px solid #CBD5E1">D</kbd>
                to select · <kbd style="font-family:monospace;background:#F1F5F9;padding:1px 5px;border-radius:3px;border:1px solid #CBD5E1">N</kbd> Next · <kbd style="font-family:monospace;background:#F1F5F9;padding:1px 5px;border-radius:3px;border:1px solid #CBD5E1">P</kbd> Previous
            </p>
            @else
            <div class="no-opts">
                ⚠️ This question's options could not be loaded. Please contact the invigilator.
            </div>
            @endif

        {{-- True / False --}}
        @elseif($q->isTrueFalse())
        <div class="tf-row">
            <button type="button" class="tf-btn {{ $savedMcq==='a'?'t-sel':'' }}"
                    id="tf-{{ $idx }}-a" onclick="onTf({{ $idx }},{{ $q->id }},'a')">
                <svg viewBox="0 0 24 24" width="20" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                True
                <kbd style="font-family:monospace;font-size:10px;background:rgba(255,255,255,.2);padding:1px 5px;border-radius:3px;border:1px solid rgba(255,255,255,.3)">A</kbd>
            </button>
            <button type="button" class="tf-btn {{ $savedMcq==='b'?'f-sel':'' }}"
                    id="tf-{{ $idx }}-b" onclick="onTf({{ $idx }},{{ $q->id }},'b')">
                <svg viewBox="0 0 24 24" width="20" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/></svg>
                False
                <kbd style="font-family:monospace;font-size:10px;background:rgba(255,255,255,.2);padding:1px 5px;border-radius:3px;border:1px solid rgba(255,255,255,.3)">B</kbd>
            </button>
        </div>
        <input type="hidden" name="answers[{{ $q->id }}]" id="tfhid-{{ $idx }}" value="{{ $savedMcq ?? '' }}">
        <div class="tf-hint">💡 Press <kbd style="font-family:monospace;background:#F1F5F9;padding:1px 5px;border-radius:3px;border:1px solid #CBD5E1">A</kbd> = True &nbsp;|&nbsp; <kbd style="font-family:monospace;background:#F1F5F9;padding:1px 5px;border-radius:3px;border:1px solid #CBD5E1">B</kbd> = False</div>

        {{-- Fill Blank --}}
        @elseif($q->isFillBlank())
        <input type="text" class="fill-input" name="answers[{{ $q->id }}]"
               placeholder="Type your answer here…" value="{{ $savedMcq }}"
               oninput="onFill({{ $idx }}, this)">
        <div class="fill-hint">⬆ Type the word or phrase that completes the statement.</div>

        {{-- Essay --}}
        @elseif($q->isEssay())
        @if($q->word_limit)
        <div style="font-size:12px;color:var(--muted);margin-bottom:10px;">
            📝 Word limit: <strong>{{ $q->word_limit }} words</strong>
        </div>
        @endif
        <textarea class="essay-area"
                  name="essay_answers[{{ $q->id }}]"
                  placeholder="Write your answer here. Be clear and structured. Cover all parts of the question."
                  oninput="onEssay({{ $idx }}, this, {{ $q->word_limit ?? 0 }})"
                  {{ $q->word_limit ? "data-limit={$q->word_limit}":'' }}>{{ $savedEssay }}</textarea>
        <div class="essay-meta">
            <span>💡 Write clear paragraphs and address all sub-questions.</span>
            <span id="wc-{{ $idx }}">0{{ $q->word_limit ? ' / '.$q->word_limit.' words':' words' }}</span>
        </div>

        {{-- Short Answer --}}
        @elseif($q->isShortAnswer())
        <textarea class="essay-area" style="min-height:120px;"
                  name="essay_answers[{{ $q->id }}]"
                  placeholder="Write a concise answer…"
                  oninput="onEssay({{ $idx }}, this, 0)">{{ $savedEssay }}</textarea>
        <div class="essay-meta">
            <span>Keep your answer brief and precise.</span>
            <span id="wc-{{ $idx }}">0 words</span>
        </div>
        @endif

    </div>
    @endforeach
    </div>{{-- q-viewport --}}

    {{-- ════ BOTTOM NAV BAR ════ --}}
    <div class="nav-bar">
        <button type="button" class="nbtn" id="btnPrev" onclick="goTo(currentIdx-1)">
            <svg viewBox="0 0 24 24" width="16" fill="currentColor"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
            Previous
            <span class="nbtn-key">P</span>
        </button>

        <div class="q-position">
            <strong id="posLabel">1</strong> / {{ $totalQ }}
            <span style="margin-left:12px;font-size:11px;color:var(--muted)" id="sectionLabel">
                @if($hasMcq && $hasTheory) Section A @endif
            </span>
        </div>

        <div class="answered-badge" id="answeredBadge">0 / {{ $totalQ }} answered</div>

        <button type="button" class="nbtn" id="btnNext" onclick="goTo(currentIdx+1)">
            Next
            <span class="nbtn-key">N</span>
            <svg viewBox="0 0 24 24" width="16" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        </button>

        @if($existing)
        <button type="button" class="nbtn-submit" id="btnSubmit" onclick="confirmSubmit()">
            <svg viewBox="0 0 24 24" width="16" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
            Submit Exam
        </button>
        @else
        <div style="font-size:11px;color:var(--muted);font-style:italic;">👁 Preview — no submit</div>
        @endif
    </div>

    </form>
</div>{{-- exam-area --}}
</div>{{-- main --}}

<div class="kb-hint">
    <span class="kb">A B C D</span> — Select MCQ option<br>
    <span class="kb">N</span> — Next question<br>
    <span class="kb">P</span> — Previous question
</div>

<div class="calc-panel" id="calcPanel">
    <div class="calc-head" id="calcHead">
        <span>Scientific Calculator</span>
        <button type="button" class="calc-close" id="calcClose">×</button>
    </div>
    <div class="calc-screen">
        <span class="calc-mode" id="calcMode">DEG</span>
        <div class="calc-expr" id="calcExpr"></div>
        <div class="calc-out" id="calcOut">0</div>
    </div>
    <div class="calc-grid">
        <button type="button" class="ck fn" data-act="deg">DEG</button>
        <button type="button" class="ck fn" data-ins="(">(</button>
        <button type="button" class="ck fn" data-ins=")">)</button>
        <button type="button" class="ck op" data-act="back">⌫</button>
        <button type="button" class="ck clr" data-act="clear">C</button>

        <button type="button" class="ck fn" data-ins="sin(">sin</button>
        <button type="button" class="ck fn" data-ins="cos(">cos</button>
        <button type="button" class="ck fn" data-ins="tan(">tan</button>
        <button type="button" class="ck fn" data-ins="ln(">ln</button>
        <button type="button" class="ck fn" data-ins="log(">log</button>

        <button type="button" class="ck fn" data-ins="√(">√</button>
        <button type="button" class="ck fn" data-ins="^2">x²</button>
        <button type="button" class="ck fn" data-ins="^">xʸ</button>
        <button type="button" class="ck fn" data-ins="π">π</button>
        <button type="button" class="ck fn" data-ins="!">n!</button>

        <button type="button" class="ck" data-ins="7">7</button>
        <button type="button" class="ck" data-ins="8">8</button>
        <button type="button" class="ck" data-ins="9">9</button>
        <button type="button" class="ck op" data-ins="÷">÷</button>
        <button type="button" class="ck op" data-ins="%">%</button>

        <button type="button" class="ck" data-ins="4">4</button>
        <button type="button" class="ck" data-ins="5">5</button>
        <button type="button" class="ck" data-ins="6">6</button>
        <button type="button" class="ck op" data-ins="×">×</button>
        <button type="button" class="ck fn" data-ins="e">e</button>

        <button type="button" class="ck" data-ins="1">1</button>
        <button type="button" class="ck" data-ins="2">2</button>
        <button type="button" class="ck" data-ins="3">3</button>
        <button type="button" class="ck op" data-ins="-">−</button>
        <button type="button" class="ck op" data-act="ans">Ans</button>

        <button type="button" class="ck" data-ins="0">0</button>
        <button type="button" class="ck" data-ins=".">.</button>
        <button type="button" class="ck op" data-ins="+">+</button>
        <button type="button" class="ck eq" data-act="eq">=</button>
    </div>
</div>

<script>
// ── State ────────────────────────────────────────────────────────────
const TOTAL       = {{ $totalQ }};
const TOTAL_SECS  = {{ ($exam->duration_minutes ?? 60) * 60 }};
const HAS_MCQ     = {{ $hasMcq ? 'true':'false' }};
const HAS_THEORY  = {{ $hasTheory ? 'true':'false' }};
const slides      = document.querySelectorAll('.q-slide');
const qnBtns      = document.querySelectorAll('.qn');

let currentIdx = 0;
let remaining  = TOTAL_SECS;
const answered = {};   // idx → 'mcq' | 'essay'
const flagged  = {};   // idx → bool

// ── Timer ─────────────────────────────────────────────────────────────
const timerVal = document.getElementById('timerVal');
const timerBox = document.getElementById('timerBox');
const tick = setInterval(() => {
    remaining--;
    if (remaining <= 0) { clearInterval(tick); document.getElementById('examForm').submit(); return; }
    const m = Math.floor(remaining / 60);
    const s = remaining % 60;
    timerVal.textContent = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
    if      (remaining <= 300) timerBox.className = 'timer-box danger';
    else if (remaining <= 600) timerBox.className = 'timer-box warn';
}, 1000);

// ── Navigate to question index ────────────────────────────────────────
function goTo(idx) {
    if (idx < 0 || idx >= TOTAL) return;

    // Hide current
    slides[currentIdx]?.classList.remove('visible');
    document.getElementById('qn-' + currentIdx)?.classList.remove('active');

    currentIdx = idx;

    // Show new
    slides[currentIdx].classList.add('visible');
    const btn = document.getElementById('qn-' + currentIdx);
    if (btn) { btn.classList.add('active'); btn.scrollIntoView({block:'nearest',behavior:'smooth'}); }

    // Update prev/next buttons
    document.getElementById('btnPrev').disabled = (currentIdx === 0);
    document.getElementById('btnNext').disabled = (currentIdx === TOTAL - 1);
    document.getElementById('posLabel').textContent = currentIdx + 1;

    // Section label
    const sec = slides[currentIdx].dataset.section;
    const sl  = document.getElementById('sectionLabel');
    if (sl) sl.textContent = (sec === 'mcq') ? 'Section A — Objectives' : 'Section B — Theory';

    // Update section tabs
    if (HAS_MCQ && HAS_THEORY) {
        document.getElementById('stab-mcq')?.classList.toggle('active', sec === 'mcq');
        document.getElementById('stab-theory')?.classList.toggle('active', sec === 'theory');
    }

    // Focus textarea if essay
    if (sec === 'theory') {
        setTimeout(() => {
            const ta = slides[currentIdx].querySelector('textarea');
            if (ta) ta.focus();
        }, 50);
    }
}

// ── Section switch via tab ────────────────────────────────────────────
function switchSection(sec) {
    const first = Array.from(slides).findIndex(s => s.dataset.section === sec);
    if (first >= 0) goTo(first);
}

// ── Mark answered ─────────────────────────────────────────────────────
function markAnswered(idx, type) {
    answered[idx] = type;
    const btn = document.getElementById('qn-' + idx);
    if (btn) {
        btn.classList.remove('answered','essay-ans');
        btn.classList.add(type === 'essay' ? 'essay-ans' : 'answered');
        if (idx === currentIdx) btn.classList.add('active');
    }
    updateCounts();
}

function updateCounts() {
    const n    = Object.keys(answered).length;
    const left = TOTAL - n;
    document.getElementById('navSub').textContent       = n + ' / ' + TOTAL + ' answered';
    document.getElementById('answeredBadge').textContent = n + ' / ' + TOTAL + ' answered';

    // Per-section done indicators
    if (HAS_MCQ && HAS_THEORY) {
        const mcqTotal  = {{ $mcqQs->count() }};
        const thTotal   = {{ $theoryQs->count() }};
        let mcqDone = 0, thDone = 0;
        Array.from(slides).forEach((s,i) => {
            if (answered[i]) {
                if (s.dataset.section === 'mcq')    mcqDone++;
                else                                thDone++;
            }
        });
        const sdMcq = document.getElementById('sd-mcq');
        const sdTh  = document.getElementById('sd-theory');
        if (sdMcq)  sdMcq.style.display  = (mcqDone === mcqTotal) ? '' : 'none';
        if (sdTh)   sdTh.style.display   = (thDone  === thTotal)  ? '' : 'none';
    }
}

// ── MCQ selection ─────────────────────────────────────────────────────
function onMcq(idx, letter) {
    const slide = slides[idx];
    slide.querySelectorAll('.opt').forEach(o => o.classList.remove('chosen'));
    const chosen = slide.querySelector('#opt-' + idx + '-' + letter);
    if (chosen) chosen.classList.add('chosen');
    markAnswered(idx, 'mcq');
}

// ── True/False ────────────────────────────────────────────────────────
function onTf(idx, qid, val) {
    document.getElementById('tfhid-' + idx).value = val;
    ['a','b'].forEach(v => {
        const b = document.getElementById('tf-' + idx + '-' + v);
        if (b) b.className = 'tf-btn' + (v === val ? (val==='a'?' t-sel':' f-sel') : '');
    });
    markAnswered(idx, 'mcq');
}

// ── Fill blank ────────────────────────────────────────────────────────
function onFill(idx, el) { if (el.value.trim()) markAnswered(idx, 'mcq'); }

// ── Essay ─────────────────────────────────────────────────────────────
function onEssay(idx, el, limit) {
    const words = el.value.trim().split(/\s+/).filter(w=>w).length;
    const el2   = document.getElementById('wc-' + idx);
    if (el2) {
        el2.textContent = limit ? words + ' / ' + limit + ' words' : words + ' words';
        el2.className   = (limit && words > limit) ? 'wc-over' : '';
    }
    if (el.value.trim()) markAnswered(idx, 'essay');
}

// ── Flag ──────────────────────────────────────────────────────────────
function toggleFlag(idx) {
    flagged[idx] = !flagged[idx];
    const btn = document.getElementById('flag-' + idx);
    const dot = document.getElementById('qn-' + idx);
    if (btn) btn.className = 'flag-btn' + (flagged[idx] ? ' flagged':'');
    if (dot) dot.classList.toggle('flagged', flagged[idx]);
}

// ── Keyboard shortcuts ────────────────────────────────────────────────
document.addEventListener('keydown', e => {
    // Ignore when typing in a textarea or input
    const tag = document.activeElement.tagName;
    if (tag === 'TEXTAREA' || tag === 'INPUT') return;

    const slide = slides[currentIdx];
    const sec   = slide?.dataset.section;
    const k     = e.key.toLowerCase();

    if (k === 'n') { e.preventDefault(); goTo(currentIdx + 1); return; }
    if (k === 'p') { e.preventDefault(); goTo(currentIdx - 1); return; }

    // A/B/C/D for MCQ / TF only (not in essay section)
    if (['a','b','c','d'].includes(k) && sec === 'mcq') {
        e.preventDefault();
        const qid = slide.dataset.qid;
        // True/False
        const tfhid = document.getElementById('tfhid-' + currentIdx);
        if (tfhid !== null && (k === 'a' || k === 'b')) {
            onTf(currentIdx, qid, k);
            return;
        }
        // MCQ
        const opt = slide.querySelector('#opt-' + currentIdx + '-' + k);
        if (opt) {
            const radio = opt.querySelector('input[type="radio"]');
            if (radio) { radio.checked = true; onMcq(currentIdx, k); }
        }
    }
});

// ── Submit ────────────────────────────────────────────────────────────
function confirmSubmit() {
    const n    = Object.keys(answered).length;
    const left = TOTAL - n;
    const msg  = left > 0
        ? `⚠️ You still have ${left} unanswered question${left>1?'s':''}.\n\nSubmit anyway? This cannot be undone.`
        : '✅ All questions answered!\n\nSubmit your exam now?';
    if (!confirm(msg)) return;
    const b = document.getElementById('btnSubmit');
    b.disabled = true;
    b.innerHTML = '⏳ Submitting…';
    document.getElementById('examForm').submit();
}

// ── Init: restore saved answers ───────────────────────────────────────
document.querySelectorAll('input[type="radio"]:checked').forEach(el => {
    const slide = el.closest('.q-slide');
    if (slide) {
        const idx    = parseInt(slide.dataset.idx);
        const letter = el.value;
        slide.querySelectorAll('.opt').forEach(o => o.classList.remove('chosen'));
        const cho = slide.querySelector('#opt-' + idx + '-' + letter);
        if (cho) cho.classList.add('chosen');
        markAnswered(idx, 'mcq');
    }
});
document.querySelectorAll('input[id^="tfhid-"]').forEach(el => {
    if (el.value) {
        const idx = el.id.replace('tfhid-','');
        const qid = slides[idx]?.dataset.qid;
        onTf(parseInt(idx), qid, el.value);
    }
});
document.querySelectorAll('textarea').forEach(el => {
    if (el.value.trim()) {
        const slide = el.closest('.q-slide');
        if (slide) markAnswered(parseInt(slide.dataset.idx), 'essay');
    }
});
document.querySelectorAll('.fill-input').forEach(el => {
    if (el.value.trim()) {
        const slide = el.closest('.q-slide');
        if (slide) markAnswered(parseInt(slide.dataset.idx), 'mcq');
    }
});

// Init nav state
goTo(0);

// ══ ANTI-MALPRACTICE: auto-submit on tab change / minimize / close ══
let autoSubmitTriggered = false;
let warningShown = false;

function triggerAutoSubmit(reason) {
    if (autoSubmitTriggered) return;
    @if($existing)
    autoSubmitTriggered = true;
    // Show brief overlay
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(220,38,38,0.95);z-index:9999;display:flex;flex-direction:column;align-items:center;justify-content:center;color:white;font-family:inherit';
    overlay.innerHTML = '<div style="font-size:48px;margin-bottom:16px">⚠️</div><div style="font-size:22px;font-weight:800;margin-bottom:8px">Exam Auto-Submitted</div><div style="font-size:14px;opacity:.85">' + reason + '</div>';
    document.body.appendChild(overlay);
    setTimeout(() => document.getElementById('examForm').submit(), 1500);
    @endif
}

// Tab visibility change (tab switch, minimize)
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        if (!warningShown) {
            // Give 1 warning on first hide
            warningShown = true;
        } else {
            triggerAutoSubmit('Tab was switched or window was minimised.');
        }
    }
});

// Window blur (switched to another app/window)
let blurCount = 0;
window.addEventListener('blur', () => {
    blurCount++;
    if (blurCount >= 2) {
        triggerAutoSubmit('Window focus was lost.');
    }
});

// Beforeunload — ask confirm + auto-submit
window.addEventListener('beforeunload', (e) => {
    @if($existing)
    if (!autoSubmitTriggered) {
        triggerAutoSubmit('Browser tab or window was closed.');
        e.preventDefault();
        e.returnValue = 'Your exam will be auto-submitted if you leave this page.';
        return e.returnValue;
    }
    @endif
});

// Right-click disable
document.addEventListener('contextmenu', e => e.preventDefault());

// Copy/paste disable
document.addEventListener('copy',  e => e.preventDefault());
document.addEventListener('paste', e => e.preventDefault());

// Screenshot / print screen warning (best-effort)
document.addEventListener('keydown', e => {
    if (e.key === 'PrintScreen') {
        e.preventDefault();
        alert('Screenshots are not permitted during this exam.');
    }
    // Block Ctrl+P (print), Ctrl+S, Ctrl+U (view source)
    if (e.ctrlKey && ['p','s','u'].includes(e.key.toLowerCase())) {
        e.preventDefault();
    }
});
// ── Scientific Calculator (in-page only; never opens a window/tab) ─────
(function(){
    const panel=document.getElementById('calcPanel');
    const toggle=document.getElementById('calcToggle');
    if(!panel||!toggle) return;
    const closeBtn=document.getElementById('calcClose');
    const exprEl=document.getElementById('calcExpr');
    const outEl=document.getElementById('calcOut');
    const modeEl=document.getElementById('calcMode');
    const head=document.getElementById('calcHead');
    let expr='', ans=0, isDeg=true, errored=false;

    toggle.addEventListener('click',()=>panel.classList.toggle('open'));
    closeBtn.addEventListener('click',()=>panel.classList.remove('open'));

    function render(){ exprEl.textContent=expr; outEl.textContent = expr===''? '0' : expr; }

    function evaluate(){
        if(expr.trim()===''){return;}
        let js=expr
            .replace(/×/g,'*').replace(/÷/g,'/')
            .replace(/π/g,'(PI)').replace(/e/g,'(E)')
            .replace(/√\(/g,'sqrt(')
            .replace(/\^2/g,'**2').replace(/\^/g,'**')
            .replace(/(\d+(?:\.\d+)?)!/g,'fact($1)')
            .replace(/%/g,'/100');
        // Whitelist: after removing known tokens, only math chars may remain.
        const stripped=js.replace(/sin|cos|tan|ln|log|sqrt|fact|PI|E|\*\*/g,'');
        if(/[^0-9+\-*/().,\s]/.test(stripped)){ outEl.textContent='Error'; errored=true; return; }
        try{
            const D=isDeg?Math.PI/180:1;
            const scope={
                sin:x=>Math.sin(x*D), cos:x=>Math.cos(x*D), tan:x=>Math.tan(x*D),
                ln:Math.log, log:Math.log10, sqrt:Math.sqrt, PI:Math.PI, E:Math.E,
                fact:n=>{n=Math.round(n); if(n<0||n>170) return NaN; let r=1; for(let i=2;i<=n;i++) r*=i; return r;}
            };
            const fn=new Function(...Object.keys(scope),'return ('+js+');');
            let r=fn(...Object.values(scope));
            if(!isFinite(r)){ outEl.textContent='Error'; errored=true; return; }
            r=Math.round((r+Number.EPSILON)*1e10)/1e10;
            ans=r;
            const s=String(r);
            expr = s.indexOf('e')===-1 ? s : '';   // avoid exponential strings re-entering input
            exprEl.textContent=expr;
            outEl.textContent=String(r);
            errored=false;
        }catch(err){ outEl.textContent='Error'; errored=true; }
    }

    panel.querySelectorAll('.ck').forEach(btn=>{
        btn.addEventListener('click',()=>{
            const ins=btn.getAttribute('data-ins');
            const act=btn.getAttribute('data-act');
            if(ins!==null){ if(errored){expr='';errored=false;} expr+=ins; render(); return; }
            if(act==='clear'){ expr=''; errored=false; render(); }
            else if(act==='back'){ if(errored){expr='';errored=false;} else {expr=expr.slice(0,-1);} render(); }
            else if(act==='eq'){ evaluate(); }
            else if(act==='ans'){ if(errored){expr='';errored=false;} expr+=String(ans); render(); }
            else if(act==='deg'){ isDeg=!isDeg; modeEl.textContent=isDeg?'DEG':'RAD'; btn.textContent=isDeg?'DEG':'RAD'; }
        });
    });

    // Drag by header — stays within the page (no focus loss).
    let drag=false,ox=0,oy=0;
    head.addEventListener('mousedown',e=>{ drag=true; const r=panel.getBoundingClientRect(); ox=e.clientX-r.left; oy=e.clientY-r.top; panel.style.right='auto'; });
    document.addEventListener('mousemove',e=>{ if(!drag) return; panel.style.left=(e.clientX-ox)+'px'; panel.style.top=(e.clientY-oy)+'px'; });
    document.addEventListener('mouseup',()=>{ drag=false; });
})();
</script>

<script>
/* ── KaTeX auto-render: runs after KaTeX + mhchem are loaded ──────
   Renders all LaTeX delimiters found in question text, options,
   explanations, and model answers throughout the exam.
   Supports:
     Inline math:   \( ... \)  or  $ ... $
     Block math:    \[ ... \]  or  $$ ... $$
     Chemistry:     \ce{ ... }  (via mhchem extension)
──────────────────────────────────────────────────────────────────── */
function renderAllMath() {
    if (typeof renderMathInElement === 'undefined') return;
    var opts = {
        delimiters: [
            { left: '$$',   right: '$$',   display: true  },
            { left: '\\[',  right: '\\]',  display: true  },
            { left: '\\(',  right: '\\)',  display: false },
            { left: '$',    right: '$',    display: false },
        ],
        throwOnError: false,
        trust: false,
        macros: {
            '\\celsius':  '^\\circ\\text{C}',
            '\\degC':     '^\\circ\\text{C}',
            '\\half':     '\\frac{1}{2}',
            '\\sq':       '^2',
            '\\cube':     '^3',
        }
    };
    // Render in all question text + option containers
    document.querySelectorAll('.qs-text, .opt-text, .essay-hint, .fill-hint').forEach(function(el) {
        renderMathInElement(el, opts);
    });
}
</script>
</body>
</html>
