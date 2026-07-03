@extends('layouts.app')
@section('title', 'Lesson Planner')

@section('content')
<div class="page-content">
    <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
        <div>
            <h1 style="font-size:22px;font-weight:800;color:var(--midnight)">Lesson Planner</h1>
            <p style="font-size:13px;color:var(--slate-light);margin-top:2px">AI-assisted NERDC &amp; British curriculum lesson plans</p>
        </div>
        <a href="{{ route('lesson-planner.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;margin-right:6px"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            New Lesson Plan
        </a>
    </div>

    @if(session('success'))
    <div class="alert-s" style="margin-bottom:16px">{{ session('success') }}</div>
    @endif

    {{-- Filters --}}
    <form method="GET" style="background:white;border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:20px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
        <div class="fg" style="flex:1;min-width:160px">
            <label class="fl">Subject</label>
            <select name="subject_id" class="fc">
                <option value="">All Subjects</option>
                @foreach($subjects as $s)
                <option value="{{ $s->id }}" {{ request('subject_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="fg" style="flex:1;min-width:140px">
            <label class="fl">Curriculum</label>
            <select name="curriculum_type" class="fc">
                <option value="">All</option>
                <option value="nerdc" {{ request('curriculum_type') === 'nerdc' ? 'selected' : '' }}>NERDC (Nigerian)</option>
                <option value="british" {{ request('curriculum_type') === 'british' ? 'selected' : '' }}>British</option>
            </select>
        </div>
        <div class="fg" style="flex:2;min-width:180px">
            <label class="fl">Search Topic</label>
            <input type="text" name="search" class="fc" value="{{ request('search') }}" placeholder="Search topic...">
        </div>
        <button type="submit" class="btn btn-primary" style="height:38px">Filter</button>
        @if(request()->anyFilled(['subject_id','curriculum_type','search']))
        <a href="{{ route('lesson-planner.index') }}" class="btn btn-secondary" style="height:38px">Clear</a>
        @endif
    </form>

    @if($plans->isEmpty())
    <div style="text-align:center;padding:60px 20px;background:white;border-radius:var(--radius);border:1px solid var(--border)">
        <svg viewBox="0 0 24 24" fill="currentColor" style="width:48px;height:48px;color:var(--slate-light);margin:0 auto 12px;display:block"><path d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1z"/></svg>
        <p style="color:var(--slate-light);font-size:14px">No lesson plans yet. Create your first one!</p>
        <a href="{{ route('lesson-planner.create') }}" class="btn btn-primary" style="margin-top:16px;display:inline-flex">Create Lesson Plan</a>
    </div>
    @else
    <div style="background:white;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:var(--bg);border-bottom:1px solid var(--border)">
                    <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em">Topic</th>
                    <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em">Subject</th>
                    <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em">Class</th>
                    <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em">Curriculum</th>
                    <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em">Status</th>
                    <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em">Date</th>
                    <th style="padding:10px 16px;text-align:right;font-size:12px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($plans as $plan)
                <tr style="border-bottom:1px solid var(--border)" onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='white'">
                    <td style="padding:12px 16px">
                        <a href="{{ route('lesson-planner.show', $plan) }}" style="font-weight:600;color:var(--midnight);text-decoration:none;font-size:14px">{{ $plan->topic }}</a>
                        @if($plan->subtopic)
                        <div style="font-size:12px;color:var(--slate-light);margin-top:2px">{{ $plan->subtopic }}</div>
                        @endif
                        @if($plan->ai_generated)
                        <span style="font-size:10px;background:#EDE9FE;color:#6D28D9;padding:2px 6px;border-radius:20px;margin-top:4px;display:inline-block">✨ AI</span>
                        @endif
                    </td>
                    <td style="padding:12px 16px;font-size:13px;color:var(--slate)">{{ $plan->subject->name ?? '—' }}</td>
                    <td style="padding:12px 16px;font-size:13px;color:var(--slate)">{{ $plan->classLevel->name ?? '—' }}{{ $plan->classArm ? ' ' . $plan->classArm->name : '' }}</td>
                    <td style="padding:12px 16px">
                        @if($plan->isNerdc())
                        <span style="font-size:11px;background:#FEF9EC;color:#92400E;padding:3px 8px;border-radius:20px;font-weight:600">NERDC</span>
                        @else
                        <span style="font-size:11px;background:#EFF6FF;color:#1E40AF;padding:3px 8px;border-radius:20px;font-weight:600">British</span>
                        @endif
                    </td>
                    <td style="padding:12px 16px">
                        @if($plan->isPublished())
                        <span class="badge-success" style="font-size:11px">Published</span>
                        @else
                        <span style="font-size:11px;background:#F1F5F9;color:var(--slate);padding:3px 8px;border-radius:20px">Draft</span>
                        @endif
                    </td>
                    <td style="padding:12px 16px;font-size:13px;color:var(--slate-light)">{{ $plan->plan_date ? $plan->plan_date->format('d M Y') : ($plan->week_number ? 'Wk '.$plan->week_number : '—') }}</td>
                    <td style="padding:12px 16px;text-align:right">
                        <div style="display:flex;gap:6px;justify-content:flex-end">
                            <a href="{{ route('lesson-planner.show', $plan) }}" class="btn btn-secondary" style="padding:4px 10px;font-size:12px">View</a>
                            <a href="{{ route('lesson-planner.edit', $plan) }}" class="btn btn-secondary" style="padding:4px 10px;font-size:12px">Edit</a>
                            <a href="{{ route('lesson-planner.print', $plan) }}" target="_blank" class="btn btn-secondary" style="padding:4px 10px;font-size:12px">Print</a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px">{{ $plans->links() }}</div>
    @endif
</div>
@endsection
