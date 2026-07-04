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
            <label>Next term begins <input type="date" name="next_term_begins" value="{{ old('next_term_begins') }}"></label>
            <label><input type="checkbox" name="activate" value="1" @checked(old('activate'))> Activate now</label>
            <button class="cycle-btn" type="submit">Save Term</button>
        </div>
    </form>

    <div class="cycle-panel">
        <div class="cycle-table-wrap">
            <table class="cycle-table">
                <thead><tr><th>Term</th><th>Session</th><th>Dates</th><th>Next Term Begins</th><th>Current</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($terms as $term)
                    <tr>
                        <td>{{ $term->name }}</td>
                        <td>{{ optional($term->session)->name }}</td>
                        <td>{{ optional($term->start_date)->format('M d, Y') }} – {{ optional($term->end_date)->format('M d, Y') }}</td>
                        <td>
                            @if($term->next_term_begins)
                                {{ $term->next_term_begins->format('M d, Y') }}
                            @else
                                <span style="color:#94a3b8">—</span>
                            @endif
                            <form method="POST" action="{{ route('academic-cycle.terms.update', $term) }}" style="display:inline-flex;align-items:center;gap:6px;margin-top:4px">
                                @csrf @method('PATCH')
                                <input type="hidden" name="name" value="{{ $term->name }}">
                                <input type="hidden" name="start_date" value="{{ optional($term->start_date)->toDateString() }}">
                                <input type="hidden" name="end_date" value="{{ optional($term->end_date)->toDateString() }}">
                                <input type="date" name="next_term_begins" value="{{ optional($term->next_term_begins)->toDateString() }}" style="border:1px solid #cbd5e1;border-radius:6px;padding:4px 6px;font-size:11px">
                                <button class="cycle-btn secondary" type="submit" style="padding:4px 10px;font-size:11px">Set</button>
                            </form>
                        </td>
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
                    <tr><td colspan="6">No terms found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
