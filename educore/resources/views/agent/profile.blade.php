@extends('agent.layout')
@section('title','My Profile & Bank Details')
@section('content')
<div style="max-width:600px">
<div style="background:white;border:1px solid #E2E8F0;border-radius:12px;overflow:hidden;margin-bottom:16px">
    <div style="padding:13px 18px;border-bottom:1px solid #E2E8F0;background:#F8FAFC;font-size:13px;font-weight:700">👤 Profile Details</div>
    <div style="padding:20px">
    <form method="POST" action="{{ route('agent.portal.profile.update') }}">
        @csrf
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
            <div>
                <label style="display:block;font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Full Name</label>
                <div style="padding:10px 12px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;font-size:13px;color:#64748B">{{ $agent->name }} <span style="font-size:10px">(contact admin to change)</span></div>
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Email</label>
                <div style="padding:10px 12px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;font-size:13px;color:#64748B">{{ $agent->email }}</div>
            </div>
        </div>
        @foreach([['phone','Phone Number','08012345678'],['state','State / Location','Lagos']] as [$n,$l,$p])
        <div style="margin-bottom:14px">
            <label style="display:block;font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">{{ $l }}</label>
            <input name="{{ $n }}" style="width:100%;padding:10px 12px;font-size:13px;font-family:inherit;border:1.5px solid #E2E8F0;border-radius:8px;background:#F8FAFC;outline:none" value="{{ $agent->$n }}" placeholder="{{ $p }}">
        </div>
        @endforeach
        <div style="font-size:13px;font-weight:700;margin:18px 0 10px;padding-top:14px;border-top:1px solid #F1F5F9">🏦 Bank Details (for payouts)</div>
        @foreach([['bank_name','Bank Name','e.g. First Bank'],['bank_account_number','Account Number','10-digit account number'],['bank_account_name','Account Name','Name as registered with bank']] as [$n,$l,$p])
        <div style="margin-bottom:12px">
            <label style="display:block;font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">{{ $l }}</label>
            <input name="{{ $n }}" style="width:100%;padding:10px 12px;font-size:13px;font-family:inherit;border:1.5px solid #E2E8F0;border-radius:8px;background:#F8FAFC;outline:none" value="{{ $agent->$n }}" placeholder="{{ $p }}">
        </div>
        @endforeach
        <button type="submit" style="padding:10px 22px;background:#2563EB;color:white;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer">💾 Save Details</button>
    </form>
    </div>
</div>

<div style="background:white;border:1px solid #E2E8F0;border-radius:12px;overflow:hidden">
    <div style="padding:13px 18px;border-bottom:1px solid #E2E8F0;background:#F8FAFC;font-size:13px;font-weight:700">🔑 Change Password</div>
    <div style="padding:20px">
    <form method="POST" action="{{ route('agent.portal.password') }}">
        @csrf
        @foreach([['current_password','Current Password'],['password','New Password'],['password_confirmation','Confirm New Password']] as [$n,$l])
        <div style="margin-bottom:12px">
            <label style="display:block;font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">{{ $l }}</label>
            <input type="password" name="{{ $n }}" style="width:100%;padding:10px 12px;font-size:13px;font-family:inherit;border:1.5px solid #E2E8F0;border-radius:8px;background:#F8FAFC;outline:none" required>
        </div>
        @endforeach
        <button type="submit" style="padding:10px 22px;background:#059669;color:white;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer">🔑 Update Password</button>
    </form>
    </div>
</div>
</div>
@endsection
