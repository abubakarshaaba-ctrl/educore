package androidx.compose.runtime

/**
 * Temporary source-compatibility bridge for presentation files that imported
 * rememberSaveable from androidx.compose.runtime instead of the saveable package.
 * It delegates to the canonical Compose implementation.
 */
@Composable
inline fun <T> rememberSaveable(crossinline init: () -> T): T =
    androidx.compose.runtime.saveable.rememberSaveable { init() }
