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
     * Global accessibility rules live here because a number of legacy views
     * still contain inline CSS. Important declarations intentionally allow the
     * design-system layer to normalise focus, motion and touch-target behaviour
     * without changing feature-specific markup or business logic.
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
:root{--brand-navy:{$sidebar};--brand-gold:{$accent};--midnight:{$primary};--indigo:{$accent};--indigo-dark:#B8810D;--indigo-bg:#FEF9EC;--focus-ring:rgba(215,154,33,.72);--focus-ring-soft:rgba(215,154,33,.20);}
.sidebar{background:{$sidebar}!important;}
.nav-item.active{background:{$accent}28!important;color:#F2C35B!important;}
.nav-item.active::before{background:{$accent}!important;}
.btn-p{background:{$accent}!important;color:{$primary}!important;}
.nav-section-label{color:#D8B968!important;}

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

/* Keep interactive controls comfortably tappable without enlarging dense data cells. */
button:not(.collapse-btn):not(.icon-btn),
.btn,
[role=button]:not(.icon-btn),
.page-tab,
.pagination a,
.pagination button{
    min-height:40px;
}
@media (max-width:768px){
    button:not(.collapse-btn):not(.icon-btn),
    .btn,
    [role=button]:not(.icon-btn),
    .page-tab,
    .pagination a,
    .pagination button{
        min-height:44px;
    }
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

/* Make native disabled states unambiguous on legacy forms. */
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
