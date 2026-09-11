package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class AdminStaffAttendanceSettingsResponseDto(
    @Json(name = "settings") val settings: AdminStaffAttendanceSettingsDto,
)

@JsonClass(generateAdapter = true)
data class AdminAttendanceOfflineSyncRequestDto(
    @Json(name = "client_uuid") val clientUuid: String,
    @Json(name = "staff_id") val staffId: Long,
    @Json(name = "action") val action: String,
    @Json(name = "attendance_date") val attendanceDate: String,
    @Json(name = "local_timestamp") val localTimestamp: String,
    @Json(name = "latitude") val latitude: Double? = null,
    @Json(name = "longitude") val longitude: Double? = null,
    @Json(name = "accuracy") val accuracy: Double? = null,
    @Json(name = "qr_token") val qrToken: String? = null,
)

@JsonClass(generateAdapter = true)
data class AdminAttendanceProxyClockRequestDto(
    @Json(name = "staff_id") val staffId: String,
    @Json(name = "date") val date: String,
    @Json(name = "clock_in_time") val clockInTime: String,
    @Json(name = "clock_out_time") val clockOutTime: String? = null,
    @Json(name = "reason") val reason: String,
    @Json(name = "status") val status: String? = null,
    @Json(name = "device") val device: String? = null,
)

@JsonClass(generateAdapter = true)
data class SelfAttendanceOfflineSyncResponseDto(
    val success: Boolean = false,
    val idempotent: Boolean = false,
    val status: String? = null,
    val message: String? = null,
    @Json(name = "record_id") val recordId: Long? = null,
)
