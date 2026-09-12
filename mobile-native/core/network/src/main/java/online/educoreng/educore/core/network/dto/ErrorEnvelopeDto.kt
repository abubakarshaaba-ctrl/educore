package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class ErrorEnvelopeDto(
    val message: String? = null,
    val state: String? = null,
    val errors: Map<String, List<String>>? = null,
    @param:Json(name = "request_id") val requestId: String? = null,
)
