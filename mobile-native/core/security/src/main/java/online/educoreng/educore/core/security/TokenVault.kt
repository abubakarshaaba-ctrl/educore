package online.educoreng.educore.core.security

import online.educoreng.educore.core.common.BearerTokenProvider

interface TokenVault : BearerTokenProvider {
    fun save(token: String)
    fun clear()
    fun hasToken(): Boolean = !bearerToken().isNullOrBlank()
}
