{{-- Mobile responsiveness for the standalone parent portal pages.
     Included AFTER each page's own <style> so these overrides win at small widths.
     Targets class names that exist across the parent pages; rules for classes a
     given page doesn't use are harmless no-ops. Desktop layout is untouched. --}}
<style>
@media (max-width:640px){
    .nav{padding:0 14px;height:auto;min-height:54px;flex-wrap:wrap;gap:6px;padding-top:8px;padding-bottom:8px}
    .nav-links{display:flex;flex-wrap:wrap;gap:4px;width:100%}
    .nav-link,.nav a{padding:6px 10px;font-size:12px}
    .nav-right{gap:6px}
    .content{max-width:100%;padding:14px}
    .stats{grid-template-columns:1fr 1fr;gap:10px}
    .stat-val{font-size:20px}
    .student-tabs{gap:6px}
    /* Let wide tables scroll horizontally instead of overflowing the viewport */
    .card{overflow-x:auto}
    table{min-width:520px}
    .card-head{flex-wrap:wrap;gap:6px}
}
@media (max-width:400px){
    .stats{grid-template-columns:1fr}
}
</style>
