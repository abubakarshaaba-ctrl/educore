package online.educoreng.educore.presentation

/**
 * Presentation-layer compatibility helper for request DTO fields where a blank
 * form value should be transmitted as null. Kotlin stdlib String.ifBlank only
 * permits a non-null String fallback, while these API contracts intentionally
 * use nullable strings for optional values.
 */
internal inline fun String.ifBlank(defaultValue: () -> Nothing?): String? =
    if (isBlank()) defaultValue() else this
