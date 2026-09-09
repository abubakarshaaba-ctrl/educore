package online.educoreng.educore.presentation

import androidx.compose.ui.unit.Dp

/** Local unit compatibility for presentation files that use unqualified dp literals. */
internal val Int.dp: Dp get() = Dp(toFloat())
internal val Float.dp: Dp get() = Dp(this)
