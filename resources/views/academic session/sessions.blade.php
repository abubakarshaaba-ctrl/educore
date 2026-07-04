@extends('layouts.app')

@section('title', 'Academic Sessions')
@section('page-title', 'Academic Sessions')

@push('styles')
<style>
.cycle-form,.cycle-panel{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:18px}
.cycle-row{display:grid;grid-template-columns:1fr auto;gap:12px;align-items:end}
.cycle-row input{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:10px}
.cycle-check{display:flex;align-items:center;gap:8px}
.cycle-btn{border:0;border-radius:8px;padding:10px 14px;background:#0f3b6f;color:#fff;font-weight:800;cursor:pointer}
.cycle-btn.secondary{background:#f1f5f9;color:#0f172a;border:1px solid #dbe3ef}
.cycle-table-wrap{overflow-x:auto}.cycle-table{width:100%;border-collapse:collapse;min-width:560px}.cycle-table th,.cycle-table td{padding:11px 12px;border-bottom:1px solid #e5e7eb;text-align:left}.cycle-table th{background:#f8fafc;color:#64748b;font-size:12px;text-transform:uppercase}
</style>
@endpush

@section('content')
    <form class="cycle-form" method="POST" action="{{ route('academic-cycle.sessions.store') }}">
        @csrf
        <div class="cycle-row">
            <label>Session name
                <input name="name" value="{{ old('name') }}" placeholder="2026/2027" required>
                @error('name')<small class="text-danger">{{ $message }}</small>@enderror
            </label>
            <label class="cycle-check"><input type="checkbox" name="activate" value="1" @checked(old('activate'))> Activate now</label>
            <button class="cycle-btn" type="submit">Save Session</button>
        </div>
    </form>

    <div class="cycle-panel">
        <div class="cycle-table-wrap">
            <div class="tbl"><table class="cycle-table">
                <thead><tr><th>Name</th><th>Current</th><th>Created</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($sessions as $session)
                    <tr>
                        <td>{{ $session->name }}</td>
                        <td>{{ $session->is_current ? 'Yes' : 'No' }}</td>
                        <td>{{ optional($session->created_at)->format('M d, Y') }}</td>
                        <td>
                            @if(!$session->is_current)
                                <form method="POST" action="{{ route('academic-cycle.sessions.activate', $session) }}" style="display:inline">@csrf<button class="cycle-btn secondary">Activate</button></form>
                            @else
                                <form method="POST" action="{{ route('academic-cycle.sessions.close', $session) }}" style="display:inline">@csrf<button class="cycle-btn secondary">Close</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">No academic sessions found.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
    </div>
@endsection
