package online.educoreng.educore.core.network

import com.squareup.moshi.Moshi
import java.io.IOException
import java.net.SocketTimeoutException
import online.educoreng.educore.core.common.AppError
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.network.dto.ErrorEnvelopeDto
import retrofit2.HttpException

suspend fun <T> safeApiCall(
    moshi: Moshi,
    block: suspend () -> T,
): AppResult<T> = try {
    AppResult.Success(block())
} catch (error: SocketTimeoutException) {
    AppResult.Failure(AppError.Timeout())
} catch (error: HttpException) {
    AppResult.Failure(error.toAppError(moshi))
} catch (error: IOException) {
    AppResult.Failure(AppError.NetworkUnavailable())
} catch (error: Throwable) {
    AppResult.Failure(AppError.Unexpected(cause = error))
}

private fun HttpException.toAppError(moshi: Moshi): AppError {
    val httpResponse = response()
    val envelope = runCatching {
        httpResponse?.errorBody()?.string()
            ?.takeIf(String::isNotBlank)
            ?.let { body ->
                moshi.adapter(ErrorEnvelopeDto::class.java).fromJson(body)
            }
    }.getOrNull()
    val message = envelope?.message
    val requestId = sequenceOf(
        envelope?.requestId,
        httpResponse?.headers()?.get("X-EduCore-Request-Id"),
        httpResponse?.headers()?.get("X-EduCore-Bootstrap-Reference"),
        httpResponse?.headers()?.get("X-Request-Id"),
    ).filterNotNull()
        .map(String::trim)
        .firstOrNull { it.matches(REQUEST_ID_PATTERN) }

    return when (code()) {
        401 -> AppError.Unauthenticated(message ?: AppError.Unauthenticated().userMessage)
        403 -> if (envelope?.state in SUBSCRIPTION_STATES) {
            AppError.SubscriptionRestricted(
                userMessage = message ?: "This school account is currently unavailable.",
                state = envelope?.state,
            )
        } else {
            AppError.Forbidden(message ?: AppError.Forbidden().userMessage)
        }
        404 -> AppError.NotFound(message ?: AppError.NotFound().userMessage)
        409 -> AppError.Conflict(message ?: AppError.Conflict().userMessage)
        419 -> AppError.Unauthenticated(message ?: AppError.Unauthenticated().userMessage)
        423 -> AppError.Forbidden(message ?: "This record is locked and cannot be changed.")
        422 -> AppError.Validation(
            userMessage = message ?: "Check the highlighted information and try again.",
            fieldErrors = envelope?.errors.orEmpty(),
        )
        429 -> AppError.RateLimited(message ?: AppError.RateLimited().userMessage)
        in 500..599 -> AppError.Server(
            userMessage = message ?: AppError.Server().userMessage,
            statusCode = code(),
            requestId = requestId,
        )
        else -> AppError.Server(
            userMessage = message ?: AppError.Server().userMessage,
            statusCode = code(),
            requestId = requestId,
        )
    }
}

private val SUBSCRIPTION_STATES = setOf(
    "inactive",
    "suspended",
    "expired",
    "missing",
)

private val REQUEST_ID_PATTERN = Regex("[A-Za-z0-9._-]{1,80}")
