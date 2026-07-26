{{-- Shared PDF letterhead. Expects $tenant. --}}
<style>
.lh-wrap { border-bottom: 4px double #0B1D3A; padding-bottom: 18px; margin-bottom: 28px; }
.lh-bar { height: 6px; background: linear-gradient(90deg, #0B1D3A 0%, #1A56DB 55%, #D79A21 100%); margin: -60px -70px 22px -70px; }
.lh-row { display: table; width: 100%; }
.lh-logo-cell { display: table-cell; width: 62px; vertical-align: middle; }
.lh-logo { width: 52px; height: 52px; border-radius: 50%; background: #0B1D3A; color: white; text-align: center; line-height: 52px; font-size: 20px; font-weight: 800; }
.lh-logo img { width: 52px; height: 52px; border-radius: 50%; object-fit: cover; }
.lh-text-cell { display: table-cell; vertical-align: middle; padding-left: 14px; }
.lh-name { font-size: 21px; font-weight: 800; color: #0B1D3A; letter-spacing: 0.5px; text-transform: uppercase; }
.lh-meta { font-size: 10px; color: #64748B; margin-top: 3px; }
</style>
<div class="lh-bar"></div>
<div class="lh-wrap">
    <div class="lh-row">
        <div class="lh-logo-cell">
            <div class="lh-logo">
                @if($tenant->logo_path)
                    <img src="{{ storage_path('app/public/' . $tenant->logo_path) }}" alt="">
                @else
                    {{ strtoupper(substr($tenant->name, 0, 1)) }}
                @endif
            </div>
        </div>
        <div class="lh-text-cell">
            <div class="lh-name">{{ $tenant->name }}</div>
            <div class="lh-meta">
                {{ $tenant->address }}
                @if($tenant->phone) &middot; {{ $tenant->phone }} @endif
                @if($tenant->email) &middot; {{ $tenant->email }} @endif
            </div>
        </div>
    </div>
</div>
