package online.educoreng.educore.core.designsystem.theme

import androidx.compose.ui.graphics.Color

/**
 * EduCore mobile brand palette.
 *
 * Primary UI: navy, white and gold.
 * Semantic exceptions: green for success/present/active and red for
 * danger/absent/error. Warning resolves to gold; informational and legacy
 * purple tones resolve back into the navy family so feature screens cannot
 * drift into unrelated accent colours.
 */
object EduCoreColors {
    val Navy900 = Color(0xFF071E45)
    val Navy800 = Color(0xFF0B2D63)
    val Navy700 = Color(0xFF15447F)

    val Gold700 = Color(0xFFB77B0B)
    val Gold600 = Color(0xFFD79A21)
    val Gold400 = Color(0xFFF5B72E)
    val Gold200 = Color(0xFFF7D98A)
    val Gold100 = Color(0xFFFFF3D6)
    val Gold50 = Color(0xFFFFFAED)

    val Ink900 = Color(0xFF101828)
    val Slate700 = Color(0xFF344054)
    val Slate600 = Color(0xFF475467)
    val Muted500 = Color(0xFF667085)
    val Muted400 = Color(0xFF98A2B3)
    val Page50 = Color(0xFFF6F8FB)
    val Surface100 = Color(0xFFF8FAFC)
    val SurfaceBlue50 = Color(0xFFFAFBFD)
    val Line200 = Color(0xFFE4EAF2)
    val Line300 = Color(0xFFD0D8E5)

    val Success700 = Color(0xFF11663F)
    val Success600 = Color(0xFF16794B)
    val Success100 = Color(0xFFDDF7E9)

    // Warning is intentionally a gold-family semantic state.
    val Warning700 = Gold700
    val Warning600 = Gold600
    val Warning100 = Gold100

    val Danger700 = Color(0xFF912018)
    val Danger600 = Color(0xFFB42318)
    val Danger100 = Color(0xFFFFE4E1)

    // Informational UI stays inside the navy brand family.
    val Info700 = Navy700
    val Info200 = Color(0xFFC9D5E8)
    val Info100 = Color(0xFFEDF2F8)

    // Legacy aliases retained for binary/source compatibility only.
    // They intentionally do not introduce purple into the mobile interface.
    val Purple700 = Navy700
    val Purple100 = Gold50

    val White = Color(0xFFFFFFFF)
}
