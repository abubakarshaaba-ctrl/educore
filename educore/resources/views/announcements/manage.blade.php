@extends('layouts.app')
@section('title','Manage Announcements')
@section('page-title','Announcements')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:11px 14px;border-bottom:1px solid var(--border);font-size:13px}
tbody tr:last-child td{border-bottom:none}tbody tr:hover td{background:#F8FAFC}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 12px;font-size:11.5px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-r{background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA}.btn-g{background:#ECFDF5;color:var(--emerald);border:1px solid #A7F3D0}
.pri{display:inline-flex;font-size:10px;font-weight:600;padding:2px 7px;border-radius:20px}
.pri-normal{background:#F1F5F9;color:var(--slate)}.pri-important{background:#FFFBEB;color:var(--amber)}.pri-urgent{background:#FEF2F2;color:var(--crimson)}
.back{font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
<a href="{{ route('announcements.index') }}" class="back">← Back to Board</a>
<div class="card">
    <div class="ch">All Announcements</div>
    <div class="tbl"><table>
        <thead><tr><th>Title</th><th>Audience</th><th>Priority</th><th>Published</th><th>Expires</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($announcements as $ann)
        <tr>
            <td><strong>{{ $ann->title }}</strong><br><span style="font-size:11px;color:var(--slate-light)">{{ Str::limit($ann->body,60) }}</span></td>
            <td style="font-size:11px;text-transform:capitalize">{{ $ann->audience }}</td>
            <td><span class="pri pri-{{ $ann->priority }}">{{ ucfirst($ann->priority) }}</span></td>
            <td style="font-size:11px">{{ \Carbon\Carbon::parse($ann->publish_date)->format('d M Y') }}</td>
            <td style="font-size:11px">{{ $ann->expire_date ? \Carbon\Carbon::parse($ann->expire_date)->format('d M Y') : 'No expiry' }}</td>
            <td>
                <form method="POST" action="{{ route('announcements.toggle',$ann) }}" style="display:inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn {{ $ann->is_published?'btn-g':'btn-p' }}">{{ $ann->is_published?'Published':'Draft' }}</button>
                </form>
            </td>
            <td>
                <form method="POST" action="{{ route('announcements.destroy',$ann) }}" style="display:inline" onsubmit="return confirm('Delete?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-r">Delete</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--slate-light)">No announcements</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $announcements->links() }}
</div>
@endsection