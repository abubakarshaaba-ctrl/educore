@extends('layouts.app')
@section('title','New Internal Message')
@section('page-title','New Internal Message')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:20px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
label{font-size:12px;font-weight:600;color:var(--midnight)}
.fc{padding:10px 13px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:9px;background:white;outline:none;width:100%;transition:border 200ms;resize:vertical}
.fc:focus{border-color:var(--indigo)}
select.fc{cursor:pointer}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer}
.btn-p{background:var(--indigo);color:white}
.btn-ghost{background:white;border:1px solid var(--border);color:var(--midnight);text-decoration:none}
</style>
@endpush
@section('content')
<a href="{{ route('messages.inbox') }}" style="font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px">← Inbox</a>
<div class="card">
    <div class="ch">New Internal Message</div>
    <div style="padding:18px">
        <form method="POST" action="{{ route('messages.internal.store') }}">
            @csrf
            <div class="fg">
                <label>To</label>
                <select name="recipient_id" class="fc" required>
                    <option value="">Select recipient...</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ old('recipient_id') == $u->id ? 'selected' : '' }}>
                        {{ $u->name }} ({{ ucfirst($u->role ?? 'staff') }})
                    </option>
                    @endforeach
                </select>
                @error('recipient_id')<span style="font-size:11px;color:#DC2626">{{ $message }}</span>@enderror
            </div>
            <div class="fg">
                <label>Subject</label>
                <input type="text" name="subject" class="fc" placeholder="Message subject" required value="{{ old('subject') }}">
                @error('subject')<span style="font-size:11px;color:#DC2626">{{ $message }}</span>@enderror
            </div>
            <div class="fg">
                <label>Message</label>
                <textarea name="body" class="fc" rows="6" placeholder="Write your message..." required>{{ old('body') }}</textarea>
                @error('body')<span style="font-size:11px;color:#DC2626">{{ $message }}</span>@enderror
            </div>
            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-p">Send Message</button>
                <a href="{{ route('messages.inbox') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
