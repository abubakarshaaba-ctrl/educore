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
:root{--brand-navy:{$sidebar};--brand-gold:{$accent};--midnight:{$primary};--indigo:{$accent};--indigo-dark:{$accent};--indigo-bg:{$accent}18;}
.sidebar{background:{$sidebar}!important;}
.nav-item.active{background:{$accent}28!important;color:{$accent}!important;}
.nav-item.active::before{background:{$accent}!important;}
.btn-p{background:{$accent}!important;color:{$primary}!important;}
.nav-section-label{color:{$accent}99!important;}
{$rbacCss}
</style>";
    }
}
