@extends('layouts.app')

@section('title', 'Academic Terms')
@section('page-title', 'Academic Terms')

@push('styles')
<style>
.cycle-form,.cycle-panel{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:18px}
.cycle-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end}
.cycle-grid input,.cycle-grid select{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:10px}
.cycle-btn{border:0;border-radius:8px;padding:10px 14px;background:#0f3b6f;color:#fff;font-weight:800;cursor:pointer}
.cycle-btn.secondary{background:#f1f5f9;color:#0f172a;border:1px solid #dbe3ef}
.cycle-table-wrap{overflow-x:auto}.cycle-table{width:100%;border-collapse:collapse;min-width:760px}.cycle-table th,.cycle-table td{padding:11px 12px;border-bottom:1px solid #e5e7eb;text-align:left}.cycle-table th{background:#f8fafc;color:#64748b;font-size:12px;text-transform:uppercase}
</style>
@endpush

@section('content')
    <form class="cycle-form" method="POST" action="{{ route('academic-cycle.terms.store') }}">
        @csrf
        <div class="cycle-grid">
            <label>Session
                <select name="session_id" required>
                    <option value="">Select session</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" @selected(old('session_id') == $session->id)>{{ $session->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Term name <input name="name" value="{{ old('name') }}" placeholder="First Term" required></label>
            <label>Start date <input type="date" name="start_date" value="{{ old('start_date') }}" required></label>
            <label>End date <input type="date" name="end_date" value="{{ old('end_date') }}" required></label>
            <label><input type="checkbox" name="activate" value="1" @checked(old('activate'))> Activate now</label>
            <button class="cycle-btn" type="submit">Save Term</button>
        </div>
    </form>

    <div class="cycle-panel">
        <div class="cycle-table-wrap">
            <div class="tbl"><table class="cycle-table">
                <thead><tr><th>Term</th><th>Session</th><th>Dates</th><th>Current</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($terms as $term)
                    <tr>
                        <td>{{ $term->name }}</td>
                        <td>{{ optional($term->session)->name }}</td>
                        <td>{{ optional($term->start_date)->format('M d, Y') }} to {{ optional($term->end_date)->format('M d, Y') }}</td>
                        <td>{{ $term->is_current ? 'Yes' : 'No' }}</td>
                        <td>
                            @if(!$term->is_current)
                                <form method="POST" action="{{ route('academic-cycle.terms.activate', $term) }}" style="display:inline">@csrf<button class="cycle-btn secondary">Activate</button></form>
                            @else
                                <form method="POST" action="{{ route('academic-cycle.terms.close', $term) }}" style="display:inline">@csrf<button class="cycle-btn secondary">Close</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No terms found.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
    </div>
@endsection
