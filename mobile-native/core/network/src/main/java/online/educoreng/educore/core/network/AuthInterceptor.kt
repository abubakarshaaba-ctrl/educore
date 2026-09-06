package online.educoreng.educore.core.network

import okhttp3.Interceptor
import okhttp3.Response
import online.educoreng.educore.core.common.BearerTokenProvider

class AuthInterceptor(
    private val tokenProvider: BearerTokenProvider,
) : Interceptor {
    override fun intercept(chain: Interceptor.Chain): Response {
        val original = chain.request()
        val builder = original.newBuilder()
            .header("Accept", "application/json")
            .header("X-EduCore-Client", "android-native")

        tokenProvider.bearerToken()
            ?.takeIf(String::isNotBlank)
            ?.let { builder.header("Authorization", "Bearer $it") }

        return chain.proceed(builder.build())
    }
}
