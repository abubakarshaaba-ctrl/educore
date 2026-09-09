package online.educoreng.educore.presentation

import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar as DesignSystemSearchBar

/**
 * Compatibility overload for older presentation call sites that supplied the
 * search placeholder as the third positional argument.
 */
@Composable
internal fun EduCoreSearchBar(
    value: String,
    onValueChange: (String) -> Unit,
    placeholder: String,
) {
    DesignSystemSearchBar(
        value = value,
        onValueChange = onValueChange,
        modifier = Modifier,
        placeholder = placeholder,
    )
}

/** Null-safe variant used by result payloads whose student name is optional. */
internal inline fun String?.ifBlank(defaultValue: () -> String): String =
    if (this.isNullOrBlank()) defaultValue() else this
