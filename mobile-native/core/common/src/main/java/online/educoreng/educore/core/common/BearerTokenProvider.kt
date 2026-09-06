package online.educoreng.educore.core.common

fun interface BearerTokenProvider {
    fun bearerToken(): String?
}
