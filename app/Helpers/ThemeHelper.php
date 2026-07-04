<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class ThemeHelper
{
    public static function css(): string
    {
        try {
            $tenant = Auth::user()?->tenant;
            $primary = $tenant?->theme_primary ?? '#071E45';
            $accent  = $tenant?->theme_accent  ?? '#D79A21';
            $sidebar = $tenant?->theme_sidebar  ?? '#071E45';
        } catch (\Throwable) {
            $primary = '#071E45';
            $accent  = '#D79A21';
            $sidebar = '#071E45';
        }

        // Validate hex colours — reject anything that's not a valid 6-digit hex
        $hex = '/^#[0-9A-Fa-f]{6}$/';
        if (!preg_match($hex, $primary)) $primary = '#071E45';
        if (!preg_match($hex, $accent))  $accent  = '#D79A21';
        if (!preg_match($hex, $sidebar)) $sidebar = '#071E45';

        return "<style>
:root{--brand-navy:{$sidebar};--brand-gold:{$accent};--midnight:{$primary};--indigo:{$accent};--indigo-dark:{$accent};--indigo-bg:{$accent}18;}
.sidebar{background:{$sidebar}!important;}
.nav-item.active{background:{$accent}28!important;color:{$accent}!important;}
.nav-item.active::before{background:{$accent}!important;}
.btn-p{background:{$accent}!important;color:{$primary}!important;}
.nav-section-label{color:{$accent}99!important;}
</style>";
    }
}
