package online.educoreng.educore.presentation

import kotlin.OverloadResolutionByLambdaReturnType
import kotlin.experimental.ExperimentalTypeInference

/**
 * Narrow source-compatibility overload for optional request fields where a blank
 * String should become null. Lambda-return-type overload resolution keeps normal
 * stdlib String.ifBlank { "fallback" } calls on the standard implementation while
 * allowing String.ifBlank { null } for nullable API DTO fields.
 */
@OptIn(ExperimentalTypeInference::class)
@OverloadResolutionByLambdaReturnType
internal inline fun String.ifBlank(defaultValue: () -> Nothing?): String? =
    if (isBlank()) defaultValue() else this
