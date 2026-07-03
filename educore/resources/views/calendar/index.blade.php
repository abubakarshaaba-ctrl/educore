@extends('layouts.app')
@section('title','Academic Calendar')
@section('page-title','Academic Calendar')
@push('styles')
<style>
.page-grid{display:grid;grid-template-columns:1fr 340px;gap:16px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:16px}
.event-list{padding:0}
.event-item{display:flex;align-items:flex-start;gap:12px;padding:12px 18px;border-bottom:1px solid var(--border);transition:background 150ms}
.event-item:last-child{border-bottom:none}
.event-item:hover{background:#F8FAFC}
.event-dot{width:12px;height:12px;border-radius:50%;flex-shrink:0;margin-top:3px}
.event-title{font-size:13px;font-weight:600;color:var(--midnight)}
.event-date{font-size:11px;color:var(--slate-light);margin-top:2px}
.event-type{font-size:10px;font-weight:600;padding:2px 7px;border-radius:20px;margin-top:3px;display:inline-block}
.type-holiday{background:#FEF2F2;color:var(--crimson)}
.type-exam{background:#EFF6FF;color:var(--indigo)}
.type-pta{background:#F5F3FF;color:#7C3AED}
.type-event{background:#ECFDF5;color:var(--emerald)}
.type-resumption{background:#FFFBEB;color:var(--amber)}
.type-closing{background:#FEF2F2;color:var(--crimson)}
.type-other{background:#F1F5F9;color:var(--slate)}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%;transition:border 200ms}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms;width:100%;justify-content:center}
.btn-p{background:var(--indigo);color:white}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
@media(max-width:768px){.page-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
<div class="page-grid">
  <div class="card">
    <div class="ch">Calendar Events — {{ optional($current)->name ?? 'Current Session' }}</div>
    <div class="event-list">
      @forelse($events as $event)
      <div class="event-item">
        <div class="event-dot" style="background:{{ $event->color }}"></div>
        <div style="flex:1">
          <div class="event-title">{{ $event->title }}</div>
          <div class="event-date">
            {{ \Carbon\Carbon::parse($event->start_date)->format('d M Y') }}
            @if($event->end_date && $event->end_date !== $event->start_date)
              → {{ \Carbon\Carbon::parse($event->end_date)->format('d M Y') }}
            @endif
          </div>
          <span class="event-type type-{{ $event->type }}">{{ ucfirst($event->type) }}</span>
        </div>
        @if(auth()->user()->canManage('calendar'))
        <form method="POST" action="{{ route('calendar.destroy',$event) }}">
          @csrf @method('DELETE')
          <button type="submit" style="background:none;border:none;color:var(--slate-light);cursor:pointer;font-size:16px" onclick="return confirm('Delete this event?')">×</button>
        </form>
        @endif
      </div>
      @empty
      <div style="padding:40px;text-align:center;color:var(--slate-light)">No events yet. Add events using the form →</div>
      @endforelse
    </div>
  </div>
  <div>
    <div class="card">
      <div class="ch">Add Event</div>
      <div class="cb">
        @if(auth()->user()->canManage('calendar'))
        <form method="POST" action="{{ route('calendar.store') }}">
          @csrf
          <div class="fg"><label class="fl">Title *</label><input type="text" name="title" class="fc" required placeholder="e.g. First Term Examination"></div>
          <div class="fg"><label class="fl">Type *</label>
            <select name="type" class="fc">
              <option value="event">Event</option>
              <option value="holiday">Holiday</option>
              <option value="exam">Examination</option>
              <option value="pta">PTA Meeting</option>
              <option value="resumption">Resumption</option>
              <option value="closing">Closing</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="fg"><label class="fl">Start Date *</label><input type="date" name="start_date" class="fc" required></div>
          <div class="fg"><label class="fl">End Date</label><input type="date" name="end_date" class="fc"></div>
          <div class="fg"><label class="fl">Colour</label>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              @foreach(['#2563EB'=>'Blue','#DC2626'=>'Red','#059669'=>'Green','#D97706'=>'Amber','#7C3AED'=>'Purple','#0891B2'=>'Cyan'] as $hex=>$name)
              <label style="cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:2px;font-size:9px;color:var(--slate)">
                <input type="radio" name="color" value="{{ $hex }}" style="display:none" @if($hex==='#2563EB') checked @endif>
                <span style="width:22px;height:22px;border-radius:50%;background:{{ $hex }};display:block;border:2px solid transparent" class="color-swatch"></span>
                {{ $name }}
              </label>
              @endforeach
            </div>
          </div>
          <input type="hidden" name="session_id" value="{{ optional($current)->id }}">
          <button type="submit" class="btn btn-p">Add to Calendar</button>
        </form>
        @else
        <div style="background:#F8FAFC;border:1px solid var(--border);border-radius:8px;padding:20px;text-align:center;font-size:13px;color:var(--slate-light)">📅 You can view calendar events but cannot add or modify them.</div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection