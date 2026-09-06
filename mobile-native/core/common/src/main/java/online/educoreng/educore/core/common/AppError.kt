package online.educoreng.educore.core.common

sealed interface AppError {
    val userMessage: String

    data class NetworkUnavailable(
        override val userMessage: String = "No internet connection. Check your network and try again.",
    ) : AppError

    data class Timeout(
        override val userMessage: String = "The request took too long. Please try again.",
    ) : AppError

    data class Unauthenticated(
        override val userMessage: String = "Your session has expired. Sign in again.",
    ) : AppError

    data class Forbidden(
        override val userMessage: String = "You do not have permission to perform this action.",
    ) : AppError

    data class NotFound(
        override val userMessage: String = "The requested record could not be found.",
    ) : AppError

    data class Conflict(
        override val userMessage: String = "This record changed on the server. Refresh and try again.",
    ) : AppError

    data class Validation(
        override val userMessage: String,
        val fieldErrors: Map<String, List<String>> = emptyMap(),
    ) : AppError

    data class RateLimited(
        override val userMessage: String = "Too many requests. Wait briefly and try again.",
    ) : AppError

    data class SubscriptionRestricted(
        override val userMessage: String,
        val state: String? = null,
    ) : AppError

    data class Server(
        override val userMessage: String = "EduCore could not complete the request. Please try again.",
        val statusCode: Int? = null,
        val requestId: String? = null,
    ) : AppError

    data class Unexpected(
        override val userMessage: String = "Something went wrong. Please try again.",
        val cause: Throwable? = null,
    ) : AppError
}
