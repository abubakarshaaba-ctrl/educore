<?php
namespace App\Helpers;

class ThemeHelper
{
    /**
     * EduCore's fixed brand palette. Per-tenant colour customisation has
     * been removed — every school uses the same navy/gold branding.
     */
    public static function css(): string
    {
        $primary = '#071E45';
        $accent  = '#D79A21';
        $sidebar = '#071E45';

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
