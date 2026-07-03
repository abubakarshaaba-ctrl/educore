@extends('layouts.app')
@section('title','Fee Reminders')
@section('page-title','Fee Reminders')
@push('styles') @include('partials.simple-page-styles') @endpush
@section('content')
@if(session('success'))<div class="alert alert-ok">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-bad">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('fees.reminders.bulk') }}" style="margin-bottom:12px">@csrf<button class="btn btn-primary">Bulk Send SMS</button></form>
<div class="card"><div class="card-h">Outstanding Invoices</div><div class="tbl"><table><thead><tr><th>Invoice</th><th>Student</th><th>Balance</th><th>Due</th><th></th></tr></thead><tbody>
@forelse($invoices as $invoice)<tr><td>{{ $invoice->invoice_number }}</td><td>{{ optional($invoice->student)->full_name }}</td><td>NGN {{ number_format($invoice->balance, 2) }}</td><td>{{ optional($invoice->due_date)->format('d M Y') ?? '-' }}</td><td><form method="POST" action="{{ route('fees.reminders.send') }}">@csrf<input type="hidden" name="invoice_id" value="{{ $invoice->id }}"><input type="hidden" name="channel" value="sms"><button class="btn btn-light">Send</button></form></td></tr>@empty<tr><td colspan="5" class="muted">No outstanding invoices.</td></tr>@endforelse
</tbody></table></div>{{ $invoices->links() }}</div>
<div class="card"><div class="card-h">Recent Reminders</div><div class="tbl"><table><thead><tr><th>Recipient</th><th>Channel</th><th>Status</th><th>Sent</th></tr></thead><tbody>
@forelse($reminders as $reminder)<tr><td>{{ $reminder->recipient }}</td><td>{{ strtoupper($reminder->channel) }}</td><td>{{ ucfirst($reminder->status) }}</td><td>{{ optional($reminder->sent_at)->format('d M Y H:i') }}</td></tr>@empty<tr><td colspan="4" class="muted">No reminders sent.</td></tr>@endforelse
</tbody></table></div></div>
@endsection
