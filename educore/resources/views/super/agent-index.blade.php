@extends('layouts.super')
@section('title','Agents')
@section('page-title','Platform Agents')
@push('styles') @include('partials.simple-page-styles') @endpush
@section('content')
@if(session('success'))<div class="alert alert-ok">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-bad">{{ $errors->first() }}</div>@endif
<div class="grid grid-2">
  <div class="card"><div class="card-h">Agents</div><div class="tbl"><table><thead><tr><th>Name</th><th>Email</th><th>Code</th><th>Status</th><th></th></tr></thead><tbody>
  @forelse($agents as $agent)<tr><td>{{ $agent->name }}</td><td>{{ $agent->email }}</td><td>{{ $agent->referral_code }}</td><td><span class="badge {{ $agent->is_active ? 'on' : 'off' }}">{{ $agent->is_active ? 'Active' : 'Inactive' }}</span></td><td><a class="btn btn-light" href="{{ route('super.agents.show', $agent) }}">View</a></td></tr>@empty<tr><td colspan="5" class="muted">No agents yet.</td></tr>@endforelse
  </tbody></table></div>{{ $agents->links() }}</div>
  <div class="card"><div class="card-h">Create Agent</div><div class="card-b"><form method="POST" action="{{ route('super.agents.store') }}">@csrf
    <div class="form-row"><label>Name</label><input class="control" name="name" required></div>
    <div class="form-row"><label>Email</label><input class="control" type="email" name="email" required></div>
    <div class="form-row"><label>Phone</label><input class="control" name="phone"></div>
    <div class="form-row"><label>State</label><input class="control" name="state"></div>
    <div class="form-row"><label>Commission Rate (%)</label><input class="control" type="number" step="0.01" name="commission_rate" value="10" required></div>
    <button class="btn btn-primary">Create Agent</button>
  </form></div></div>
</div>
@endsection
