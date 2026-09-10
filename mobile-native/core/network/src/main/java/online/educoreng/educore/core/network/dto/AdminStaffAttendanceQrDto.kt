package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class AdminStaffAttendanceQrDto(
    @Json(name = "success") val success: Boolean = true,
    @Json(name = "data") val data: AdminStaffAttendanceQrDataDto,
) {
    val payload: String get() = data.qrToken
    val school: String? get() = data.schoolName
    val generatedAt: String? get() = data.generatedAt
    val note: String? get() = "This QR stays valid until an authorized administrator resets it."
}

@JsonClass(generateAdapter = true)
data class AdminStaffAttendanceQrDataDto(
    @Json(name = "qr_token") val qrToken: String,
    @Json(name = "school_name") val schoolName: String? = null,
    @Json(name = "generated_at") val generatedAt: String? = null,
    @Json(name = "expires_at") val expiresAt: String? = null,
)
