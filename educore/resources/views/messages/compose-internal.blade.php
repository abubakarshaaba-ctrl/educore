@extends('layouts.app')
@section('title','New School Message')
@section('page-title','New School Message')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:20px}.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}label{font-size:12px;font-weight:600;color:var(--midnight)}.fc{padding:10px 13px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:9px;background:white;outline:none;width:100%;resize:vertical}.fc:focus{border-color:var(--indigo)}.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none}.btn-p{background:var(--indigo);color:white}.btn-ghost{background:white;border:1px solid var(--border);color:var(--midnight)}.hint{font-size:11px;color:var(--slate);line-height:1.5}.recipient-block{display:none}.recipient-block.active{display:block}
</style>
@endpush
@section('content')
<a href="{{ route('messages.inbox') }}" style="font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px">← Inbox</a>
<div class="card"><div class="ch">New School Message</div><div style="padding:18px">
<form method="POST" action="{{ route('messages.internal.store') }}">@csrf
<div class="fg"><label>To</label><select name="target_type" id="targetType" class="fc" required>
<option value="">Select recipient type...</option>
@if($canBroadcast)
<option value="all_staff" {{ old('target_type')==='all_staff'?'selected':'' }}>All Staff</option>
<option value="staff" {{ old('target_type')==='staff'?'selected':'' }}>Individual Staff</option>
<option value="all_parents" {{ old('target_type')==='all_parents'?'selected':'' }}>All Parents</option>
<option value="parent" {{ old('target_type')==='parent'?'selected':'' }}>Individual Parent</option>
@else
<option value="admin" {{ old('target_type','admin')==='admin'?'selected':'' }}>School Administration</option>
@endif
</select>@error('target_type')<span style="font-size:11px;color:#DC2626">{{ $message }}</span>@enderror</div>

@if($canBroadcast)
<div class="fg recipient-block" id="staffRecipient"><label>Staff member</label><select name="recipient_id_staff" class="fc"><option value="">Select staff...</option>@foreach($staff as $u)<option value="{{ $u->id }}">{{ $u->name }}{{ $u->staff_id ? ' · '.$u->staff_id : '' }}</option>@endforeach</select></div>
<div class="fg recipient-block" id="parentRecipient"><label>Parent</label><select name="recipient_id_parent" class="fc"><option value="">Select parent...</option>@foreach($parents as $u)<option value="{{ $u->id }}">{{ $u->name }}{{ $u->phone ? ' · '.$u->phone : '' }}</option>@endforeach</select></div>
@else
<div class="fg"><div class="hint">Your message will be sent privately to school administration.</div></div>
@endif
<input type="hidden" name="recipient_id" id="recipientId" value="{{ old('recipient_id') }}">
<div class="fg"><label>Subject</label><input type="text" name="subject" class="fc" maxlength="150" required value="{{ old('subject') }}">@error('subject')<span style="font-size:11px;color:#DC2626">{{ $message }}</span>@enderror</div>
<div class="fg"><label>Message</label><textarea name="body" class="fc" rows="6" maxlength="10000" required>{{ old('body') }}</textarea>@error('body')<span style="font-size:11px;color:#DC2626">{{ $message }}</span>@enderror</div>
<div style="display:flex;gap:10px"><button type="submit" class="btn btn-p">Send Message</button><a href="{{ route('messages.inbox') }}" class="btn btn-ghost">Cancel</a></div>
</form></div></div>
<script>
const type=document.getElementById('targetType'), hidden=document.getElementById('recipientId'), staff=document.querySelector('[name="recipient_id_staff"]'), parent=document.querySelector('[name="recipient_id_parent"]');
function sync(){ const v=type.value; document.getElementById('staffRecipient')?.classList.toggle('active',v==='staff'); document.getElementById('parentRecipient')?.classList.toggle('active',v==='parent'); hidden.value=v==='staff'?(staff?.value||''):v==='parent'?(parent?.value||''):''; }
type?.addEventListener('change',sync); staff?.addEventListener('change',sync); parent?.addEventListener('change',sync); sync();
</script>
@endsection
