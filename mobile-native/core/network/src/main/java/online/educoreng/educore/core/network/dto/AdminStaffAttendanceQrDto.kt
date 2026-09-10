package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class AdminStaffAttendanceQrDto(
    @Json(name = "payload") val payload: String,
    @Json(name = "type") val type: String,
    @Json(name = "tenant_id") val tenantId: Long,
    @Json(name = "school") val school: String? = null,
    @Json(name = "generated_at") val generatedAt: String? = null,
    @Json(name = "note") val note: String? = null,
)
