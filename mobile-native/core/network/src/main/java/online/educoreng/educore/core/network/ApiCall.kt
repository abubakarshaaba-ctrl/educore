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
    val envelope = runCatching {
        response()?.errorBody()?.string()
            ?.takeIf(String::isNotBlank)
            ?.let { body ->
                moshi.adapter(ErrorEnvelopeDto::class.java).fromJson(body)
            }
    }.getOrNull()
    val message = envelope?.message

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
        in 500..599 -> AppError.Server(userMessage = message ?: AppError.Server().userMessage, statusCode = code())
        else -> AppError.Server(userMessage = message ?: AppError.Server().userMessage, statusCode = code())
    }
}

private val SUBSCRIPTION_STATES = setOf(
    "inactive",
    "suspended",
    "expired",
    "missing",
)
