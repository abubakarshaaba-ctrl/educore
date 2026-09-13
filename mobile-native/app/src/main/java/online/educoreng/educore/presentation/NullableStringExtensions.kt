package online.educoreng.educore.presentation

/**
 * Nullable-safe counterpart used by presentation mappings where API labels
 * may legitimately be absent.
 */
internal fun String?.isNotBlank(): Boolean = !isNullOrBlank()
