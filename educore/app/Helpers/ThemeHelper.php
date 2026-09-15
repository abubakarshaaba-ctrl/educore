<?php
namespace App\Helpers;

use App\Services\Auth\StrictWebRbacPolicy;

class ThemeHelper
{
    /**
     * EduCore's fixed brand palette. Per-tenant colour customisation has
     * been removed — every school uses the same navy/gold branding.
     *
     * This also applies presentation-level RBAC to the shared web sidebar.
     * Server middleware remains the security boundary; these selectors simply
     * stop users seeing modules that their strict role scope cannot open.
     *
     * Global accessibility and UI-normalisation rules live here because a
     * number of legacy views still contain local CSS. Important declarations
     * intentionally allow the design-system layer to normalise typography,
     * alignment, responsiveness, focus, motion and control geometry without
     * changing feature business logic.
     */
    public static function css(): string
    {
        $primary = '#071E45';
        $accent  = '#D79A21';
        $sidebar = '#071E45';

        $rbacCss = '';
        $user = auth()->user();
        if ($user && ! $user->isSuperAdmin() && $user->isTenantStaff()) {
            /** @var StrictWebRbacPolicy $policy */
            $policy = app(StrictWebRbacPolicy::class);
            $selectors = [];

            foreach ($policy->navigationPaths() as $module => $path) {
                if ($policy->moduleAllowed($user, $module)) {
                    continue;
                }

                $path = rtrim($path, '/');
                $selectors[] = '.sidebar a.nav-item[href$="'.$path.'"]';
                $selectors[] = '.sidebar a.nav-item[href*="'.$path.'/"]';
                $selectors[] = '.sidebar a.nav-item[href*="'.$path.'?"]';
            }

            if ($selectors !== []) {
                $rbacCss = implode(',', $selectors).'{display:none!important;}';
            }
        }

        return "<style>
/* Canonical EduCore palette and geometry. */
html:root{
    --brand-navy:{$sidebar}!important;
    --brand-navy-hover:#0B2D63!important;
    --brand-navy-soft:#E9F0F9!important;
    --brand-gold:{$accent}!important;
    --brand-gold-dark:#B8810D!important;
    --brand-gold-light:#FEF9EC!important;
    --midnight:{$primary}!important;
    --navy:{$primary}!important;
    --indigo:{$accent}!important;
    --indigo-dark:#B8810D!important;
    --indigo-dk:#B8810D!important;
    --indigo-bg:#FEF9EC!important;
    --amber:{$accent}!important;
    --focus-ring:rgba(215,154,33,.72);
    --focus-ring-soft:rgba(215,154,33,.20);
    --ui-font:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
    --control-height:40px;
    --control-radius:8px;
    --card-radius:12px;
    --table-head-height:38px;
    --table-row-height:44px;
    --table-edge-space:14px;
}

/* One compact typography system across every tenant screen. */
html,body,button,input,select,textarea{
    font-family:var(--ui-font)!important;
}
html{font-size:13px!important;max-width:100%;overflow-x:hidden;}
body{font-size:13px!important;line-height:1.42!important;max-width:100%;overflow-x:hidden;}
h1{font-size:20px!important;line-height:1.2!important;}
h2{font-size:17px!important;line-height:1.25!important;}
h3{font-size:15px!important;line-height:1.3!important;}
h4,h5,h6{font-size:13px!important;line-height:1.35!important;}
p,li,td,th,label,input,select,textarea,button,.btn,.page-tab,.nav-item{line-height:1.35!important;}
.sidebar .nav-item{font-size:12.5px!important;}
.nav-section-label{font-size:10px!important;letter-spacing:.06em!important;color:#D8B968!important;}
.page-title,.card-title,.ch,.card-head .card-title{font-size:13px!important;}
.form-label,.fl,thead th{font-size:10px!important;}
tbody td{font-size:12px!important;}
.badge,.status-badge{font-size:10px!important;}

.sidebar{background:{$sidebar}!important;}
.nav-item.active{background:{$accent}28!important;color:#F2C35B!important;}
.nav-item.active::before{background:{$accent}!important;}
.btn-p{background:{$accent}!important;color:{$primary}!important;}

/* Canonical control geometry and vertical alignment. */
button:not(.collapse-btn):not(.icon-btn),
.btn,
[role=button]:not(.icon-btn),
.page-tab,
.pagination a,
.pagination button{
    min-height:var(--control-height)!important;
    height:auto;
    display:inline-flex!important;
    align-items:center!important;
    justify-content:center!important;
    vertical-align:middle!important;
    line-height:1!important;
    padding-top:0!important;
    padding-bottom:0!important;
    border-radius:var(--control-radius)!important;
    box-sizing:border-box!important;
    white-space:nowrap;
    max-width:100%;
}

input:not([type=checkbox]):not([type=radio]):not([type=hidden]):not([type=file]),
select,
.form-control,
.fc{
    min-height:var(--control-height)!important;
    height:var(--control-height)!important;
    padding-top:0!important;
    padding-bottom:0!important;
    border-radius:var(--control-radius)!important;
    box-sizing:border-box!important;
    font-size:12px!important;
    line-height:var(--control-height)!important;
    max-width:100%;
}
textarea,textarea.form-control,textarea.fc{
    height:auto!important;
    min-height:84px!important;
    padding-top:9px!important;
    padding-bottom:9px!important;
    line-height:1.4!important;
}
select,select.form-control,select.fc{line-height:normal!important;}

/* Align controls and shapes whenever they share a horizontal workflow row. */
.form-row,.filter-row,.filters-row,.toolbar,.actions,.page-actions,.card-actions,.scope-tabs,.page-tabs{
    align-items:center!important;
}
.form-row > *,.filter-row > *,.filters-row > *,.toolbar > *,.actions > *,.page-actions > *,.card-actions > *{
    align-self:center;
}
.page-tabs{min-height:48px;}
.page-tab{padding-left:14px!important;padding-right:14px!important;}

/* Cards, tables and media resolve safely inside the viewport. */
.card,.filter-card,.panel,.stat,.sc,.sum-card{
    border-radius:var(--card-radius)!important;
    max-width:100%;
    min-width:0;
}
img,video,canvas,svg{max-width:100%;}
img,video{height:auto;}

/*
 * Global table rhythm. Compact typography should not mean compressed tables:
 * headers remain visually distinct and every data row has enough vertical space
 * for names, secondary metadata, status pills and action buttons.
 */
.page-content table:not(.no-table-edge-space){
    width:calc(100% - (var(--table-edge-space) * 2))!important;
    margin-left:var(--table-edge-space)!important;
    margin-right:var(--table-edge-space)!important;
}
table thead tr{
    min-height:var(--table-head-height)!important;
    height:var(--table-head-height)!important;
}
table thead th{
    height:var(--table-head-height)!important;
    min-height:var(--table-head-height)!important;
    padding-top:10px!important;
    padding-bottom:10px!important;
    vertical-align:middle!important;
}
table tbody tr{
    min-height:var(--table-row-height)!important;
    height:var(--table-row-height)!important;
}
table tbody td{
    min-height:var(--table-row-height)!important;
    padding-top:9px!important;
    padding-bottom:9px!important;
    vertical-align:middle!important;
}
table tfoot td{
    padding-top:10px!important;
    padding-bottom:10px!important;
    vertical-align:middle!important;
}
table th,table td{vertical-align:middle!important;}
td .btn,td button,td [role=button]{vertical-align:middle!important;}
.tbl,.table-wrap,.trx,.subject-wrap{
    max-width:100%;
    overflow-x:auto!important;
    -webkit-overflow-scrolling:touch;
}

/*
 * Global responsive safety net. Many older screens define their own grid/flex
 * layouts or fixed widths; these rules make those screens shrink, wrap or stack
 * without requiring business-logic rewrites.
 */
.main,.page-content,.page-content > *,main,section,article,form{min-width:0;max-width:100%;}
.page-content *{box-sizing:border-box;}
.page-content .row,.page-content [class*=grid],.page-content [class*=col],.page-content [class*=wrap]{min-width:0;}
.page-content [style*='display:flex'],
.page-content [style*='display: flex']{
    max-width:100%;
}
.page-content [style*='grid-template-columns']{
    max-width:100%;
}
.page-content input,.page-content select,.page-content textarea{min-width:0;}
.page-content pre,.page-content code{max-width:100%;overflow-wrap:anywhere;}
.page-content pre{overflow-x:auto;-webkit-overflow-scrolling:touch;}

/* Brand-consistent keyboard focus across legacy and modern views. */
a:focus-visible,
button:focus-visible,
[role=button]:focus-visible,
input:focus-visible,
select:focus-visible,
textarea:focus-visible,
[tabindex]:not([tabindex='-1']):focus-visible{
    outline:3px solid var(--focus-ring)!important;
    outline-offset:2px!important;
    box-shadow:0 0 0 4px var(--focus-ring-soft)!important;
}

/* Tablet: reduce multi-column density before content starts colliding. */
@media (max-width:1024px){
    .page-content{max-width:100%!important;}
    .two-col,.gen-grid,.invoice-grid,.pg{
        grid-template-columns:1fr!important;
    }
    .stats-row,.stat-row,.sum-grid,.sg,.kpi{
        grid-template-columns:repeat(2,minmax(0,1fr))!important;
    }
    .page-header,.ph,.toolbar,.page-actions{
        gap:10px!important;
    }
}

/* Phones/tablets: enforce wrapping/stacking and contain fixed-width legacy UI. */
@media (max-width:768px){
    html{font-size:12.5px!important;}
    :root{--control-height:44px;--table-head-height:40px;--table-row-height:46px;--table-edge-space:10px;}
    .sidebar .nav-item{font-size:12px!important;}

    .page-content{
        width:100%!important;
        max-width:100%!important;
        overflow-x:hidden!important;
    }

    .page-content > *,
    .card,.panel,.filter-card,.tcard,
    form,fieldset{
        width:100%;
        max-width:100%!important;
        min-width:0!important;
    }

    .page-header,.ph,.toolbar,.page-actions,.card-actions,
    .filter-bar,.filter-row,.filters-row,
    .page-content > div[style*='justify-content:space-between'],
    .page-content > div[style*='justify-content: space-between']{
        flex-wrap:wrap!important;
    }

    .page-content [style*='width:380px'],
    .page-content [style*='width:360px'],
    .page-content [style*='width:320px'],
    .page-content [style*='min-width:380px'],
    .page-content [style*='min-width:360px'],
    .page-content [style*='min-width:320px']{
        width:100%!important;
        min-width:0!important;
        max-width:100%!important;
    }

    .two,.fr,.two-col,.gen-grid,.invoice-grid,.pg{
        grid-template-columns:1fr!important;
    }
    .fr3{grid-template-columns:repeat(2,minmax(0,1fr))!important;}

    .page-tabs,.scope-tabs{
        width:100%!important;
        max-width:100%!important;
        overflow-x:auto!important;
        flex-wrap:nowrap!important;
        -webkit-overflow-scrolling:touch;
        scrollbar-width:thin;
    }
    .page-tab,.scope-tab{flex:0 0 auto;}

    /* Unwrapped legacy tables remain usable rather than widening the page. */
    .page-content table{
        max-width:calc(100% - (var(--table-edge-space) * 2))!important;
    }
    .page-content table:not(.no-responsive-table){
        min-width:620px;
    }
    .tbl,.table-wrap,.trx,.subject-wrap,
    .card:has(> table),.card:has(> .tbl),.card:has(> .table-wrap){
        overflow-x:auto!important;
        -webkit-overflow-scrolling:touch;
    }

    .page-content .btn,
    .page-content button,
    .page-content input,
    .page-content select,
    .page-content textarea{
        max-width:100%;
    }
}

/* Narrow phones: collapse secondary columns and make action groups usable. */
@media (max-width:640px){
    .stats-row,.stat-row,.sum-grid,.sg,.kpi,
    .fr3{
        grid-template-columns:1fr!important;
    }

    .page-header,.ph,
    .page-content > div[style*='justify-content:space-between'],
    .page-content > div[style*='justify-content: space-between']{
        flex-direction:column!important;
        align-items:stretch!important;
    }

    .page-header-actions,.actions,.page-actions,.card-actions,
    .filter-bar,.filter-row,.filters-row{
        width:100%!important;
        align-items:stretch!important;
    }

    .page-header-actions > *,
    .filter-card > .fg,
    .filter-card > .form-group,
    .filter-bar > input,.filter-bar > select,
    .filter-row > input,.filter-row > select,
    .filters-row > input,.filters-row > select{
        flex:1 1 100%!important;
        width:100%!important;
        min-width:0!important;
    }

    .filter-card{
        flex-direction:column!important;
        align-items:stretch!important;
    }

    .auto-grid,.auto-grid-sm{
        grid-template-columns:1fr!important;
    }
}

@media (max-width:480px){
    .page-content{padding-left:10px!important;padding-right:10px!important;}
    .page-tabs{margin-left:0!important;margin-right:0!important;}
    .card,.panel,.filter-card{border-radius:10px!important;}
}

/* Prevent motion-heavy navigation from becoming an accessibility barrier. */
@media (prefers-reduced-motion:reduce){
    *,*::before,*::after{
        scroll-behavior:auto!important;
        animation-duration:.01ms!important;
        animation-iteration-count:1!important;
        transition-duration:.01ms!important;
    }
}

button:disabled,
input:disabled,
select:disabled,
textarea:disabled,
.btn[aria-disabled=true]{
    cursor:not-allowed!important;
    opacity:.58!important;
}

{$rbacCss}
</style>";
    }
}
