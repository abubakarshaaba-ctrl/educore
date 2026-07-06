@extends('layouts.app')
@section('title','Proxy Clock-In Review')
@section('page-title','Staff Attendance')

@push('styles')
<style>
.breadcrumb{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--slate-light);margin-bottom:18px}
.breadcrumb a{color:var(--indigo);text-decoration:none;font-weight:500}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:var(--emerald);margin-bottom:14px}
.review-row{display:grid;grid-template-columns:130px 130px 1fr auto;gap:16px;align-items:center;padding:14px 18px;border-bottom:1px solid var(--border)}
.review-row:last-child{border-bottom:none}
.photo-box{width:120px;height:120px;border-radius:10px;overflow:hidden;background:#F1F5F9;display:flex;align-items:center;justify-content:center;border:1px solid var(--border)}
.photo-box img{width:100%;height:100%;object-fit:cover}
.photo-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--slate-light);margin-bottom:4px;text-align:center}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12.5px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none}
.btn-p{background:var(--emerald);color:white}
.btn-r{background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA}
</style>
@endpush

@section('content')
<div class="breadcrumb">
    <a href="{{ route('staff-attendance.index') }}">Staff Attendance</a>
    <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
    Proxy Clock-In Review
</div>

@if(session('success'))<div class="alert-s">✓ {{ session('success') }}</div>@endif

<div class="card">
    <div class="ch">
        Compare the captured photo against the passport photo — {{ $records->total() }} pending
    </div>

    @forelse($records as $rec)
    <div class="review-row">
        <div>
            <div class="photo-label">Passport photo</div>
            <div class="photo-box">
                @if(optional($rec->staff)->passport_photo)
                    <img src="{{ asset('storage/' . $rec->staff->passport_photo) }}" alt="Passport photo">
                @else
                    <span style="font-size:11px;color:var(--slate-light)">No photo on file</span>
                @endif
            </div>
        </div>
        <div>
            <div class="photo-label">Captured at clock-in</div>
            <div class="photo-box">
                @if($rec->proxy_photo)
                    <img src="{{ asset('storage/' . $rec->proxy_photo) }}" alt="Captured photo">
                @else
                    <span style="font-size:11px;color:var(--slate-light)">No photo captured</span>
                @endif
            </div>
        </div>
        <div>
            <div style="font-weight:700;color:var(--midnight)">{{ optional($rec->staff)->name }}</div>
            <div style="font-size:12px;color:var(--slate-light);margin-top:2px">
                Clocked in by {{ optional($rec->clockedInBy)->name }} · {{ $rec->attendance_date->format('D d M Y') }} · {{ \Carbon\Carbon::parse($rec->clock_in_time)->format('g:i A') }}
            </div>
        </div>
        <div style="display:flex;gap:8px">
            <form method="POST" action="{{ route('staff-attendance.proxy-review.decide', $rec) }}">
                @csrf
                <input type="hidden" name="action" value="confirmed">
                <button class="btn btn-p">✓ Matches</button>
            </form>
            <form method="POST" action="{{ route('staff-attendance.proxy-review.decide', $rec) }}"
                  onsubmit="return confirm('Flag this clock-in as not matching the passport photo?')">
                @csrf
                <input type="hidden" name="action" value="flagged">
                <button class="btn btn-r">✗ Doesn't match</button>
            </form>
        </div>
    </div>
    @empty
    <div style="padding:40px;text-align:center;color:var(--slate-light)">No proxy clock-ins awaiting review.</div>
    @endforelse
</div>

<div style="padding:0 4px">{{ $records->links() }}</div>
@endsection
