@extends('layouts.auth')
@section('page-title', 'Agent Portal Access - EduCore')
@section('auth-body')
<style>
.agent-shell{min-height:100dvh;display:grid;grid-template-columns:minmax(0,1.45fr) minmax(430px,.9fr);background:#eef4fb}
.agent-hero{position:relative;overflow:hidden;padding:34px 4.5vw 28px;color:#fff;background:linear-gradient(90deg,rgba(3,25,55,.97) 0%,rgba(4,37,76,.91) 48%,rgba(5,40,79,.56) 100%),url('https://images.pexels.com/photos/3769021/pexels-photo-3769021.jpeg?auto=compress&cs=tinysrgb&w=1600') center/cover}
.agent-hero:after{content:"";position:absolute;inset:auto -80px -120px auto;width:330px;height:330px;border:1px solid rgba(242,195,91,.3);border-radius:50%}.agent-hero>*{position:relative;z-index:1}
.agent-logo{max-width:260px}.agent-logo .ec-brand-logo{max-width:250px}.agent-logo .ec-brand-logo img{max-width:250px}
.agent-copy{max-width:720px;margin-top:42px}.agent-pill{display:inline-flex;align-items:center;gap:9px;border:1px solid rgba(242,195,91,.6);border-radius:999px;padding:8px 14px;color:#f7c95b;font-size:.75rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;background:rgba(4,30,65,.55)}
.agent-copy h1{font-size:clamp(2.6rem,5vw,4.5rem);line-height:1.02;letter-spacing:-.045em;margin:16px 0 14px;max-width:700px}.agent-copy h1 span{color:#f4bd42}.agent-copy>p{font-size:1.05rem;line-height:1.55;color:#e0e9f5;max-width:640px}
.agent-feature-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:22px;max-width:620px}.agent-feature{display:flex;gap:13px;padding:14px;border:1px solid rgba(80,160,230,.45);border-radius:12px;background:rgba(3,35,73,.58);backdrop-filter:blur(5px)}.agent-feature svg{width:28px;height:28px;color:#f4bd42;flex:none}.agent-feature b{display:block;font-size:.88rem}.agent-feature small{display:block;margin-top:4px;color:#c6d4e5;font-size:.75rem;line-height:1.4}
.agent-quote{margin:26px 0 0;color:#fff;font-size:1.05rem;font-weight:700;font-style:italic}.agent-quote span{color:#f4bd42}
.agent-trust{display:flex;gap:0;margin-top:30px;border-top:1px solid rgba(255,255,255,.15);padding-top:18px}.agent-trust div{padding:0 20px;border-left:1px solid rgba(255,255,255,.22)}.agent-trust div:first-child{padding-left:0;border-left:0}.agent-trust b{display:block;font-size:.78rem}.agent-trust small{color:#c3d0e0;font-size:.67rem}
.agent-panel{display:grid;place-items:center;padding:26px;background:radial-gradient(circle at 15% 10%,rgba(215,154,33,.1),transparent 28%),#f4f7fb}.agent-card{width:min(100%,520px);padding:32px;border:1px solid #d8e0e8;border-radius:22px;background:#fff;box-shadow:0 24px 70px rgba(7,30,69,.15)}
.agent-card-head{display:flex;justify-content:space-between;gap:18px;align-items:flex-start}.agent-wordmark{font-weight:900;font-size:1.15rem;color:#071e45}.agent-wordmark span{color:#d79a21}.agent-card .auth-card__eyebrow{margin-top:4px}.agent-card .auth-title{font-size:2rem}.agent-card .auth-subtitle{font-size:.9rem}
.agent-card .ec-input{min-height:48px;background:#f7faff}.agent-card .ec-btn{min-height:50px;background:linear-gradient(180deg,#f4c657,#e7aa2a);color:#071e45;font-weight:800}
.agent-help{margin-top:20px;padding:16px 18px;border-radius:14px;background:linear-gradient(135deg,#edf6ff,#dcecff);border:1px solid #d5e6f8}.agent-help h3{margin:0 0 2px;color:#071e45;font-size:1rem}.agent-help>p{margin:0 0 10px;color:#52647b;font-size:.75rem}.agent-contact{display:grid;gap:8px}.agent-contact a{display:flex;align-items:center;gap:9px;text-decoration:none;color:#071e45;font-size:.8rem;font-weight:700}.agent-contact svg{width:20px;height:20px}.wa{color:#16a34a}.phone{color:#071e45}.mail{color:#071e45}
.agent-links{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-top:17px;font-size:.78rem}.agent-home{display:block;text-align:center;margin-top:18px;color:#175cd3;font-size:.78rem;font-weight:750;text-decoration:none}.agent-foot{margin-top:18px;padding-top:14px;border-top:1px solid #e8edf3;text-align:center;color:#8491a3;font-size:.67rem}
@media(max-width:1050px){.agent-shell{grid-template-columns:1fr}.agent-hero{padding:30px 24px}.agent-copy{margin-top:28px}.agent-panel{padding:30px 18px}.agent-card{max-width:600px}.agent-trust{flex-wrap:wrap;gap:16px}.agent-trust div{border:0;padding:0 12px 0 0}}
@media(max-width:620px){.agent-copy h1{font-size:2.25rem}.agent-feature-grid{grid-template-columns:1fr}.agent-card{padding:24px 18px}.agent-card .auth-title{font-size:1.6rem}.agent-card-head{display:block}.agent-wordmark{margin-bottom:14px}.agent-links{align-items:flex-start;flex-direction:column}.agent-trust{display:grid;grid-template-columns:1fr}.agent-quote{font-size:.9rem}}
</style>
<div class="agent-shell">
<aside class="agent-hero">
  <div class="agent-logo"><x-auth.logo /></div>
  <div class="agent-copy">
    <div class="agent-pill">● &nbsp; Agent Portal Access</div>
    <h1>Referral operations for <span>approved agents.</span></h1>
    <p>Manage school referrals, track commission activity, review messages, and maintain your agent profile — all in one secure portal.</p>
    <div class="agent-feature-grid">
      <div class="agent-feature"><svg viewBox="0 0 24 24" fill="none"><path d="M4 20V9l8-5 8 5v11M8 20v-6h8v6" stroke="currentColor" stroke-width="1.8"/></svg><div><b>Referral Dashboard</b><small>Track your schools and referral status</small></div></div>
      <div class="agent-feature"><svg viewBox="0 0 24 24" fill="none"><path d="M4 19h16M7 16V9m5 7V5m5 11v-4" stroke="currentColor" stroke-width="1.8"/></svg><div><b>Earnings</b><small>View commissions and payment history</small></div></div>
      <div class="agent-feature"><svg viewBox="0 0 24 24" fill="none"><path d="M5 20V8l7-4 7 4v12M9 20v-7h6v7" stroke="currentColor" stroke-width="1.8"/></svg><div><b>School Directory</b><small>See available schools in your network</small></div></div>
      <div class="agent-feature"><svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4V6Zm0 1 8 6 8-6" stroke="currentColor" stroke-width="1.8"/></svg><div><b>Platform Messages</b><small>Get updates and announcements</small></div></div>
    </div>
    <div class="agent-quote"><span>“</span> More schools. Brighter futures.</div>
    <div class="agent-trust"><div><b>Trusted Platform</b><small>Secure & reliable</small></div><div><b>Real Opportunities</b><small>Partner. Refer. Earn.</small></div><div><b>Built for Nigeria</b><small>Supporting education growth nationwide.</small></div></div>
  </div>
</aside>
<main class="agent-panel">
<section class="agent-card">
  <div class="agent-card-head"><header class="auth-card__header"><p class="auth-card__eyebrow">Agent Portal Access</p><h2 class="auth-title">Sign in as an agent</h2><p class="auth-subtitle">Manage referrals, schools, commissions, messages, and your portal profile.</p></header><div class="agent-wordmark">Edu<span>Core</span></div></div>
  @if($errors->any())<x-auth.alert type="error">{{ $errors->first() }}</x-auth.alert>@endif
  @if(session('success'))<x-auth.alert type="ok">{{ session('success') }}</x-auth.alert>@endif
  <form method="POST" action="{{ route('agent.portal.login.post') }}" novalidate>@csrf
    <div class="ec-form-group"><label class="ec-label" for="email">Email Address</label><input id="email" class="ec-input{{ $errors->has('email') ? ' ec-input--error' : '' }}" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>@error('email')<p class="ec-field-error">{{ $message }}</p>@enderror</div>
    <x-auth.password-input id="password" label="Password" autocomplete="current-password" :hasError="$errors->has('password')" />
    <x-auth.submit-button>Sign In to Agent Portal →</x-auth.submit-button>
  </form>
  <div class="agent-links"><span class="ec-hint">Not an approved agent yet?</span><a class="ec-link" href="{{ route('agent.register') }}">Apply for agent access →</a></div>
  <div class="agent-help"><h3>Need Help?</h3><p>Contact our support team.</p><div class="agent-contact">
    <a href="tel:+2347065595768"><svg class="phone" viewBox="0 0 24 24" fill="currentColor"><path d="M6.6 10.8c1.4 2.8 3.7 5.1 6.5 6.5l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1.1.5 1.1 1.1V20c0 .6-.5 1.1-1.1 1.1C10.9 21.1 2.9 13.1 2.9 3.2c0-.6.5-1.1 1.1-1.1h3.5c.6 0 1.1.5 1.1 1.1 0 1.2.2 2.4.6 3.6.1.3 0 .7-.2 1l-2.4 3z"/></svg>Call: 07065595768</a>
    <a href="https://wa.me/2348083070142" target="_blank" rel="noopener"><svg class="wa" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.5 15.3L2 22l4.8-1.5A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-2.8.9.9-2.8-.2-.3A8.2 8.2 0 1 1 12 20.2Z"/></svg>WhatsApp: 08083070142</a>
    <a href="mailto:support@educoreng.online"><svg class="mail" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm0 4-8 5-8-5V6l8 5 8-5v2Z"/></svg>Email: support@educoreng.online</a>
  </div></div>
  <a class="agent-home" href="{{ url('/') }}">Back to EduCore home →</a>
  <div class="agent-foot">EduCore © {{ date('Y') }} &nbsp; | &nbsp; Agent Network &nbsp; | &nbsp; Smarter Schools. Brighter Futures.</div>
</section>
</main>
</div>
@endsection