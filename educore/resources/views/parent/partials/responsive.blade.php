{{-- Mobile responsiveness for the standalone parent portal pages.
     Included AFTER each page's own <style> so these overrides win at small widths.
     Targets shared class names across the parent pages without altering desktop behavior. --}}
<style>
.nav,.content,.card,.card-body,.card-head{min-width:0}
.content{width:100%;box-sizing:border-box}
.nav>div{min-width:0}
.tbl{width:100%;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;overscroll-behavior-inline:contain}
.card-head,.thread,.subject,.meta,.reply{overflow-wrap:anywhere;word-break:break-word}

@media (max-width:760px){
    .nav{padding:8px 14px;height:auto;min-height:54px;flex-wrap:wrap;gap:8px}
    .nav-links,.nav>div{display:flex;flex-wrap:wrap;gap:4px;max-width:100%}
    .nav-link,.nav a{padding:7px 10px;font-size:12px}
    .nav-right{gap:6px;margin-left:auto}
    .content{max-width:100%;padding:16px}
    .student-tabs{gap:6px;overflow-x:auto;-webkit-overflow-scrolling:touch;flex-wrap:nowrap;padding-bottom:2px}
    .s-tab{white-space:nowrap;flex:0 0 auto}
    .card{max-width:100%}
    .card-head{flex-wrap:wrap;gap:6px}
    table{min-width:560px}
}

@media (max-width:640px){
    .nav{padding-inline:12px}
    .nav-logo{min-width:0}
    .nav-title,.nav-user{overflow-wrap:anywhere}
    .content{padding:14px 12px}
    .stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
    .stat-card{min-width:0;padding:14px}
    .stat-val{font-size:20px;overflow-wrap:anywhere}
    .thread{padding:12px 14px}
    .reply{padding:9px 10px}
}

@media (max-width:420px){
    .nav{align-items:flex-start}
    .nav-right{width:100%;margin-left:0;justify-content:space-between}
    .stats{grid-template-columns:1fr}
    .content{padding:12px 10px}
    .card-head{padding:11px 13px}
    table{min-width:520px}
}
</style>
