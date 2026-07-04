@extends('layouts.app')
@section('title','Library')
@section('page-title','Library')
@push('styles')
<style>
.ph{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px}
.sg{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px}
.sc{background:white;border:1px solid var(--border);border-radius:10px;padding:14px 16px;text-align:center}
.sv{font-size:22px;font-weight:800;letter-spacing:-0.02em}.sl{font-size:10px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;margin-top:2px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:10px 14px;border-bottom:1px solid var(--border);font-size:13px}
tbody tr:last-child td{border-bottom:none}tbody tr:hover td{background:#F8FAFC}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12.5px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-sm{padding:4px 10px;font-size:11px}
.badge{display:inline-flex;font-size:10px;font-weight:600;padding:2px 7px;border-radius:20px}
.bg{background:#ECFDF5;color:var(--emerald)}.br{background:#FEF2F2;color:var(--crimson)}.ba{background:#FFFBEB;color:var(--amber)}
.filter-bar{background:white;border:1px solid var(--border);border-radius:10px;padding:12px 16px;margin-bottom:14px;display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap}
.fg{display:flex;flex-direction:column;gap:4px}.fl{font-size:10px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:8px 11px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
@media(max-width:1024px){.sg{grid-template-columns:repeat(2,1fr)}}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
<div class="ph"><div><h2 style="font-size:18px;font-weight:700">Library Management</h2></div>
<div style="display:flex;gap:8px"><a href="{{ route('library.loans') }}" class="btn" style="background:white;border:1px solid var(--border);color:var(--midnight)">Loans & Returns</a></div></div>
<div class="sg">
    <div class="sc"><div class="sv" style="color:var(--indigo)">{{ $stats['total_books'] }}</div><div class="sl">Total Copies</div></div>
    <div class="sc"><div class="sv" style="color:var(--emerald)">{{ $stats['available'] }}</div><div class="sl">Available</div></div>
    <div class="sc"><div class="sv" style="color:var(--amber)">{{ $stats['issued'] }}</div><div class="sl">Issued</div></div>
    <div class="sc"><div class="sv" style="color:var(--crimson)">{{ $stats['overdue'] }}</div><div class="sl">Overdue</div></div>
</div>
<form method="GET">
<div class="filter-bar">
    <div class="fg"><span class="fl">Search</span><input type="text" name="search" class="fc" value="{{ request('search') }}" placeholder="Title or author"></div>
    <div class="fg"><span class="fl">Category</span>
        <select name="category" class="fc">
            <option value="">All</option>
            @foreach($categories as $cat)<option value="{{ $cat }}" {{ request('category')===$cat?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$cat)) }}</option>@endforeach
        </select>
    </div>
    <button type="submit" class="btn btn-p">Search</button>
</div>
</form>
<div class="card">
    <div class="ch">Book Inventory <a href="#add-book" class="btn btn-p btn-sm">+ Add Book</a></div>
    <div class="tbl"><table>
        <thead><tr><th>Title</th><th>Author</th><th>Category</th><th>Copies</th><th>Available</th><th>Condition</th></tr></thead>
        <tbody>
        @forelse($books as $book)
        <tr>
            <td><strong>{{ $book->title }}</strong>@if($book->isbn)<br><span style="font-size:10px;color:var(--slate-light)">ISBN: {{ $book->isbn }}</span>@endif</td>
            <td>{{ $book->author }}</td>
            <td style="text-transform:capitalize;font-size:11px">{{ str_replace('_',' ',$book->category) }}</td>
            <td>{{ $book->total_copies }}</td>
            <td><span class="badge {{ $book->available_copies>0?'bg':'br' }}">{{ $book->available_copies }}</span></td>
            <td style="font-size:11px;text-transform:capitalize">{{ $book->condition }}</td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--slate-light)">No books in library yet</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $books->links() }}
</div>
<div class="card" id="add-book">
    <div class="ch">Add New Book</div>
    <div style="padding:16px">
        <form method="POST" action="{{ route('library.books.store') }}">
        @csrf
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
            <div class="fg"><label class="fl">Title *</label><input type="text" name="title" class="fc" required></div>
            <div class="fg"><label class="fl">Author *</label><input type="text" name="author" class="fc" required></div>
            <div class="fg"><label class="fl">ISBN</label><input type="text" name="isbn" class="fc"></div>
            <div class="fg"><label class="fl">Category *</label>
                <select name="category" class="fc" required>
                    @foreach($categories as $cat)<option value="{{ $cat }}">{{ ucfirst(str_replace('_',' ',$cat)) }}</option>@endforeach
                </select>
            </div>
            <div class="fg"><label class="fl">Total Copies *</label><input type="number" name="total_copies" class="fc" value="1" min="1" required></div>
            <div class="fg"><label class="fl">Location</label><input type="text" name="location" class="fc" placeholder="Shelf/Rack"></div>
        </div>
        <button type="submit" class="btn btn-p" style="width:auto;margin-top:8px">Add Book</button>
        </form>
    </div>
</div>
@endsection